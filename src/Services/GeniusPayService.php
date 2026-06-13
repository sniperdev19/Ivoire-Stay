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
            'Authorization: Bearer ' . GENIUS_PAY_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    private static function post(string $endpoint, array $payload): array
    {
        $ch = curl_init(self::baseUrl() . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => self::headers(),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Genius Pay: erreur réseau');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Genius Pay: réponse invalide');
        }

        return ['code' => $httpCode, 'data' => $data];
    }

    private static function get(string $endpoint): array
    {
        $ch = curl_init(self::baseUrl() . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => self::headers(),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        return ['code' => $httpCode, 'data' => $data ?? []];
    }

    /**
     * Initiate a payment and return the payment URL + reference.
     *
     * @param array{amount:int, description:string, reference:string, callback_url:string, return_url:string, customer_name:string, customer_email:string} $params
     */
    public static function initiate(array $params): array
    {
        $result = self::post('/payments/initiate', [
            'amount'        => $params['amount'],
            'currency'      => 'XOF',
            'description'   => $params['description'],
            'reference'     => $params['reference'],
            'callback_url'  => $params['callback_url'],
            'return_url'    => $params['return_url'],
            'customer'      => [
                'name'  => $params['customer_name']  ?? 'Client SYNC',
                'email' => $params['customer_email'] ?? '',
            ],
        ]);

        if ($result['code'] !== 200 && $result['code'] !== 201) {
            throw new \RuntimeException('Genius Pay: ' . ($result['data']['message'] ?? 'Erreur lors de l\'initiation'));
        }

        return [
            'payment_url' => $result['data']['payment_url'] ?? $result['data']['checkout_url'] ?? '',
            'token'       => $result['data']['token']       ?? $result['data']['transaction_id'] ?? '',
            'reference'   => $params['reference'],
        ];
    }

    /**
     * Verify payment status by reference.
     */
    public static function verify(string $reference): array
    {
        $result = self::get('/payments/' . urlencode($reference) . '/status');

        return [
            'status'    => $result['data']['status']    ?? 'unknown',
            'amount'    => $result['data']['amount']    ?? 0,
            'reference' => $reference,
            'raw'       => $result['data'],
        ];
    }

    /**
     * Validate the webhook signature from Genius Pay.
     */
    public static function validateWebhook(string $rawBody, string $signature): bool
    {
        if (!GENIUS_PAY_WEBHOOK_SECRET) {
            // Tolérance uniquement en développement ; en production, l'absence de
            // secret doit faire échouer la validation pour éviter les activations
            // d'abonnement non authentifiées.
            return APP_ENV === 'development';
        }
        if ($signature === '') return false;
        $expected = hash_hmac('sha256', $rawBody, GENIUS_PAY_WEBHOOK_SECRET);
        return hash_equals($expected, $signature);
    }

    /**
     * Map Genius Pay status strings to internal status.
     */
    public static function mapStatus(string $gpStatus): string
    {
        return match (strtoupper($gpStatus)) {
            'SUCCESS', 'COMPLETED', 'PAID'    => 'active',
            'FAILED', 'DECLINED', 'CANCELLED' => 'failed',
            default                           => 'pending',
        };
    }
}
