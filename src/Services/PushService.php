<?php

namespace Services;

use Minishlink\WebPush\{WebPush, Subscription};
use Models\{PushSubscription, GuestPushSubscription};

/**
 * Notifications push navigateur (hors application) — s'ajoute à
 * NotificationService (qui écrit en base pour le centre de notifications
 * in-app). Best-effort : une erreur d'envoi ne doit jamais faire échouer
 * l'action métier qui déclenche la notification.
 */
class PushService
{
    private static function enabled(): bool
    {
        return VAPID_PUBLIC_KEY !== '' && VAPID_PRIVATE_KEY !== '';
    }

    public static function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        if (!self::enabled()) return;

        $subs = PushSubscription::forUser($userId);
        if (!$subs) return;

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => VAPID_SUBJECT,
                    'publicKey'  => VAPID_PUBLIC_KEY,
                    'privateKey' => VAPID_PRIVATE_KEY,
                ],
            ]);

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
            ]);

            foreach ($subs as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint'   => $sub['endpoint'],
                        'publicKey'  => $sub['p256dh'],
                        'authToken'  => $sub['auth_token'],
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                // 404/410 : l'abonnement n'existe plus côté navigateur (désinstallation,
                // permission révoquée…) — on le retire pour ne pas retenter indéfiniment.
                if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                    PushSubscription::deleteExpired($report->getEndpoint());
                }
            }
        } catch (\Throwable $e) {
            error_log('[PushService] ' . $e->getMessage());
        }
    }

    public static function sendToUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        foreach (array_unique($userIds) as $userId) {
            self::sendToUser((int) $userId, $title, $body, $data);
        }
    }

    /** Voyageur sans compte, abonné via /api/public/push/subscribe (cf. GuestPushSubscription). */
    public static function sendToBooking(int $bookingId, string $title, string $body, array $data = []): void
    {
        if (!self::enabled()) return;

        $subs = GuestPushSubscription::forBooking($bookingId);
        if (!$subs) return;

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => VAPID_SUBJECT,
                    'publicKey'  => VAPID_PUBLIC_KEY,
                    'privateKey' => VAPID_PRIVATE_KEY,
                ],
            ]);

            $payload = json_encode(['title' => $title, 'body' => $body, 'data' => $data]);

            foreach ($subs as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint'  => $sub['endpoint'],
                        'publicKey' => $sub['p256dh'],
                        'authToken' => $sub['auth_token'],
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                    GuestPushSubscription::deleteExpired($report->getEndpoint());
                }
            }
        } catch (\Throwable $e) {
            error_log('[PushService] sendToBooking — ' . $e->getMessage());
        }
    }
}
