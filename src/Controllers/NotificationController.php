<?php

namespace Controllers;

use Core\{Request, Response};
use Models\Notification;

class NotificationController
{
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
}
