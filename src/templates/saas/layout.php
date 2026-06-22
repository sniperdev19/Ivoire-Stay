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
  <title><?= isset($title) ? htmlspecialchars($title) : 'Ivoire Stay SaaS' ?></title>

  <!-- PWA -->
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base ?>/manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $base ?>/assets/icons/apple-touch-icon.png">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Ivoire Stay">
  <script src="<?= $base ?>/assets/js/pwa.js"></script>

  <!-- Google Fonts : Inter uniquement -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Tailwind (base + utilitaires) + styles composant SaaS, compilés ensemble -->
  <link rel="stylesheet" href="<?= $base ?>/assets/css/saas.css">

  <style>
    /* Sidebar : hauteur fixe pour que le <nav> interne puisse scroller */
    .saas-sidebar { height: 100vh; overflow: hidden; }
    /* Empêche le flash des modals avant qu'Alpine s'initialise */
    [x-cloak] { display: none !important; }
  </style>

  <!-- CSS spécifique à la page -->
  <?php foreach ($pageCss as $css): ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/pages/<?= htmlspecialchars($css) ?>.css">
  <?php endforeach; ?>

  <!-- JS global + JS de page (définis avant Alpine pour exposer les composants x-data) -->
  <?php $saasJsPath = BASE_PATH . '/public/assets/js/saas.js'; ?>
  <script defer src="<?= $base ?>/assets/js/saas.js?v=<?= file_exists($saasJsPath) ? filemtime($saasJsPath) : 1 ?>"></script>
  <?php foreach ((array)($pageJs ?? []) as $js): ?>
  <?php $jsPath = BASE_PATH . '/public/assets/js/pages/' . $js . '.js'; ?>
  <script defer src="<?= $base ?>/assets/js/pages/<?= htmlspecialchars($js) ?>.js?v=<?= file_exists($jsPath) ? filemtime($jsPath) : 1 ?>"></script>
  <?php endforeach; ?>

  <!-- Alpine.js en dernier : s'initialise après la définition des composants -->
  <script defer src="<?= $base ?>/assets/js/alpine.min.js"></script>
</head>

<body>
<div class="saas-layout"
  x-data="saasLayout('<?= $base_url ?? '' ?>')"
  x-init="init()"
>
  <aside class="saas-sidebar" :class="sidebarOpen ? 'mobile-open' : ''">
    <div class="saas-sidebar-logo">
      <div style="display:flex;align-items:center;gap:10px;">
        <img src="<?= $base_url ?? '' ?>/assets/logo.png" alt="Ivoire Stay">
        <div>
          <div class="brand-title">Ivoire Stay</div>
          <div class="brand-subtitle">SaaS Hôtelier</div>
        </div>
      </div>
    </div>

    <div style="padding:12px 12px 8px 12px;" x-show="establishments.length > 0">
      <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:10px 12px;cursor:pointer;position:relative;"
        x-data="{ open: false }"
        @click="open = !open"
      >
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <div>
            <div style="font-size:12px;color:rgba(255,255,255,0.45);margin-bottom:2px;">Établissement actif</div>
            <div style="font-size:13px;color:white;font-weight:600;" x-text="establishment?.name ?? 'Sélectionner...'"></div>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            :class="open ? 'rotate-180' : ''"
            style="width:14px;height:14px;color:rgba(255,255,255,0.4);transition:transform 0.2s;"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>

        <div x-show="open" x-transition
          @click.outside="open = false"
          style="position:absolute;top:100%;left:0;right:0;margin-top:4px;background:#1B4332;border:1px solid rgba(255,255,255,0.1);border-radius:10px;overflow:hidden;z-index:50;"
        >
          <template x-for="est in establishments" :key="est.id">
            <button type="button"
              @click.stop="switchEstablishment(est.id); open = false"
              style="width:100%;text-align:left;padding:10px 12px;font-size:13px;color:white;background:transparent;border:none;cursor:pointer;transition:background 0.2s;"
              :class="est.id == establishment?.id ? 'bg-[rgba(201,168,76,0.2)] text-[#C9A84C]' : ''"
              @mouseover="($el.style.background = 'rgba(255,255,255,0.06)')"
              @mouseout="($el.style.background = est.id == establishment?.id ? 'rgba(201,168,76,0.2)' : 'transparent')"
              x-text="est.name"
            ></button>
          </template>
        </div>
      </div>
    </div>

    <nav style="flex:1;overflow-y:auto;padding-bottom:16px;">
      <div class="saas-nav-section">
        <div class="saas-nav-label">Principal</div>

        <a href="<?= $base_url ?? '' ?>/saas" class="saas-nav-item <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Tableau de bord
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/planning" class="saas-nav-item <?= ($page ?? '') === 'planning' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Planning
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/bookings" class="saas-nav-item <?= ($page ?? '') === 'bookings' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          Réservations
          <span class="nav-badge">3</span>
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/rooms" class="saas-nav-item <?= ($page ?? '') === 'rooms' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Chambres & Tarifs
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/clients" class="saas-nav-item <?= ($page ?? '') === 'clients' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Clients
        </a>
      </div>

      <div class="saas-nav-section" x-show="canSeeFinance">
        <div class="saas-nav-label">Finances</div>

        <a href="<?= $base_url ?? '' ?>/saas/invoices" class="saas-nav-item <?= in_array($page ?? '', ['invoices','payments','billing']) ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Comptabilité
          <span x-show="!canFeature('invoices')" style="margin-left:auto;font-size:10px;font-weight:700;color:#C9A84C;background:rgba(201,168,76,0.15);padding:1px 5px;border-radius:4px;flex-shrink:0;">PRO</span>
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/expenses" class="saas-nav-item <?= ($page ?? '') === 'expenses' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          Dépenses
          <span x-show="!canFeature('expenses')" style="margin-left:auto;font-size:10px;font-weight:700;color:#C9A84C;background:rgba(201,168,76,0.15);padding:1px 5px;border-radius:4px;flex-shrink:0;">PRO</span>
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/reports" class="saas-nav-item <?= ($page ?? '') === 'reports' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Rapports
          <span x-show="!canFeature('reports')" style="margin-left:auto;font-size:10px;font-weight:700;color:#C9A84C;background:rgba(201,168,76,0.15);padding:1px 5px;border-radius:4px;flex-shrink:0;">PRO</span>
        </a>
      </div>

      <div class="saas-nav-section" x-show="canSeeSettings">
        <div class="saas-nav-label">Configuration</div>

        <a href="<?= $base_url ?? '' ?>/saas/settings" class="saas-nav-item <?= ($page ?? '') === 'settings' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Paramètres
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/help" class="saas-nav-item <?= ($page ?? '') === 'help' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Centre d'aide
        </a>
      </div>
    </nav>

    <div class="saas-plan-card">
      <div class="plan-header">
        <div class="plan-label">Plan actuel</div>
        <div class="plan-pill">STARTER</div>
      </div>
      <div class="plan-copy">Passez en Pro pour débloquer toutes les fonctionnalités</div>
      <a href="<?= $base_url ?? '' ?>/tarifs">Upgrader →</a>
    </div>

    <div style="padding:12px;">
      <button @click="logout()" style="width:100%;display:flex;align-items:center;gap:10px;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.15);color:rgba(220,38,38,0.9);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:500;cursor:pointer;transition:all 0.2s;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        Déconnexion
      </button>
    </div>
  </aside>

  <header class="saas-topbar">
    <div style="display:flex;align-items:center;gap:16px;">
      <button @click="sidebarOpen = !sidebarOpen" class="md:hidden" style="background:none;border:none;cursor:pointer;padding:4px;color:#374151;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:22px;height:22px;">
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
              'reports'   => 'Rapports',
              'settings'  => 'Paramètres',
              'help'      => 'Centre d\'aide',
            ];
            echo $titles[$page ?? 'dashboard'] ?? 'Dashboard';
          ?>
        </div>
        <div style="font-size:12px;color:#9CA3AF;">
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
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" placeholder="Rechercher..." style="padding:8px 14px 8px 32px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:10px;font-size:13px;color:#374151;outline:none;width:200px;transition:all 0.2s;"
          onfocus="this.style.borderColor='#C9A84C'; this.style.boxShadow='0 0 0 3px rgba(201,168,76,0.1)'"
          onblur="this.style.borderColor='rgba(0,0,0,0.08)'; this.style.boxShadow='none'"
        >
      </div>

      <div style="position:relative;" x-data="{ open: false }">
        <button @click="open = !open" style="position:relative;width:38px;height:38px;border-radius:10px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:18px;height:18px;color:#6B7280;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <div style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:#DC2626;border-radius:50%;border:2px solid white;"></div>
        </button>
        <div x-show="open" x-transition @click.away="open=false" style="position:absolute;top:100%;right:0;margin-top:8px;width:300px;background:white;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:50;">
          <div style="padding:16px 16px 12px;border-bottom:1px solid rgba(0,0,0,0.06);font-size:13px;font-weight:700;color:#111827;">Notifications</div>
          <div style="padding:8px;">
            <button type="button" style="width:100%;padding:10px;border-radius:10px;text-align:left;cursor:pointer;transition:background 0.2s;border:none;background:transparent;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <div style="font-size:13px;color:#111827;font-weight:500;">Nouvelle réservation</div>
              <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">Chambre Deluxe · il y a 5 min</div>
            </button>
            <button type="button" style="width:100%;padding:10px;border-radius:10px;text-align:left;cursor:pointer;transition:background 0.2s;border:none;background:transparent;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <div style="font-size:13px;color:#111827;font-weight:500;">Check-in aujourd'hui</div>
              <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">3 clients · à 14h00</div>
            </button>
          </div>
          <div style="padding:12px;text-align:center;border-top:1px solid rgba(0,0,0,0.06);">
            <a href="#" style="font-size:13px;color:var(--saas-gold);font-weight:500;text-decoration:none;">Voir tout →</a>
          </div>
        </div>
      </div>

      <div x-data="{ open: false }" style="position:relative;">
        <button @click="open = !open" style="display:flex;align-items:center;gap:8px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:10px;padding:6px 10px;cursor:pointer;transition:all 0.2s;">
          <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--saas-gold),#A67C2E);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white;" x-text="userInitials || 'IS'"></div>
          <div style="text-align:left;" class="hidden md:block">
            <div style="font-size:13px;font-weight:600;color:#111827;line-height:1.2;" x-text="userName"></div>
            <div style="font-size:11px;color:#9CA3AF;text-transform:capitalize;" x-text="userRole"></div>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:14px;height:14px;color:#9CA3AF;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div x-show="open" x-transition @click.away="open=false" style="position:absolute;top:100%;right:0;margin-top:8px;width:200px;background:white;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:50;">
          <div style="padding:8px;">
            <a href="<?= $base_url ?? '' ?>/saas/settings" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#374151;text-decoration:none;font-size:13px;transition:background 0.2s;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;color:#9CA3AF;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Mon profil
            </a>
            <a href="<?= $base_url ?? '' ?>/saas/settings" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#374151;text-decoration:none;font-size:13px;transition:background 0.2s;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;color:#9CA3AF;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Paramètres
            </a>
          </div>
          <div style="border-top:1px solid rgba(0,0,0,0.06);padding:8px;">
            <button @click="logout()" style="width:100%;display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#DC2626;font-size:13px;background:none;border:none;cursor:pointer;transition:background 0.2s;" @mouseover="($event.currentTarget.style.background='#FEF2F2')" @mouseout="($event.currentTarget.style.background='transparent')">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
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

  <div x-show="sidebarOpen" @click="sidebarOpen = false" class="md:hidden" style="position:fixed;inset:0;z-index:39;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px);"></div>
</div>
</body>
</html>
