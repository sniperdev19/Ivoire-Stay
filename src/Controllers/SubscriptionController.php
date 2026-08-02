<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate, Guard};
use Models\Establishment;
use Services\{GeniusPayService, MailService, NotificationService, EstablishmentFreezeService, CommissionService, PlanPricingService};

class SubscriptionController
{
    // ─── GET /api/subscriptions/plans ────────────────────────────────────────
    public function plans(Request $req, array $params = []): void
    {
        Response::success(PlanPricingService::effectivePlans());
    }

    /**
     * Marque comme 'failed' les abonnements restés 'pending' plus de 30 minutes.
     * Genius Pay n'envoie pas toujours un webhook quand l'utilisateur abandonne
     * le paiement (ferme l'app/l'onglet sans revenir sur success_url/error_url) —
     * sans ce nettoyage opportuniste, ces lignes restent "en attente" indéfiniment.
     * Déclenché à chaque consultation de l'abonnement plutôt que par cron (pas
     * d'infra cron sur cet hébergement).
     */
    private function expireStalePending(int $estabId): void
    {
        Database::query(
            "UPDATE subscriptions SET status = 'failed'
             WHERE establishment_id = ? AND status = 'pending' AND created_at < NOW() - INTERVAL 30 MINUTE",
            [$estabId]
        );
    }

    // ─── GET /api/subscriptions/status ───────────────────────────────────────
    public function status(Request $req, array $params = []): void
    {
        $user  = $_REQUEST['_user'];
        // Guard::resolveEstabId($req) et non $user['establishment_id'] directement :
        // ce dernier est figé sur le PREMIER établissement du owner à
        // l'inscription — un owner multi-établissements (plan Business) ne
        // pouvait jamais consulter/gérer l'abonnement d'un second établissement,
        // ces actions retombaient toujours (silencieusement) sur le premier.
        $estab = Establishment::find(Guard::resolveEstabId($req));
        if (!$estab) Response::notFound('Établissement introuvable');

        $this->expireStalePending((int) $estab['id']);

        $plan       = PlanGate::getPlan($estab);
        $expiresAt  = $estab['plan_expires_at'] ?? null;
        $isExpired  = $expiresAt && strtotime($expiresAt) < time();

        $last = Database::query(
            "SELECT * FROM subscriptions WHERE establishment_id = ? ORDER BY created_at DESC LIMIT 1",
            [$estab['id']]
        )->fetch();

        Response::success([
            'plan'              => $plan,
            'plan_label'        => (require BASE_PATH . '/config/plans.php')[$plan]['name'] ?? $plan,
            'expires_at'        => $expiresAt,
            'is_expired'        => $isExpired,
            'last_sub'          => $last ?: null,
            'max_rooms'         => PlanGate::maxRooms($estab),
            'proration_credit'  => $this->prorationCredit($estab),
        ]);
    }

    // ─── GET /api/subscriptions/history ──────────────────────────────────────
    public function history(Request $req, array $params = []): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find(Guard::resolveEstabId($req)); // cf. status() ci-dessus
        if (!$estab) Response::notFound('Établissement introuvable');

        $this->expireStalePending((int) $estab['id']);

        $rows = Database::query(
            "SELECT * FROM subscriptions WHERE establishment_id = ? ORDER BY created_at DESC",
            [$estab['id']]
        )->fetchAll();

        Response::success($rows);
    }

    // ─── POST /api/subscriptions/cancel ──────────────────────────────────────
    public function cancel(Request $req, array $params = []): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find(Guard::resolveEstabId($req)); // cf. status() ci-dessus
        if (!$estab) Response::notFound('Établissement introuvable');

        $currentPlan = PlanGate::getPlan($estab);
        if ($currentPlan === 'starter') {
            Response::error('Aucun abonnement payant actif à annuler');
        }

        Database::query(
            "UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW()
             WHERE establishment_id = ? AND status = 'active'",
            [$estab['id']]
        );
        Database::query(
            "UPDATE establishments SET plan = 'starter', plan_expires_at = NULL WHERE id = ?",
            [$estab['id']]
        );

        $this->notifyPlanChange($estab, $currentPlan, 'starter');
        EstablishmentFreezeService::recompute((int) $estab['owner_id']);

        Response::success(null, 'Abonnement annulé. Vous êtes repassé au plan Starter.');
    }

    // ─── POST /api/subscriptions/downgrade  { plan: 'starter'|'pro' } ────────
    public function downgrade(Request $req, array $params = []): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find(Guard::resolveEstabId($req)); // cf. status() ci-dessus
        if (!$estab) Response::notFound('Établissement introuvable');

        $target      = $req->all()['plan'] ?? '';
        $currentPlan = PlanGate::getPlan($estab);
        $plans       = require BASE_PATH . '/config/plans.php';

        if (!isset($plans[$target])) Response::error('Plan invalide');
        if ($this->planRank($target) >= $this->planRank($currentPlan)) {
            Response::error("Ce n'est pas une rétrogradation, utilisez la page d'abonnement pour mettre à niveau");
        }

        Database::query(
            "UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW()
             WHERE establishment_id = ? AND status = 'active'",
            [$estab['id']]
        );

        if ($target === 'starter') {
            Database::query(
                "UPDATE establishments SET plan = 'starter', plan_expires_at = NULL WHERE id = ?",
                [$estab['id']]
            );
        } else {
            // Rétrogradation vers un plan payant intermédiaire : conserve l'échéance en cours,
            // pas de nouveau paiement (le nouveau plan est moins cher, pas de solde à réclamer).
            Database::query(
                "INSERT INTO subscriptions (establishment_id, plan, billing, amount, currency, status, started_at, expires_at)
                 VALUES (?, ?, 'monthly', 0, 'XOF', 'active', NOW(), ?)",
                [$estab['id'], $target, $estab['plan_expires_at']]
            );
            Database::query("UPDATE establishments SET plan = ? WHERE id = ?", [$target, $estab['id']]);
        }

        $this->notifyPlanChange($estab, $currentPlan, $target);
        EstablishmentFreezeService::recompute((int) $estab['owner_id']);

        Response::success(null, 'Abonnement rétrogradé vers ' . ($plans[$target]['name'] ?? $target));
    }

    private function planRank(string $plan): int
    {
        return ['starter' => 0, 'pro' => 1, 'business' => 2][$plan] ?? 0;
    }

    private function notifyPlanChange(array $estab, string $fromPlan, string $toPlan): void
    {
        $owner = Database::query(
            "SELECT name, email FROM users WHERE id = ?", [$estab['owner_id']]
        )->fetch();
        if (!$owner) return;

        $plans = require BASE_PATH . '/config/plans.php';
        MailService::subscriptionCancelled(
            $owner['email'],
            $owner['name'],
            $plans[$fromPlan]['name'] ?? $fromPlan,
            $plans[$toPlan]['name'] ?? $toPlan
        );
    }

    /**
     * Crédit de prorata (en FCFA) sur le temps restant de l'abonnement payant en cours,
     * à déduire du prochain paiement lors d'un changement de plan/période en cours de cycle.
     */
    private function prorationCredit(array $estab): float
    {
        $plan = PlanGate::getPlan($estab);
        if ($plan === 'starter') return 0.0;

        $sub = Database::query(
            "SELECT * FROM subscriptions WHERE establishment_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1",
            [$estab['id']]
        )->fetch();
        if (!$sub || empty($sub['started_at']) || empty($sub['expires_at'])) return 0.0;

        $now   = time();
        $start = strtotime($sub['started_at']);
        $end   = strtotime($sub['expires_at']);
        if ($now >= $end || $end <= $start) return 0.0;

        $totalDays     = max(1, ($end - $start) / 86400);
        $remainingDays = max(0, ($end - $now) / 86400);

        return round(((float) $sub['amount']) * ($remainingDays / $totalDays));
    }

    // ─── POST /api/subscriptions/initiate ────────────────────────────────────
    public function initiate(Request $req, array $params = []): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find(Guard::resolveEstabId($req)); // cf. status() ci-dessus
        if (!$estab) Response::notFound('Établissement introuvable');

        $this->expireStalePending((int) $estab['id']);

        $data    = $req->all();
        $plan    = $data['plan']    ?? '';
        $billing = $data['billing'] ?? 'monthly';

        $plans = require BASE_PATH . '/config/plans.php';
        if (!isset($plans[$plan]) || $plan === 'starter') {
            Response::error('Plan invalide');
        }

        $fullAmount = PlanPricingService::price($plan, $billing);
        if ($fullAmount <= 0) Response::error('Montant invalide');

        // Crédit de prorata sur le temps restant d'un abonnement payant en cours (upgrade/downgrade/changement de période)
        $credit = $this->prorationCredit($estab);
        $amount = max(0, $fullAmount - $credit);

        $reference = 'SYNC-' . $estab['id'] . '-' . strtoupper($plan) . '-' . time();

        // Crédit de prorata suffisant pour couvrir entièrement le nouveau plan : pas de paiement à initier,
        // on active tout de suite (évite d'envoyer un montant à 0 à GeniusPay).
        if ($amount <= 0) {
            Database::query(
                "UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW()
                 WHERE establishment_id = ? AND status = 'active'",
                [$estab['id']]
            );

            $months    = $billing === 'yearly' ? 12 : 1;
            $startedAt = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime("+$months months"));

            Database::query(
                "INSERT INTO subscriptions (establishment_id, plan, billing, amount, status, started_at, expires_at)
                 VALUES (?, ?, ?, 0, 'active', ?, ?)",
                [$estab['id'], $plan, $billing, $startedAt, $expiresAt]
            );
            Database::query(
                "UPDATE establishments SET plan = ?, plan_expires_at = ? WHERE id = ?",
                [$plan, $expiresAt, $estab['id']]
            );

            $owner = Database::query("SELECT name, email FROM users WHERE id = ?", [$estab['owner_id']])->fetch();
            if ($owner) MailService::subscriptionActivated($owner['email'], $owner['name'], $plan, $expiresAt);
            NotificationService::subscriptionActivated($estab['name'] ?? ('#' . $estab['id']), $plans[$plan]['name'] ?? $plan, (int) $estab['id']);
            if (($estab['plan'] ?? 'starter') === 'starter') {
                CommissionService::recordFirstSubscription((int) $estab['id'], $plan);
            }
            EstablishmentFreezeService::recompute((int) $estab['owner_id']);

            Response::success(['activated' => true], 'Abonnement activé, entièrement couvert par votre crédit de prorata');
            return;
        }

        // Save pending subscription
        Database::query(
            "INSERT INTO subscriptions (establishment_id, plan, billing, amount, gp_reference, status)
             VALUES (?, ?, ?, ?, ?, 'pending')",
            [$estab['id'], $plan, $billing, $amount, $reference]
        );

        try {
            $result = GeniusPayService::initiate([
                'amount'         => $amount,
                'description'    => 'Abonnement SYNC ' . $plans[$plan]['name'] . ($credit > 0 ? ' (prorata déduit)' : ''),
                'reference'      => $reference,
                'pay_method'     => $data['pay_method'] ?? '',
                'success_url'    => APP_URL . '/saas/settings?sub=ok&plan=' . $plan . '&ref=' . $reference,
                'error_url'      => APP_URL . '/saas/checkout?plan=' . $plan . '&error=1&ref=' . $reference,
                'customer_name'  => $user['name']  ?? 'Client',
                'customer_email' => $user['email'] ?? '',
            ]);

            // Store token
            Database::query(
                "UPDATE subscriptions SET gp_token = ? WHERE gp_reference = ?",
                [$result['token'], $reference]
            );

            Response::success([
                'payment_url' => $result['payment_url'],
                'reference'   => $reference,
            ], 'Paiement initié');

        } catch (\Exception $e) {
            // Mark failed
            Database::query(
                "UPDATE subscriptions SET status = 'failed' WHERE gp_reference = ?",
                [$reference]
            );
            Response::error('Erreur Genius Pay : ' . $e->getMessage());
        }
    }

    // ─── POST /api/subscriptions/callback (webhook Genius Pay, no auth) ──────
    public function callback(Request $_req, array $_params = []): void
    {
        $rawBody  = file_get_contents('php://input');
        $sig      = $_SERVER['HTTP_X_GENIUSPAY_SIGNATURE'] ?? '';

        if (!GeniusPayService::validateWebhook($rawBody, $sig)) {
            http_response_code(403);
            exit('Invalid signature');
        }

        $data        = json_decode($rawBody, true) ?? [];
        $tx          = $data['data']['transaction'] ?? [];
        $gpEvent     = $data['event']           ?? '';
        $gpReference = $tx['reference']         ?? '';
        $internalRef = $tx['metadata']['internal_reference'] ?? '';

        if (!$internalRef && !$gpReference) { http_response_code(400); exit('Missing reference'); }

        // Lookup by our internal reference first, then by GP reference stored in gp_token
        $sub = null;
        if ($internalRef) {
            $sub = Database::query(
                "SELECT * FROM subscriptions WHERE gp_reference = ?", [$internalRef]
            )->fetch();
        }
        if (!$sub && $gpReference) {
            $sub = Database::query(
                "SELECT * FROM subscriptions WHERE gp_token = ?", [$gpReference]
            )->fetch();
        }

        if (!$sub) { http_response_code(404); exit('Subscription not found'); }

        $txStatus       = $tx['status'] ?? '';
        $internalStatus = GeniusPayService::mapStatus($txStatus ?: $gpEvent);

        if ($internalStatus === 'active') {
            $months   = $sub['billing'] === 'yearly' ? 12 : 1;
            $startedAt = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime("+$months months"));

            Database::query(
                "UPDATE subscriptions SET status = 'active', started_at = ?, expires_at = ? WHERE id = ?",
                [$startedAt, $expiresAt, $sub['id']]
            );

            $prevPlan = Database::query(
                "SELECT plan FROM establishments WHERE id = ?", [$sub['establishment_id']]
            )->fetchColumn();

            Database::query(
                "UPDATE establishments SET plan = ?, plan_expires_at = ? WHERE id = ?",
                [$sub['plan'], $expiresAt, $sub['establishment_id']]
            );

            if (($prevPlan ?: 'starter') === 'starter') {
                CommissionService::recordFirstSubscription((int) $sub['establishment_id'], $sub['plan']);
            }

            // Email de confirmation d'abonnement
            $owner = Database::query(
                "SELECT u.name, u.email, e.name as estab_name FROM users u
                 JOIN establishments e ON e.owner_id = u.id
                 WHERE e.id = ?",
                [$sub['establishment_id']]
            )->fetch();
            if ($owner) {
                MailService::subscriptionActivated($owner['email'], $owner['name'], $sub['plan'], $expiresAt);
                $plans = require BASE_PATH . '/config/plans.php';
                NotificationService::subscriptionActivated(
                    $owner['estab_name'] ?? ('#' . $sub['establishment_id']),
                    $plans[$sub['plan']]['name'] ?? $sub['plan'],
                    (int) $sub['establishment_id']
                );
            }

            $ownerId = (int) Database::query(
                "SELECT owner_id FROM establishments WHERE id = ?", [$sub['establishment_id']]
            )->fetchColumn();
            if ($ownerId) EstablishmentFreezeService::recompute($ownerId);
        } elseif ($internalStatus === 'failed') {
            Database::query(
                "UPDATE subscriptions SET status = 'failed' WHERE id = ?",
                [$sub['id']]
            );
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
        exit;
    }

    // ─── GET /api/subscriptions/verify/{ref} ─────────────────────────────────
    public function verify(Request $_req, array $params = []): void
    {
        $reference = $params['ref'] ?? $_GET['_route_id'] ?? '';
        if (!$reference) Response::error('Référence manquante');

        $sub = Database::query(
            "SELECT * FROM subscriptions WHERE gp_reference = ?", [$reference]
        )->fetch();

        if (!$sub) Response::notFound('Transaction introuvable');

        // La référence (SYNC-{id établissement}-{plan}-{timestamp}) est prévisible :
        // sans ce contrôle, n'importe quel utilisateur authentifié pourrait consulter
        // ou déclencher l'activation de l'abonnement d'un autre établissement.
        Guard::requireEstablishment((int) $sub['establishment_id']);

        // Already active — return early
        if ($sub['status'] === 'active') {
            Response::success(['status' => 'active', 'plan' => $sub['plan']]);
        }

        try {
            // gp_token contient la référence MTX-... de GeniusPay, nécessaire pour l'API verify
            $gpRef = $sub['gp_token'] ?? '';
            if (!$gpRef) {
                Response::success(['status' => $sub['status'], 'gp_status' => 'unknown']);
            }

            $result         = GeniusPayService::verify($gpRef);
            $internalStatus = GeniusPayService::mapStatus($result['status']);

            // En sandbox, l'API verify retourne TRANSACTION_NOT_FOUND — on fait confiance au redirect
            if ($internalStatus === 'pending' && APP_ENV === 'development'
                && isset($result['raw']['error']['code'])
                && $result['raw']['error']['code'] === 'TRANSACTION_NOT_FOUND') {
                $internalStatus = 'active';
            }

            if ($internalStatus === 'active') {
                $months    = $sub['billing'] === 'yearly' ? 12 : 1;
                $startedAt = date('Y-m-d H:i:s');
                $expiresAt = date('Y-m-d H:i:s', strtotime("+$months months"));

                Database::query(
                    "UPDATE subscriptions SET status = 'active', started_at = ?, expires_at = ? WHERE id = ?",
                    [$startedAt, $expiresAt, $sub['id']]
                );

                $prevPlan = Database::query(
                    "SELECT plan FROM establishments WHERE id = ?", [$sub['establishment_id']]
                )->fetchColumn();

                Database::query(
                    "UPDATE establishments SET plan = ?, plan_expires_at = ? WHERE id = ?",
                    [$sub['plan'], $expiresAt, $sub['establishment_id']]
                );

                if (($prevPlan ?: 'starter') === 'starter') {
                    CommissionService::recordFirstSubscription((int) $sub['establishment_id'], $sub['plan']);
                }

                // Email activation (si pas encore envoyé via webhook)
                $owner = Database::query(
                    "SELECT u.name, u.email, e.name as estab_name FROM users u
                     JOIN establishments e ON e.owner_id = u.id
                     WHERE e.id = ?",
                    [$sub['establishment_id']]
                )->fetch();
                if ($owner) {
                    MailService::subscriptionActivated($owner['email'], $owner['name'], $sub['plan'], $expiresAt);
                    $plans = require BASE_PATH . '/config/plans.php';
                    NotificationService::subscriptionActivated(
                        $owner['estab_name'] ?? ('#' . $sub['establishment_id']),
                        $plans[$sub['plan']]['name'] ?? $sub['plan'],
                        (int) $sub['establishment_id']
                    );
                }

                $ownerId = (int) Database::query(
                    "SELECT owner_id FROM establishments WHERE id = ?", [$sub['establishment_id']]
                )->fetchColumn();
                if ($ownerId) EstablishmentFreezeService::recompute($ownerId);

                Response::success(['status' => 'active', 'plan' => $sub['plan'], 'expires_at' => $expiresAt]);
            } elseif ($internalStatus === 'failed' && $sub['status'] !== 'failed') {
                // Persiste l'échec constaté auprès de Genius Pay — sans ça, un abonnement
                // resterait "pending" indéfiniment côté DB même si le paiement a bel et
                // bien échoué (redirection vers error_url, ou retour de l'utilisateur
                // après avoir quitté l'app en cours de paiement).
                Database::query("UPDATE subscriptions SET status = 'failed' WHERE id = ?", [$sub['id']]);
            }

            Response::success(['status' => $internalStatus, 'gp_status' => $result['status']]);
        } catch (\Exception $e) {
            Response::error('Impossible de vérifier : ' . $e->getMessage());
        }
    }
}
