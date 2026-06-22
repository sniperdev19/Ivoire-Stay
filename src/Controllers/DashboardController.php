<?php

namespace Controllers;

use Core\{Request, Response};
use Core\Database;
use Models\{Booking, Expense, Payment, Room};
use Services\CalendarService;

class DashboardController
{
    public function stats(Request $req, array $params = []): void
    {
        $user   = $_REQUEST['_user'];
        $estabId = (int) ($req->get('establishment_id') ?? $user['establishment_id'] ?? 0);

        if (!$estabId) Response::error('establishment_id requis');

        $month = $req->get('month') ?? date('Y-m');

        // Revenue this month
        $revenue = Payment::totalByEstablishment($estabId, $month);

        // Occupancy rate
        $occupancy = CalendarService::getEstablishmentOccupancy($estabId, $month);

        // Active bookings
        $activeBookings = (int) Database::query(
            "SELECT COUNT(*) FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ? AND b.status IN ('confirmed','checked_in')",
            [$estabId]
        )->fetchColumn();

        // New bookings this month
        $newBookings = (int) Database::query(
            "SELECT COUNT(*) FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ? AND DATE_FORMAT(b.created_at, '%Y-%m') = ?",
            [$estabId, $month]
        )->fetchColumn();

        // Available rooms now
        $availableRooms = (int) Database::query(
            "SELECT COUNT(*) FROM rooms WHERE establishment_id = ? AND status = 'available'",
            [$estabId]
        )->fetchColumn();

        $totalRooms = (int) Database::query(
            "SELECT COUNT(*) FROM rooms WHERE establishment_id = ?",
            [$estabId]
        )->fetchColumn();

        // Expenses
        $expenses = Expense::totalByMonth($estabId, $month);

        // Revenue received
        $paymentsReceived = (float) Database::query(
            "SELECT COALESCE(SUM(p.amount), 0)
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ? AND p.status = 'completed'
               AND DATE_FORMAT(p.paid_at, '%Y-%m') = ?",
            [$estabId, $month]
        )->fetchColumn();

        // Pending payments
        $paymentsPending = (float) Database::query(
            "SELECT COALESCE(SUM(i.amount_ttc - COALESCE(paid.total, 0)), 0)
             FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             LEFT JOIN (
                 SELECT invoice_id, SUM(amount) as total FROM payments WHERE status = 'completed' GROUP BY invoice_id
             ) paid ON paid.invoice_id = i.id
             WHERE r.establishment_id = ? AND i.status != 'paid' AND i.status != 'cancelled'",
            [$estabId]
        )->fetchColumn();

        // Booking status distribution
        $distribution = Database::query(
            "SELECT b.status, COUNT(*) as count
             FROM bookings b JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ? AND DATE_FORMAT(b.created_at, '%Y-%m') = ?
             GROUP BY b.status",
            [$estabId, $month]
        )->fetchAll();

        // Room type distribution (real data)
        $typeRows = Database::query(
            "SELECT rt.name as type_name, COUNT(r.id) as room_count
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.establishment_id = ?
             GROUP BY rt.id, rt.name
             ORDER BY room_count DESC",
            [$estabId]
        )->fetchAll();

        $typeDistribution = [];
        if ($totalRooms > 0) {
            foreach ($typeRows as $row) {
                $typeDistribution[] = [
                    'label' => $row['type_name'],
                    'count' => (int) $row['room_count'],
                    'pct'   => round((int)$row['room_count'] / $totalRooms * 100),
                ];
            }
        }

        // Occupied rooms right now (bookings confirmed ou checked_in)
        $occupiedRooms = (int) Database::query(
            "SELECT COUNT(DISTINCT b.room_id) FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ?
               AND b.status IN ('confirmed','checked_in')
               AND b.check_in <= CURDATE() AND b.check_out > CURDATE()",
            [$estabId]
        )->fetchColumn();

        // Recent bookings
        $recent = Booking::recentByEstablishment($estabId, 5);

        Response::success([
            'revenue'            => $revenue,
            'occupancy_rate'     => $occupancy,
            'active_bookings'    => $activeBookings,
            'new_bookings'       => $newBookings,
            'available_rooms'    => $availableRooms,
            'total_rooms'        => $totalRooms,
            'occupied_rooms'     => $occupiedRooms,
            'expenses'           => $expenses,
            'payments_received'  => $paymentsReceived,
            'payments_pending'   => $paymentsPending,
            'net_profit'         => $paymentsReceived - $expenses,
            'distribution'       => $distribution,
            'type_distribution'  => $typeDistribution,
            'recent_bookings'    => $recent,
            'month'              => $month,
        ]);
    }

    public function planning(Request $req, array $params = []): void
    {
        $user    = $_REQUEST['_user'];
        $estabId = (int) ($req->get('establishment_id') ?? $user['establishment_id'] ?? 0);
        $from    = $req->get('from') ?? date('Y-m-d');
        $to      = $req->get('to')   ?? date('Y-m-d', strtotime('+7 days'));

        if (!$estabId) Response::error('establishment_id requis');

        $rooms    = Room::allWithDetails($estabId);
        $bookings = Booking::forPlanning($estabId, $from, $to);

        Response::success([
            'rooms'    => $rooms,
            'bookings' => $bookings,
            'from'     => $from,
            'to'       => $to,
        ]);
    }
}
