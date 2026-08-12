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

        $anchor = $req->get('date') ?? date('Y-m-d');
        [$period, $from, $to, $months] = self::resolvePeriod($req);

        $data = self::gatherSummary($estabIds, $from, $to, $months);
        $data['previous'] = self::previousPeriodComparison($estabIds, $period, $anchor, $data);
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
        $mode   = in_array($req->get('mode'), ['all', 'compare'], true) ? $req->get('mode') : 'single';
        $anchor = $req->get('date') ?? date('Y-m-d');
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
                $data['previous'] = self::previousPeriodComparison($estabIds, $period, $anchor, $data);
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

    /** @return array{0:string,1:string,2:string[]} [from, to, months] de la période immédiatement précédente. */
    private static function resolvePreviousPeriod(string $period, string $anchor): array
    {
        if ($period === 'year') {
            $prevAnchor = date('Y-m-d', strtotime($anchor . ' -1 year'));
            $year   = date('Y', strtotime($prevAnchor));
            $from   = "$year-01-01";
            $to     = "$year-12-31";
            $months = [];
            for ($m = 1; $m <= 12; $m++) $months[] = sprintf('%s-%02d', $year, $m);
        } else {
            $prevAnchor = date('Y-m-d', strtotime($anchor . ' -1 month'));
            $month  = date('Y-m', strtotime($prevAnchor));
            $from   = "$month-01";
            $to     = date('Y-m-t', strtotime($prevAnchor));
            $months = [$month];
        }
        return [$from, $to, $months];
    }

    /**
     * Comparaison vs la période immédiatement précédente (mois dernier si
     * period=month, année dernière si period=year) — revenus/dépenses/bénéfice/
     * occupation + variation en %. Recalcule un gatherSummary() complet sur la
     * période précédente (léger surcoût de requêtes, acceptable pour une page
     * de rapports peu sollicitée) plutôt que dupliquer une version allégée.
     */
    private static function previousPeriodComparison(array $estabIds, string $period, string $anchor, array $current): array
    {
        [$prevFrom, $prevTo, $prevMonths] = self::resolvePreviousPeriod($period, $anchor);
        $prev = self::gatherSummary($estabIds, $prevFrom, $prevTo, $prevMonths);

        $pct = function (float $curr, float $prev): float {
            if ($prev == 0.0) return $curr > 0 ? 100.0 : 0.0;
            return round(($curr - $prev) / $prev * 100, 1);
        };

        return [
            'revenue'          => $prev['revenue'],
            'expenses'         => $prev['expenses'],
            'net_profit'       => $prev['net_profit'],
            'occupancy_rate'   => $prev['occupancy_rate'],
            'revenue_pct'      => $pct($current['revenue'], $prev['revenue']),
            'expenses_pct'     => $pct($current['expenses'], $prev['expenses']),
            'net_profit_pct'   => $pct($current['net_profit'], $prev['net_profit']),
            'occupancy_pct'    => $pct($current['occupancy_rate'], $prev['occupancy_rate']),
        ];
    }

    /** Cœur du calcul, scopé à un ou plusieurs établissements (IN (...) au lieu de =). */
    private static function gatherSummary(array $estabIds, string $from, string $to, array $months): array
    {
        $in = implode(',', array_fill(0, count($estabIds), '?'));

        // Une réservation annulée après encaissement ne doit plus compter
        // dans le CA (voir Payment::totalByEstablishment, même règle).
        // SUM(amount - commission_amount) : voir Payment::totalByEstablishment()
        // pour l'explication — sur le plan Starter (paiement en ligne commissionné),
        // amount seul est le montant BRUT payé par le client, une partie
        // (commission_amount) ne revient jamais à l'établissement.
        $revenue = (float) Database::query(
            "SELECT COALESCE(SUM(p.amount - p.commission_amount), 0)
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

        $revenueByRoomType = Database::query(
            "SELECT rt.name AS room_type, COALESCE(SUM(p.amount - p.commission_amount), 0) as total, COUNT(DISTINCT b.id) as bookings_count
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.establishment_id IN ($in) AND p.status = 'completed'
               AND b.status != 'cancelled'
               AND DATE(p.paid_at) BETWEEN ? AND ?
             GROUP BY rt.id, rt.name
             ORDER BY total DESC",
            [...$estabIds, $from, $to]
        )->fetchAll();

        // Plus de LIMIT 5 : un rapport téléchargé/consulté doit lister TOUS les
        // paiements de la période, pas juste les 5 derniers (sinon inexploitable
        // comme export réel dès qu'il y a plus de 5 encaissements sur le mois/l'année).
        // p.amount reste le montant BRUT réellement transféré par le client (utile
        // pour rapprocher un relevé GeniusPay) ; net_amount (amount - commission_amount)
        // est ce que l'établissement en garde réellement — c'est net_amount qui doit
        // s'additionner pour retomber sur le CA affiché plus haut, pas amount seul.
        $recentPayments = Database::query(
            "SELECT p.id, i.invoice_number as reference, p.amount,
                (p.amount - p.commission_amount) as net_amount, p.commission_amount,
                p.method, p.status, p.paid_at,
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
             ORDER BY p.paid_at DESC",
            [...$estabIds, $from, $to]
        )->fetchAll();

        // Classement clients : identifiant stable même si le client est un
        // public_client (réservation en ligne) OU un user (réservation créée par
        // le personnel) — encodage COALESCE(id, -user_id) pour grouper sans
        // collision, les deux espaces d'ID démarrant à 1 (voir même pattern
        // client_name que Booking::allWithFilters()/recentPayments ci-dessus).
        // MIN(...) plutôt qu'une sélection directe de client_name/client_email : le
        // GROUP BY porte sur une expression (COALESCE), pas sur les colonnes
        // sélectionnées telles quelles — ONLY_FULL_GROUP_BY (mode strict par défaut
        // depuis MySQL 5.7) rejetterait sinon la requête même si chaque groupe ne
        // contient réellement qu'une seule valeur possible pour ces deux colonnes.
        $topClients = Database::query(
            "SELECT MIN(COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name)) as client_name,
                    MIN(COALESCE(pc.email, u.email)) as client_email,
                    COUNT(DISTINCT b.id) as bookings_count,
                    COALESCE(SUM(p.amount - p.commission_amount), 0) as total_spent
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             LEFT JOIN public_clients pc ON pc.id = b.public_client_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE r.establishment_id IN ($in) AND p.status = 'completed'
               AND b.status != 'cancelled'
               AND DATE(p.paid_at) BETWEEN ? AND ?
             GROUP BY COALESCE(b.public_client_id, -b.user_id)
             ORDER BY total_spent DESC
             LIMIT 10",
            [...$estabIds, $from, $to]
        )->fetchAll();

        return [
            'revenue'              => $revenue,
            'revenue_by_room_type' => $revenueByRoomType,
            'top_clients'          => $topClients,
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
