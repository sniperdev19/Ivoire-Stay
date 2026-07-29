<?php

namespace Controllers;

use Core\{Request, Response, Database};
use Models\{Establishment, User};
use Services\NotificationService;

/**
 * Vue d'ensemble plateforme réservée au superadmin (propriétaire d'AfriStay).
 * Toutes les routes sont protégées par le middleware ['auth', 'role:superadmin'].
 */
class AdminController
{
    public function overview(Request $req, array $params = []): void
    {
        $plans     = require BASE_PATH . '/config/plans.php';
        $breakdown = Establishment::planBreakdown();

        $mrr    = 0;
        $byPlan = ['starter' => 0, 'pro' => 0, 'business' => 0];
        foreach ($breakdown as $row) {
            $plan  = $row['effective_plan'];
            $count = (int) $row['count'];
            $byPlan[$plan] = $count;
            $mrr += $count * ($plans[$plan]['prices']['monthly'] ?? 0);
        }

        Response::success([
            'total_establishments' => array_sum($byPlan),
            'plan_breakdown'       => $byPlan,
            'estimated_mrr'        => $mrr,
            'total_bookings'       => (int) Database::query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        ]);
    }

    public function owners(Request $req, array $params = []): void
    {
        Response::success(User::allOwners());
    }

    /** POST /api/admin/notifications/broadcast — annonce envoyée à tous les propriétaires. */
    public function broadcastNotification(Request $req, array $params = []): void
    {
        $title   = trim((string) $req->input('title', ''));
        $message = trim((string) $req->input('message', ''));

        if ($title === '') Response::error('Le titre est requis');
        if (mb_strlen($title) > 150) Response::error('Titre trop long (150 caractères max)');
        if (mb_strlen($message) > 1000) Response::error('Message trop long (1000 caractères max)');

        $count = NotificationService::broadcastToOwners($title, $message);

        Response::success(['recipients' => $count], "Notification envoyée à {$count} propriétaire(s)");
    }
}
