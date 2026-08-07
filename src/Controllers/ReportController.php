<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate, Guard};
use Models\Establishment;
use Services\{CalendarService, PdfService};

class ReportController
{
    public function summary(Request $req, array $params = []): void
    {
        $user = $_REQUEST['_user'];

        if ($req->get('scope') === 'all') {
            $estabIds = Guard::establishmentIds();
            if (!$estabIds) Response::error('Aucun établissement accessible');
            $estab = self::bestPlanEstablishment($estabIds);
            $scope = 'all';
        } else {
            $estabId = (int) ($req->get('establishment_id') ?? $user['establishment_id'] ?? 0);
            if (!$estabId) Response::error('establishment_id requis');
            // Faille corrigée : cette route ne vérifiait jamais que l'établissement
            // demandé appartenait à l'appelant (aucun Guard) — n'importe quel
            // utilisateur authentifié pouvait lire le rapport financier complet
            // d'un établissement qui n'était pas le sien.
            Guard::requireEstablishment($estabId);
            $estab = Establishment::find($estabId);
            if (!$estab) Response::notFound('Établissement introuvable');
            $estabIds = [$estabId];
            $scope = 'single';
        }

        if ($estab) PlanGate::require($estab, 'reports');

        [$period, $from, $to, $months] = self::resolvePeriod($req);

        $data = self::gatherSummary($estabIds, $from, $to, $months);
        $data = ['period' => $period, 'from' => $from, 'to' => $to, 'scope' => $scope] + $data;

        Response::success($data);
    }

    /** Rapport comparatif : une ligne par établissement accessible à l'appelant. */
    public function compare(Request $req, array $params = []): void
    {
        $estabIds = Guard::establishmentIds();
        if (!$estabIds) Response::error('Aucun établissement accessible');

        $estab = self::bestPlanEstablishment($estabIds);
        if ($estab) PlanGate::require($estab, 'reports');

        [$period, $from, $to, $months] = self::resolvePeriod($req);

        Response::success([
            'period'         => $period,
            'from'           => $from,
            'to'             => $to,
            'establishments' => self::compareRows($estabIds, $from, $to, $months),
        ]);
    }

    /** Export PDF — mêmes paramètres/portées que summary()/compare() (mode=single|all|compare). */
    public function pdf(Request $req, array $params = []): void
    {
        $mode = in_array($req->get('mode'), ['all', 'compare'], true) ? $req->get('mode') : 'single';
        [$period, $from, $to, $months] = self::resolvePeriod($req);

        try {
            if ($mode === 'compare') {
                $estabIds = Guard::establishmentIds();
                if (!$estabIds) Response::error('Aucun établissement accessible');
                $estab = self::bestPlanEstablishment($estabIds);
                if ($estab) PlanGate::require($estab, 'reports');

                $path = PdfService::generateReport([
                    'mode'   => 'compare',
                    'period' => $period, 'from' => $from, 'to' => $to,
                    'rows'   => self::compareRows($estabIds, $from, $to, $months),
                ]);
                $filename = 'rapport-comparatif-' . $from . '.pdf';
            } else {
                $user = $_REQUEST['_user'];
                if ($mode === 'all') {
                    $estabIds = Guard::establishmentIds();
                    if (!$estabIds) Response::error('Aucun établissement accessible');
                    $estab = self::bestPlanEstablishment($estabIds);
                    $title = 'Tous les établissements';
                } else {
                    $estabId = (int) ($req->get('establishment_id') ?? $user['establishment_id'] ?? 0);
                    if (!$estabId) Response::error('establishment_id requis');
                    Guard::requireEstablishment($estabId);
                    $estab = Establishment::find($estabId);
                    if (!$estab) Response::notFound('Établissement introuvable');
                    $estabIds = [$estabId];
                    $title = $estab['name'] ?? '';
                }
                if ($estab) PlanGate::require($estab, 'reports');

                $data = self::gatherSummary($estabIds, $from, $to, $months);
                $path = PdfService::generateReport(
                    ['mode' => $mode, 'title' => $title, 'period' => $period, 'from' => $from, 'to' => $to] + $data
                );
                $filename = 'rapport-' . $from . '.pdf';
            }

            $absPath = BASE_PATH . '/' . $path;
            if (!file_exists($absPath)) Response::error('Erreur génération PDF');

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            readfile($absPath);
            exit;
        } catch (\Exception $e) {
            Response::error('Erreur génération PDF : ' . $e->getMessage());
        }
    }

    // ─── Internes ───────────────────────────────────────────────────────────────

    private static function compareRows(array $estabIds, string $from, string $to, array $months): array
    {
        $rows = [];
        foreach ($estabIds as $id) {
            $estab = Establishment::find($id);
            if (!$estab) continue;
            $data = self::gatherSummary([$id], $from, $to, $months);
            $rows[] = [
                'establishment_id' => $id,
                'name'             => $estab['name'],
                'revenue'          => $data['revenue'],
                'expenses'         => $data['expenses'],
                'net_profit'       => $data['net_profit'],
                'occupancy_rate'   => $data['occupancy_rate'],
                'paid_invoices'    => $data['paid_invoices'],
            ];
        }
        return $rows;
    }

    /**
     * Établissement de référence pour le PlanGate d'une vue combinée/comparative :
     * le plan le plus élevé parmi les établissements accessibles (posséder un
     * établissement Business est ce qui débloque le multi-établissements, les
     * autres établissements du même compte peuvent rester en plan inférieur).
     */
    private static function bestPlanEstablishment(array $estabIds): ?array
    {
        $rank = ['starter' => 0, 'pro' => 1, 'business' => 2];
        $best = null;
        $bestRank = -1;
        foreach ($estabIds as $id) {
            $e = Establishment::find($id);
            if (!$e) continue;
            $r = $rank[PlanGate::getPlan($e)] ?? 0;
            if ($r > $bestRank) { $bestRank = $r; $best = $e; }
        }
        return $best;
    }

    /** @return array{0:string,1:string,2:string,3:string[]} [period, from, to, months] */
    private static function resolvePeriod(Request $req): array
    {
        $period = $req->get('period') === 'year' ? 'year' : 'month';
        $anchor = $req->get('date') ?? date('Y-m-d');

        if ($period === 'year') {
            $year   = date('Y', strtotime($anchor));
            $from   = "$year-01-01";
            $to     = "$year-12-31";
            $months = [];
            for ($m = 1; $m <= 12; $m++) $months[] = sprintf('%s-%02d', $year, $m);
        } else {
            $month  = date('Y-m', strtotime($anchor));
            $from   = "$month-01";
            $to     = date('Y-m-t', strtotime($anchor));
            $months = [$month];
        }
        return [$period, $from, $to, $months];
    }

    /** Cœur du calcul, scopé à un ou plusieurs établissements (IN (...) au lieu de =). */
    private static function gatherSummary(array $estabIds, string $from, string $to, array $months): array
    {
        $in = implode(',', array_fill(0, count($estabIds), '?'));

        // Une réservation annulée après encaissement ne doit plus compter
        // dans le CA (voir Payment::totalByEstablishment, même règle).
        $revenue = (float) Database::query(
            "SELECT COALESCE(SUM(p.amount), 0)
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id IN ($in) AND p.status = 'completed'
               AND b.status != 'cancelled'
               AND DATE(p.paid_at) BETWEEN ? AND ?",
            [...$estabIds, $from, $to]
        )->fetchColumn();

        $expTotal = (float) Database::query(
            "SELECT COALESCE(SUM(amount), 0) FROM expenses
             WHERE establishment_id IN ($in) AND expense_date BETWEEN ? AND ?",
            [...$estabIds, $from, $to]
        )->fetchColumn();

        $expByCategory = Database::query(
            "SELECT category, SUM(amount) as total FROM expenses
             WHERE establishment_id IN ($in) AND expense_date BETWEEN ? AND ?
             GROUP BY category ORDER BY total DESC",
            [...$estabIds, $from, $to]
        )->fetchAll();

        $paidInvoices = (float) Database::query(
            "SELECT COALESCE(SUM(i.amount_ttc), 0)
             FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id IN ($in) AND i.status = 'paid'
               AND b.status != 'cancelled'
               AND DATE(i.issued_at) BETWEEN ? AND ?",
            [...$estabIds, $from, $to]
        )->fetchColumn();

        $pendingPayments = (float) Database::query(
            "SELECT COALESCE(SUM(i.amount_ttc - COALESCE(paid.total, 0)), 0)
             FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             LEFT JOIN (
                 SELECT invoice_id, SUM(amount) as total FROM payments WHERE status = 'completed' GROUP BY invoice_id
             ) paid ON paid.invoice_id = i.id
             WHERE r.establishment_id IN ($in) AND i.status NOT IN ('paid', 'cancelled')",
            $estabIds
        )->fetchColumn();

        $recentPayments = Database::query(
            "SELECT p.id, i.invoice_number as reference, p.amount, p.method, p.status, p.paid_at,
                COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN invoices i ON i.id = p.invoice_id
             WHERE r.establishment_id IN ($in) AND p.status = 'completed'
               AND b.status != 'cancelled'
               AND DATE(p.paid_at) BETWEEN ? AND ?
             ORDER BY p.paid_at DESC LIMIT 5",
            [...$estabIds, $from, $to]
        )->fetchAll();

        return [
            'revenue'              => $revenue,
            'expenses'             => $expTotal,
            'expenses_by_category' => $expByCategory,
            'net_profit'           => $revenue - $expTotal,
            'paid_invoices'        => $paidInvoices,
            'pending_payments'     => $pendingPayments,
            'occupancy_rate'       => self::occupancyRate($estabIds, $months),
            'recent_payments'      => $recentPayments,
        ];
    }

    /**
     * Mono-établissement : délègue tel quel à CalendarService (parité stricte
     * avec le comportement historique — même calcul, même moyenne simple par mois).
     * Multi-établissements : moyenne pondérée (nuits réservées / nuits
     * disponibles, tous établissements confondus) plutôt qu'une moyenne de
     * pourcentages, plus représentative quand les établissements ont des
     * tailles différentes.
     */
    private static function occupancyRate(array $estabIds, array $months): float
    {
        if (count($estabIds) === 1) {
            $sum = 0;
            foreach ($months as $m) $sum += CalendarService::getEstablishmentOccupancy($estabIds[0], $m);
            return round($sum / count($months), 1);
        }

        $in = implode(',', array_fill(0, count($estabIds), '?'));
        $totalNights  = 0;
        $bookedNights = 0;

        foreach ($months as $month) {
            $totalRooms = (int) Database::query(
                "SELECT COUNT(*) FROM rooms WHERE establishment_id IN ($in) AND status != 'blocked'",
                $estabIds
            )->fetchColumn();
            if ($totalRooms === 0) continue;

            $totalNights += $totalRooms * (int) date('t', strtotime($month . '-01'));

            $booked = (int) Database::query(
                "SELECT COALESCE(SUM(
                    DATEDIFF(
                        LEAST(b.check_out, LAST_DAY(CONCAT(?, '-01'))),
                        GREATEST(b.check_in, CONCAT(?, '-01'))
                    )
                 ), 0)
                 FROM bookings b
                 JOIN rooms r ON r.id = b.room_id
                 WHERE r.establishment_id IN ($in)
                   AND b.status NOT IN ('cancelled')
                   AND b.check_in < LAST_DAY(CONCAT(?, '-01'))
                   AND b.check_out > CONCAT(?, '-01')",
                [$month, $month, ...$estabIds, $month, $month]
            )->fetchColumn();
            $bookedNights += max(0, $booked);
        }

        return $totalNights > 0 ? round($bookedNights / $totalNights * 100, 1) : 0.0;
    }
}
