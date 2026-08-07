<?php

namespace Models;

use Core\Database;

class Payment extends BaseModel
{
    protected static string $table = 'payments';

    public static function allWithDetails(int $estabId, array $filters = []): array
    {
        $where = ['r.establishment_id = ?'];
        $params = [$estabId];

        if (!empty($filters['method'])) {
            $where[] = 'p.method = ?';
            $params[] = $filters['method'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.status = ?';
            $params[] = $filters['status'];
        }

        return Database::query(
            "SELECT p.*,
                i.invoice_number,
                r.number as room_number,
                b.status as booking_status,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             JOIN invoices i ON i.id = p.invoice_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.paid_at DESC",
            $params
        )->fetchAll();
    }

    public static function totalByEstablishment(int $estabId, string $month): float
    {
        // Une réservation annulée après encaissement ne doit plus compter
        // dans le CA — sinon le revenu reste gonflé indéfiniment par des
        // paiements liés à des séjours qui n'auront jamais lieu.
        return (float) Database::query(
            "SELECT COALESCE(SUM(p.amount), 0)
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ? AND p.status = 'completed'
               AND b.status != 'cancelled'
               AND DATE_FORMAT(p.paid_at, '%Y-%m') = ?",
            [$estabId, $month]
        )->fetchColumn();
    }
}
