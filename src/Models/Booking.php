<?php

namespace Models;

use Core\Database;

class Booking extends BaseModel
{
    protected static string $table = 'bookings';

    /**
     * guest_token : jeton opaque généré à chaque réservation, renvoyé une
     * seule fois au client dans la réponse de création. Requis pour
     * autoriser l'abonnement push anonyme du voyageur à SA réservation
     * (sans ça, un booking_id séquentiel serait devinable — cf. PushController).
     */
    public static function create(array $data): int
    {
        $data['guest_token'] = $data['guest_token'] ?? bin2hex(random_bytes(32));
        return parent::create($data);
    }

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
                r.number as room_number, r.floor, r.slug as room_slug,
                rt.name as room_type, rt.base_price, rt.weekend_price, rt.passage_price,
                e.name as establishment_name,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name,
                COALESCE(pc.email, u.email) as client_email,
                COALESCE(pc.phone, u.phone) as client_phone,
                i.id as invoice_id, i.invoice_number, i.status as invoice_status,
                i.amount_ttc,
                COALESCE((
                    SELECT SUM(p.amount) FROM payments p
                    WHERE p.invoice_id = i.id AND p.status = 'completed'
                ), 0) as paid_amount
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

    /**
     * Un "passage" a check_in == check_out en base (aucune heure précise n'est stockée,
     * seule la durée en heures) : on le traite comme occupant la journée entière pour la
     * comparaison de chevauchement (côté nouvelle réservation ET côté réservations
     * existantes), sinon `check_out > check_in` échoue toujours et un conflit — y compris
     * un double passage sur le même jour — n'est jamais détecté.
     */
    public static function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeId = null): bool
    {
        $effectiveCheckOut = $checkOut > $checkIn ? $checkOut : date('Y-m-d', strtotime($checkIn . ' +1 day'));

        $sql = "SELECT COUNT(*) FROM bookings
                WHERE room_id = ?
                  AND status NOT IN ('cancelled', 'checked_out')
                  AND check_in < ?
                  AND (CASE WHEN check_out <= check_in THEN DATE_ADD(check_in, INTERVAL 1 DAY) ELSE check_out END) > ?";
        $params = [$roomId, $effectiveCheckOut, $checkIn];

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

        $nights = max(1, (new \DateTime($checkOut))->diff(new \DateTime($checkIn))->days);

        $basePrice    = (float) $rt['base_price'];
        $weekendPrice = (float) ($rt['weekend_price'] ?? 0);

        // Réservation manuelle "weekend" : le forfait week-end (override staff),
        // au même tarif forfaitaire que la détection automatique ci-dessous.
        if ($bookingType === 'weekend') {
            return $weekendPrice ?: ($nights * $basePrice);
        }

        // Nuit standard : le tarif week-end est un FORFAIT pour un bloc complet
        // vendredi+samedi+dimanche (pas un prix par nuit) — un week-end incomplet
        // (arrivée un samedi par ex.) reste au tarif de base. Cohérent avec
        // l'estimation client (booking.js / saas-bookings.js).
        if (!$weekendPrice || $weekendPrice === $basePrice) {
            return $nights * $basePrice;
        }

        $dows = [];
        $day  = new \DateTime($checkIn);
        for ($i = 0; $i < $nights; $i++) {
            $dows[] = (int) $day->format('w'); // 0 = dimanche … 6 = samedi
            $day->modify('+1 day');
        }

        $total = 0.0;
        for ($i = 0; $i < count($dows); $i++) {
            if (($dows[$i] ?? null) === 5 && ($dows[$i + 1] ?? null) === 6 && ($dows[$i + 2] ?? null) === 0) {
                $total += $weekendPrice;
                $i += 2; // bloc vendredi-samedi-dimanche déjà compté
            } else {
                $total += $basePrice;
            }
        }
        return $total;
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
