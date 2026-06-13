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

    private static function minPlanFor(string $feature): string
    {
        foreach (self::plans() as $config) {
            if ($config['features'][$feature] ?? false) {
                return $config['name'];
            }
        }
        return 'Premium';
    }
}
