<?php

namespace Models;

use Core\Database;

class Notification extends BaseModel
{
    protected static string $table = 'notifications';

    public static function forUser(int $userId, int $limit = 25): array
    {
        return Database::query(
            "SELECT * FROM notifications
             WHERE user_id = ?
             ORDER BY read_at IS NULL DESC, created_at DESC
             LIMIT ?",
            [$userId, $limit]
        )->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::query(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        )->fetchColumn();
    }

    public static function markRead(int $id, int $userId): void
    {
        Database::query(
            "UPDATE notifications SET read_at = NOW()
             WHERE id = ? AND user_id = ? AND read_at IS NULL",
            [$id, $userId]
        );
    }

    public static function markAllRead(int $userId): void
    {
        Database::query(
            "UPDATE notifications SET read_at = NOW()
             WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
    }
}
