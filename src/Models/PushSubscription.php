<?php

namespace Models;

use Core\Database;

class PushSubscription extends BaseModel
{
    protected static string $table = 'push_subscriptions';

    public static function forUser(int $userId): array
    {
        return self::where(['user_id' => $userId]);
    }

    /**
     * Idempotent : un même endpoint (= un même navigateur/appareil) ne doit
     * apparaître qu'une fois pour un utilisateur — sinon on renverrait des
     * doublons de notification à chaque abonnement répété (ex: PWA relancée).
     */
    public static function upsert(int $userId, string $endpoint, string $p256dh, string $authToken, ?string $userAgent): int
    {
        $existing = Database::query(
            "SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?",
            [$userId, $endpoint]
        )->fetch();

        if ($existing) {
            self::update((int) $existing['id'], [
                'p256dh'     => $p256dh,
                'auth_token' => $authToken,
                'user_agent' => $userAgent,
            ]);
            return (int) $existing['id'];
        }

        return self::create([
            'user_id'    => $userId,
            'endpoint'   => $endpoint,
            'p256dh'     => $p256dh,
            'auth_token' => $authToken,
            'user_agent' => $userAgent,
        ]);
    }

    public static function deleteByEndpoint(int $userId, string $endpoint): bool
    {
        $stmt = Database::query(
            "DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?",
            [$userId, $endpoint]
        );
        return $stmt->rowCount() > 0;
    }

    public static function deleteExpired(string $endpoint): void
    {
        Database::query("DELETE FROM push_subscriptions WHERE endpoint = ?", [$endpoint]);
    }
}
