<?php

namespace Models;

use Core\Database;

class Announcement extends BaseModel
{
    protected static string $table = 'announcements';

    public static function allRecent(): array
    {
        return Database::query(
            "SELECT * FROM announcements ORDER BY created_at DESC"
        )->fetchAll();
    }

    /** La plus récente active — affichée en bandeau sur la vitrine publique. */
    public static function activeOne(): ?array
    {
        $row = Database::query(
            "SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1"
        )->fetch();
        return $row ?: null;
    }
}
