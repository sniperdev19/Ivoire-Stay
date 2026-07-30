<?php

namespace Models;

use Core\Database;

class Establishment extends BaseModel
{
    protected static string $table = 'establishments';

    public static function findByOwner(int $ownerId): array
    {
        return self::where(['owner_id' => $ownerId]);
    }

    public static function findByQrToken(string $qrToken): ?array
    {
        return self::first(['qr_token' => $qrToken]);
    }

    /** Génère systématiquement le qr_token ("Mon QR code") scanné par les agents commerciaux. */
    public static function create(array $data): int
    {
        $data['qr_token'] ??= bin2hex(random_bytes(16));
        return parent::create($data);
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::first(['slug' => $slug]);
    }

    /**
     * Slug unique dérivé du nom + id (l'id étant unique par définition,
     * pas besoin de gérer les collisions).
     */
    public static function generateSlug(string $name, int $id): string
    {
        return \Core\Slug::withId($name, $id);
    }

    /**
     * Chiffres réels affichés sur la vitrine publique (bandeau d'accueil).
     * Volontairement limité à des métriques vérifiables en base — pas de
     * "note moyenne" tant qu'aucun système d'avis n'existe.
     */
    public static function platformStats(): array
    {
        $establishments = (int) Database::query(
            "SELECT COUNT(*) FROM establishments WHERE is_active = 1 AND frozen_at IS NULL"
        )->fetchColumn();

        $rooms = (int) Database::query(
            "SELECT COUNT(*) FROM rooms r
             JOIN establishments e ON e.id = r.establishment_id
             WHERE e.is_active = 1 AND e.frozen_at IS NULL"
        )->fetchColumn();

        $bookings = (int) Database::query(
            "SELECT COUNT(*) FROM bookings WHERE status != 'cancelled'"
        )->fetchColumn();

        $cities = (int) Database::query(
            "SELECT COUNT(DISTINCT city) FROM establishments
             WHERE is_active = 1 AND frozen_at IS NULL AND city IS NOT NULL AND city != ''"
        )->fetchColumn();

        return [
            'establishments' => $establishments,
            'rooms'          => $rooms,
            'bookings'       => $bookings,
            'cities'         => $cities,
        ];
    }

    public static function allActive(): array
    {
        return Database::query(
            "SELECT e.*, u.name as owner_name
             FROM establishments e
             JOIN users u ON u.id = e.owner_id
             WHERE e.is_active = 1
             ORDER BY e.name"
        )->fetchAll();
    }

    /** Tous les établissements (actifs + désactivés) avec le nom du propriétaire — vue admin plateforme. */
    public static function allWithOwner(): array
    {
        return Database::query(
            "SELECT e.*, u.name as owner_name, u.email as owner_email
             FROM establishments e
             JOIN users u ON u.id = e.owner_id
             ORDER BY e.created_at DESC"
        )->fetchAll();
    }

    /**
     * Répartition du nombre d'établissements par plan effectif (un abonnement expiré
     * compte comme Starter — même logique que PlanGate::getPlan(), pour ne pas
     * surestimer le nombre d'établissements réellement payants).
     */
    public static function planBreakdown(): array
    {
        return Database::query(
            "SELECT
                CASE WHEN plan != 'starter' AND (plan_expires_at IS NULL OR plan_expires_at >= NOW())
                     THEN plan ELSE 'starter' END AS effective_plan,
                COUNT(*) as count
             FROM establishments
             GROUP BY effective_plan"
        )->fetchAll();
    }

    public static function withStats(int $id): ?array
    {
        return Database::query(
            "SELECT e.*,
                COUNT(DISTINCT r.id) as total_rooms,
                COUNT(DISTINCT CASE WHEN r.status = 'available' THEN r.id END) as available_rooms,
                COUNT(DISTINCT CASE WHEN r.status = 'occupied' THEN r.id END) as occupied_rooms
             FROM establishments e
             LEFT JOIN rooms r ON r.establishment_id = e.id
             WHERE e.id = ?
             GROUP BY e.id",
            [$id]
        )->fetch() ?: null;
    }

    public static function photos(int $id): array
    {
        return Database::query(
            "SELECT * FROM establishment_photos WHERE establishment_id = ? ORDER BY is_cover DESC, sort_order",
            [$id]
        )->fetchAll();
    }

    public static function forUser(array $user): array
    {
        if ($user['role'] === 'superadmin') {
            return self::allWithOwner();
        }
        if ($user['role'] === 'owner') {
            return self::findByOwner($user['id']);
        }
        if ($user['establishment_id']) {
            $e = self::find($user['establishment_id']);
            return $e ? [$e] : [];
        }
        return [];
    }
}
