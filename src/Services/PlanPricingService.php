<?php

namespace Services;

use Core\Settings;

/**
 * Prix effectifs des plans payants (Pro/Business), éditables depuis
 * /admin/settings (Core\Settings) — config/plans.php reste la valeur par
 * défaut/de secours tant qu'aucun réglage n'a été enregistré en base. Point
 * d'accès UNIQUE : tout ce qui facture ou affiche un prix (SubscriptionController,
 * AdminController, vitrine/pricing.php, saas/checkout.php, saas/docs.php) passe
 * par ici plutôt que de relire config/plans.php directement, pour ne jamais
 * afficher/facturer un montant différent de ce que l'admin a configuré.
 */
class PlanPricingService
{
    private const BILLABLE_PLANS = ['pro', 'business'];
    private const PERIODS        = ['monthly', 'yearly'];

    private static function plans(): array
    {
        static $plans = null;
        return $plans ??= require BASE_PATH . '/config/plans.php';
    }

    public static function price(string $plan, string $billing): float
    {
        if (!in_array($plan, self::BILLABLE_PLANS, true)) {
            return (float) (self::plans()[$plan]['prices'][$billing] ?? 0);
        }
        $default = (float) (self::plans()[$plan]['prices'][$billing] ?? 0);
        return Settings::getFloat("plan_price_{$plan}_{$billing}", $default);
    }

    /**
     * Tableau complet des plans (config/plans.php), prix Pro/Business remplacés
     * par leur valeur effective — utilisé par GET /api/subscriptions/plans, la
     * seule source que les pages SaaS/vitrine sont censées interroger.
     */
    public static function effectivePlans(): array
    {
        $plans = self::plans();
        foreach (self::BILLABLE_PLANS as $plan) {
            if (!isset($plans[$plan])) continue;
            foreach (self::PERIODS as $billing) {
                $plans[$plan]['prices'][$billing] = self::price($plan, $billing);
            }
        }
        return $plans;
    }
}
