<?php

return [
    'starter' => [
        'name'               => 'Gratuit',
        'max_rooms'          => 10,
        'max_establishments' => 1,
        'prices'             => ['monthly' => 0, 'yearly' => 0],
        'features' => [
            'invoices'  => false,
            'payments'  => false,
            'expenses'  => false,
            'reports'   => false,
            'pdf'       => false,
            'boost'     => false,
            'multi_estab' => false,
            'online_payment_control' => false,
        ],
    ],
    'pro' => [
        'name'               => 'Pro',
        'max_rooms'          => PHP_INT_MAX,
        'max_establishments' => 1,
        'prices'             => ['monthly' => 9000, 'yearly' => 86400],
        // Récompense forfaitaire versée à l'agent commercial référent tous les
        // 5 premiers-abonnements de ce plan (voir CommissionService).
        'agent_reward_per_5' => 15000,
        'features' => [
            'invoices'  => true,
            'payments'  => true,
            'expenses'  => true,
            'reports'   => true,
            'pdf'       => true,
            'boost'     => false,
            'multi_estab' => false,
            'online_payment_control' => true,
        ],
    ],
    'business' => [
        'name'               => 'Business',
        'max_rooms'          => PHP_INT_MAX,
        'max_establishments' => PHP_INT_MAX,
        'prices'             => ['monthly' => 20000, 'yearly' => 192000],
        'agent_reward_per_5' => 30000,
        'features' => [
            'invoices'  => true,
            'payments'  => true,
            'expenses'  => true,
            'reports'   => true,
            'pdf'       => true,
            'boost'     => true,
            'multi_estab' => true,
            'online_payment_control' => true,
        ],
    ],
];
