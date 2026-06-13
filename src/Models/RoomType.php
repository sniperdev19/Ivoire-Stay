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
        return Database::query(
            "SELECT rt.*, COUNT(r.id) as room_count
             FROM room_types rt
             LEFT JOIN rooms r ON r.room_type_id = rt.id
             WHERE rt.establishment_id = ?
             GROUP BY rt.id
             ORDER BY rt.name",
            [$estabId]
        )->fetchAll();
    }
}
