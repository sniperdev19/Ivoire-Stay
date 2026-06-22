<?php

namespace Controllers;

use Core\{Request, Response, Database};
use Models\Booking;
use Services\{GeniusPayService, MailService};

class BookingPaymentController
{
    // ─── POST /api/public/booking-payment/initiate ───────────────────────────
    public function initiate(Request $req, array $_params = []): void
    {
        $data      = $req->all();
        $bookingId = (int) ($data['booking_id'] ?? 0);
        $payMethod = $data['pay_method'] ?? '';

        $booking = Booking::findWithDetails($bookingId);
        if (!$booking) Response::notFound('Réservation introuvable');

        if ($booking['status'] !== 'pending') {
            Response::error('Cette réservation ne peut plus être payée en ligne.');
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
                'success_url'    => APP_URL . '/booking/' . $bookingId . '?payment=success&ref=' . urlencode($reference),
                'error_url'      => APP_URL . '/booking/' . $bookingId . '?payment=error&ref='  . urlencode($reference),
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
            Response::error('Erreur GeniusPay : ' . $e->getMessage());
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
            Database::query(
                "UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE booking_id = ?",
                [$booking['id']]
            );
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

        // Déjà confirmé
        if ($booking['pay_status'] === 'paid' || $booking['status'] === 'confirmed') {
            Response::success(['status' => 'paid', 'booking_status' => $booking['status']]);
        }

        $token = $booking['pay_token'] ?? $ref;
        try {
            $result         = GeniusPayService::verify($token);
            $internalStatus = GeniusPayService::mapStatus($result['status']);

            if ($internalStatus === 'active') {
                $this->confirmAndNotify($booking);
                Response::success(['status' => 'paid', 'booking_status' => 'confirmed']);
            } elseif ($internalStatus === 'failed') {
                Response::success(['status' => 'failed', 'booking_status' => $booking['status']]);
            } else {
                // Sandbox workaround : TRANSACTION_NOT_FOUND → faire confiance au success_url
                $isNotFound = ($result['raw']['error']['code'] ?? '') === 'TRANSACTION_NOT_FOUND';
                if ($isNotFound && APP_ENV === 'development') {
                    $this->confirmAndNotify($booking);
                    Response::success(['status' => 'paid', 'booking_status' => 'confirmed']);
                }
                Response::success(['status' => 'pending', 'booking_status' => $booking['status']]);
            }
        } catch (\Exception $e) {
            error_log('[BookingPayment verify] ' . $e->getMessage());
            Response::success(['status' => 'pending', 'booking_status' => $booking['status']]);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function confirmAndNotify(array $booking): void
    {
        Database::query(
            "UPDATE bookings SET status = 'confirmed', pay_status = 'paid', paid_at = NOW() WHERE id = ?",
            [$booking['id']]
        );
        Database::query(
            "UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE booking_id = ?",
            [$booking['id']]
        );
        MailService::bookingPaid($booking);
    }
}
