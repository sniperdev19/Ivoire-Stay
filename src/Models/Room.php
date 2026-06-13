<?php

namespace Models;

use Core\Database;

class Room extends BaseModel
{
    protected static string $table = 'rooms';

    public static function allWithDetails(int $estabId): array
    {
        return Database::query(
            "SELECT r.*, rt.name as type_name, rt.base_price, rt.weekend_price, rt.passage_price, rt.capacity,
                    (SELECT file_path FROM room_photos WHERE room_id = r.id AND is_cover = 1 LIMIT 1) as cover_photo,
                    (SELECT GROUP_CONCAT(file_path SEPARATOR '|')
                     FROM (SELECT file_path FROM room_photos WHERE room_id = r.id ORDER BY is_cover DESC, sort_order LIMIT 3) p3
                    ) as photos_list
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.establishment_id = ?
             ORDER BY r.floor, r.number",
            [$estabId]
        )->fetchAll();
    }

    public static function findWithDetails(int $id): ?array
    {
        $room = Database::query(
            "SELECT r.*, rt.name as type_name, rt.base_price, rt.weekend_price,
                    rt.passage_price, rt.capacity, rt.description as type_description,
                    e.name as establishment_name, e.type as establishment_type
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             JOIN establishments e ON e.id = r.establishment_id
             WHERE r.id = ?",
            [$id]
        )->fetch();

        if (!$room) return null;

        $room['amenities'] = Database::query(
            "SELECT * FROM room_amenities WHERE room_id = ?", [$id]
        )->fetchAll();

        $room['photos'] = Database::query(
            "SELECT * FROM room_photos WHERE room_id = ? ORDER BY is_cover DESC, sort_order", [$id]
        )->fetchAll();

        return $room;
    }

    public static function available(int $estabId, string $checkIn, string $checkOut): array
    {
        return Database::query(
            "SELECT r.*, rt.name as type_name, rt.base_price, rt.capacity,
                    (SELECT file_path FROM room_photos WHERE room_id = r.id AND is_cover = 1 LIMIT 1) as cover_photo
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.establishment_id = ?
               AND r.status = 'available'
               AND r.id NOT IN (
                   SELECT room_id FROM bookings
                   WHERE status NOT IN ('cancelled', 'checked_out')
                     AND check_in < ? AND check_out > ?
               )
             ORDER BY rt.base_price",
            [$estabId, $checkOut, $checkIn]
        )->fetchAll();
    }

    public static function updateStatus(int $id, string $status): bool
    {
        return self::update($id, ['status' => $status]);
    }
}
