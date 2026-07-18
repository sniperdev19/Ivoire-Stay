<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php
$plans = require BASE_PATH . '/config/plans.php';

$featureLabels = [
    'invoices'                => 'Comptabilité — Factures',
    'payments'                => 'Comptabilité — Paiements',
    'expenses'                => 'Dépenses',
    'reports'                 => 'Rapports',
    'pdf'                     => 'Export PDF (factures)',
    'boost'                   => 'Mise en avant (boost) dans la recherche',
    'multi_estab'             => 'Plusieurs établissements',
    'online_payment_control'  => 'Paiement en ligne (GeniusPay) + Retraits',
];

// Icônes reprises telles quelles de la sidebar (src/templates/saas/layout.php)
// pour que la doc reste visuellement cohérente avec le menu de l'app.
$modules = [
    [
        'name' => 'Tableau de bord', 'anchor' => 'dashboard', 'plan' => null, 'role' => null,
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'desc' => "Vue de synthèse de l'activité de l'établissement, en lecture seule.",
        'points' => [
            "4 indicateurs : revenus du mois, taux d'occupation, réservations actives, chambres disponibles + bénéfice net",
            'Tableau des réservations récentes, avec accès direct à la liste complète',
            'Graphique de répartition des chambres occupées par type',
            'Résumé financier : revenus, encaissé, en attente, dépenses, bénéfice net',
        ],
        'workflows' => [
            [
                'title' => 'Accéder rapidement à une action',
                'steps' => [
                    'Utilisez le bloc « Actions rapides » en bas de page.',
                    'Cliquez sur « Nouvelle réservation », « Ajouter chambre », « Voir le planning » ou « Rapports » pour être redirigé directement.',
                ],
            ],
        ],
    ],
    [
        'name' => 'Planning', 'anchor' => 'planning', 'plan' => null, 'role' => null,
        'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'desc' => "Calendrier visuel des arrivées, départs et de l'occupation des chambres.",
        'points' => [
            'Deux vues : Semaine (une ligne par chambre, type planning) et Mois (calendrier classique)',
            'Code couleur : Confirmée (vert), Arrivée (bleu), En attente (orange), Annulée (rouge)',
            "Navigation par flèches précédent/suivant et bouton « Aujourd'hui »",
        ],
        'workflows' => [
            [
                'title' => 'Consulter et ouvrir une réservation depuis le planning',
                'steps' => [
                    'Basculez entre « Semaine » et « Mois » selon la vue souhaitée.',
                    'Cliquez sur une barre (vue semaine) ou une puce (vue mois) pour voir le détail : client, chambre, dates, montant.',
                    'Cliquez sur « + Réservation » pour être redirigé vers la page Réservations et en créer une nouvelle.',
                ],
            ],
        ],
    ],
    [
        'name' => 'Réservations', 'anchor' => 'bookings', 'plan' => null, 'role' => null,
        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'desc' => 'Suivi et création des réservations de chambres.',
        'points' => [
            'Onglets de statut : Toutes, En attente, Confirmée, Arrivée, Départ, Annulée',
            "Recherche par client/chambre/téléphone + filtre par date d'arrivée",
            'Tableau : client, chambre/catégorie, type de séjour, arrivée, départ/durée, montant, statut',
        ],
        'workflows' => [
            [
                'title' => 'Créer une réservation',
                'steps' => [
                    'Cliquez sur « + Nouvelle réservation ».',
                    'Choisissez la chambre et le type de séjour (nuit, week-end, ou passage à l\'heure).',
                    "Renseignez les dates (ou la durée pour un passage) — la date de départ se pré-remplit automatiquement selon le type de séjour.",
                    "Recherchez un client déjà enregistré (son nom ou son numéro suffit, ses coordonnées se remplissent automatiquement) ou saisissez un nouveau client.",
                    'Indiquez le nombre de voyageurs, la source (manuel, téléphone, en ligne) et des notes si besoin.',
                    'Cochez « Encaisser un paiement à la création » pour prendre l\'acompte ou le montant total immédiatement.',
                    'Cliquez sur « Créer la réservation ».',
                ],
            ],
            [
                'title' => "Faire évoluer une réservation existante",
                'steps' => [
                    'Cliquez sur une ligne du tableau pour ouvrir la fiche détail.',
                    'Utilisez les boutons de statut (Confirmer, Check-in, Check-out, Annuler) selon l\'avancement du séjour.',
                    "Encaissez le solde restant directement depuis cette fiche si la facture n'est pas soldée.",
                    'Dupliquez une réservation pour créer rapidement un nouveau séjour avec les mêmes chambre/client.',
                ],
            ],
        ],
    ],
    [
        'name' => 'Chambres & Tarifs', 'anchor' => 'rooms', 'plan' => null, 'role' => 'owner',
        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'desc' => 'Gestion des chambres physiques et des types de tarifs.',
        'points' => [
            'Sélecteur de vue : Chambres / Types & Tarifs',
            'KPI rapides : total, disponibles, occupées, maintenance/ménage',
            'Chaque carte de chambre affiche son statut (cliquable pour le changer rapidement), son tarif et ses équipements',
            'Sur chaque carte : boutons Modifier, Tarifs (raccourci vers le type associé) et Supprimer',
        ],
        'workflows' => [
            [
                'title' => 'Ajouter une chambre',
                'steps' => [
                    'Cliquez sur « + Nouvelle chambre ».',
                    "Renseignez le numéro/nom et l'étage.",
                    'Choisissez un type existant, ou cliquez sur « + Créer un nouveau type » pour définir capacité, type de lit, tarifs (base/week-end/passage) et équipements en même temps que la chambre.',
                    'Choisissez le statut initial et ajoutez jusqu\'à 3 photos.',
                    'Cliquez sur « Enregistrer la chambre ».',
                ],
            ],
            [
                'title' => "Changer rapidement le statut d'une chambre",
                'steps' => [
                    'Cliquez sur le statut affiché en haut de la carte (Disponible, Occupée, Ménage, Maintenance, Bloquée).',
                    "Choisissez le nouveau statut dans le menu — pas besoin d'ouvrir la fiche complète.",
                    "Une chambre non « Disponible » disparaît automatiquement de la vitrine publique tant qu'elle n'est pas remise à ce statut.",
                ],
            ],
        ],
    ],
    [
        'name' => 'Clients', 'anchor' => 'clients', 'plan' => null, 'role' => null,
        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'desc' => 'Base de données des clients ayant déjà réservé.',
        'points' => [
            'Recherche par nom, email ou téléphone',
            'KPI : total clients, clients avec réservations, revenu total',
            'Fiche client : coordonnées, historique des séjours, total dépensé',
        ],
        'workflows' => [
            [
                'title' => 'Consulter une fiche et réserver pour un client existant',
                'steps' => [
                    'Cliquez sur un client pour ouvrir sa fiche.',
                    'Cliquez sur « Réserver » pour créer une nouvelle réservation avec ses coordonnées déjà pré-remplies.',
                    'Cliquez sur « Modifier » pour corriger ses coordonnées.',
                ],
            ],
        ],
    ],
    [
        'name' => 'Comptabilité', 'anchor' => 'billing', 'plan' => 'pro', 'role' => 'owner',
        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'desc' => "Factures et paiements de l'établissement.",
        'points' => [
            'KPI : total factures, montant TTC, factures payées, encaissé, en attente',
            'Onglet Factures : recherche, filtre par statut (Brouillon/Envoyée/Payée), téléchargement PDF, envoi par email',
            'Onglet Paiements : filtre par méthode (Mobile Money/Espèces/Carte/Virement) et par statut',
            'Une facture est déjà créée automatiquement à chaque réservation — pas besoin de tout saisir à la main',
        ],
        'workflows' => [
            [
                'title' => 'Envoyer ou télécharger une facture',
                'steps' => [
                    'Repérez la facture dans l\'onglet Factures (déjà créée automatiquement pour chaque réservation).',
                    'Cliquez sur « Envoyer », saisissez l\'email du destinataire : le PDF est généré et joint automatiquement.',
                    'Ou cliquez sur « PDF » pour la télécharger directement.',
                ],
            ],
            [
                'title' => 'Enregistrer un paiement',
                'steps' => [
                    'Cliquez sur « + Enregistrer un paiement » et choisissez la facture concernée (total, déjà payé et restant dû sont rappelés).',
                    'Indiquez le montant encaissé, la méthode (Mobile Money, Espèces, Carte, Virement) et le type (complet, acompte, partiel).',
                    'Cliquez sur « Enregistrer ».',
                ],
            ],
        ],
    ],
    [
        'name' => 'Dépenses', 'anchor' => 'expenses', 'plan' => 'pro', 'role' => 'owner',
        'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        'desc' => 'Suivi des charges opérationnelles.',
        'points' => [
            'KPI : total dépenses, dépenses du mois, catégorie principale',
            'Filtre par catégorie',
        ],
        'workflows' => [
            [
                'title' => 'Enregistrer une dépense',
                'steps' => [
                    'Cliquez sur « + Nouvelle dépense ».',
                    'Renseignez le libellé, le montant, la date et la catégorie.',
                    'Ajoutez une note si besoin, puis enregistrez.',
                ],
            ],
        ],
    ],
    [
        'name' => 'Retraits', 'anchor' => 'payouts', 'plan' => 'pro', 'role' => 'owner',
        'icon' => 'M17 9V7a4 4 0 00-8 0v2M5 9h14l1 11H4L5 9z',
        'desc' => "Solde et demandes de retrait des paiements en ligne encaissés par la plateforme pour le compte de l'établissement.",
        'points' => [
            'Trois indicateurs : encaissé en ligne, en attente/déjà retiré, solde disponible',
            'Historique des demandes avec leur statut : en attente, payé, rejeté',
        ],
        'workflows' => [
            [
                'title' => 'Demander un retrait',
                'steps' => [
                    'Cliquez sur « + Demander un retrait » (actif uniquement si un solde est disponible).',
                    'Indiquez le montant (plafonné au solde disponible), l\'opérateur Mobile Money (Orange, Wave, MTN) et le numéro.',
                    'Envoyez la demande — elle est traitée manuellement par l\'équipe AfriStay puis marquée « Payé » une fois le virement effectué.',
                ],
            ],
        ],
    ],
    [
        'name' => 'Rapports', 'anchor' => 'reports', 'plan' => 'pro', 'role' => 'owner',
        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'desc' => "Analyses de performance construites à partir des factures, dépenses et paiements.",
        'points' => [
            'Filtre par mois ou par année, export PDF',
            "5 indicateurs : chiffre d'affaires, dépenses, bénéfice net, encaissé, taux d'occupation",
            'Dépenses par catégorie, bilan de la période, tableau des paiements reçus',
        ],
        'workflows' => [
            [
                'title' => 'Consulter et exporter un rapport',
                'steps' => [
                    'Choisissez la période (mois ou année).',
                    'Consultez le bilan et la répartition des dépenses par catégorie.',
                    'Cliquez sur « Exporter PDF » pour imprimer ou sauvegarder le rapport.',
                ],
            ],
        ],
    ],
    [
        'name' => 'Paramètres', 'anchor' => 'settings', 'plan' => null, 'role' => 'owner',
        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'desc' => "Configuration de l'établissement, de l'équipe, de l'abonnement et du compte — 4 onglets.",
        'points' => [
            "Général : identité, localisation, contact, activation du paiement en ligne (Pro+) et du boost (Business), description, photos",
            "Membres : liste de l'équipe (nom, email, téléphone, rôle), ajout/modification/suppression de réceptionnistes",
            "Abonnement : plan actuel et expiration, comparaison des 3 formules, changement/downgrade/annulation, historique de facturation",
            'Compte : profil, notifications push, sécurité',
        ],
        'workflows' => [
            [
                'title' => 'Ajouter un membre à son équipe',
                'steps' => [
                    'Ouvrez l\'onglet « Membres », cliquez sur « + Ajouter un membre ».',
                    "Renseignez son nom, l'établissement auquel le rattacher (si vous en gérez plusieurs), son email, téléphone et mot de passe.",
                    'Le membre créé a automatiquement les droits « réceptionniste » (voir Rôles & permissions plus bas).',
                ],
            ],
            [
                'title' => "Changer de formule d'abonnement",
                'steps' => [
                    'Ouvrez l\'onglet « Abonnement ».',
                    'Comparez les 3 formules puis cliquez sur « Choisir ce plan », « Rétrograder en Pro » ou « Annuler l\'abonnement » selon le besoin.',
                    "Le paiement de l'abonnement se fait via Mobile Money (GeniusPay), comme un paiement client.",
                ],
            ],
        ],
    ],
];
?>
<?php $pageCss = 'saas-docs'; ?>
<div class="docs-page">

  <div class="docs-header">
    <h1>Documentation</h1>
    <p>Mode d'emploi de chaque page, formules d'abonnement et permissions par rôle.</p>
  </div>

  <div class="docs-layout">

    <!-- Navigation latérale -->
    <nav class="docs-nav">
      <div class="docs-nav-label">Sur cette page</div>
      <a href="#apropos" class="docs-nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Qu'est-ce qu'AfriStay ?
      </a>

      <div class="docs-nav-label">Modules</div>
      <?php foreach ($modules as $m): ?>
        <a href="#<?= $m['anchor'] ?>" class="docs-nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $m['icon'] ?>"/></svg>
          <?= htmlspecialchars($m['name']) ?>
        </a>
      <?php endforeach; ?>

      <div class="docs-nav-label">Référence</div>
      <a href="#plans" class="docs-nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-.34-.02-.675-.058-1.006z"/></svg>
        Formules d'abonnement
      </a>
      <a href="#roles" class="docs-nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Rôles & permissions
      </a>
    </nav>

    <!-- Contenu -->
    <div class="docs-content">

      <section id="apropos" class="docs-module">
        <div class="docs-module-header">
          <div class="docs-module-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="docs-module-title">Qu'est-ce qu'AfriStay ?</div>
        </div>
        <p class="docs-module-desc" style="margin-bottom:0;">
          AfriStay est un outil de gestion (PMS) pour hôtels et résidences : suivi des chambres et des tarifs,
          prise de réservations (sur place ou en ligne via la vitrine publique), encaissement des paiements,
          facturation, et pilotage de l'activité au quotidien. Chaque établissement dispose de son propre espace,
          avec un niveau de fonctionnalités qui dépend de la formule d'abonnement choisie (voir plus bas).
        </p>
      </section>

      <?php foreach ($modules as $m): ?>
        <section id="<?= $m['anchor'] ?>" class="docs-module">
          <div class="docs-module-header">
            <div class="docs-module-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $m['icon'] ?>"/></svg>
            </div>
            <div class="docs-module-title"><?= htmlspecialchars($m['name']) ?></div>
            <div class="docs-badges">
              <?php if ($m['plan']): ?>
                <span class="docs-badge docs-badge-plan">Plan <?= htmlspecialchars(ucfirst($m['plan'])) ?>+</span>
              <?php else: ?>
                <span class="docs-badge docs-badge-allplans">Tous les plans</span>
              <?php endif; ?>
              <?php if ($m['role'] === 'owner'): ?>
                <span class="docs-badge docs-badge-owner">Propriétaire</span>
              <?php else: ?>
                <span class="docs-badge docs-badge-team">Toute l'équipe</span>
              <?php endif; ?>
            </div>
          </div>
          <p class="docs-module-desc"><?= htmlspecialchars($m['desc']) ?></p>

          <div class="docs-module-grid">
            <div>
              <div class="docs-subhead">Ce que vous y trouvez</div>
              <ul class="docs-points">
                <?php foreach ($m['points'] as $point): ?>
                  <li><?= htmlspecialchars($point) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div>
              <?php foreach ($m['workflows'] as $w): ?>
                <div class="docs-workflow">
                  <div class="docs-workflow-title"><?= htmlspecialchars($w['title']) ?></div>
                  <ol class="docs-steps">
                    <?php foreach ($w['steps'] as $step): ?>
                      <li><?= htmlspecialchars($step) ?></li>
                    <?php endforeach; ?>
                  </ol>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
      <?php endforeach; ?>

      <!-- Plans -->
      <section id="plans" class="docs-module">
        <div class="docs-module-header">
          <div class="docs-module-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-.34-.02-.675-.058-1.006z"/></svg>
          </div>
          <div class="docs-module-title">Formules d'abonnement</div>
          <a href="<?= $base_url ?>/tarifs" class="btn-saas-secondary" style="text-decoration:none;">Voir la page tarifs →</a>
        </div>
        <div style="overflow-x:auto;margin-top:18px;">
          <table class="saas-table docs-plans-table" style="width:100%;min-width:640px;">
            <thead>
              <tr>
                <th>Fonctionnalité</th>
                <?php foreach ($plans as $key => $p): ?>
                  <th style="text-align:center;" class="<?= $key === 'pro' ? 'docs-plan-highlight' : '' ?>"><?= htmlspecialchars($p['name']) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="font-weight:600;">Tarif mensuel</td>
                <?php foreach ($plans as $key => $p): ?>
                  <td style="text-align:center;font-weight:700;color:#111827;" class="<?= $key === 'pro' ? 'docs-plan-highlight' : '' ?>"><?= $p['prices']['monthly'] > 0 ? number_format($p['prices']['monthly'], 0, ',', ' ') . ' FCFA' : 'Gratuit' ?></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td style="font-weight:600;">Chambres maximum</td>
                <?php foreach ($plans as $key => $p): ?>
                  <td style="text-align:center;" class="<?= $key === 'pro' ? 'docs-plan-highlight' : '' ?>"><?= $p['max_rooms'] >= PHP_INT_MAX ? 'Illimité' : $p['max_rooms'] ?></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td style="font-weight:600;">Établissements maximum</td>
                <?php foreach ($plans as $key => $p): ?>
                  <td style="text-align:center;" class="<?= $key === 'pro' ? 'docs-plan-highlight' : '' ?>"><?= $p['max_establishments'] >= PHP_INT_MAX ? 'Illimité' : $p['max_establishments'] ?></td>
                <?php endforeach; ?>
              </tr>
              <?php foreach ($featureLabels as $key => $label): ?>
                <tr>
                  <td><?= htmlspecialchars($label) ?></td>
                  <?php foreach ($plans as $pkey => $p): ?>
                    <td style="text-align:center;" class="<?= $pkey === 'pro' ? 'docs-plan-highlight' : '' ?>">
                      <?php if (!empty($p['features'][$key])): ?>
                        <span style="color:#16A34A;font-weight:700;">✓</span>
                      <?php else: ?>
                        <span style="color:#D1D5DB;">—</span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Rôles -->
      <section id="roles" class="docs-module">
        <div class="docs-module-header">
          <div class="docs-module-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div class="docs-module-title">Rôles & permissions</div>
        </div>
        <div class="docs-roles-grid">
          <div class="docs-role-card">
            <div class="docs-role-name">Propriétaire</div>
            <p>Accès complet à son ou ses établissements : toutes les sections, y compris Comptabilité, Dépenses, Retraits, Rapports et Paramètres. Peut créer et gérer les membres de son équipe.</p>
          </div>
          <div class="docs-role-card">
            <div class="docs-role-name">Réceptionniste (membre d'équipe)</div>
            <p>Accès aux opérations quotidiennes : réservations (création, modification, check-in/out), encaissement de paiements, consultation des chambres et clients. Pas d'accès à la Comptabilité, aux Dépenses, aux Retraits, aux Rapports ni aux Paramètres — ces sections apparaissent grisées avec un cadenas dans le menu.</p>
          </div>
        </div>
        <p class="docs-footnote">Ces restrictions sont appliquées à la fois dans le menu et sur le serveur — un membre d'équipe ne peut pas y accéder même en devinant l'adresse d'une page.</p>
      </section>

    </div>
  </div>
</div>
