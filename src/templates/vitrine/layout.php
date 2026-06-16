<?php
/**
 * Layout principal pour la vitrine
 * Injecte : $title (string) et $content (HTML rendu de la page enfant)
 * Respecte le design system fourni (glassmorphisme, typographie, couleurs)
 *
 * Assets par page (optionnels, définis en tête du template enfant) :
 *   $pageCss : string|array  → public/assets/css/pages/<nom>.css
 *   $pageJs  : string|array  → public/assets/js/pages/<nom>.js
 *
 * @var string $title    Titre de la page, injecté par Response::render().
 * @var string $content  HTML de la page enfant, injecté par Response::render().
 * @var string $base_url URL de base de l'app, injectée par Response::render().
 */
$content = $content ?? '';
$base = $base_url ?? rtrim(APP_URL, '/');
$pageCss = isset($pageCss) ? (array) $pageCss : [];
$pageJs  = isset($pageJs)  ? (array) $pageJs  : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#FAF7F2">
  <title><?= htmlspecialchars($title ?? 'Ivoire Stay') ?></title>

  <!-- PWA -->
  <link rel="manifest" href="<?= $base ?>/manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $base ?>/assets/icons/apple-touch-icon.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Ivoire Stay">
  <script defer src="<?= $base ?>/assets/js/pwa.js"></script>

  <!-- Google Fonts : Cormorant Garamond (titres) + Inter (UI) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind (base + utilitaires) + styles composant vitrine, compilés ensemble -->
  <link rel="stylesheet" href="<?= $base ?>/assets/css/vitrine.css">

  <!-- CSS spécifique à la page -->
  <?php foreach ($pageCss as $css): ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/pages/<?= htmlspecialchars($css) ?>.css">
  <?php endforeach; ?>

  <!-- JS global + JS de page (définis avant Alpine pour exposer les composants x-data) -->
  <script defer src="<?= $base ?>/assets/js/vitrine.js"></script>
  <?php foreach ($pageJs as $js): ?>
  <script defer src="<?= $base ?>/assets/js/pages/<?= htmlspecialchars($js) ?>.js"></script>
  <?php endforeach; ?>

  <!-- Alpine.js en dernier : s'initialise après la définition des composants -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>
<body class="bg-[var(--color-cream)]">

  <!-- NAVBAR (fixe en haut) -->
  <header>
<?php 
  // Détecter la page active pour le lien surligné
  $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $base = $base_url ?? rtrim(APP_URL, '/');
?>
  <nav
    x-data="vitrineNav()"
    x-init="init()"
    :style="scrolled ? `
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 48px);
      max-width: 860px;
      background: rgba(250,247,242,0.92);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(201,168,76,0.2);
      border-radius: 60px;
      box-shadow: 0 8px 32px rgba(27,67,50,0.12);
      padding: 8px 28px;
      z-index: 50;
      transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
    ` : `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      width: 100%;
      background: transparent;
      border: none;
      border-radius: 0;
      box-shadow: none;
      padding: 20px 40px;
      z-index: 50;
      transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
    `"
  >
    <div style="display:flex; align-items:center; justify-content:space-between; max-width:1100px; margin:0 auto;">

      <!-- LOGO -->
      <a href="<?= $base ?>/">
        <img 
          src="<?= $base ?>/assets/logo.png" 
          alt="Ivoire Stay"
          style="height:42px; width:auto; object-fit:contain; transition: all 0.3s ease;"
        >
      </a>

      <!-- LIENS DESKTOP -->
      <div class="hidden md:flex items-center gap-2">
        <?php 
          $links = [
            ['label'=>'Accueil',      'href'=> $base . '/'],
            ['label'=>'Destinations', 'href'=> $base . '/search'],
            ['label'=>'À propos',     'href'=> $base . '/apropos'],
            ['label'=>'Tarifs',       'href'=> $base . '/tarifs'],
            ['label'=>'Contact',      'href'=> $base . '/contact'],
          ];
        ?>
        <?php foreach($links as $link): ?>
          <a href="<?= $link['href'] ?>" 
            :style="scrolled ? 
              'color:#1B4332;font-family:Inter,sans-serif;font-size:14px;font-weight:500;padding:8px 16px;border-radius:50px;text-decoration:none;transition:all 0.3s;' 
              : 
              'color:white;font-family:Inter,sans-serif;font-size:14px;font-weight:400;padding:8px 16px;border-radius:50px;text-decoration:none;transition:all 0.3s;'"
            onmouseover="this.style.background='rgba(201,168,76,0.12)'"
            onmouseout="this.style.background='transparent'"
          >
            <?= $link['label'] ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- BOUTONS DROITE -->
      <div class="flex items-center gap-2.5">
        <!-- Commencer : desktop uniquement -->
        <div class="hidden md:flex items-center gap-2.5">
          <!-- Commencer (toujours gold) -->
          <a href="<?= $base ?>/register" style="background:linear-gradient(135deg,#C9A84C,#A67C2E); color:white;font-family:Inter,sans-serif; font-size:14px;font-weight:600; padding:8px 22px;border-radius:50px; text-decoration:none; box-shadow:0 4px 16px rgba(201,168,76,0.35); transition:all 0.3s; white-space:nowrap;">
            Commencer
          </a>
        </div>

        <!-- Burger mobile -->
        <button
          @click="mobileOpen = !mobileOpen"
          class="md:hidden"
          :style="scrolled ? 'color:#1B4332;background:none;border:none;cursor:pointer;padding:4px;' : 'color:white;background:none;border:none;cursor:pointer;padding:4px;'"
        >
          <svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- MENU MOBILE (dropdown) -->
    <div 
      x-show="mobileOpen"
      x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0 -translate-y-2"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-150"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
      style="margin-top:12px; background:rgba(250,247,242,0.96); backdrop-filter:blur(24px); border:1px solid rgba(201,168,76,0.2); border-radius:24px; padding:16px;"
      class="md:hidden"
    >
      <?php foreach($links as $link): ?>
        <a href="<?= $link['href'] ?>" style="display:block; color:#1B4332; font-family:Inter,sans-serif; font-size:15px; font-weight:500; padding:12px 16px; border-radius:12px; text-decoration:none; transition:background 0.2s;" onmouseover="this.style.background='rgba(201,168,76,0.1)'" onmouseout="this.style.background='transparent'"><?= $link['label'] ?></a>
      <?php endforeach; ?>
      <div style="border-top:1px solid rgba(201,168,76,0.2); margin:8px 0; padding-top:8px; display:flex; flex-direction:column; gap:8px;">
        <a href="<?= $base ?>/register" style="display:block; text-align:center; background:#C9A84C; color:white; font-family:Inter,sans-serif; font-size:14px; font-weight:600; padding:10px; border-radius:12px; text-decoration:none;">Commencer</a>
      </div>
    </div>
  </nav>
  </header>

  <!-- POINT D'INJECTION DU CONTENU ENFANT -->
  <main>
    <?= $content ?>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer mt-16 text-sm">
    <div class="max-w-7xl mx-auto px-6 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Colonne 1 : Logo + tagline -->
        <div class="space-y-4">
          <a href="<?= $base ?>/" class="inline-block">
            <img src="<?= $base ?>/assets/logo.png" alt="Ivoire Stay" class="h-12 filter brightness-0 invert" />
          </a>
          <p class="text-[14px] max-w-xs">Gérez votre établissement avec élégance</p>
        </div>

        <!-- Colonne 2 : Navigation -->
        <div>
          <h4 class="font-display text-lg mb-3">Navigation</h4>
          <ul class="space-y-2">
            <li><a href="<?= $base ?>/">Accueil</a></li>
            <li><a href="<?= $base ?>/search">Destinations</a></li>
            <li><a href="<?= $base ?>/tarifs">Tarifs</a></li>
            <li><a href="<?= $base ?>/apropos">À propos</a></li>
          </ul>
        </div>

        <!-- Colonne 3 : SaaS -->
        <div>
          <h4 class="font-display text-lg mb-3">SaaS</h4>
          <ul class="space-y-2">
            <li><a href="<?= $base ?>/register">S'inscrire</a></li>
            <li><a href="<?= $base ?>/">Fonctionnalités</a></li>
            <li><a href="<?= $base ?>/contact">Support</a></li>
          </ul>
        </div>

        <!-- Colonne 4 : Contact -->
        <div>
          <h4 class="font-display text-lg mb-3">Contact</h4>
          <p>Abidjan, Côte d'Ivoire</p>
          <p class="mt-2">support@ivoire-stay.ci</p>
          <p class="mt-1">+225 01 23 45 67 89</p>
        </div>
      </div>
    </div>

    <div style="border-top:1px solid rgba(201,168,76,0.3)" class="py-4">
      <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-3">
        <p class="text-[13px]">&copy; <?= date('Y') ?> Ivoire Stay — Tous droits réservés</p>
        <div class="flex items-center gap-4">
          <a href="<?= $base ?>/apropos" class="text-[13px]">Politique</a>
          <a href="<?= $base ?>/contact" class="text-[13px]">Contact</a>
        </div>
      </div>
    </div>
  </footer>

</body>
</html>
