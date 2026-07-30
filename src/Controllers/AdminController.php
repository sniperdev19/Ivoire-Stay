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
            'total_establishments'     => array_sum($byPlan),
            'plan_breakdown'           => $byPlan,
            'estimated_mrr'            => $mrr,
            'total_bookings'           => (int) Database::query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
            'bookings_by_month'        => $this->monthlySeries('bookings', 'created_at'),
            'establishments_by_month'  => $this->monthlySeries('establishments', 'created_at'),
        ]);
    }

    /**
     * Série des 6 derniers mois (mois courant inclus), zéro-remplie pour les
     * mois sans ligne — sinon un mois sans activité disparaîtrait du graphe
     * au lieu d'apparaître à zéro. `$table`/`$dateCol` sont toujours des
     * constantes internes (jamais dérivées de la requête), donc pas de
     * risque d'injection à les interpoler directement.
     */
    private function monthlySeries(string $table, string $dateCol): array
    {
        // Part du 1er du mois avant de soustraire : "-5 months" appliqué
        // directement à un jour 29/30/31 déborde sur le mois suivant si le
        // mois cible en a moins (ex. depuis le 29/30/31, -5 mois tombe sur
        // un février qui n'a que 28/29 jours) — strtotime()/DateTime
        // rallongent alors au lieu de clamper, ce qui dupliquait un mois et
        // en sautait un autre.
        $months = [];
        $cursor = new \DateTime('first day of this month');
        for ($i = 5; $i >= 0; $i--) {
            $months[] = (clone $cursor)->modify("-{$i} months")->format('Y-m');
        }

        $rows = Database::query(
            "SELECT DATE_FORMAT({$dateCol}, '%Y-%m') as ym, COUNT(*) as cnt
             FROM {$table}
             WHERE {$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY ym"
        )->fetchAll();
        $byMonth = array_column($rows, 'cnt', 'ym');

        return array_map(
            fn($m) => ['month' => $m, 'count' => (int) ($byMonth[$m] ?? 0)],
            $months
        );
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
