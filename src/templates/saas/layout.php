<?php
// Wrapper SaaS : attend $content, $title, $page, $base_url
// Assets par page (optionnels, définis en tête du template enfant) :
//   $pageCss : string|array  → public/assets/css/pages/<nom>.css
//   $pageJs  : string|array  → public/assets/js/pages/<nom>.js
/**
 * @var string $content  HTML de la page enfant, injecté par Response::render().
 * @var string $title    Titre de la page.
 * @var string $page     Clé de la page active (pour le menu).
 * @var string $base_url URL de base de l'app.
 */
$content = $content ?? '';
$base    = $base_url ?? '';
$pageCss = isset($pageCss) ? (array) $pageCss : [];
$pageJs  = isset($pageJs)  ? (array) $pageJs  : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= isset($title) ? htmlspecialchars($title) : 'Afristay SaaS' ?></title>

  <!-- PWA -->
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base ?>/manifest.webmanifest">
  <link rel="icon" href="<?= $base ?>/assets/icons/icon-192.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base ?>/assets/icons/apple-touch-icon.png">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Afristay">
  <script src="<?= $base ?>/assets/js/pwa.js"></script>

  <!-- Polices auto-hébergées : Inter (UI) + Cormorant Garamond (wordmark "Afristay") -->
  <link rel="stylesheet" href="<?= $base ?>/assets/css/fonts.css">

  <!-- Tailwind (base + utilitaires) + styles composant SaaS, compilés ensemble -->
  <?php $saasCssPath = BASE_PATH . '/public/assets/css/saas.css'; ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/saas.css?v=<?= file_exists($saasCssPath) ? filemtime($saasCssPath) : 1 ?>">

  <!-- Responsivité SaaS (PWA, tous écrans) — chargé après saas.css, jamais écrasé par le build -->
  <?php $responsivePath = BASE_PATH . '/public/assets/css/saas-responsive.css'; ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/saas-responsive.css?v=<?= file_exists($responsivePath) ? filemtime($responsivePath) : 1 ?>">

  <style>
  /* Sidebar : hauteur fixe pour que le <nav> interne puisse scroller */
  .saas-sidebar {
    height: 100vh;
    overflow: hidden;
  }

  /* Empêche le flash des modals avant qu'Alpine s'initialise */
  [x-cloak] {
    display: none !important;
  }

  /* Section réservée au propriétaire (Comptabilité, Dépenses, Retraits, Rapports,
     Paramètres) : visible pour un receptionist mais non cliquable, cadenas au lieu
     du badge PRO — la restriction de rôle est appliquée par le backend quoi qu'il
     arrive (role:owner|superadmin), ceci n'en est que le reflet visuel. */
  .saas-nav-locked {
    opacity: 0.45;
    cursor: not-allowed;
  }
  .saas-nav-item.saas-nav-locked:hover {
    background: transparent;
  }
  .saas-nav-lock-icon {
    width: 14px;
    height: 14px;
    margin-left: auto;
    flex-shrink: 0;
    color: rgba(255,255,255,0.5);
  }
  </style>

  <!-- CSS spécifique à la page -->
  <?php foreach ($pageCss as $css): ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/pages/<?= htmlspecialchars($css) ?>.css">
  <?php endforeach; ?>

  <!-- JS global + JS de page (définis avant Alpine pour exposer les composants x-data) -->
  <?php $saasJsPath = BASE_PATH . '/public/assets/js/saas.js'; ?>
  <script defer src="<?= $base ?>/assets/js/saas.js?v=<?= file_exists($saasJsPath) ? filemtime($saasJsPath) : 1 ?>">
  </script>
  <?php foreach ((array)($pageJs ?? []) as $js): ?>
  <?php $jsPath = BASE_PATH . '/public/assets/js/pages/' . $js . '.js'; ?>
  <script defer
    src="<?= $base ?>/assets/js/pages/<?= htmlspecialchars($js) ?>.js?v=<?= file_exists($jsPath) ? filemtime($jsPath) : 1 ?>">
  </script>
  <?php endforeach; ?>

  <!-- Alpine.js en dernier : s'initialise après la définition des composants -->
  <script defer src="<?= $base ?>/assets/js/alpine.min.js"></script>
</head>

<body>
  <div class="saas-layout" x-data="saasLayout('<?= $base_url ?? '' ?>')" x-init="init()">
    <aside class="saas-sidebar" :class="sidebarOpen ? 'mobile-open' : ''">
      <div class="saas-sidebar-logo">
        <div style="display:flex;align-items:center;gap:10px;">
          <div>
            <div class="brand-title" style="font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:700;">
              <span style="color:#F6EFE6;">Afri</span> <span style="color:#C9A84C;">Stay</span>
            </div>
            <div class="brand-subtitle">SaaS Hôtelier</div>
          </div>
        </div>
      </div>

      <div style="padding:12px 12px 8px 12px;" x-show="establishments.length > 0">
        <div
          style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:10px 12px;cursor:pointer;position:relative;"
          x-data="{ open: false }" @click="open = !open">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:12px;color:rgba(255,255,255,0.45);margin-bottom:2px;">Établissement actif</div>
              <div style="font-size:13px;color:white;font-weight:600;"
                x-text="establishment?.name ?? 'Sélectionner...'"></div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              :class="open ? 'rotate-180' : ''"
              style="width:14px;height:14px;color:rgba(255,255,255,0.4);transition:transform 0.2s;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>

          <div x-show="open" x-transition @click.outside="open = false"
            style="position:absolute;top:100%;left:0;right:0;margin-top:4px;background:#1B4332;border:1px solid rgba(255,255,255,0.1);border-radius:10px;overflow:hidden;z-index:50;">
            <template x-for="est in establishments" :key="est.id">
              <button type="button" @click.stop="switchEstablishment(est.id); open = false"
                style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:13px;color:white;background:transparent;border:none;cursor:pointer;transition:background 0.2s;"
                :style="{ background: est.id == establishment?.id ? 'rgba(201,168,76,0.2)' : 'transparent', color: est.id == establishment?.id ? '#C9A84C' : 'white' }"
                @mouseover="($el.style.background = 'rgba(255,255,255,0.06)')"
                @mouseout="($el.style.background = est.id == establishment?.id ? 'rgba(201,168,76,0.2)' : 'transparent')">
                <span x-text="est.name"></span>
                <span x-show="est.frozen_at" style="margin-left:6px;font-size:9px;font-weight:700;color:#DC2626;background:rgba(220,38,38,0.18);padding:1px 6px;border-radius:20px;">GELÉ</span>
              </button>
            </template>
            <a x-show="canSeeSettings" href="<?= $base_url ?? '' ?>/saas/settings?add_estab=1" @click="open = false"
              style="display:block;padding:10px 12px;font-size:13px;color:#C9A84C;text-decoration:none;border-top:1px solid rgba(255,255,255,0.08);">
              + Ajouter un établissement
            </a>
          </div>
        </div>
      </div>

      <nav style="flex:1;overflow-y:auto;padding-bottom:16px;">
        <div class="saas-nav-section">
          <div class="saas-nav-label">Principal</div>

          <a href="<?= $base_url ?? '' ?>/saas"
            class="saas-nav-item <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Tableau de bord
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/planning"
            class="saas-nav-item <?= ($page ?? '') === 'planning' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Planning
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/bookings"
            class="saas-nav-item <?= ($page ?? '') === 'bookings' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Réservations
            <span class="nav-badge" x-show="pendingBookingsCount > 0" x-cloak x-text="pendingBookingsCount"></span>
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/rooms"
            class="saas-nav-item <?= ($page ?? '') === 'rooms' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Chambres & Tarifs
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/clients"
            class="saas-nav-item <?= ($page ?? '') === 'clients' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Clients
          </a>
        </div>

        <div class="saas-nav-section">
          <div class="saas-nav-label">Finances</div>

          <a href="<?= $base_url ?? '' ?>/saas/invoices"
            class="saas-nav-item <?= in_array($page ?? '', ['invoices','payments','billing']) ? 'active' : '' ?>"
            :class="{ 'saas-nav-locked': !canSeeFinance }"
            @click="!canSeeFinance && $event.preventDefault()"
            :title="!canSeeFinance ? 'Réservé au propriétaire' : ''">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Comptabilité
            <svg x-show="!canSeeFinance" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="saas-nav-lock-icon">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span x-show="canSeeFinance && !canFeature('invoices')"
              style="margin-left:auto;font-size:10px;font-weight:700;color:#C9A84C;background:rgba(201,168,76,0.15);padding:1px 5px;border-radius:4px;flex-shrink:0;">PRO</span>
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/expenses"
            class="saas-nav-item <?= ($page ?? '') === 'expenses' ? 'active' : '' ?>"
            :class="{ 'saas-nav-locked': !canSeeFinance }"
            @click="!canSeeFinance && $event.preventDefault()"
            :title="!canSeeFinance ? 'Réservé au propriétaire' : ''">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Dépenses
            <svg x-show="!canSeeFinance" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="saas-nav-lock-icon">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span x-show="canSeeFinance && !canFeature('expenses')"
              style="margin-left:auto;font-size:10px;font-weight:700;color:#C9A84C;background:rgba(201,168,76,0.15);padding:1px 5px;border-radius:4px;flex-shrink:0;">PRO</span>
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/payouts"
            class="saas-nav-item <?= ($page ?? '') === 'payouts' ? 'active' : '' ?>"
            :class="{ 'saas-nav-locked': !canSeeFinance }"
            @click="!canSeeFinance && $event.preventDefault()"
            :title="!canSeeFinance ? 'Réservé au propriétaire' : ''">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 9V7a4 4 0 00-8 0v2M5 9h14l1 11H4L5 9z" />
            </svg>
            Retraits
            <svg x-show="!canSeeFinance" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="saas-nav-lock-icon">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span x-show="canSeeFinance && !canFeature('online_payment_control')"
              style="margin-left:auto;font-size:10px;font-weight:700;color:#C9A84C;background:rgba(201,168,76,0.15);padding:1px 5px;border-radius:4px;flex-shrink:0;">PRO</span>
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/reports"
            class="saas-nav-item <?= ($page ?? '') === 'reports' ? 'active' : '' ?>"
            :class="{ 'saas-nav-locked': !canSeeFinance }"
            @click="!canSeeFinance && $event.preventDefault()"
            :title="!canSeeFinance ? 'Réservé au propriétaire' : ''">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Rapports
            <svg x-show="!canSeeFinance" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="saas-nav-lock-icon">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span x-show="canSeeFinance && !canFeature('reports')"
              style="margin-left:auto;font-size:10px;font-weight:700;color:#C9A84C;background:rgba(201,168,76,0.15);padding:1px 5px;border-radius:4px;flex-shrink:0;">PRO</span>
          </a>
        </div>

        <div class="saas-nav-section">
          <div class="saas-nav-label">Configuration</div>

          <a href="<?= $base_url ?? '' ?>/saas/settings"
            class="saas-nav-item <?= ($page ?? '') === 'settings' ? 'active' : '' ?>"
            :class="{ 'saas-nav-locked': !canSeeSettings }"
            @click="!canSeeSettings && $event.preventDefault()"
            :title="!canSeeSettings ? 'Réservé au propriétaire' : ''">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Paramètres
            <svg x-show="!canSeeSettings" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="saas-nav-lock-icon">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </a>

          <a href="<?= $base_url ?? '' ?>/saas/help"
            class="saas-nav-item <?= ($page ?? '') === 'help' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Centre d'aide
          </a>
        </div>
      </nav>

      <div class="saas-plan-card">
        <div class="plan-header">
          <div class="plan-label">Plan actuel</div>
          <div class="plan-pill" x-text="planLabel.toUpperCase()"></div>
        </div>
        <template x-if="planUpsellLabel">
          <div>
            <div class="plan-copy" x-text="'Passez en ' + planUpsellLabel + ' pour débloquer toutes les fonctionnalités'"></div>
            <a href="<?= $base_url ?? '' ?>/saas/settings?tab=subscription">Upgrader →</a>
          </div>
        </template>
        <template x-if="!planUpsellLabel">
          <div class="plan-copy">Vous profitez de toutes les fonctionnalités.</div>
        </template>
      </div>

      <div style="padding:12px;">
        <button @click="logout()"
          style="width:100%;display:flex;align-items:center;gap:10px;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.15);color:rgba(220,38,38,0.9);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:500;cursor:pointer;transition:all 0.2s;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- ── En-tête mobile (carte verte) + sélecteur d'établissement ── -->
    <header class="saas-mobile-header">
      <div class="saas-mobile-header-top">
        <div>
          <div class="brand-title" style="font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:700;line-height:1;">
            <span style="color:#F6EFE6;">Afri</span> <span style="color:#C9A84C;">Stay</span>
          </div>
          <div style="font-size:9px;letter-spacing:0.08em;color:rgba(255,255,255,0.45);font-weight:600;text-transform:uppercase;margin-top:3px;">SaaS Hôtelier</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <div x-data="notificationsPanel('<?= $base_url ?? '' ?>')" x-init="init()" @keydown.escape.window="open = false" style="position:relative;">
            <button @click="open = !open" class="saas-mobile-header-icon-btn" title="Notifications">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <div x-show="unread > 0" x-cloak style="position:absolute;top:-3px;right:-3px;min-width:16px;height:16px;background:#DC2626;border-radius:999px;border:2px solid #0F2B20;display:flex;align-items:center;justify-content:center;padding:0 3px;">
                <span x-text="badgeLabel" style="font-size:8px;font-weight:700;color:white;line-height:1;"></span>
              </div>
            </button>
            <div x-show="open" x-cloak x-transition @click.away="open=false" class="saas-anchored-dropdown"
              style="position:fixed;left:12px;right:12px;top:118px;background:white;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.24);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:100;">
              <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px 12px;border-bottom:1px solid rgba(0,0,0,0.06);">
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:13px;font-weight:700;color:#111827;">Notifications</span>
                  <span x-show="unread > 0" style="background:#FEE2E2;color:#DC2626;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;" x-text="unread + ' non lu' + (unread > 1 ? 'es' : 'e')"></span>
                </div>
                <button x-show="unread > 0" @click="markAllRead()" style="font-size:12px;color:#C9A84C;font-weight:600;background:none;border:none;cursor:pointer;padding:0;">Tout marquer lu</button>
              </div>
              <div style="max-height:340px;overflow-y:auto;display:flex;flex-direction:column;">
                <template x-if="!loading && notifications.length === 0">
                  <div style="padding:36px 24px;text-align:center;">
                    <div style="font-size:28px;margin-bottom:8px;">🔔</div>
                    <div style="font-size:13px;color:#9CA3AF;font-weight:500;">Aucune notification</div>
                  </div>
                </template>
                <template x-for="n in notifications" :key="n.id">
                  <button type="button" @click="markRead(n.id); open = false"
                    :style="'display:flex;width:100%;box-sizing:border-box;align-items:flex-start;gap:10px;padding:12px 14px;border:none;border-bottom:1px solid rgba(0,0,0,0.04);cursor:pointer;text-align:left;background:' + (n.read_at ? 'transparent' : '#FAFBFF') + ';'">
                    <div :style="'flex-shrink:0;width:32px;height:32px;border-radius:10px;background:' + typeConfig(n.type).bg + ';display:flex;align-items:center;justify-content:center;font-size:15px;'">
                      <span x-text="typeConfig(n.type).icon"></span>
                    </div>
                    <div style="flex:1;min-width:0;">
                      <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="n.title"></div>
                      <div style="font-size:11px;color:#9CA3AF;margin-top:2px;" x-text="timeAgo(n.created_at)"></div>
                    </div>
                  </button>
                </template>
              </div>
            </div>
          </div>
          <a href="<?= $base_url ?? '' ?>/saas/settings" class="saas-mobile-header-avatar" x-text="userInitials || 'IS'"></a>
        </div>
      </div>
    </header>

    <div class="saas-mobile-establishment" x-show="establishments.length > 0" x-data="{ open: false }">
      <div class="saas-mobile-establishment-card" @click="open = !open">
        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
          <span style="width:7px;height:7px;border-radius:50%;background:#4ADE80;flex-shrink:0;"></span>
          <div style="min-width:0;">
            <div style="font-size:10px;color:rgba(255,255,255,0.5);">Établissement actif</div>
            <div style="font-size:13px;color:white;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="establishment?.name ?? 'Sélectionner...'"></div>
          </div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" :class="open ? 'rotate-180' : ''" style="width:14px;height:14px;color:rgba(255,255,255,0.5);transition:transform 0.2s;flex-shrink:0;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
      <div x-show="open" x-transition @click.outside="open = false" style="margin-top:6px;background:#1B4332;border:1px solid rgba(255,255,255,0.1);border-radius:12px;overflow:hidden;">
        <template x-for="est in establishments" :key="est.id">
          <button type="button" @click.stop="switchEstablishment(est.id); open = false"
            style="display:block;width:100%;text-align:left;padding:12px 14px;font-size:13px;color:white;background:transparent;border:none;cursor:pointer;"
            :style="{ background: est.id == establishment?.id ? 'rgba(201,168,76,0.2)' : 'transparent', color: est.id == establishment?.id ? '#C9A84C' : 'white' }">
            <span x-text="est.name"></span>
            <span x-show="est.frozen_at" style="margin-left:6px;font-size:9px;font-weight:700;color:#DC2626;background:rgba(220,38,38,0.18);padding:1px 6px;border-radius:20px;">GELÉ</span>
          </button>
        </template>
        <a x-show="canSeeSettings" href="<?= $base_url ?? '' ?>/saas/settings?add_estab=1"
          style="display:block;padding:12px 14px;font-size:13px;color:#C9A84C;text-decoration:none;border-top:1px solid rgba(255,255,255,0.08);">
          + Ajouter un établissement
        </a>
      </div>
    </div>

    <header class="saas-topbar">
      <div style="display:flex;align-items:center;gap:16px;">
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden"
          style="background:none;border:none;cursor:pointer;padding:4px;color:#374151;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            style="width:22px;height:22px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div>
          <div style="font-size:16px;font-weight:700;color:#111827;">
            <?php
            $titles = [
              'dashboard' => 'Tableau de bord',
              'planning'  => 'Planning',
              'bookings'  => 'Réservations',
              'rooms'     => 'Chambres & Tarifs',
              'clients'   => 'Clients',
              'invoices'  => 'Comptabilité',
              'payments'  => 'Comptabilité',
              'billing'   => 'Comptabilité',
              'expenses'  => 'Dépenses',
              'payouts'   => 'Retraits',
              'reports'   => 'Rapports',
              'settings'  => 'Paramètres',
              'help'      => 'Centre d\'aide',
            ];
            echo $titles[$page ?? 'dashboard'] ?? 'Dashboard';
          ?>
          </div>
          <div class="saas-topbar-date" style="font-size:12px;color:#9CA3AF;">
            <?php
            $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
            $mois = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
            $date = new DateTime();
            echo $jours[(int)$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[(int)$date->format('n') - 1] . ' ' . $date->format('Y');
          ?>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:12px;">
        <div style="position:relative;" class="hidden md:block">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            style="width:15px;height:15px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input type="text" placeholder="Rechercher..."
            style="padding:8px 14px 8px 32px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:10px;font-size:13px;color:#374151;outline:none;width:200px;transition:all 0.2s;"
            onfocus="this.style.borderColor='#C9A84C'; this.style.boxShadow='0 0 0 3px rgba(201,168,76,0.1)'"
            onblur="this.style.borderColor='rgba(0,0,0,0.08)'; this.style.boxShadow='none'">
        </div>

        <!-- ── Panneau notifications ────────────────────────────────────── -->
        <div style="position:relative;" x-data="notificationsPanel('<?= $base_url ?? '' ?>')" x-init="init()"
          @keydown.escape.window="open = false">

          <!-- Bouton cloche -->
          <button @click="open = !open"
            style="position:relative;width:38px;height:38px;border-radius:10px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
            title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              style="width:18px;height:18px;color:#6B7280;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <!-- Badge compteur non-lu -->
            <div x-show="unread > 0" x-cloak
              style="position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;background:#DC2626;border-radius:999px;border:2px solid white;display:flex;align-items:center;justify-content:center;padding:0 3px;">
              <span x-text="badgeLabel" style="font-size:9px;font-weight:700;color:white;line-height:1;"></span>
            </div>
            <!-- Point vide quand 0 non-lus -->
            <div x-show="unread === 0" x-cloak
              style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:#D1D5DB;border-radius:50%;border:2px solid white;">
            </div>
          </button>

          <!-- Dropdown -->
          <div x-show="open" x-cloak x-transition @click.away="open=false" class="saas-anchored-dropdown"
            style="position:absolute;top:calc(100% + 8px);right:0;width:340px;background:white;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.14);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:100;">

            <!-- En-tête -->
            <div
              style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px 12px;border-bottom:1px solid rgba(0,0,0,0.06);">
              <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:13px;font-weight:700;color:#111827;">Notifications</span>
                <span x-show="unread > 0"
                  style="background:#FEE2E2;color:#DC2626;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;"
                  x-text="unread + ' non lu' + (unread > 1 ? 'es' : 'e')"></span>
              </div>
              <button x-show="unread > 0" @click="markAllRead()"
                style="font-size:12px;color:#C9A84C;font-weight:600;background:none;border:none;cursor:pointer;padding:0;">
                Tout marquer lu
              </button>
            </div>

            <!-- Liste -->
            <div style="max-height:380px;overflow-y:auto;display:flex;flex-direction:column;">
              <!-- Loading -->
              <template x-if="loading && notifications.length === 0">
                <div style="padding:32px;text-align:center;">
                  <div
                    style="width:20px;height:20px;border-radius:50%;border:2px solid #E5E7EB;border-top-color:#C9A84C;animation:spin 0.7s linear infinite;margin:0 auto;">
                  </div>
                </div>
              </template>

              <!-- Vide -->
              <template x-if="!loading && notifications.length === 0">
                <div style="padding:36px 24px;text-align:center;">
                  <div style="font-size:28px;margin-bottom:8px;">🔔</div>
                  <div style="font-size:13px;color:#9CA3AF;font-weight:500;">Aucune notification</div>
                  <div style="font-size:12px;color:#D1D5DB;margin-top:4px;">Vous êtes à jour !</div>
                </div>
              </template>

              <!-- Notifications -->
              <template x-for="n in notifications" :key="n.id">
                <button type="button" @click="markRead(n.id); open = false"
                  :style="'display:flex;width:100%;box-sizing:border-box;flex:0 0 auto;align-items:flex-start;gap:10px;padding:12px 14px;border:none;border-bottom:1px solid rgba(0,0,0,0.04);cursor:pointer;text-align:left;transition:background 0.15s;background:' + (n.read_at ? 'transparent' : '#FAFBFF') + ';'"
                  @mouseover="$el.style.background='#F9FAFB'"
                  @mouseout="$el.style.background = n.read_at ? 'transparent' : '#FAFBFF'">

                  <!-- Icône type -->
                  <div
                    :style="'flex-shrink:0;width:34px;height:34px;border-radius:10px;background:' + typeConfig(n.type).bg + ';display:flex;align-items:center;justify-content:center;font-size:16px;font-family:\'Segoe UI Emoji\',\'Apple Color Emoji\',\'Noto Color Emoji\',sans-serif;'">
                    <span x-text="typeConfig(n.type).icon"></span>
                  </div>

                  <!-- Texte -->
                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                      <span
                        style="flex:1;min-width:0;font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                        x-text="n.title"></span>
                      <span x-show="!n.read_at"
                        style="flex-shrink:0;width:6px;height:6px;background:#3B82F6;border-radius:50%;"></span>
                    </div>
                    <div x-show="n.message"
                      style="font-size:12px;color:#6B7280;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                      x-text="n.message"></div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:3px;" x-text="timeAgo(n.created_at)"></div>
                  </div>
                </button>
              </template>
            </div>

            <!-- Pied -->
            <div style="padding:10px 14px;border-top:1px solid rgba(0,0,0,0.06);display:flex;justify-content:center;">
              <button @click="load(); "
                style="font-size:12px;color:#9CA3AF;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;"
                :class="loading ? 'opacity-50' : ''">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  :style="'width:12px;height:12px;' + (loading ? 'animation:spin 1s linear infinite' : '')">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Actualiser
              </button>
            </div>
          </div>
        </div>

        <div x-data="{ open: false }" style="position:relative;">
          <button @click="open = !open"
            style="display:flex;align-items:center;gap:8px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:10px;padding:6px 10px;cursor:pointer;transition:all 0.2s;">
            <div
              style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--saas-gold),#A67C2E);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white;"
              x-text="userInitials || 'IS'"></div>
            <div style="text-align:left;" class="hidden md:block">
              <div style="font-size:13px;font-weight:600;color:#111827;line-height:1.2;" x-text="userName"></div>
              <div style="font-size:11px;color:#9CA3AF;text-transform:capitalize;" x-text="userRole"></div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              style="width:14px;height:14px;color:#9CA3AF;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div x-show="open" x-transition @click.away="open=false" class="saas-anchored-dropdown"
            style="position:absolute;top:100%;right:0;margin-top:8px;width:200px;background:white;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:50;">
            <div style="padding:8px;">
              <a href="<?= $base_url ?? '' ?>/saas/settings"
                style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#374151;text-decoration:none;font-size:13px;transition:background 0.2s;"
                @mouseover="($event.currentTarget.style.background='#F9FAFB')"
                @mouseout="($event.currentTarget.style.background='transparent')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  style="width:15px;height:15px;color:#9CA3AF;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Mon profil
              </a>
              <a href="<?= $base_url ?? '' ?>/saas/settings"
                style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#374151;text-decoration:none;font-size:13px;transition:background 0.2s;"
                @mouseover="($event.currentTarget.style.background='#F9FAFB')"
                @mouseout="($event.currentTarget.style.background='transparent')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  style="width:15px;height:15px;color:#9CA3AF;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Paramètres
              </a>
            </div>
            <div style="border-top:1px solid rgba(0,0,0,0.06);padding:8px;">
              <button @click="logout()"
                style="width:100%;display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#DC2626;font-size:13px;background:none;border:none;cursor:pointer;transition:background 0.2s;"
                @mouseover="($event.currentTarget.style.background='#FEF2F2')"
                @mouseout="($event.currentTarget.style.background='transparent')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  style="width:15px;height:15px;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Déconnexion
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="saas-content">
      <?= $content ?? '' ?>
    </main>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="md:hidden"
      style="position:fixed;inset:0;z-index:39;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px);"></div>

    <!-- ── Barre d'onglets mobile ─────────────────────────────── -->
    <?php
      $primaryTabs = [
        ['key'=>'dashboard','label'=>'Accueil','href'=>'/saas','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['key'=>'planning','label'=>'Planning','href'=>'/saas/planning','icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['key'=>'bookings','label'=>'Réserv.','href'=>'/saas/bookings','icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['key'=>'rooms','label'=>'Chambres','href'=>'/saas/rooms','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5'],
        ['key'=>'clients','label'=>'Clients','href'=>'/saas/clients','icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
      ];
      $moreTabs = [
        ['key'=>'invoices','label'=>'Comptabilité','href'=>'/saas/invoices','icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','guard'=>'canSeeFinance','feature'=>'invoices'],
        ['key'=>'expenses','label'=>'Dépenses','href'=>'/saas/expenses','icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z','guard'=>'canSeeFinance','feature'=>'expenses'],
        ['key'=>'payouts','label'=>'Retraits','href'=>'/saas/payouts','icon'=>'M17 9V7a4 4 0 00-8 0v2M5 9h14l1 11H4L5 9z','guard'=>'canSeeFinance','feature'=>'online_payment_control'],
        ['key'=>'reports','label'=>'Rapports','href'=>'/saas/reports','icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','guard'=>'canSeeFinance','feature'=>'reports'],
        ['key'=>'settings','label'=>'Paramètres','href'=>'/saas/settings','icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z','guard'=>'canSeeSettings'],
      ];
      $currentPage   = $page ?? 'dashboard';
      $onMoreTabPage = in_array($currentPage, array_column($moreTabs, 'key'), true);
    ?>
    <nav class="saas-mobile-tabbar" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
      <?php foreach ($primaryTabs as $t): ?>
        <a href="<?= $base_url ?? '' ?>/<?= ltrim($t['href'], '/') ?>" class="saas-mobile-tabbar-item <?= $currentPage === $t['key'] ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $t['icon'] ?>"/>
          </svg>
          <span><?= $t['label'] ?></span>
          <?php if ($t['key'] === 'bookings'): ?>
            <span class="saas-mobile-tabbar-badge" x-show="pendingBookingsCount > 0" x-cloak x-text="pendingBookingsCount"></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <!-- Bouton "Menu" : ouvre un petit panneau à droite avec les pages restantes -->
      <button type="button" class="saas-mobile-tabbar-item <?= $onMoreTabPage ? 'active' : '' ?>" @click="moreOpen = !moreOpen">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <span>Menu</span>
      </button>

      <div x-show="moreOpen" x-cloak x-transition class="saas-mobile-more-panel">
        <?php foreach ($moreTabs as $t): ?>
          <a href="<?= $base_url ?? '' ?>/<?= ltrim($t['href'], '/') ?>" class="saas-mobile-more-item <?= $currentPage === $t['key'] ? 'active' : '' ?>"
            <?php if (!empty($t['guard'])): ?>
            :class="{ 'saas-nav-locked': !<?= $t['guard'] ?> }"
            @click="!<?= $t['guard'] ?> && $event.preventDefault()"
            :title="!<?= $t['guard'] ?> ? 'Réservé au propriétaire' : ''"
            <?php endif; ?>>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $t['icon'] ?>"/>
            </svg>
            <span><?= $t['label'] ?></span>
            <?php if (!empty($t['guard'])): ?>
              <svg x-show="!<?= $t['guard'] ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="saas-nav-lock-icon" style="color:#9CA3AF;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            <?php endif; ?>
            <?php if (!empty($t['feature'])): ?>
              <span class="saas-mobile-more-pro" x-show="<?= $t['guard'] ?? 'true' ?> && !canFeature('<?= $t['feature'] ?>')" x-cloak>PRO</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>
  </div>
</body>

</html>