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

    public static function forUser(array $user): array
    {
        if ($user['role'] === 'superadmin') {
            return self::all('name');
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
