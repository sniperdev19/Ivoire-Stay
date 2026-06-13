<?php

namespace Services;

use Core\Database;

class CalendarService
{
    public static function getRoomAvailability(int $roomId, string $from, string $to): array
    {
        $bookings = Database::query(
            "SELECT check_in, check_out, status FROM bookings
             WHERE room_id = ? AND status NOT IN ('cancelled','checked_out')
               AND check_in < ? AND check_out > ?
             ORDER BY check_in",
            [$roomId, $to, $from]
        )->fetchAll();

        $days = [];
        $current = new \DateTime($from);
        $end     = new \DateTime($to);

        while ($current < $end) {
            $date = $current->format('Y-m-d');
            $days[$date] = 'available';

            foreach ($bookings as $b) {
                if ($date >= $b['check_in'] && $date < $b['check_out']) {
                    $days[$date] = $b['status'];
                    break;
                }
            }

            $current->modify('+1 day');
        }

        return $days;
    }

    public static function getEstablishmentOccupancy(int $estabId, string $month): float
    {
        $totalRooms = (int) Database::query(
            "SELECT COUNT(*) FROM rooms WHERE establishment_id = ? AND status != 'blocked'",
            [$estabId]
        )->fetchColumn();

        if ($totalRooms === 0) return 0;

        $daysInMonth = (int) date('t', strtotime($month . '-01'));
        $totalNights = $totalRooms * $daysInMonth;

        $bookedNights = (int) Database::query(
            "SELECT SUM(
                DATEDIFF(
                    LEAST(check_out, LAST_DAY(CONCAT(?, '-01'))),
                    GREATEST(check_in, CONCAT(?, '-01'))
                )
             )
             FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ?
               AND b.status NOT IN ('cancelled')
               AND b.check_in < LAST_DAY(CONCAT(?, '-01'))
               AND b.check_out > CONCAT(?, '-01')",
            [$month, $month, $estabId, $month, $month]
        )->fetchColumn();

        return $totalNights > 0 ? round($bookedNights / $totalNights * 100, 1) : 0;
    }
}
