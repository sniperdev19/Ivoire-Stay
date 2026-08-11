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

    /** Taux de commission TOTAL (%) prélevé par la plateforme sur les paiements en ligne d'un plan. */
    public static function commissionPct(string $plan): float
    {
        $default = (float) (self::plans()[$plan]['online_payment_commission_pct'] ?? 0);
        return Settings::getFloat("plan_commission_{$plan}_pct", $default);
    }

    /**
     * Part de la commission (%) répercutée sur le CLIENT — ajoutée au prix affiché/
     * facturé (voir applyClientMarkup()). Le reste (commissionPct - clientSharePct)
     * est absorbé par l'établissement, prélevé sur le montant collecté au paiement.
     */
    public static function clientSharePct(string $plan): float
    {
        $default = (float) (self::plans()[$plan]['online_payment_client_share_pct'] ?? 0);
        return Settings::getFloat("plan_commission_{$plan}_client_pct", $default);
    }

    /** Part de la commission (%) absorbée par l'établissement (commissionPct - clientSharePct). */
    public static function establishmentSharePct(string $plan): float
    {
        return max(0, self::commissionPct($plan) - self::clientSharePct($plan));
    }

    /**
     * Applique la majoration client à un montant (prix chambre, total réservation…).
     * Un seul prix affiché/facturé du début à la fin (recherche, fiche chambre,
     * réservation, facture) quel que soit le mode de paiement finalement choisi —
     * pas de ligne de frais séparée. Sans effet (facteur ×1) si clientSharePct = 0.
     */
    public static function applyClientMarkup(float $amount, string $plan): float
    {
        $pct = self::clientSharePct($plan);
        return $pct > 0 ? round($amount * (1 + $pct / 100), 2) : $amount;
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
        foreach ($plans as $plan => $config) {
            $plans[$plan]['online_payment_commission_pct']   = self::commissionPct($plan);
            $plans[$plan]['online_payment_client_share_pct'] = self::clientSharePct($plan);
        }
        return $plans;
    }
}
