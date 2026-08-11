<?php

namespace Services;

class GeniusPayService
{
    private static function baseUrl(): string
    {
        return rtrim(GENIUS_PAY_URL, '/');
    }

    private static function headers(): array
    {
        return [
            'X-API-Key: '    . GENIUS_PAY_KEY,
            'X-API-Secret: ' . GENIUS_PAY_SECRET,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    private static function curlBase(): array
    {
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => self::headers(),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        if (APP_ENV === 'development') {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
        }
        return $opts;
    }

    private static function post(string $endpoint, array $payload): array
    {
        $ch = curl_init(self::baseUrl() . $endpoint);
        curl_setopt_array($ch, self::curlBase() + [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            error_log('[GeniusPay] curl error: ' . $curlError . ' — url: ' . self::baseUrl() . $endpoint);
            throw new \RuntimeException('Erreur réseau: ' . $curlError);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('[GeniusPay] réponse non-JSON (HTTP ' . $httpCode . '): ' . self::redact(substr($response, 0, 150)));
            throw new \RuntimeException('Réponse invalide (HTTP ' . $httpCode . ')');
        }

        return ['code' => $httpCode, 'data' => $data];
    }

    /** Masque les emails avant de logger un contenu tiers non maîtrisé. */
    private static function redact(string $text): string
    {
        return preg_replace('/[\w.+-]+@[\w-]+\.[a-z]{2,}/i', '[email masqué]', $text);
    }

    private static function get(string $endpoint): array
    {
        $ch = curl_init(self::baseUrl() . $endpoint);
        curl_setopt_array($ch, self::curlBase());
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            error_log('[GeniusPay] curl error: ' . $curlError . ' — url: ' . self::baseUrl() . $endpoint);
            throw new \RuntimeException('Erreur réseau: ' . $curlError);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('[GeniusPay get] réponse non-JSON (HTTP ' . $httpCode . '): ' . self::redact(substr($response, 0, 150)));
            throw new \RuntimeException('Réponse invalide (HTTP ' . $httpCode . ')');
        }

        return ['code' => $httpCode, 'data' => $data];
    }

    /**
     * Initiate a payment and return the checkout/payment URL.
     *
     * @param array{amount:int, description:string, reference:string, pay_method:string,
     *              success_url:string, error_url:string,
     *              customer_name:string, customer_email:string} $params
     */
    public static function initiate(array $params): array
    {
        // Mapping nos codes internes → codes GeniusPay
        $methodMap = [
            'orange' => 'orange_money',
            'mtn'    => 'mtn_money',
            'wave'   => 'wave',
        ];
        $payMethod = $methodMap[$params['pay_method'] ?? ''] ?? null;

        $payload = [
            'amount'      => $params['amount'],
            'currency'    => 'XOF',
            'description' => $params['description'],
            'success_url' => $params['success_url'],
            'error_url'   => $params['error_url'],
            'customer'    => [
                'name'  => $params['customer_name']  ?? 'Client',
                'email' => $params['customer_email'] ?? '',
            ],
            'metadata'    => ['internal_reference' => $params['reference']],
        ];

        if ($payMethod) {
            $payload['payment_method'] = $payMethod;
        }

        $result = self::post('/payments', $payload);

        if ($result['code'] !== 200 && $result['code'] !== 201) {
            $msg = $result['data']['message'] ?? json_encode($result['data']);
            error_log('[GeniusPay initiate] HTTP ' . $result['code'] . ' — ' . $msg);
            throw new \RuntimeException('HTTP ' . $result['code'] . ': ' . $msg);
        }

        $tx = $result['data']['data'] ?? $result['data'];

        return [
            'payment_url' => $tx['checkout_url'] ?? $tx['payment_url'] ?? '',
            'token'       => $tx['reference']    ?? '',
            'reference'   => $params['reference'],
        ];
    }

    /**
     * Verify payment status by GeniusPay reference (MTX-...).
     */
    public static function verify(string $reference): array
    {
        $result = self::get('/payments/' . urlencode($reference));
        $tx = $result['data']['data'] ?? $result['data'];

        return [
            'status'    => $tx['status'] ?? 'unknown',
            'amount'    => $tx['amount'] ?? 0,
            'reference' => $reference,
            'raw'       => $tx,
        ];
    }

    /**
     * Validate the webhook signature from GeniusPay.
     */
    public static function validateWebhook(string $rawBody, string $signature): bool
    {
        if (!GENIUS_PAY_WEBHOOK_SECRET) {
            return APP_ENV === 'development';
        }
        if ($signature === '') return false;
        $expected = hash_hmac('sha256', $rawBody, GENIUS_PAY_WEBHOOK_SECRET);
        return hash_equals($expected, $signature);
    }

    /**
     * Map GeniusPay status/event to internal status.
     */
    public static function mapStatus(string $gpStatus): string
    {
        return match (strtolower($gpStatus)) {
            'completed', 'success', 'payment.success'              => 'active',
            'failed', 'cancelled', 'payment.failed',
            'payment.cancelled', 'declined'                        => 'failed',
            default                                                => 'pending',
        };
    }

    /**
     * Frais réels prélevés par GeniusPay sur un encaissement — 100 XOF fixe prélevé
     * D'ABORD, puis 2.5% calculés sur le montant RESTANT (pas sur le montant brut) :
     * fee = 100 + (montant − 100) × 2.5%. Coût de la passerelle, distinct de la
     * commission plateforme (Services\PlanPricingService::commissionPct). Purement
     * informatif pour l'instant : ne modifie aucun montant facturé au client ni versé
     * à l'établissement, sert uniquement à calculer la marge nette réelle de la
     * plateforme (voir AdminController::overview()).
     */
    public static function paymentFee(float $amount): float
    {
        return round(100 + max(0, $amount - 100) * 0.025, 2);
    }

    /** Frais réels prélevés par GeniusPay sur un retrait (1% du montant) — même logique informative. */
    public static function withdrawalFee(float $amount): float
    {
        return round($amount * 0.01, 2);
    }
}
