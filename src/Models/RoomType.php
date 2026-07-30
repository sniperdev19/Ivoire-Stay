<?php

namespace Models;

use Core\Database;

class RoomType extends BaseModel
{
    protected static string $table = 'room_types';

    public static function findByEstablishment(int $estabId): array
    {
        return self::where(['establishment_id' => $estabId], 'name');
    }

    public static function withRoomCount(int $estabId): array
    {
        $rows = Database::query(
            "SELECT rt.*, COUNT(r.id) as room_count
             FROM room_types rt
             LEFT JOIN rooms r ON r.room_type_id = rt.id
             WHERE rt.establishment_id = ?
             GROUP BY rt.id
             ORDER BY rt.name",
            [$estabId]
        )->fetchAll();
        return array_map([self::class, 'decorate'], $rows);
    }

    public static function find(int $id): ?array
    {
        $row = parent::find($id);
        return $row ? self::decorate($row) : null;
    }

    private static function decorate(array $row): array
    {
        $row['amenities'] = $row['amenities'] ? json_decode($row['amenities'], true) : [];
        return $row;
    }
}
