<?php

/**
 * Primes agents commerciaux — en plus des versements par palier de
 * config/plans.php (`agent_reward_tiers`, gérés par CommissionService).
 * Chaque prime est décernée au plus une fois par "portée" (cf. le commentaire
 * de agent_bonus_awards dans scripts/migration_agent_bonuses.sql) et versée
 * via le même circuit agent_payouts (validation superadmin sur /admin/agents).
 */
return [
    // Le premier agent, tous plans confondus, à atteindre CE nombre de
    // premiers-abonnements payants gagne la prime — une seule fois, jamais
    // réattribuée. "target" est volontairement distinct de CommissionService::
    // BATCH_SIZE (les paliers par plan) : ici on compte le cumul pro+business.
    'first_to_5' => [
        'target' => 5,
        'amount' => 20000,
    ],

    // Premier établissement en plan Business qu'un agent fait signer (une
    // fois par agent) — le plan le plus rentable pour la plateforme.
    'first_business' => [
        'amount' => 15000,
    ],

    // Agent avec le plus de premiers-abonnements payants sur le mois
    // calendaire précédent (évalué automatiquement, cf. SchedulerService::
    // runAgentMonthlyBonus). Ex æquo : tous les agents à égalité la touchent.
    'monthly_top' => [
        'amount' => 15000,
    ],

    // Bonus si l'établissement référé passe payant dans les N jours suivant
    // le scan du QR code (cf. agent_establishments.linked_at) — récompense la
    // qualité du démarchage, pas seulement le volume de scans.
    'fast_conversion' => [
        'days'   => 7,
        'amount' => 7000,
    ],
];
