<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate};
use Models\{Booking, Invoice};
use Services\{GeniusPayService, MailService};

class BookingPaymentController
{
    // ─── POST /api/public/booking-payment/initiate ───────────────────────────
    public function initiate(Request $req, array $_params = []): void
    {
        // Verrou global v1 (config.php) : paiement en ligne en cours de développement.
        // Vérifié en premier, avant toute lecture de plan/réglage établissement — même
        // un appel API direct (bypass du frontend) ne peut pas déclencher GeniusPay.
        if (!ONLINE_PAYMENTS_ENABLED) {
            Response::error("Le paiement en ligne est en cours de développement et sera bientôt disponible. Merci de choisir le paiement sur place.");
        }

        $data      = $req->all();
        $bookingId = (int) ($data['booking_id'] ?? 0);
        $payMethod = $data['pay_method'] ?? '';

        $booking = Booking::findWithDetails($bookingId);
        if (!$booking) Response::notFound('Réservation introuvable');

        if ($booking['status'] !== 'pending') {
            Response::error('Cette réservation ne peut plus être payée en ligne.');
        }

        // Bloquer la double initiation de paiement
        if (!empty($booking['pay_token']) && $booking['pay_status'] === 'pending') {
            Response::error('Un paiement est déjà en cours pour cette réservation. Vérifiez votre téléphone ou contactez l\'établissement.');
        }

        // Le paiement en ligne est une fonctionnalité payante (plans Pro/Business),
        // et peut en plus être désactivé par l'établissement lui-même
        $estabRow = Database::query(
            "SELECT e.plan, e.plan_expires_at, e.online_payment_enabled
             FROM rooms r JOIN establishments e ON e.id = r.establishment_id
             WHERE r.id = ?",
            [$booking['room_id']]
        )->fetch();
        $onlinePaymentAllowed = $estabRow
            && PlanGate::can(['plan' => $estabRow['plan'], 'plan_expires_at' => $estabRow['plan_expires_at']], 'online_payment_control')
            && (bool) $estabRow['online_payment_enabled'];
        if (!$onlinePaymentAllowed) {
            Response::error("Le paiement en ligne n'est pas disponible pour cet établissement. Merci de choisir le paiement sur place.");
        }

        $reference = 'BK-' . $bookingId . '-' . time();
        $amount    = (float) ($booking['total_amount'] ?? 0);
        if ($amount <= 0) Response::error('Montant invalide pour cette réservation.');

        Database::query(
            "UPDATE bookings SET pay_reference = ?, pay_method = ?, pay_status = 'pending' WHERE id = ?",
            [$reference, $payMethod, $bookingId]
        );

        try {
            $result = GeniusPayService::initiate([
                'amount'         => $amount,
                'description'    => 'Réservation chambre ' . ($booking['room_number'] ?? $bookingId),
                'reference'      => $reference,
                'pay_method'     => $payMethod,
                // '/booking/{slug}' attend le slug de la CHAMBRE (pour recharger la page de réservation),
                // pas l'ID de la réservation — sinon la page se rouvre sur la mauvaise chambre au retour.
                'success_url'    => APP_URL . '/booking/' . ($booking['room_slug'] ?? $booking['room_id']) . '?payment=success&ref=' . urlencode($reference),
                'error_url'      => APP_URL . '/booking/' . ($booking['room_slug'] ?? $booking['room_id']) . '?payment=error&ref='  . urlencode($reference),
                'customer_name'  => $booking['client_name']  ?? 'Client',
                'customer_email' => $booking['client_email'] ?? '',
            ]);

            Database::query(
                "UPDATE bookings SET pay_token = ? WHERE id = ?",
                [$result['token'], $bookingId]
            );

            Response::success([
                'payment_url' => $result['payment_url'],
                'reference'   => $reference,
            ], 'Paiement initié');

        } catch (\Exception $e) {
            Database::query(
                "UPDATE bookings SET pay_status = 'failed' WHERE id = ?",
                [$bookingId]
            );
            error_log('[GeniusPay initiate] booking=' . $bookingId . ' — ' . $e->getMessage());
            Response::error('Le service de paiement est temporairement indisponible. Veuillez réessayer ou choisir le paiement sur place.');
        }
    }

    // ─── POST /api/public/booking-payment/callback (webhook, pas d'auth) ─────
    public function callback(Request $_req, array $_params = []): void
    {
        $rawBody = file_get_contents('php://input');
        $sig     = $_SERVER['HTTP_X_GENIUSPAY_SIGNATURE'] ?? '';

        if (!GeniusPayService::validateWebhook($rawBody, $sig)) {
            http_response_code(403);
            exit('Invalid signature');
        }

        $data        = json_decode($rawBody, true) ?? [];
        $tx          = $data['data']['transaction'] ?? [];
        $gpEvent     = $data['event']               ?? '';
        $gpReference = $tx['reference']             ?? '';
        $internalRef = $tx['metadata']['internal_reference'] ?? '';

        if (!$internalRef && !$gpReference) { http_response_code(400); exit('Missing reference'); }

        $booking = null;
        if ($internalRef) {
            $booking = Database::query(
                "SELECT * FROM bookings WHERE pay_reference = ?", [$internalRef]
            )->fetch();
        }
        if (!$booking && $gpReference) {
            $booking = Database::query(
                "SELECT * FROM bookings WHERE pay_token = ?", [$gpReference]
            )->fetch();
        }
        if (!$booking) { http_response_code(404); exit('Booking not found'); }

        $internalStatus = GeniusPayService::mapStatus($tx['status'] ?? $gpEvent);

        if ($internalStatus === 'active') {
            Database::query(
                "UPDATE bookings SET status = 'confirmed', pay_status = 'paid', paid_at = NOW() WHERE id = ?",
                [$booking['id']]
            );
            $this->recordOnlinePayment((int) $booking['id']);
            // Email confirmation paiement
            $full = Booking::findWithDetails($booking['id']);
            if ($full) MailService::bookingPaid($full);
        } elseif ($internalStatus === 'failed') {
            Database::query(
                "UPDATE bookings SET pay_status = 'failed' WHERE id = ?",
                [$booking['id']]
            );
        }

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ─── GET /api/public/booking-payment/verify/{ref} ────────────────────────
    public function verify(Request $_req, array $params = []): void
    {
        $ref     = $params['ref'] ?? $_GET['ref'] ?? '';
        $booking = Database::query(
            "SELECT b.*, r.number as room_number, e.name as establishment_name
             FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             JOIN establishments e ON e.id = r.establishment_id
             WHERE b.pay_reference = ?",
            [$ref]
        )->fetch();

        if (!$booking) Response::notFound('Réservation introuvable');

        // Détails renvoyés au client pour reconstituer l'écran de confirmation
        // (le retour depuis GeniusPay est un rechargement complet de la page : le formulaire est vide)
        $details = [
            'booking_id'         => $booking['id'],
            'reference'          => $booking['pay_reference'],
            'room_number'        => $booking['room_number'],
            'establishment_name' => $booking['establishment_name'],
            'booking_type'       => $booking['booking_type'],
            'check_in'           => $booking['check_in'],
            'check_out'          => $booking['check_out'],
            'hours'              => $booking['hours'],
            'total_amount'       => $booking['total_amount'],
            'guest_token'        => $booking['guest_token'],
        ];

        // Déjà confirmé
        if ($booking['pay_status'] === 'paid' || $booking['status'] === 'confirmed') {
            Response::success(['status' => 'paid', 'booking_status' => $booking['status']] + $details);
        }

        $token = $booking['pay_token'] ?? $ref;
        try {
            $result         = GeniusPayService::verify($token);
            $internalStatus = GeniusPayService::mapStatus($result['status']);

            if ($internalStatus === 'active') {
                $this->confirmAndNotify($booking);
                Response::success(['status' => 'paid', 'booking_status' => 'confirmed'] + $details);
            } elseif ($internalStatus === 'failed') {
                Response::success(['status' => 'failed', 'booking_status' => $booking['status']] + $details);
            } else {
                // Sandbox workaround : TRANSACTION_NOT_FOUND → faire confiance au success_url
                $isNotFound = ($result['raw']['error']['code'] ?? '') === 'TRANSACTION_NOT_FOUND';
                if ($isNotFound && APP_ENV === 'development') {
                    $this->confirmAndNotify($booking);
                    Response::success(['status' => 'paid', 'booking_status' => 'confirmed'] + $details);
                }
                Response::success(['status' => 'pending', 'booking_status' => $booking['status']] + $details);
            }
        } catch (\Exception $e) {
            error_log('[BookingPayment verify] ' . $e->getMessage());
            Response::success(['status' => 'pending', 'booking_status' => $booking['status']] + $details);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function confirmAndNotify(array $booking): void
    {
        Database::query(
            "UPDATE bookings SET status = 'confirmed', pay_status = 'paid', paid_at = NOW() WHERE id = ?",
            [$booking['id']]
        );
        $this->recordOnlinePayment((int) $booking['id']);
        MailService::bookingPaid($booking);
    }

    /**
     * Enregistre le paiement en ligne dans la table `payments` (Invoice::registerPayment
     * met aussi à jour le statut de la facture à 'paid'). Sans ça, un paiement GeniusPay
     * confirmait la facture mais n'apparaissait jamais dans le CA du dashboard ni dans
     * l'onglet Paiements de la Comptabilité (qui ne lisent que la table `payments`).
     * Idempotent : webhook et vérification manuelle peuvent toutes deux appeler ceci
     * pour la même réservation.
     */
    private function recordOnlinePayment(int $bookingId): void
    {
        $invoice = Invoice::first(['booking_id' => $bookingId]);
        if (!$invoice) return;
        if (Invoice::paidAmount((int) $invoice['id']) > 0) return;

        $payMethod = (string) Database::query(
            "SELECT pay_method FROM bookings WHERE id = ?", [$bookingId]
        )->fetchColumn();

        Invoice::registerPayment(
            $bookingId,
            (int) $invoice['id'],
            (float) $invoice['amount_ttc'],
            'mobile_money',
            'full',
            'Paiement en ligne GeniusPay' . ($payMethod ? " ($payMethod)" : '')
        );
    }
}
