<?php

namespace Controllers;

use Core\{Request, Response, RateLimiter};
use Models\{PushSubscription, GuestPushSubscription, Booking};

class PushController
{
    public function publicKey(Request $req, array $params = []): void
    {
        Response::success(['public_key' => VAPID_PUBLIC_KEY ?: null]);
    }

    public function subscribe(Request $req, array $params = []): void
    {
        $data = $req->all();
        $user = $_REQUEST['_user'];

        $endpoint = $data['endpoint'] ?? '';
        $keys     = $data['keys'] ?? [];
        $p256dh   = $keys['p256dh'] ?? '';
        $auth     = $keys['auth'] ?? '';

        if (!$endpoint || !$p256dh || !$auth) {
            Response::error('Abonnement push invalide');
        }

        PushSubscription::upsert(
            (int) $user['id'],
            $endpoint,
            $p256dh,
            $auth,
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)
        );

        Response::success(null, 'Notifications activées', 201);
    }

    public function unsubscribe(Request $req, array $params = []): void
    {
        $data     = $req->all();
        $user     = $_REQUEST['_user'];
        $endpoint = $data['endpoint'] ?? '';

        if (!$endpoint) Response::error('endpoint requis');

        PushSubscription::deleteByEndpoint((int) $user['id'], $endpoint);
        Response::success(null, 'Notifications désactivées');
    }

    // ─── Voyageur sans compte, abonnement lié à SA réservation ─────────────────

    public function subscribeGuest(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'guest-push-subscribe:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 20, 3600)) {
            Response::error('Trop de tentatives.', 429);
        }
        RateLimiter::hit($key, 3600);

        $data       = $req->all();
        $bookingId  = (int) ($data['booking_id'] ?? 0);
        $guestToken = (string) ($data['guest_token'] ?? '');
        $endpoint   = $data['endpoint'] ?? '';
        $keys       = $data['keys'] ?? [];
        $p256dh     = $keys['p256dh'] ?? '';
        $auth       = $keys['auth'] ?? '';

        if (!$bookingId || !$guestToken || !$endpoint || !$p256dh || !$auth) {
            Response::error('Requête invalide');
        }

        // Le guest_token (émis une seule fois à la création, cf. Models\Booking::create)
        // prouve que l'appelant connaît bien SA réservation — sans ce contrôle,
        // un booking_id séquentiel serait devinable et n'importe qui pourrait
        // s'abonner aux notifications de la réservation d'un inconnu.
        $booking = Booking::find($bookingId);
        if (!$booking || empty($booking['guest_token']) || !hash_equals($booking['guest_token'], $guestToken)) {
            Response::error('Réservation ou jeton invalide', 403);
        }

        GuestPushSubscription::upsert((int) $bookingId, $endpoint, $p256dh, $auth);

        Response::success(null, 'Rappel activé', 201);
    }
}
