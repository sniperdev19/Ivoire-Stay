<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate};
use Models\Establishment;
use Services\{GeniusPayService, MailService};

class SubscriptionController
{
    // ─── GET /api/subscriptions/plans ────────────────────────────────────────
    public function plans(Request $req, array $params = []): void
    {
        Response::success(require BASE_PATH . '/config/plans.php');
    }

    // ─── GET /api/subscriptions/status ───────────────────────────────────────
    public function status(Request $req, array $params = []): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find($user['establishment_id'] ?? 0);
        if (!$estab) Response::notFound('Établissement introuvable');

        $plan       = PlanGate::getPlan($estab);
        $expiresAt  = $estab['plan_expires_at'] ?? null;
        $isExpired  = $expiresAt && strtotime($expiresAt) < time();

        $last = Database::query(
            "SELECT * FROM subscriptions WHERE establishment_id = ? ORDER BY created_at DESC LIMIT 1",
            [$estab['id']]
        )->fetch();

        Response::success([
            'plan'           => $plan,
            'plan_label'     => (require BASE_PATH . '/config/plans.php')[$plan]['name'] ?? $plan,
            'expires_at'     => $expiresAt,
            'is_expired'     => $isExpired,
            'last_sub'       => $last ?: null,
            'max_rooms'      => PlanGate::maxRooms($estab),
        ]);
    }

    // ─── POST /api/subscriptions/initiate ────────────────────────────────────
    public function initiate(Request $req, array $params = []): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find($user['establishment_id'] ?? 0);
        if (!$estab) Response::notFound('Établissement introuvable');

        $data    = $req->all();
        $plan    = $data['plan']    ?? '';
        $billing = $data['billing'] ?? 'monthly';

        $plans = require BASE_PATH . '/config/plans.php';
        if (!isset($plans[$plan]) || $plan === 'starter') {
            Response::error('Plan invalide');
        }

        $amount = $plans[$plan]['prices'][$billing] ?? 0;
        if ($amount <= 0) Response::error('Montant invalide');

        $reference = 'SYNC-' . $estab['id'] . '-' . strtoupper($plan) . '-' . time();

        // Save pending subscription
        Database::query(
            "INSERT INTO subscriptions (establishment_id, plan, billing, amount, gp_reference, status)
             VALUES (?, ?, ?, ?, ?, 'pending')",
            [$estab['id'], $plan, $billing, $amount, $reference]
        );

        try {
            $result = GeniusPayService::initiate([
                'amount'         => $amount,
                'description'    => 'Abonnement SYNC ' . $plans[$plan]['name'],
                'reference'      => $reference,
                'pay_method'     => $data['pay_method'] ?? '',
                'success_url'    => APP_URL . '/saas/settings?sub=ok&plan=' . $plan . '&ref=' . $reference,
                'error_url'      => APP_URL . '/saas/checkout?plan=' . $plan . '&error=1',
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

            Database::query(
                "UPDATE establishments SET plan = ?, plan_expires_at = ? WHERE id = ?",
                [$sub['plan'], $expiresAt, $sub['establishment_id']]
            );

            // Email de confirmation d'abonnement
            $owner = Database::query(
                "SELECT u.name, u.email FROM users u
                 JOIN establishments e ON e.owner_id = u.id
                 WHERE e.id = ?",
                [$sub['establishment_id']]
            )->fetch();
            if ($owner) {
                MailService::subscriptionActivated($owner['email'], $owner['name'], $sub['plan'], $expiresAt);
            }
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
                Database::query(
                    "UPDATE establishments SET plan = ?, plan_expires_at = ? WHERE id = ?",
                    [$sub['plan'], $expiresAt, $sub['establishment_id']]
                );

                // Email activation (si pas encore envoyé via webhook)
                $owner = Database::query(
                    "SELECT u.name, u.email FROM users u
                     JOIN establishments e ON e.owner_id = u.id
                     WHERE e.id = ?",
                    [$sub['establishment_id']]
                )->fetch();
                if ($owner) {
                    MailService::subscriptionActivated($owner['email'], $owner['name'], $sub['plan'], $expiresAt);
                }

                Response::success(['status' => 'active', 'plan' => $sub['plan'], 'expires_at' => $expiresAt]);
            }

            Response::success(['status' => $internalStatus, 'gp_status' => $result['status']]);
        } catch (\Exception $e) {
            Response::error('Impossible de vérifier : ' . $e->getMessage());
        }
    }
}
