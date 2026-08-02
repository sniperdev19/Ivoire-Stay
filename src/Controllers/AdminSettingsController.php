<?php

namespace Controllers;

use Core\{Request, Response, Settings};
use Services\PlanPricingService;

/**
 * Réglages plateforme (superadmin uniquement) — /admin/settings, section
 * "Réglages plateforme". Chaque valeur retourne l'effectif courant (réglage
 * enregistré en base, sinon la valeur par défaut des fichiers config/*.php)
 * pour que le formulaire admin affiche toujours ce qui s'applique réellement.
 */
class AdminSettingsController
{
    private const AMOUNT_KEYS = [
        'agent_reward_pro', 'agent_reward_business',
        'bonus_first_to_5_amount', 'bonus_first_business_amount',
        'bonus_fast_conversion_amount', 'bonus_monthly_top_amount',
    ];

    /** Prix des plans payants : jamais 0 (contrairement aux primes, qui peuvent l'être pour se désactiver). */
    private const PRICE_KEYS = [
        'plan_price_pro_monthly', 'plan_price_pro_yearly',
        'plan_price_business_monthly', 'plan_price_business_yearly',
    ];

    private const INT_KEYS = ['bonus_first_to_5_target', 'bonus_fast_conversion_days'];

    public function index(Request $req, array $params = []): void
    {
        $plans   = require BASE_PATH . '/config/plans.php';
        $bonuses = require BASE_PATH . '/config/agent_bonuses.php';

        Response::success([
            'agents_enabled' => AGENTS_ENABLED,
            'contact_email'  => Settings::get('contact_email', 'afristay24@gmail.com'),
            'contact_phone'  => Settings::get('contact_phone', '+225 01 61 95 90 80'),

            'plan_price_pro_monthly'      => PlanPricingService::price('pro', 'monthly'),
            'plan_price_pro_yearly'       => PlanPricingService::price('pro', 'yearly'),
            'plan_price_business_monthly' => PlanPricingService::price('business', 'monthly'),
            'plan_price_business_yearly'  => PlanPricingService::price('business', 'yearly'),

            'agent_reward_pro'      => Settings::getFloat('agent_reward_pro', (float) ($plans['pro']['agent_reward_per_5'] ?? 0)),
            'agent_reward_business' => Settings::getFloat('agent_reward_business', (float) ($plans['business']['agent_reward_per_5'] ?? 0)),

            'bonus_first_to_5_amount'      => Settings::getFloat('bonus_first_to_5_amount', (float) $bonuses['first_to_5']['amount']),
            'bonus_first_to_5_target'      => Settings::getInt('bonus_first_to_5_target', (int) $bonuses['first_to_5']['target']),
            'bonus_first_business_amount'  => Settings::getFloat('bonus_first_business_amount', (float) $bonuses['first_business']['amount']),
            'bonus_fast_conversion_amount' => Settings::getFloat('bonus_fast_conversion_amount', (float) $bonuses['fast_conversion']['amount']),
            'bonus_fast_conversion_days'   => Settings::getInt('bonus_fast_conversion_days', (int) $bonuses['fast_conversion']['days']),
            'bonus_monthly_top_amount'     => Settings::getFloat('bonus_monthly_top_amount', (float) $bonuses['monthly_top']['amount']),
        ]);
    }

    public function update(Request $req, array $params = []): void
    {
        $data = $req->all();

        if (array_key_exists('agents_enabled', $data)) {
            Settings::set('agents_enabled', $data['agents_enabled'] ? '1' : '0');
        }

        if (isset($data['contact_email'])) {
            $email = trim((string) $data['contact_email']);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::error('Email de contact invalide');
            }
            Settings::set('contact_email', $email);
        }

        if (isset($data['contact_phone'])) {
            Settings::set('contact_phone', trim((string) $data['contact_phone']));
        }

        foreach (self::AMOUNT_KEYS as $key) {
            if (!isset($data[$key])) continue;
            if (!is_numeric($data[$key]) || (float) $data[$key] < 0) {
                Response::error("Montant invalide : {$key}");
            }
            Settings::set($key, (string) (float) $data[$key]);
        }

        foreach (self::PRICE_KEYS as $key) {
            if (!isset($data[$key])) continue;
            if (!is_numeric($data[$key]) || (float) $data[$key] <= 0) {
                Response::error("Prix invalide : {$key}");
            }
            Settings::set($key, (string) (float) $data[$key]);
        }

        foreach (self::INT_KEYS as $key) {
            if (!isset($data[$key])) continue;
            if (!is_numeric($data[$key]) || (int) $data[$key] < 1) {
                Response::error("Valeur invalide : {$key}");
            }
            Settings::set($key, (string) (int) $data[$key]);
        }

        Response::success(null, 'Réglages mis à jour');
    }
}
