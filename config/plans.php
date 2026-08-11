<?php

return [
    'starter' => [
        'name'               => 'Starter',
        'max_rooms'          => PHP_INT_MAX,
        'max_establishments' => 1,
        'prices'             => ['monthly' => 0, 'yearly' => 0],
        // Pas d'abonnement, mais le paiement en ligne des réservations est
        // imposé (non désactivable par l'établissement) en contrepartie —
        // voir 'online_payment_commission_pct' et Core\PlanGate::commissionPct().
        // Taux calé sur les frais RÉELS de GeniusPay (Services\GeniusPayService::
        // paymentFee()/withdrawalFee()) + une marge plateforme visée de 1.5% :
        // GeniusPay prend 2.5% à l'encaissement (dont 1.5% absorbé par la plateforme,
        // 1% répercuté au client) + 1% au retrait (absorbé par la plateforme) + 1.5%
        // de marge plateforme = 5% de commission totale, dont 1% côté client.
        // - 'online_payment_client_share_pct' est ajoutée au prix affiché/facturé au
        //   client (Services\PlanPricingService::applyClientMarkup(), appliquée partout
        //   où le prix est montré/calculé, quel que soit le mode de paiement finalement
        //   choisi — un seul prix, pas de ligne de frais séparée).
        // - Le reste (commission_pct - client_share_pct = 4%) est absorbé par
        //   l'établissement, prélevé sur le montant collecté au moment du paiement en
        //   ligne (payments.commission_amount = montant collecté × commission_pct, PAS
        //   recalculé sur le prix hors majoration — approximation volontaire, simple et
        //   robuste plutôt qu'une reconstitution exacte du prix de base). Le forfait fixe
        //   de 100 XOF de GeniusPay n'est pas dans ce %, seulement tracé (informatif,
        //   payments.geniuspay_fee_amount) — sur une petite réservation, il peut manger
        //   une partie de la marge de 1.5% visée (voir AdminController::platformMargin()).
        'online_payment_commission_pct'    => 5,
        'online_payment_client_share_pct'  => 1,
        'features' => [
            'invoices'  => true,
            'payments'  => true,
            'expenses'  => true,
            'reports'   => true,
            'pdf'       => true,
            'boost'     => false,
            'multi_estab' => false,
            // Accès de base au paiement en ligne (tous les plans l'ont) —
            // distinct de 'online_payment_control' (capacité à activer/désactiver
            // le toggle soi-même, réservée à Pro/Business : sur Starter c'est
            // toujours actif, non négociable).
            'online_payment'         => true,
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
        // Abonnement déjà payé : aucune commission plateforme sur le paiement en ligne.
        'online_payment_commission_pct'    => 0,
        'online_payment_client_share_pct'  => 0,
        'features' => [
            'invoices'  => true,
            'payments'  => true,
            'expenses'  => true,
            'reports'   => true,
            'pdf'       => true,
            'boost'     => false,
            'multi_estab' => false,
            'online_payment'         => true,
            'online_payment_control' => true,
        ],
    ],
    'business' => [
        'name'               => 'Business',
        'max_rooms'          => PHP_INT_MAX,
        'max_establishments' => PHP_INT_MAX,
        'prices'             => ['monthly' => 20000, 'yearly' => 192000],
        'agent_reward_per_5' => 30000,
        'online_payment_commission_pct'    => 0,
        'online_payment_client_share_pct'  => 0,
        'features' => [
            'invoices'  => true,
            'payments'  => true,
            'expenses'  => true,
            'reports'   => true,
            'pdf'       => true,
            'boost'     => true,
            'multi_estab' => true,
            'online_payment'         => true,
            'online_payment_control' => true,
        ],
    ],
];
