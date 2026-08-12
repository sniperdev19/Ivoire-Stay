<?php

namespace Models;

use Core\Database;

class Invoice extends BaseModel
{
    protected static string $table = 'invoices';

    public static function allWithDetails(int $estabId, array $filters = []): array
    {
        $where = ['r.establishment_id = ?'];
        $params = [$estabId];

        if (!empty($filters['status'])) {
            $where[] = 'i.status = ?';
            $params[] = $filters['status'];
        }

        return Database::query(
            "SELECT i.*,
                b.check_in, b.check_out,
                r.number as room_number,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name,
                COALESCE(pc.email, u.email) as client_email,
                COALESCE((
                    SELECT SUM(p.amount) FROM payments p
                    WHERE p.invoice_id = i.id AND p.status = 'completed'
                ), 0) as paid_amount
             FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY i.issued_at DESC",
            $params
        )->fetchAll();
    }

    public static function findWithDetails(int $id): ?array
    {
        $inv = Database::query(
            "SELECT i.*,
                b.check_in, b.check_out, b.guests_count, b.total_amount,
                b.booking_type, b.hours,
                r.number as room_number, rt.name as room_type,
                e.name as establishment_name, e.address as establishment_address,
                e.phone as establishment_phone,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name,
                COALESCE(pc.email, u.email) as client_email,
                COALESCE(pc.phone, u.phone) as client_phone,
                COALESCE((
                    SELECT SUM(p.amount) FROM payments p
                    WHERE p.invoice_id = i.id AND p.status = 'completed'
                ), 0) as paid_amount
             FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             JOIN room_types rt ON rt.id = r.room_type_id
             JOIN establishments e ON e.id = r.establishment_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE i.id = ?",
            [$id]
        )->fetch();

        if (!$inv) return null;

        $inv['payments'] = Database::query(
            "SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at",
            [$id]
        )->fetchAll();

        return $inv;
    }

    public static function generateNumber(int $estabId): string
    {
        // invoice_number est UNIQUE sur toute la plateforme (toutes établissements confondus) :
        // on inclut l'ID de l'établissement dans le numéro pour garantir l'unicité globale
        // tout en gardant une séquence propre à chaque établissement, remise à zéro chaque année.
        $count = (int) Database::query(
            "SELECT COUNT(*) FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ? AND YEAR(i.issued_at) = YEAR(CURDATE())",
            [$estabId]
        )->fetchColumn();

        return 'SYNC-' . $estabId . '-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function createForBooking(int $bookingId, float $amount, float $taxRate = 0): int
    {
        $booking = Booking::find($bookingId);
        if (!$booking) return 0;

        $room = Room::find($booking['room_id']);
        $estabId = $room['establishment_id'];

        $amountHt  = round($amount / (1 + $taxRate / 100), 2);
        $amountTtc = $amount;

        // generateNumber() n'est pas atomique : sous forte concurrence, deux réservations
        // peuvent obtenir le même numéro. On retente avec un nouveau numéro en cas de collision
        // sur la contrainte UNIQUE plutôt que de laisser la réservation sans facture.
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return self::create([
                    'booking_id'     => $bookingId,
                    'invoice_number' => self::generateNumber($estabId),
                    'amount_ht'      => $amountHt,
                    'tax_rate'       => $taxRate,
                    'amount_ttc'     => $amountTtc,
                    'status'         => 'draft',
                ]);
            } catch (\PDOException $e) {
                $isDuplicate = $e->getCode() === '23000';
                if (!$isDuplicate || $attempt === $maxAttempts) throw $e;
            }
        }

        return 0;
    }

    /**
     * Bascule la/les facture(s) d'une réservation annulée en 'cancelled' —
     * même si elle était déjà payée : une réservation annulée ne doit plus
     * apparaître comme du CA actif dans la Comptabilité (même règle que
     * l'exclusion des paiements côté Dashboard/Rapports, voir
     * Payment::totalByEstablishment). Ne touche pas les factures déjà
     * annulées (idempotent).
     */
    public static function cancelForBooking(int $bookingId): void
    {
        Database::query(
            "UPDATE invoices SET status = 'cancelled' WHERE booking_id = ? AND status != 'cancelled'",
            [$bookingId]
        );
    }

    public static function paidAmount(int $invoiceId): float
    {
        return (float) Database::query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND status = 'completed'",
            [$invoiceId]
        )->fetchColumn();
    }

    /**
     * Enregistre un paiement pour une facture et met à jour son statut en conséquence.
     * Partagé entre l'encaissement manuel (InvoiceController) et l'encaissement à la
     * création d'une réservation (BookingController).
     */
    public static function registerPayment(
        int $bookingId,
        int $invoiceId,
        float $amount,
        string $method,
        string $type = 'full',
        ?string $notes = null,
        float $commissionPct = 0.0,
        bool $viaGeniusPay = false,
        float $clientSharePct = 0.0,
        float $commissionFixed = 0.0
    ): int {
        $now = date('Y-m-d H:i:s');

        // $amount est le montant réellement encaissé (TTC), qui inclut déjà la
        // majoration client le cas échéant (PlanGate::applyClientMarkup(), plan
        // Starter) — cette part est donc DÉJÀ couverte par le client et ne doit pas
        // être reprélevée en plus sur la commission plateforme. On retrouve le prix
        // de base (avant majoration) pour calculer la commission (commissionPct)
        // dessus, pas sur l'amount majoré, sans quoi l'établissement finit par
        // absorber le commissionPct entier au lieu de establishmentSharePct% —
        // constaté en conditions réelles le 2026-08-11 (5% prélevés au lieu de 4%).
        $baseAmount = $clientSharePct > 0 ? $amount / (1 + $clientSharePct / 100) : $amount;

        // $commissionFixed (PlanGate::commissionFixedAmount()) n'est jamais répercuté
        // au client (contrairement à clientSharePct) : entièrement absorbé par
        // l'établissement, en plus du taux — compense le forfait fixe réel de
        // GeniusPay (Services\GeniusPayService::paymentFee()) qu'un taux proportionnel
        // seul ne couvre jamais sur une petite réservation. Nul pour un encaissement
        // manuel ($viaGeniusPay = false), qui n'engendre aucun frais GeniusPay réel.
        $commissionAmount = round($baseAmount * $commissionPct / 100, 2) + ($viaGeniusPay ? $commissionFixed : 0);

        $paymentId = Payment::create([
            'booking_id'           => $bookingId,
            'invoice_id'           => $invoiceId,
            'amount'               => $amount,
            'commission_pct'       => $commissionPct,
            'commission_amount'    => $commissionAmount,
            // Frais réels GeniusPay (informatif, cf. GeniusPayService::paymentFee) : uniquement
            // pour les paiements réellement passés par la passerelle, jamais pour un encaissement
            // manuel (espèces/mobile money saisi à la main par le personnel).
            'geniuspay_fee_amount' => $viaGeniusPay ? \Services\GeniusPayService::paymentFee($amount) : 0,
            'method'               => $method,
            'type'                 => $type,
            'status'               => 'completed',
            'notes'                => $notes,
            'paid_at'              => $now,
        ]);

        $inv  = self::find($invoiceId);
        $paid = self::paidAmount($invoiceId);

        // Une facture annulée (réservation annulée) ne doit pas se
        // retrouver "paid"/"sent" au simple fait qu'un paiement lui soit
        // encore rattaché — voir Invoice::cancelForBooking.
        if ($inv && $inv['status'] !== 'cancelled') {
            $ttc = (float) $inv['amount_ttc'];
            if ($paid >= $ttc) {
                self::update($invoiceId, ['status' => 'paid', 'paid_at' => $now]);
            } elseif ($paid > 0 && $inv['status'] === 'draft') {
                self::update($invoiceId, ['status' => 'sent']);
            }
        }

        return $paymentId;
    }
}
