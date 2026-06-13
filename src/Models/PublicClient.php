<?php

namespace Models;

use Core\Database;

class PublicClient extends BaseModel
{
    protected static string $table = 'public_clients';

    public static function findOrCreate(array $data): int
    {
        $existing = self::first(['email' => $data['email']]);
        if ($existing) return $existing['id'];
        return self::create($data);
    }

    /**
     * Liste les clients avec leur nombre de réservations.
     *
     * @param int[]|null $estabIds null = tous (superadmin) ; sinon restreint aux
     *                             clients ayant au moins une réservation dans ces
     *                             établissements.
     */
    public static function allWithBookingCount(?array $estabIds = null): array
    {
        if ($estabIds === null) {
            return Database::query(
                "SELECT pc.*, COUNT(b.id) as booking_count,
                        MAX(b.created_at) as last_booking
                 FROM public_clients pc
                 LEFT JOIN bookings b ON b.public_client_id = pc.id
                 GROUP BY pc.id
                 ORDER BY pc.created_at DESC"
            )->fetchAll();
        }

        if (!$estabIds) return [];

        $in = implode(',', array_fill(0, count($estabIds), '?'));
        return Database::query(
            "SELECT pc.*, COUNT(b.id) as booking_count,
                    MAX(b.created_at) as last_booking
             FROM public_clients pc
             JOIN bookings b ON b.public_client_id = pc.id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id IN ($in)
             GROUP BY pc.id
             ORDER BY pc.created_at DESC",
            $estabIds
        )->fetchAll();
    }
}
