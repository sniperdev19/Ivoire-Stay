<?php

namespace Core;

class PlanGate
{
    private static array $cache = [];

    private static function plans(): array
    {
        if (!self::$cache) {
            self::$cache = require BASE_PATH . '/config/plans.php';
        }
        return self::$cache;
    }

    public static function getPlan(array $estab): string
    {
        $plan = $estab['plan'] ?? 'starter';
        if ($plan === 'starter') return 'starter';

        if (!empty($estab['plan_expires_at']) && strtotime($estab['plan_expires_at']) < time()) {
            return 'starter';
        }

        return $plan;
    }

    public static function can(array $estab, string $feature): bool
    {
        $plan = self::getPlan($estab);
        return self::plans()[$plan]['features'][$feature] ?? false;
    }

    public static function require(array $estab, string $feature): void
    {
        if (!self::can($estab, $feature)) {
            $minPlan = self::minPlanFor($feature);
            Response::json([
                'success'          => false,
                'upgrade_required' => true,
                'feature'          => $feature,
                'min_plan'         => $minPlan,
                'message'          => "Cette fonctionnalité est réservée au plan $minPlan. Mettez à niveau votre abonnement.",
            ], 403);
            exit;
        }
    }

    /** Taux de commission TOTAL plateforme (%) sur les paiements en ligne de cet établissement, selon son plan effectif. */
    public static function commissionPct(array $estab): float
    {
        $plan = self::getPlan($estab);
        return \Services\PlanPricingService::commissionPct($plan);
    }

    /** Part de la commission (%) répercutée sur le client — voir PlanPricingService::applyClientMarkup(). */
    public static function clientSharePct(array $estab): float
    {
        $plan = self::getPlan($estab);
        return \Services\PlanPricingService::clientSharePct($plan);
    }

    /** Montant fixe (XOF) de commission, jamais répercuté au client — voir PlanPricingService::commissionFixedAmount(). */
    public static function commissionFixedAmount(array $estab): float
    {
        $plan = self::getPlan($estab);
        return \Services\PlanPricingService::commissionFixedAmount($plan);
    }

    /** Applique la majoration client au montant, selon le plan effectif de cet établissement. */
    public static function applyClientMarkup(float $amount, array $estab): float
    {
        $plan = self::getPlan($estab);
        return \Services\PlanPricingService::applyClientMarkup($amount, $plan);
    }

    public static function canAddRoom(array $estab, int $current): bool
    {
        $plan = self::getPlan($estab);
        $max  = self::plans()[$plan]['max_rooms'] ?? 10;
        return $current < $max;
    }

    public static function maxRooms(array $estab): int
    {
        $plan = self::getPlan($estab);
        $max  = self::plans()[$plan]['max_rooms'] ?? 10;
        return $max === PHP_INT_MAX ? -1 : $max;
    }

    public static function maxEstablishments(array $estab): int
    {
        $plan = self::getPlan($estab);
        $max  = self::plans()[$plan]['max_establishments'] ?? 1;
        return $max === PHP_INT_MAX ? -1 : $max;
    }

    /**
     * Un propriétaire peut créer un nouvel établissement tant qu'au moins un de ses
     * établissements existants autorise, par son plan, le nombre total visé.
     * Sans établissement existant (premier établissement), toujours autorisé.
     */
    public static function canAddEstablishment(array $ownerEstablishments, int $currentCount): bool
    {
        if ($currentCount === 0) return true;

        $maxAllowed = 1;
        foreach ($ownerEstablishments as $estab) {
            $max = self::maxEstablishments($estab);
            if ($max === -1) return true;
            $maxAllowed = max($maxAllowed, $max);
        }

        return $currentCount < $maxAllowed;
    }

    /**
     * Établissement en excédent par rapport à la limite de son plan effectif
     * (voir Services\EstablishmentFreezeService) — phase A ou B du gel.
     */
    public static function isFrozen(array $estab): bool
    {
        return !empty($estab['frozen_at']);
    }

    /**
     * Phase B : le délai de grâce (fin de la dernière réservation active au
     * moment du gel) est dépassé — plus aucune gestion possible, même des
     * réservations déjà en cours.
     */
    public static function isHardFrozen(array $estab): bool
    {
        return !empty($estab['frozen_at'])
            && !empty($estab['frozen_hard_at'])
            && strtotime($estab['frozen_hard_at']) <= time();
    }

    private static function minPlanFor(string $feature): string
    {
        foreach (self::plans() as $config) {
            if ($config['features'][$feature] ?? false) {
                return $config['name'];
            }
        }
        return 'Pro';
    }
}
