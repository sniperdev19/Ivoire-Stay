<?php

namespace Models;

use Core\Database;

class PayoutRequest extends BaseModel
{
    protected static string $table = 'payout_requests';

    public static function findByEstablishment(int $estabId): array
    {
        return Database::query(
            "SELECT * FROM payout_requests WHERE establishment_id = ? ORDER BY requested_at DESC",
            [$estabId]
        )->fetchAll();
    }

    public static function allPending(): array
    {
        return Database::query(
            "SELECT pr.*, e.name as establishment_name
             FROM payout_requests pr
             JOIN establishments e ON e.id = pr.establishment_id
             WHERE pr.status = 'pending'
             ORDER BY pr.requested_at ASC"
        )->fetchAll();
    }

    public static function allWithStatus(?string $status = null): array
    {
        $sql = "SELECT pr.*, e.name as establishment_name
                FROM payout_requests pr
                JOIN establishments e ON e.id = pr.establishment_id";
        $params = [];
        if ($status) {
            $sql .= " WHERE pr.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY pr.requested_at DESC";
        return Database::query($sql, $params)->fetchAll();
    }

    /** Montant brut des paiements réellement collectés en ligne (GeniusPay) pour l'établissement. */
    public static function grossOnlineCollected(int $estabId): float
    {
        return (float) Database::query(
            "SELECT COALESCE(SUM(p.amount), 0) FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ?
               AND b.pay_status = 'paid'
               AND p.status = 'completed'",
            [$estabId]
        )->fetchColumn();
    }

    /** Déjà versé ou réservé par une demande en cours (empêche un double retrait). */
    public static function reservedOrPaid(int $estabId): float
    {
        return (float) Database::query(
            "SELECT COALESCE(SUM(amount), 0) FROM payout_requests
             WHERE establishment_id = ? AND status IN ('pending', 'paid')",
            [$estabId]
        )->fetchColumn();
    }

    /** L'établissement peut retirer 100% du brut collecté en ligne (aucune commission plateforme). */
    public static function availableBalance(int $estabId): float
    {
        return round(self::grossOnlineCollected($estabId) - self::reservedOrPaid($estabId), 2);
    }
}
