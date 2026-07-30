<?php

namespace Controllers;

use Core\{Request, Response};
use Models\{Notification, User};

class NotificationController
{
    /** Types désactivables — tenu en phase avec les raccourcis de NotificationService. */
    private const MUTABLE_TYPES = [
        'booking_new', 'booking_cancelled', 'payment_received', 'invoice_sent', 'invoice_paid',
        'team_member_added', 'subscription_expiring', 'arrival_reminder', 'departure_reminder',
        'new_establishment', 'payout_requested', 'subscription_activated',
    ];

    private function userId(): int
    {
        return (int) ($_REQUEST['_user']['id'] ?? 0);
    }

    public function index(Request $req, array $params = []): void
    {
        $userId = $this->userId();
        if (!$userId) Response::error('Non authentifié', 401);

        $notifications = Notification::forUser($userId, 25);
        $unread        = Notification::unreadCount($userId);

        Response::success([
            'notifications' => $notifications,
            'unread'        => $unread,
        ]);
    }

    public function count(Request $req, array $params = []): void
    {
        $userId = $this->userId();
        Response::success(['count' => $userId ? Notification::unreadCount($userId) : 0]);
    }

    public function markRead(Request $req, array $params = []): void
    {
        $userId = $this->userId();
        $id     = (int) ($params['id'] ?? 0);
        if ($id && $userId) {
            Notification::markRead($id, $userId);
        }
        Response::success(null, 'Lu');
    }

    public function markAllRead(Request $req, array $params = []): void
    {
        $userId = $this->userId();
        if ($userId) {
            Notification::markAllRead($userId);
        }
        Response::success(null, 'Tout marqué comme lu');
    }

    // ─── Préférences (types de notification désactivés par l'utilisateur) ─────

    public function preferences(Request $req, array $params = []): void
    {
        $userId = $this->userId();
        if (!$userId) Response::error('Non authentifié', 401);

        $user  = User::find($userId);
        $muted = $user['notification_muted_types'] ?? null;
        $muted = $muted ? (json_decode($muted, true) ?: []) : [];

        Response::success([
            'muted_types'   => array_values(array_intersect($muted, self::MUTABLE_TYPES)),
            'mutable_types' => self::MUTABLE_TYPES,
        ]);
    }

    public function updatePreferences(Request $req, array $params = []): void
    {
        $userId = $this->userId();
        if (!$userId) Response::error('Non authentifié', 401);

        $muted = $req->input('muted_types', []);
        if (!is_array($muted)) Response::error('Format invalide');

        // Seuls les types connus sont retenus : évite qu'un client mal formé
        // ne désactive silencieusement un type qui n'existe pas (ou plus).
        $muted = array_values(array_intersect($muted, self::MUTABLE_TYPES));

        User::update($userId, ['notification_muted_types' => $muted ? json_encode($muted) : null]);

        Response::success(['muted_types' => $muted], 'Préférences enregistrées');
    }
}
