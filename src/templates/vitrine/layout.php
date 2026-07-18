<?php
/**
 * Layout principal pour la vitrine
 * Injecte : $title (string) et $content (HTML rendu de la page enfant)
 */
$base = $base_url ?? rtrim(APP_URL, '/');

/* ── Détection de la page courante ── */
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pageName = 'home';
if     (preg_match('#/search#',   $uri)) $pageName = 'search';
elseif (preg_match('#/property/#', $uri)) $pageName = 'property';
elseif (preg_match('#/booking#',  $uri)) $pageName = 'booking';
elseif (preg_match('#/apropos#',  $uri)) $pageName = 'apropos';
elseif (preg_match('#/tarifs#',   $uri)) $pageName = 'pricing';
elseif (preg_match('#/contact#',  $uri)) $pageName = 'contact';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#FAF7F2">
  <title><?= htmlspecialchars($title ?? 'Afristay') ?></title>
  <link rel="icon" href="<?= $base ?>/assets/icons/icon-192.png" type="image/png">

  <link rel="stylesheet" href="<?= $base ?>/assets/css/fonts.css">

  <!-- Styles globaux vitrine (remplace Tailwind CDN) -->
  <link rel="stylesheet" href="<?= $base ?>/assets/css/vitrine.css">
  <!-- Styles spécifiques à la page -->
  <?php if (file_exists(BASE_PATH . '/public/assets/css/pages/' . $pageName . '.css')): ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/pages/<?= $pageName ?>.css">
  <?php endif; ?>

  <!-- Leaflet (carte de localisation) — uniquement sur la fiche établissement.
       Auto-hébergé (public/assets/vendor/leaflet) car la CSP du site n'autorise
       que 'self' pour script-src/style-src (pas de CDN externe type unpkg). -->
  <?php if ($pageName === 'property'): ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/vendor/leaflet/leaflet.css">
  <?php endif; ?>

  <!-- Alpine.js — vitrineNav() doit être déclaré avant -->
  <?php $vitrineJsPath = BASE_PATH . '/public/assets/js/vitrine.js'; ?>
  <script src="<?= $base ?>/assets/js/vitrine.js?v=<?= file_exists($vitrineJsPath) ? filemtime($vitrineJsPath) : 1 ?>"
    defer></script>
  <?php
  $pageJsPath = BASE_PATH . '/public/assets/js/pages/' . $pageName . '.js';
  if (file_exists($pageJsPath)):
  ?>
  <script src="<?= $base ?>/assets/js/pages/<?= $pageName ?>.js?v=<?= filemtime($pageJsPath) ?>" defer></script>
  <?php endif; ?>
  <?php if ($pageName === 'property'): ?>
  <script defer src="<?= $base ?>/assets/vendor/leaflet/leaflet.js"></script>
  <?php endif; ?>
  <script defer src="<?= $base ?>/assets/js/alpine.min.js"></script>

  <style>
  /* ── Navigation vitrine ── */
  .v-nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
  }

  .v-nav-links {
    display: flex;
    align-items: center;
    gap: 2px;
    flex: 1;
    justify-content: center;
  }

  .v-nav-link {
    font-family: Inter, sans-serif;
    font-size: 14px;
    font-weight: 500;
    padding: 8px 14px;
    border-radius: 50px;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.2s, color 0.2s;
  }

  .v-nav-link:hover {
    background: rgba(201, 168, 76, 0.13);
  }

  .v-nav-link.active {
    background: rgba(201, 168, 76, 0.18);
    font-weight: 600;
  }

  .v-nav-sep {
    width: 1px;
    height: 20px;
    background: rgba(201, 168, 76, 0.3);
    margin: 0 6px;
    flex-shrink: 0;
  }

  .v-nav-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }

  .v-nav-btn-cta {
    background: linear-gradient(135deg, #C9A84C, #A67C2E);
    color: white;
    font-family: Inter, sans-serif;
    font-size: 14px;
    font-weight: 600;
    padding: 9px 22px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(201, 168, 76, 0.35);
    white-space: nowrap;
    flex-shrink: 0;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .v-nav-btn-cta:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(201, 168, 76, 0.45);
  }

  /* Hamburger — visible only on mobile */
  .v-nav-hamburger {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.2s;
  }

  .v-nav-hamburger:hover {
    background: rgba(201, 168, 76, 0.12);
  }

  /* Mobile menu drawer */
  .v-nav-mobile {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 48;
    flex-direction: column;
    background: rgba(250, 247, 242, 0.97);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    padding: 88px 28px 36px;
    gap: 4px;
    overflow-y: auto;
  }

  .v-nav-mobile.open {
    display: flex;
  }

  .v-nav-mobile-link {
    font-family: Inter, sans-serif;
    font-size: 17px;
    font-weight: 500;
    color: #1B4332;
    text-decoration: none;
    padding: 14px 16px;
    border-radius: 14px;
    border-bottom: 1px solid rgba(201, 168, 76, 0.1);
    transition: background 0.2s;
  }

  .v-nav-mobile-link:hover {
    background: rgba(201, 168, 76, 0.1);
  }

  .v-nav-mobile-link.active {
    background: rgba(201, 168, 76, 0.15);
    color: #A67C2E;
    font-weight: 600;
  }

  .v-nav-mobile-sep {
    height: 1px;
    background: rgba(201, 168, 76, 0.15);
    margin: 8px 0;
  }

  .v-nav-mobile-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 8px;
  }

  .v-nav-mobile-btn-cta {
    display: block;
    text-align: center;
    background: linear-gradient(135deg, #C9A84C, #A67C2E);
    color: white;
    font-family: Inter, sans-serif;
    font-size: 15px;
    font-weight: 600;
    padding: 14px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(201, 168, 76, 0.35);
  }

  /* Tablette : liens + CTA réduits entre 1024px et 1280px */
  @media (max-width: 1280px) {
    .v-nav-link {
      font-size: 13px;
      padding: 7px 10px;
    }

    .v-nav-btn-cta {
      font-size: 13px;
      padding: 8px 16px;
    }
  }

  /* Hamburger à partir de la tablette */
  @media (max-width: 1023px) {

    .v-nav-links,
    .v-nav-sep,
    .v-nav-actions {
      display: none !important;
    }

    .v-nav-hamburger {
      display: flex;
    }
  }

  /* ── Footer responsive ── */
  .v-footer-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 64px 48px 48px;
  }

  .v-footer-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr;
    gap: 48px;
  }

  .v-footer-bar {
    border-top: 1px solid rgba(201, 168, 76, 0.2);
    padding: 16px 48px;
  }

  .v-footer-bar-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
  }

  @media (max-width: 1023px) {
    .v-footer-inner {
      padding: 48px 32px 40px;
    }

    .v-footer-grid {
      grid-template-columns: 1.4fr 1fr;
      gap: 32px;
    }

    .v-footer-bar {
      padding: 14px 32px;
    }
  }

  @media (max-width: 767px) {
    .v-footer-inner {
      padding: 40px 24px 32px;
    }

    .v-footer-grid {
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }

    .v-footer-bar {
      padding: 14px 24px;
    }
  }

  @media (max-width: 479px) {
    .v-footer-inner {
      padding: 32px 20px 24px;
    }

    .v-footer-grid {
      grid-template-columns: 1fr 1fr;
      gap: 28px 20px;
    }

    /* Marque + accroche : pleine largeur, séparée par un filet doré */
    .v-footer-grid>div:nth-child(1) {
      grid-column: 1 / -1;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(201, 168, 76, 0.15);
    }

    /* Navigation / Espace SaaS : côte à côte pour réduire le défilement */
    .v-footer-grid>div:nth-child(2),
    .v-footer-grid>div:nth-child(3) {
      grid-column: span 1;
    }

    .v-footer-grid>div:nth-child(2) h4,
    .v-footer-grid>div:nth-child(3) h4 {
      font-size: 17px;
    }

    .v-footer-grid>div:nth-child(2) ul,
    .v-footer-grid>div:nth-child(3) ul {
      gap: 8px;
    }

    /* Contact : pleine largeur, séparée par un filet doré */
    .v-footer-grid>div:nth-child(4) {
      grid-column: 1 / -1;
      padding-top: 20px;
      border-top: 1px solid rgba(201, 168, 76, 0.15);
    }

    .v-footer-bar {
      padding: 12px 20px;
    }

    .v-footer-bar-inner {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }
  }
  </style>
</head>

<body>

  <?php
  /* Détection lien actif */
  $navLinks = [
    ['label' => 'Accueil',      'href' => $base . '/'],
    ['label' => 'Destinations', 'href' => $base . '/search'],
    ['label' => 'À propos',     'href' => $base . '/apropos'],
    ['label' => 'Tarifs',       'href' => $base . '/tarifs'],
    ['label' => 'Contact',      'href' => $base . '/contact'],
  ];
  $activeMap = [
    'home'     => '/',
    'search'   => '/search',
    'apropos'  => '/apropos',
    'pricing'  => '/tarifs',
    'contact'  => '/contact',
    'property' => '/search',
    'booking'  => '/search',
  ];
  $activePath = $activeMap[$pageName] ?? '/';
  ?>
  <header x-data="vitrineNav()" x-init="init()" @keydown.escape.window="mobileOpen = false">
    <nav style="position:fixed;top:0;left:0;right:0;width:100%;background:transparent;padding:18px 40px;z-index:50;"
      :style="scrolled
      ? 'position:fixed;top:16px;left:50%;transform:translateX(-50%);width:calc(100% - 48px);max-width:1100px;background:rgba(250,247,242,0.94);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(201,168,76,0.2);border-radius:60px;box-shadow:0 8px 32px rgba(27,67,50,0.12);padding:8px 24px;z-index:50;transition:all 0.4s cubic-bezier(0.4,0,0.2,1);'
      : 'position:fixed;top:0;left:0;right:0;width:100%;background:transparent;border:none;border-radius:0;box-shadow:none;padding:18px 40px;z-index:50;transition:all 0.4s cubic-bezier(0.4,0,0.2,1);'">
      <div class="v-nav-inner">

        <!-- Logo -->
        <a href="<?= $base ?>/" class="font-display"
          style="flex-shrink:0;line-height:1;font-size:26px;font-weight:600;text-decoration:none;white-space:nowrap;">
          <span :style="{ color: scrolled ? '#1B4332' : '#FFFFFF' }">Afri</span> <span
            style="color:#C9A84C;">Stay</span>
        </a>

        <!-- Links — desktop -->
        <div class="v-nav-links">
          <?php foreach($navLinks as $link):
          $isActive = $link['href'] === $base . $activePath;
        ?>
          <a href="<?= $link['href'] ?>" class="v-nav-link<?= $isActive ? ' active' : '' ?>"
            :style="scrolled ? 'color:#1B4332;' : 'color:rgba(255,255,255,0.92);'"><?= $link['label'] ?></a>
          <?php endforeach; ?>
        </div>

        <!-- Actions — desktop -->
        <div class="v-nav-actions">
          <div class="v-nav-sep"></div>
          <a href="<?= $base ?>/register" class="v-nav-btn-cta">Commencer</a>
        </div>

        <!-- Hamburger — mobile only -->
        <button class="v-nav-hamburger" @click="mobileOpen = !mobileOpen" aria-label="Menu"
          :style="scrolled ? 'color:#1B4332;' : 'color:white;'">
          <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg x-show="mobileOpen" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

    </nav>

    <!-- Drawer mobile — hors de <nav> pour éviter le stacking context du transform -->
    <div class="v-nav-mobile" :class="{ open: mobileOpen }">
      <div
        style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;padding-bottom:16px;border-bottom:1px solid rgba(201,168,76,0.15);">
        <span class="font-display" style="font-size:22px;font-weight:600;line-height:1;white-space:nowrap;">
          <span style="color:#1B4332;">Afri</span> <span style="color:#C9A84C;">Stay</span>
        </span>
        <button @click="mobileOpen = false"
          style="background:none;border:none;cursor:pointer;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#1B4332;">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <?php foreach($navLinks as $link):
      $isActive = $link['href'] === $base . $activePath;
    ?>
      <a href="<?= $link['href'] ?>" class="v-nav-mobile-link<?= $isActive ? ' active' : '' ?>"
        @click="mobileOpen = false"><?= $link['label'] ?></a>
      <?php endforeach; ?>
      <div class="v-nav-mobile-sep"></div>
      <div class="v-nav-mobile-actions">
        <a href="<?= $base ?>/register" class="v-nav-mobile-btn-cta">Commencer gratuitement →</a>
      </div>
    </div>
  </header>

  <main>
    <?= $content ?>
  </main>

  <footer class="site-footer" style="margin-top:0;font-size:14px;">
    <div class="v-footer-inner">
      <div class="v-footer-grid">
        <div style="display:flex;flex-direction:column;gap:16px;">
          <a href="<?= $base ?>/" class="font-display"
            style="display:inline-block;font-size:30px;font-weight:600;line-height:1;text-decoration:none;white-space:nowrap;">
            <span style="color:#F6EFE6;">Afri</span> <span style="color:#C9A84C;">Stay</span>
          </a>
          <p style="font-size:14px;max-width:240px;color:rgba(246,239,230,0.65);line-height:1.75;">La plateforme
            hôtelière pensée pour la Côte d'Ivoire.</p>
        </div>
        <div>
          <h4 class="font-display" style="font-size:20px;margin-bottom:14px;font-weight:400;">Navigation</h4>
          <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
            <li><a href="<?= $base ?>/">Accueil</a></li>
            <li><a href="<?= $base ?>/search">Destinations</a></li>
            <li><a href="<?= $base ?>/tarifs">Tarifs</a></li>
            <li><a href="<?= $base ?>/apropos">À propos</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-display" style="font-size:20px;margin-bottom:14px;font-weight:400;">Espace SaaS</h4>
          <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
            <li><a href="<?= $base ?>/register">S'inscrire</a></li>
            <li><a href="<?= $base ?>/tarifs">Fonctionnalités</a></li>
            <li><a href="<?= $base ?>/contact">Support</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-display" style="font-size:20px;margin-bottom:14px;font-weight:400;">Contact</h4>
          <p style="color:rgba(246,239,230,0.65);">Yamoussoukro, Côte d'Ivoire</p>
          <p style="margin-top:8px;color:rgba(246,239,230,0.65);">support@afristay.ci</p>
          <p style="margin-top:4px;color:rgba(246,239,230,0.65);">+225 01 61 95 90 80</p>
        </div>
      </div>
    </div>
    <div class="v-footer-bar">
      <div class="v-footer-bar-inner">
        <p style="font-size:13px;color:rgba(246,239,230,0.45);">&copy; <?= date('Y') ?> Afristay Tous droits
          réservés</p>
        <div style="display:flex;align-items:center;gap:20px;">
          <a href="<?= $base ?>/cgu" style="font-size:13px;">CGU</a>
          <a href="<?= $base ?>/confidentialite" style="font-size:13px;">Politique de confidentialité</a>
          <a href="<?= $base ?>/contact" style="font-size:13px;">Contact</a>
        </div>
      </div>
    </div>
  </footer>

</body>

</html>