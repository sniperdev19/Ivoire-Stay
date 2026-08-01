<?php

namespace Services;

use Core\Settings;
use Models\{AgentEstablishment, AgentReferral, AgentPayout, AgentBonusAward, Agent};

/**
 * Récompenses des agents commerciaux référents :
 * 1. Forfait FIXE par plan (agent_reward_per_5 dans config/plans.php) versé
 *    tous les 5 premiers-abonnements d'un même plan (compteurs séparés Pro/Business).
 * 2. Quatre primes ponctuelles (config/agent_bonuses.php), décernées au plus
 *    une fois chacune par "portée" (cf. agent_bonus_awards) :
 *    - "premier arrivé"    : le premier agent, tous plans confondus, à 5 premiers-abonnements
 *    - "premier Business"  : le tout premier établissement Business qu'un agent fait signer
 *    - "conversion rapide" : établissement passé payant peu après le scan du QR
 *    - "top du mois"       : cf. SchedulerService::runAgentMonthlyBonus (job mensuel, pas ici)
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

    private static function bonuses(): array
    {
        static $bonuses = null;
        return $bonuses ??= require BASE_PATH . '/config/agent_bonuses.php';
    }

    /**
     * Valeurs EFFECTIVES (réglage /admin/settings s'il existe, sinon la valeur
     * des fichiers config/*.php) — réutilisées par AgentController::me() pour
     * ne jamais dupliquer cette résolution ailleurs.
     */
    public static function rewardForPlan(string $plan): float
    {
        return Settings::getFloat('agent_reward_' . $plan, (float) (self::plans()[$plan]['agent_reward_per_5'] ?? 0));
    }

    public static function firstTo5Config(): array
    {
        $cfg = self::bonuses()['first_to_5'];
        return [
            'amount' => Settings::getFloat('bonus_first_to_5_amount', (float) $cfg['amount']),
            'target' => Settings::getInt('bonus_first_to_5_target', (int) $cfg['target']),
        ];
    }

    public static function firstBusinessAmount(): float
    {
        return Settings::getFloat('bonus_first_business_amount', (float) self::bonuses()['first_business']['amount']);
    }

    public static function fastConversionConfig(): array
    {
        $cfg = self::bonuses()['fast_conversion'];
        return [
            'amount' => Settings::getFloat('bonus_fast_conversion_amount', (float) $cfg['amount']),
            'days'   => Settings::getInt('bonus_fast_conversion_days', (int) $cfg['days']),
        ];
    }

    public static function monthlyTopAmount(): float
    {
        return Settings::getFloat('bonus_monthly_top_amount', (float) self::bonuses()['monthly_top']['amount']);
    }

    /**
     * Deux points d'appel :
     * 1. Juste après tout `UPDATE establishments SET plan = ...` vers
     *    pro/business, uniquement quand le plan précédent était 'starter'
     *    (premier abonnement payant de l'établissement) — voir
     *    SubscriptionController.
     * 2. Juste après `AgentEstablishment::create()` dans
     *    AgentController::scanQr(), avec le plan courant de l'établissement.
     * Idempotent dans les deux cas : `AgentReferral::findByEstablishment()`
     * empêche tout double comptage (scan puis renouvellement, ou double appel).
     *
     * Effet secondaire pur : un souci ici ne doit JAMAIS faire échouer la
     * confirmation du paiement elle-même — même principe que SchedulerService
     * dans Core\Middleware. Toute exception est avalée et journalisée.
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

            self::maybeCreateBatchPayout($agentId, $plan);
            self::maybeAwardFirstTo5($agentId);
            self::maybeAwardFirstBusiness($agentId, $plan);
            self::maybeAwardFastConversion($agentId, $establishmentId, (string) $link['linked_at']);
        } catch (\Throwable $e) {
            error_log('[CommissionService] ' . $e->getMessage());
        }
    }

    /** Forfait fixe tous les 5 premiers-abonnements d'un même plan. */
    private static function maybeCreateBatchPayout(int $agentId, string $plan): void
    {
        if (AgentReferral::countPending($agentId, $plan) < self::BATCH_SIZE) return;

        $ids = AgentReferral::oldestPendingIds($agentId, $plan, self::BATCH_SIZE);
        if (count($ids) < self::BATCH_SIZE) return;

        $agent  = Agent::find($agentId);
        $amount = self::rewardForPlan($plan);
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

    /** Prime "premier arrivé" : le premier agent, tous plans confondus, à atteindre le seuil. */
    private static function maybeAwardFirstTo5(int $agentId): void
    {
        $cfg = self::firstTo5Config();
        if ($cfg['amount'] <= 0) return;
        if (AgentBonusAward::existsForType('first_to_5')) return; // déjà remportée, la course est close

        if (AgentReferral::countTotal($agentId) < $cfg['target']) return;

        $agent = Agent::find($agentId);
        if (!$agent) return;

        // scope_key = 'global' constant : le premier INSERT à passer gagne la
        // course, même si deux agents franchissent le seuil quasi simultanément.
        $awardId = AgentBonusAward::tryAward($agentId, 'first_to_5', 'global', $cfg['amount']);
        if (!$awardId) return;

        $payoutId = AgentPayout::create([
            'agent_id'              => $agentId,
            'plan'                  => null,
            'label'                 => 'Prime : premier agent à ' . $cfg['target'] . ' établissements',
            'amount'                => $cfg['amount'],
            'mobile_money_operator' => $agent['operateur_money'],
            'mobile_money_number'   => $agent['numero'],
            'status'                => 'pending',
        ]);
        AgentBonusAward::assignPayout($awardId, $payoutId);

        NotificationService::agentPayoutReady($agent['nom'], 'Prime "premier arrivé"', $cfg['amount'], $payoutId);
    }

    /** Prime "premier client Business" : une fois par agent. */
    private static function maybeAwardFirstBusiness(int $agentId, string $plan): void
    {
        if ($plan !== 'business') return;

        $amount = self::firstBusinessAmount();
        if ($amount <= 0) return;

        $agent = Agent::find($agentId);
        if (!$agent) return;

        $awardId = AgentBonusAward::tryAward($agentId, 'first_business', (string) $agentId, $amount);
        if (!$awardId) return; // déjà touchée par cet agent

        $payoutId = AgentPayout::create([
            'agent_id'              => $agentId,
            'plan'                  => 'business',
            'label'                 => 'Prime : premier client Business',
            'amount'                => $amount,
            'mobile_money_operator' => $agent['operateur_money'],
            'mobile_money_number'   => $agent['numero'],
            'status'                => 'pending',
        ]);
        AgentBonusAward::assignPayout($awardId, $payoutId);

        NotificationService::agentPayoutReady($agent['nom'], 'Prime "premier client Business"', $amount, $payoutId);
    }

    /** Prime "conversion rapide" : établissement passé payant dans les N jours suivant le scan. */
    private static function maybeAwardFastConversion(int $agentId, int $establishmentId, string $linkedAt): void
    {
        $cfg = self::fastConversionConfig();
        if ($cfg['amount'] <= 0) return;

        $daysSinceScan = (time() - strtotime($linkedAt)) / 86400;
        if ($daysSinceScan > $cfg['days']) return;

        $agent = Agent::find($agentId);
        if (!$agent) return;

        $awardId = AgentBonusAward::tryAward($agentId, 'fast_conversion', (string) $establishmentId, $cfg['amount']);
        if (!$awardId) return;

        $payoutId = AgentPayout::create([
            'agent_id'              => $agentId,
            'plan'                  => null,
            'label'                 => "Prime : conversion rapide (≤ {$cfg['days']} j)",
            'amount'                => $cfg['amount'],
            'mobile_money_operator' => $agent['operateur_money'],
            'mobile_money_number'   => $agent['numero'],
            'status'                => 'pending',
        ]);
        AgentBonusAward::assignPayout($awardId, $payoutId);

        NotificationService::agentPayoutReady($agent['nom'], 'Prime "conversion rapide"', $cfg['amount'], $payoutId);
    }
}
