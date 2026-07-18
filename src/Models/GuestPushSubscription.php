<?php

namespace Models;

use Core\Database;

/**
 * Abonnements push des voyageurs sans compte, liés à une réservation
 * (booking_id) plutôt qu'à un user_id — cf. PushSubscription pour l'équivalent
 * côté utilisateurs authentifiés.
 */
class GuestPushSubscription extends BaseModel
{
    protected static string $table = 'guest_push_subscriptions';

    public static function forBooking(int $bookingId): array
    {
        return self::where(['booking_id' => $bookingId]);
    }

    public static function upsert(int $bookingId, string $endpoint, string $p256dh, string $authToken): int
    {
        $existing = Database::query(
            "SELECT id FROM guest_push_subscriptions WHERE booking_id = ? AND endpoint = ?",
            [$bookingId, $endpoint]
        )->fetch();

        if ($existing) {
            self::update((int) $existing['id'], ['p256dh' => $p256dh, 'auth_token' => $authToken]);
            return (int) $existing['id'];
        }

        return self::create([
            'booking_id' => $bookingId,
            'endpoint'   => $endpoint,
            'p256dh'     => $p256dh,
            'auth_token' => $authToken,
        ]);
    }

    public static function deleteExpired(string $endpoint): void
    {
        Database::query("DELETE FROM guest_push_subscriptions WHERE endpoint = ?", [$endpoint]);
    }
}
