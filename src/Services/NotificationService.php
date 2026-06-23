<?php

namespace Services;

use Models\{Notification, Establishment};

class NotificationService
{
    public static function create(int $userId, string $type, string $title, string $message = '', array $data = []): void
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message ?: null,
                'data'    => $data ? json_encode($data) : null,
            ]);
        } catch (\Exception $e) {
            error_log('[NotificationService] ' . $e->getMessage());
        }
    }

    // Notifie le propriétaire de l'établissement
    public static function forEstab(int $estabId, string $type, string $title, string $message = '', array $data = []): void
    {
        $estab = Establishment::find($estabId);
        if ($estab && !empty($estab['owner_id'])) {
            self::create((int) $estab['owner_id'], $type, $title, $message, $data);
        }
    }

    // ── Raccourcis sémantiques ─────────────────────────────────────────────────

    public static function bookingNew(int $estabId, string $clientName, string $roomNumber, int $bookingId): void
    {
        self::forEstab($estabId, 'booking_new',
            'Nouvelle réservation',
            $clientName . ' · Chambre ' . $roomNumber,
            ['booking_id' => $bookingId]
        );
    }

    public static function paymentReceived(int $estabId, float $amount, string $method, int $invoiceId): void
    {
        $methods = ['mobile_money' => 'Mobile Money', 'cash' => 'Espèces', 'card' => 'Carte', 'bank_transfer' => 'Virement'];
        $label   = $methods[$method] ?? $method;
        self::forEstab($estabId, 'payment_received',
            'Paiement reçu',
            number_format($amount, 0, ',', ' ') . ' FCFA · ' . $label,
            ['invoice_id' => $invoiceId]
        );
    }

    public static function invoicePaid(int $estabId, string $invoiceNumber, int $invoiceId): void
    {
        self::forEstab($estabId, 'invoice_paid',
            'Facture entièrement réglée',
            'Facture ' . $invoiceNumber,
            ['invoice_id' => $invoiceId]
        );
    }

    public static function invoiceSent(int $estabId, string $invoiceNumber, string $email, int $invoiceId): void
    {
        self::forEstab($estabId, 'invoice_sent',
            'Facture envoyée par email',
            $invoiceNumber . ' → ' . $email,
            ['invoice_id' => $invoiceId]
        );
    }
}
