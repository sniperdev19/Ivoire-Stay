<?php
// Wrapper Admin plateforme (superadmin) : attend $content, $title, $page, $base_url
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

$navItems = [
    ['key' => 'dashboard',      'label' => "Vue d'ensemble", 'href' => '/admin',               'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['key' => 'establishments', 'label' => 'Établissements', 'href' => '/admin/establishments','icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    ['key' => 'owners',         'label' => 'Propriétaires',  'href' => '/admin/owners',        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
    ['key' => 'payouts',        'label' => 'Retraits',       'href' => '/admin/payouts',       'icon' => 'M17 9V7a4 4 0 00-8 0v2M5 9h14l1 11H4L5 9z'],
];
$currentPage = $page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= isset($title) ? htmlspecialchars($title) : 'AfriStay Admin' ?></title>

  <meta name="theme-color" content="#1e1b4b">
  <link rel="icon" href="<?= $base ?>/assets/icons/icon-192.png" type="image/png">

  <link rel="stylesheet" href="<?= $base ?>/assets/css/fonts.css">

  <?php $saasCssPath = BASE_PATH . '/public/assets/css/saas.css'; ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/saas.css?v=<?= file_exists($saasCssPath) ? filemtime($saasCssPath) : 1 ?>">

  <?php $responsivePath = BASE_PATH . '/public/assets/css/saas-responsive.css'; ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/saas-responsive.css?v=<?= file_exists($responsivePath) ? filemtime($responsivePath) : 1 ?>">

  <!-- Identité visuelle distincte de l'espace hôtelier (indigo/slate) — chargé après saas.css -->
  <?php $adminCssPath = BASE_PATH . '/public/assets/css/admin.css'; ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css?v=<?= file_exists($adminCssPath) ? filemtime($adminCssPath) : 1 ?>">

  <style>
  .saas-sidebar { height: 100vh; overflow: hidden; }
  [x-cloak] { display: none !important; }
  </style>

  <?php foreach ($pageCss as $css): ?>
  <?php $cssPath = BASE_PATH . '/public/assets/css/pages/' . $css . '.css'; ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/pages/<?= htmlspecialchars($css) ?>.css?v=<?= file_exists($cssPath) ? filemtime($cssPath) : 1 ?>">
  <?php endforeach; ?>

  <?php $saasJsPath = BASE_PATH . '/public/assets/js/saas.js'; ?>
  <script defer src="<?= $base ?>/assets/js/saas.js?v=<?= file_exists($saasJsPath) ? filemtime($saasJsPath) : 1 ?>"></script>
  <?php $adminJsPath = BASE_PATH . '/public/assets/js/admin.js'; ?>
  <script defer src="<?= $base ?>/assets/js/admin.js?v=<?= file_exists($adminJsPath) ? filemtime($adminJsPath) : 1 ?>"></script>
  <?php foreach ($pageJs as $js): ?>
  <?php $jsPath = BASE_PATH . '/public/assets/js/pages/' . $js . '.js'; ?>
  <script defer src="<?= $base ?>/assets/js/pages/<?= htmlspecialchars($js) ?>.js?v=<?= file_exists($jsPath) ? filemtime($jsPath) : 1 ?>"></script>
  <?php endforeach; ?>

  <script defer src="<?= $base ?>/assets/js/alpine.min.js"></script>
</head>

<body>
  <div class="saas-layout" x-data="adminLayout('<?= $base_url ?? '' ?>')" x-init="init()">
    <aside class="saas-sidebar" :class="sidebarOpen ? 'mobile-open' : ''">
      <div class="saas-sidebar-logo">
        <div class="brand-title" style="font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:700;">
          <span style="color:#F6EFE6;">Afri</span> <span style="color:#6366F1;">Stay</span>
        </div>
        <div class="brand-subtitle">Admin plateforme</div>
      </div>

      <nav style="flex:1;overflow-y:auto;padding:12px;">
        <div class="saas-nav-section">
          <div class="saas-nav-label">Plateforme</div>
          <?php foreach ($navItems as $item): ?>
            <a href="<?= $base ?><?= $item['href'] ?>"
              class="saas-nav-item <?= $currentPage === $item['key'] ? 'active' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>" />
              </svg>
              <?= htmlspecialchars($item['label']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </nav>

      <div style="padding:12px;">
        <button @click="logout()"
          style="width:100%;display:flex;align-items:center;gap:10px;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.15);color:rgba(220,38,38,0.9);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:500;cursor:pointer;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- En-tête mobile -->
    <header class="saas-mobile-header">
      <div class="saas-mobile-header-top">
        <div>
          <div class="brand-title" style="font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:700;line-height:1;">
            <span style="color:#F6EFE6;">Afri</span> <span style="color:#6366F1;">Stay</span>
          </div>
          <div style="font-size:9px;letter-spacing:0.08em;color:rgba(255,255,255,0.45);font-weight:600;text-transform:uppercase;margin-top:3px;">Admin plateforme</div>
        </div>
        <div class="saas-mobile-header-avatar" x-text="userInitials || 'AD'"></div>
      </div>
    </header>

    <header class="saas-topbar">
      <div style="display:flex;align-items:center;gap:16px;">
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden" style="background:none;border:none;cursor:pointer;padding:4px;color:#374151;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:22px;height:22px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div>
          <div style="font-size:16px;font-weight:700;color:#111827;"><?= htmlspecialchars($title ?? 'AfriStay Admin') ?></div>
          <div class="saas-topbar-date" style="font-size:12px;color:#9CA3AF;">
            <?php
              $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
              $mois  = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
              $date  = new DateTime();
              echo $jours[(int)$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[(int)$date->format('n') - 1] . ' ' . $date->format('Y');
            ?>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:12px;">
        <span class="admin-badge">Superadmin</span>

        <!-- ── Panneau notifications (réutilise le composant partagé saas.js) ── -->
        <div style="position:relative;" x-data="notificationsPanel('<?= $base_url ?? '' ?>')" x-init="init()"
          @keydown.escape.window="open = false">

          <button @click="open = !open"
            style="position:relative;width:38px;height:38px;border-radius:10px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
            title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              style="width:18px;height:18px;color:#6B7280;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <div x-show="unread > 0" x-cloak
              style="position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;background:#DC2626;border-radius:999px;border:2px solid white;display:flex;align-items:center;justify-content:center;padding:0 3px;">
              <span x-text="badgeLabel" style="font-size:9px;font-weight:700;color:white;line-height:1;"></span>
            </div>
            <div x-show="unread === 0" x-cloak
              style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:#D1D5DB;border-radius:50%;border:2px solid white;">
            </div>
          </button>

          <div x-show="open" x-cloak x-transition @click.away="open=false" class="saas-anchored-dropdown"
            style="position:absolute;top:calc(100% + 8px);right:0;width:340px;background:white;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.14);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:100;">

            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px 12px;border-bottom:1px solid rgba(0,0,0,0.06);">
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

            <div style="max-height:380px;overflow-y:auto;display:flex;flex-direction:column;">
              <template x-if="loading && notifications.length === 0">
                <div style="padding:32px;text-align:center;">
                  <div style="width:20px;height:20px;border-radius:50%;border:2px solid #E5E7EB;border-top-color:#6366F1;animation:spin 0.7s linear infinite;margin:0 auto;"></div>
                </div>
              </template>

              <template x-if="!loading && notifications.length === 0">
                <div style="padding:36px 24px;text-align:center;">
                  <div style="font-size:28px;margin-bottom:8px;">🔔</div>
                  <div style="font-size:13px;color:#9CA3AF;font-weight:500;">Aucune notification</div>
                  <div style="font-size:12px;color:#D1D5DB;margin-top:4px;">Vous êtes à jour !</div>
                </div>
              </template>

              <template x-for="n in notifications" :key="n.id">
                <button type="button" @click="markRead(n.id); open = false"
                  :style="'display:flex;width:100%;box-sizing:border-box;flex:0 0 auto;align-items:flex-start;gap:10px;padding:12px 14px;border:none;border-bottom:1px solid rgba(0,0,0,0.04);cursor:pointer;text-align:left;transition:background 0.15s;background:' + (n.read_at ? 'transparent' : '#FAFBFF') + ';'"
                  @mouseover="$el.style.background='#F9FAFB'"
                  @mouseout="$el.style.background = n.read_at ? 'transparent' : '#FAFBFF'">
                  <div :style="'flex-shrink:0;width:34px;height:34px;border-radius:10px;background:' + typeConfig(n.type).bg + ';display:flex;align-items:center;justify-content:center;font-size:16px;font-family:\'Segoe UI Emoji\',\'Apple Color Emoji\',\'Noto Color Emoji\',sans-serif;'">
                    <span x-text="typeConfig(n.type).icon"></span>
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                      <span style="flex:1;min-width:0;font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="n.title"></span>
                      <span x-show="!n.read_at" style="flex-shrink:0;width:6px;height:6px;background:#3B82F6;border-radius:50%;"></span>
                    </div>
                    <div x-show="n.message" style="font-size:12px;color:#6B7280;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="n.message"></div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:3px;" x-text="timeAgo(n.created_at)"></div>
                  </div>
                </button>
              </template>
            </div>
          </div>
        </div>

        <div x-data="{ open: false }" style="position:relative;">
          <button @click="open = !open" style="display:flex;align-items:center;gap:8px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:10px;padding:6px 10px;cursor:pointer;">
            <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366F1,#4338CA);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white;" x-text="userInitials || 'AD'"></div>
            <div style="text-align:left;" class="hidden md:block">
              <div style="font-size:13px;font-weight:600;color:#111827;line-height:1.2;" x-text="userName"></div>
              <div style="font-size:11px;color:#9CA3AF;">Propriétaire AfriStay</div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:14px;height:14px;color:#9CA3AF;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div x-show="open" x-transition @click.away="open=false" class="saas-anchored-dropdown"
            style="position:absolute;top:100%;right:0;margin-top:8px;width:200px;background:white;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:50;">
            <!-- Pas de lien "Espace hôtelier" générique : le superadmin n'est
                 rattaché à aucun établissement particulier (voir admin.js). -->
            <div style="padding:8px;">
              <button @click="logout()" style="width:100%;display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#DC2626;font-size:13px;background:none;border:none;cursor:pointer;">
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

    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="md:hidden"
      style="position:fixed;inset:0;z-index:39;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px);"></div>

    <!-- Barre d'onglets mobile : les 4 sections tiennent toutes en primaire, pas de "Menu" -->
    <nav class="saas-mobile-tabbar">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= $base ?><?= $item['href'] ?>" class="saas-mobile-tabbar-item <?= $currentPage === $item['key'] ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>" />
          </svg>
          <span><?= htmlspecialchars($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</body>

</html>
