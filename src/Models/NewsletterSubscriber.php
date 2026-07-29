<?php

namespace Models;

use Core\Database;

class NewsletterSubscriber extends BaseModel
{
    protected static string $table = 'newsletter_subscribers';

    public static function findByEmail(string $email): ?array
    {
        return self::first(['email' => $email]);
    }

    public static function findByToken(string $token): ?array
    {
        return self::first(['unsubscribe_token' => $token]);
    }

    /** Destinataires actifs d'une campagne (jamais désabonnés). */
    public static function activeSubscribers(): array
    {
        return Database::query(
            "SELECT * FROM newsletter_subscribers WHERE unsubscribed_at IS NULL ORDER BY subscribed_at DESC"
        )->fetchAll();
    }

    public static function countActive(): int
    {
        return (int) Database::query(
            "SELECT COUNT(*) FROM newsletter_subscribers WHERE unsubscribed_at IS NULL"
        )->fetchColumn();
    }
}
