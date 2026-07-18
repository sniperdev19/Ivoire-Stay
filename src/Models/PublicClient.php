<?php

namespace Models;

use Core\Database;

class PublicClient extends BaseModel
{
    protected static string $table = 'public_clients';

    public static function findOrCreate(array $data): int
    {
        $existing = self::first(['email' => $data['email']]);
        if ($existing) {
            // Synchronise les coordonnées avec la dernière saisie : sans cela, un client
            // qui réserve avec un email déjà connu (mais un nom/téléphone différent, ex.
            // email partagé) voyait ses vraies infos silencieusement ignorées au profit
            // de celles du tout premier client enregistré sous cet email.
            self::update($existing['id'], array_filter([
                'first_name'    => $data['first_name']    ?? null,
                'last_name'     => $data['last_name']     ?? null,
                'phone'         => $data['phone']         ?? null,
                'id_doc_type'   => $data['id_doc_type']   ?? null,
                'id_doc_number' => $data['id_doc_number'] ?? null,
            ], fn($v) => $v !== null && $v !== ''));
            return $existing['id'];
        }
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
