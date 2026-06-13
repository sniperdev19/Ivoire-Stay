<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate};
use Models\Establishment;
use Services\GeniusPayService;

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
                'callback_url'   => APP_URL . '/api/subscriptions/callback',
                'return_url'     => APP_URL . '/saas/settings?sub=ok&plan=' . $plan,
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
    public function callback(Request $req, array $params = []): void
    {
        $rawBody  = file_get_contents('php://input');
        $sig      = $_SERVER['HTTP_X_GENIUSPAY_SIGNATURE'] ?? '';

        if (!GeniusPayService::validateWebhook($rawBody, $sig)) {
            http_response_code(403);
            exit('Invalid signature');
        }

        $data      = json_decode($rawBody, true) ?? $req->all();
        $reference = $data['reference'] ?? $data['merchant_reference'] ?? '';
        $gpStatus  = $data['status']    ?? '';

        if (!$reference) { http_response_code(400); exit('Missing reference'); }

        $sub = Database::query(
            "SELECT * FROM subscriptions WHERE gp_reference = ?", [$reference]
        )->fetch();

        if (!$sub) { http_response_code(404); exit('Subscription not found'); }

        $internalStatus = GeniusPayService::mapStatus($gpStatus);

        if ($internalStatus === 'active') {
            $plans    = require BASE_PATH . '/config/plans.php';
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
    public function verify(Request $req, array $params = []): void
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
            $result         = GeniusPayService::verify($reference);
            $internalStatus = GeniusPayService::mapStatus($result['status']);

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

                // Refresh cached estab
                $estab = Establishment::find($sub['establishment_id']);
                Response::success(['status' => 'active', 'plan' => $sub['plan'], 'expires_at' => $expiresAt]);
            }

            Response::success(['status' => $internalStatus, 'gp_status' => $result['status']]);
        } catch (\Exception $e) {
            Response::error('Impossible de vérifier : ' . $e->getMessage());
        }
    }
}
