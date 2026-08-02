<?php

namespace Controllers;

use Core\{Request, Response, Database};
use Models\{Establishment, User};
use Services\{NotificationService, PlanPricingService};

/**
 * Vue d'ensemble plateforme réservée au superadmin (propriétaire d'AfriStay).
 * Toutes les routes sont protégées par le middleware ['auth', 'role:superadmin'].
 */
class AdminController
{
    public function overview(Request $req, array $params = []): void
    {
        $breakdown = Establishment::planBreakdown();

        $mrr    = 0;
        $byPlan = ['starter' => 0, 'pro' => 0, 'business' => 0];
        foreach ($breakdown as $row) {
            $plan  = $row['effective_plan'];
            $count = (int) $row['count'];
            $byPlan[$plan] = $count;
            $mrr += $count * PlanPricingService::price($plan, 'monthly');
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

    /** GET /api/admin/owners/{id}/establishments — fiche détail propriétaire (owners.php). */
    public function ownerEstablishments(Request $req, array $params = []): void
    {
        $id    = (int) ($params['id'] ?? 0);
        $owner = User::find($id);
        if (!$owner || $owner['role'] !== 'owner') Response::notFound('Propriétaire introuvable');

        Response::success(Establishment::findByOwner($id));
    }

    /**
     * GET /api/admin/establishments-analytics — occupation, CA et classement
     * du mois en cours, agrégés par établissement puis par ville. Tout est
     * calculé en 3 requêtes groupées (pas de N+1 par établissement) : le
     * même principe que CalendarService::getEstablishmentOccupancy(), mais
     * pour l'ensemble de la plateforme d'un coup.
     */
    public function establishmentsAnalytics(Request $req, array $params = []): void
    {
        $monthStart  = date('Y-m-01');
        $daysInMonth = (int) date('t');

        $roomsByEstab = array_column(
            Database::query(
                "SELECT establishment_id, COUNT(*) AS total_rooms
                 FROM rooms WHERE status != 'blocked' GROUP BY establishment_id"
            )->fetchAll(),
            'total_rooms', 'establishment_id'
        );

        $nightsByEstab = array_column(
            Database::query(
                "SELECT r.establishment_id,
                        SUM(DATEDIFF(LEAST(b.check_out, LAST_DAY(?)), GREATEST(b.check_in, ?))) AS booked_nights
                 FROM bookings b
                 JOIN rooms r ON r.id = b.room_id
                 WHERE b.status != 'cancelled'
                   AND b.check_in < LAST_DAY(?) AND b.check_out > ?
                 GROUP BY r.establishment_id",
                [$monthStart, $monthStart, $monthStart, $monthStart]
            )->fetchAll(),
            'booked_nights', 'establishment_id'
        );

        $revenueByEstab = array_column(
            Database::query(
                "SELECT r.establishment_id, COALESCE(SUM(p.amount), 0) AS revenue
                 FROM payments p
                 JOIN bookings b ON b.id = p.booking_id
                 JOIN rooms r ON r.id = b.room_id
                 WHERE p.status = 'completed' AND DATE(p.paid_at) BETWEEN ? AND LAST_DAY(?)
                 GROUP BY r.establishment_id",
                [$monthStart, $monthStart]
            )->fetchAll(),
            'revenue', 'establishment_id'
        );

        $establishments = Database::query(
            "SELECT id, name, city, latitude, longitude FROM establishments WHERE is_active = 1"
        )->fetchAll();

        $list = array_map(function (array $e) use ($roomsByEstab, $nightsByEstab, $revenueByEstab, $daysInMonth) {
            $totalRooms = (int) ($roomsByEstab[$e['id']] ?? 0);
            $occupancy  = $totalRooms > 0
                ? round((float) ($nightsByEstab[$e['id']] ?? 0) / ($totalRooms * $daysInMonth) * 100, 1)
                : 0.0;

            return [
                'id'        => (int) $e['id'],
                'name'      => $e['name'],
                'city'      => $e['city'] !== null && trim($e['city']) !== '' ? trim($e['city']) : 'Ville non renseignée',
                'latitude'  => $e['latitude']  !== null ? (float) $e['latitude']  : null,
                'longitude' => $e['longitude'] !== null ? (float) $e['longitude'] : null,
                'occupancy' => $occupancy,
                'revenue'   => (float) ($revenueByEstab[$e['id']] ?? 0),
            ];
        }, $establishments);

        // Agrégation par ville — coordonnées moyennées sur les établissements
        // géolocalisés (pas de table de référence ville→lat/lng dans l'app :
        // les positions individuelles des établissements suffisent au marqueur).
        $byCity = [];
        foreach ($list as $e) {
            $key = mb_strtolower($e['city']);
            $byCity[$key] ??= ['city' => $e['city'], 'count' => 0, 'occ_sum' => 0.0, 'revenue' => 0.0, 'lat_sum' => 0.0, 'lng_sum' => 0.0, 'geo_count' => 0];
            $byCity[$key]['count']++;
            $byCity[$key]['occ_sum']  += $e['occupancy'];
            $byCity[$key]['revenue']  += $e['revenue'];
            if ($e['latitude'] !== null && $e['longitude'] !== null) {
                $byCity[$key]['lat_sum'] += $e['latitude'];
                $byCity[$key]['lng_sum'] += $e['longitude'];
                $byCity[$key]['geo_count']++;
            }
        }
        $cities = array_map(fn($c) => [
            'city'            => $c['city'],
            'establishments'  => $c['count'],
            'occupancy'       => round($c['occ_sum'] / $c['count'], 1),
            'revenue'         => $c['revenue'],
            'latitude'        => $c['geo_count'] > 0 ? $c['lat_sum'] / $c['geo_count'] : null,
            'longitude'       => $c['geo_count'] > 0 ? $c['lng_sum'] / $c['geo_count'] : null,
        ], array_values($byCity));
        usort($cities, fn($a, $b) => $b['occupancy'] <=> $a['occupancy']);

        $top = $list;
        usort($top, fn($a, $b) => $b['occupancy'] <=> $a['occupancy']);
        $top = array_slice($top, 0, 8);

        $activeCount = count($list);

        Response::success([
            'month'  => date('Y-m'),
            'totals' => [
                'active_establishments' => $activeCount,
                'avg_occupancy'         => $activeCount > 0 ? round(array_sum(array_column($list, 'occupancy')) / $activeCount, 1) : 0.0,
                'total_revenue'         => array_sum(array_column($list, 'revenue')),
            ],
            'cities'             => $cities,
            'top_establishments' => $top,
        ]);
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
