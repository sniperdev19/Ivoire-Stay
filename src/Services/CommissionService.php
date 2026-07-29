<?php

namespace Services;

use Models\{AgentEstablishment, AgentReferral, AgentPayout, Agent};

/**
 * Récompense des agents commerciaux référents. PAS de commission par
 * établissement : un forfait (agent_reward_per_5 dans config/plans.php) est
 * versé chaque fois que l'agent totalise 5 premiers-abonnements d'un même
 * plan (compteurs séparés Pro / Business) — cf. plan "Espace Agent commercial".
 */
class CommissionService
{
    /** Public : réutilisée par AgentController::me() pour l'objectif affiché côté dashboard. */
    public const BATCH_SIZE = 5;

    private static function plans(): array
    {
        static $plans = null;
        return $plans ??= require BASE_PATH . '/config/plans.php';
    }

    /**
     * Deux points d'appel :
     * 1. Juste après tout `UPDATE establishments SET plan = ...` vers
     *    pro/business, uniquement quand le plan précédent était 'starter'
     *    (premier abonnement payant de l'établissement) — voir
     *    SubscriptionController.
     * 2. Juste après `AgentEstablishment::create()` dans
     *    AgentController::scanQr(), avec le plan courant de l'établissement :
     *    couvre le cas où l'établissement est scanné alors qu'il est déjà sur
     *    un plan payant (crédite l'agent immédiatement plutôt que d'exiger
     *    qu'un upgrade ait lieu après le rattachement).
     * Idempotent dans les deux cas : `AgentReferral::findByEstablishment()`
     * empêche tout double comptage (scan puis renouvellement, ou double appel).
     *
     * Effet secondaire pur : un souci ici (ex. migration_agents.sql pas
     * encore appliquée en base) ne doit JAMAIS faire échouer la confirmation
     * du paiement elle-même — même principe que SchedulerService dans
     * Core\Middleware. Toute exception est avalée et journalisée.
     */
    public static function recordFirstSubscription(int $establishmentId, string $plan): void
    {
        try {
            if (!AGENTS_ENABLED) return;
            if (!in_array($plan, ['pro', 'business'], true)) return;

            $link = AgentEstablishment::findByEstablishment($establishmentId);
            if (!$link) return; // établissement auto-inscrit, aucun agent à créditer

            if (AgentReferral::findByEstablishment($establishmentId)) return; // déjà compté (sécurité)

            $agentId = (int) $link['agent_id'];
            AgentReferral::create([
                'agent_id'         => $agentId,
                'establishment_id' => $establishmentId,
                'plan'             => $plan,
            ]);

            self::maybeCreatePayout($agentId, $plan);
        } catch (\Throwable $e) {
            error_log('[CommissionService] ' . $e->getMessage());
        }
    }

    private static function maybeCreatePayout(int $agentId, string $plan): void
    {
        if (AgentReferral::countPending($agentId, $plan) < self::BATCH_SIZE) return;

        $ids = AgentReferral::oldestPendingIds($agentId, $plan, self::BATCH_SIZE);
        if (count($ids) < self::BATCH_SIZE) return;

        $agent  = Agent::find($agentId);
        $amount = (float) (self::plans()[$plan]['agent_reward_per_5'] ?? 0);
        if (!$agent || $amount <= 0) return;

        $payoutId = AgentPayout::create([
            'agent_id'              => $agentId,
            'plan'                  => $plan,
            'amount'                => $amount,
            'mobile_money_operator' => $agent['operateur_money'],
            'mobile_money_number'   => $agent['numero'],
            'status'                => 'pending',
        ]);

        AgentReferral::assignPayout($ids, $payoutId);

        $planLabel = self::plans()[$plan]['name'] ?? $plan;
        NotificationService::agentPayoutReady($agent['nom'], $planLabel, $amount, $payoutId);
    }
}
