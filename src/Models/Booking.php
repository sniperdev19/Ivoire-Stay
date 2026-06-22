<?php

namespace Models;

use Core\Database;

class Booking extends BaseModel
{
    protected static string $table = 'bookings';

    public static function allWithDetails(int $estabId, array $filters = []): array
    {
        $where = ['r.establishment_id = ?'];
        $params = [$estabId];

        if (!empty($filters['status'])) {
            $where[] = 'b.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'b.check_in >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'b.check_out <= ?';
            $params[] = $filters['to'];
        }

        $sql = "SELECT b.*, b.booking_type, b.hours,
                    r.number as room_number, r.floor,
                    rt.name as room_type,
                    COALESCE(
                        CONCAT(pc.first_name, ' ', pc.last_name),
                        u.name
                    ) as client_name,
                    COALESCE(pc.email, u.email) as client_email,
                    COALESCE(pc.phone, u.phone) as client_phone
                FROM bookings b
                JOIN rooms r ON r.id = b.room_id
                JOIN room_types rt ON rt.id = r.room_type_id
                LEFT JOIN public_clients pc ON pc.id = b.public_client_id
                LEFT JOIN users u ON u.id = b.user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.created_at DESC";

        return Database::query($sql, $params)->fetchAll();
    }

    public static function findWithDetails(int $id): ?array
    {
        return Database::query(
            "SELECT b.*, b.booking_type, b.hours,
                r.number as room_number, r.floor,
                rt.name as room_type, rt.base_price, rt.weekend_price, rt.passage_price,
                e.name as establishment_name,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name,
                COALESCE(pc.email, u.email) as client_email,
                COALESCE(pc.phone, u.phone) as client_phone,
                i.id as invoice_id, i.invoice_number, i.status as invoice_status,
                i.amount_ttc
             FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             JOIN room_types rt ON rt.id = r.room_type_id
             JOIN establishments e ON e.id = r.establishment_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN invoices i ON i.booking_id = b.id
             WHERE b.id = ?",
            [$id]
        )->fetch() ?: null;
    }

    public static function forPlanning(int $estabId, string $from, string $to): array
    {
        return Database::query(
            "SELECT b.id, b.check_in, b.check_out, b.status,
                    b.total_amount, b.booking_type, b.hours,
                    r.id as room_id, r.number as room_number, r.floor,
                    rt.name as room_type,
                    COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name
             FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             JOIN room_types rt ON rt.id = r.room_type_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE r.establishment_id = ?
               AND b.status NOT IN ('cancelled')
               AND b.check_in < ? AND b.check_out > ?
             ORDER BY r.floor, r.number, b.check_in",
            [$estabId, $to, $from]
        )->fetchAll();
    }

    public static function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM bookings
                WHERE room_id = ?
                  AND status NOT IN ('cancelled', 'checked_out')
                  AND check_in < ? AND check_out > ?";
        $params = [$roomId, $checkOut, $checkIn];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        return (int) Database::query($sql, $params)->fetchColumn() === 0;
    }

    public static function calculateAmount(int $roomTypeId, string $checkIn, string $checkOut, string $bookingType = 'nuit', int $hours = 0): float
    {
        $rt = RoomType::find($roomTypeId);
        if (!$rt) return 0;

        if ($bookingType === 'passage') {
            $pricePerHour = (float) ($rt['passage_price'] ?: $rt['base_price']);
            return max(1, $hours) * $pricePerHour;
        }

        $nights = (new \DateTime($checkOut))->diff(new \DateTime($checkIn))->days;
        $price  = $bookingType === 'weekend'
            ? (float) ($rt['weekend_price'] ?: $rt['base_price'])
            : (float) $rt['base_price'];
        return max(1, $nights) * $price;
    }

    public static function recentByEstablishment(int $estabId, int $limit = 5): array
    {
        return Database::query(
            "SELECT b.*,
                r.number as room_number, rt.name as room_type,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name
             FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             JOIN room_types rt ON rt.id = r.room_type_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE r.establishment_id = ?
             ORDER BY b.created_at DESC
             LIMIT ?",
            [$estabId, $limit]
        )->fetchAll();
    }
}
