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
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name
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
                r.number as room_number, rt.name as room_type,
                e.name as establishment_name, e.address as establishment_address,
                e.phone as establishment_phone,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name,
                COALESCE(pc.email, u.email) as client_email,
                COALESCE(pc.phone, u.phone) as client_phone
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
        $count = (int) Database::query(
            "SELECT COUNT(*) FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ?",
            [$estabId]
        )->fetchColumn();

        return 'SYNC-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function createForBooking(int $bookingId, float $amount, float $taxRate = 0): int
    {
        $booking = Booking::find($bookingId);
        if (!$booking) return 0;

        $room = Room::find($booking['room_id']);
        $estabId = $room['establishment_id'];

        $amountHt  = round($amount / (1 + $taxRate / 100), 2);
        $amountTtc = $amount;

        return self::create([
            'booking_id'     => $bookingId,
            'invoice_number' => self::generateNumber($estabId),
            'amount_ht'      => $amountHt,
            'tax_rate'       => $taxRate,
            'amount_ttc'     => $amountTtc,
            'status'         => 'draft',
        ]);
    }

    public static function paidAmount(int $invoiceId): float
    {
        return (float) Database::query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND status = 'completed'",
            [$invoiceId]
        )->fetchColumn();
    }
}
