<?php

namespace Models;

use Core\Database;

class ContactMessage extends BaseModel
{
    protected static string $table = 'contact_messages';

    public static function allRecent(): array
    {
        return Database::query(
            "SELECT * FROM contact_messages ORDER BY created_at DESC"
        )->fetchAll();
    }

    public static function markRead(int $id): void
    {
        Database::query(
            "UPDATE contact_messages SET read_at = NOW() WHERE id = ? AND read_at IS NULL",
            [$id]
        );
    }
}
