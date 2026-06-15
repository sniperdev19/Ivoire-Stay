<?php $base = 'http://localhost/Ivoire-Stay/public'; ?>
<?php
/**
 * Layout principal pour la vitrine
 * Injecte : $title (string) et $content (HTML rendu de la page enfant)
 * Respecte le design system fourni (glassmorphisme, typographie, couleurs)
 */
$base = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#FAF7F2">
  <title><?= htmlspecialchars($title ?? 'Ivoire Stay') ?></title>

  <!-- Google Fonts : Cormorant Garamond (titres) + Inter (UI) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CDN (pas de build tooling) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js pour le menu mobile -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Variables couleurs et styles réutilisables -->
  <style>
    :root{
      --color-gold: #C9A84C;
      --color-gold-light: #E8D5A3;
      --color-gold-dark: #A67C2E;
      --color-forest: #1B4332;
      --color-forest-light: #2D6A4F;
      --color-forest-dark: #0F2B20;
      --color-cream: #FAF7F2;
      --color-cream-dark: #F0EBE1;
      --color-white-glass: rgba(255,255,255,0.72);
    }

    /* Design system global */
    html,body{height:100%;}
    body{background:var(--color-cream); font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; color:var(--color-forest);}
    .font-display{ font-family: 'Cormorant Garamond', serif; }

    /* Glassmorphisme utilitaires */
    .glass-card {
      background: rgba(255, 255, 255, 0.72);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(201, 168, 76, 0.25);
      border-radius: 24px;
      box-shadow: 0 8px 32px rgba(27, 67, 50, 0.08);
    }
    .glass-card-strong {
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(32px);
      -webkit-backdrop-filter: blur(32px);
      border: 1px solid rgba(201, 168, 76, 0.35);
      border-radius: 32px;
      box-shadow: 0 16px 48px rgba(27, 67, 50, 0.12);
    }

    /* Boutons */
    .btn-gold{
      background: linear-gradient(135deg, #C9A84C, #E8D5A3, #C9A84C);
      background-size: 200% auto;
      color: white;
      border-radius: 50px;
      padding: 12px 28px;
      font-weight: 600;
      transition: background-position 0.4s ease, transform 0.2s ease;
      font-family: 'Inter', sans-serif;
    }
    .btn-gold:hover{ background-position: right center; transform: translateY(-1px); }

    .btn-outline-gold{
      border: 1.5px solid var(--color-gold);
      color: var(--color-forest);
      border-radius: 50px;
      padding: 11px 28px;
      font-weight: 500;
      transition:all 0.3s ease;
      font-family: 'Inter', sans-serif;
      background: transparent;
    }
    .btn-outline-gold:hover{ background: var(--color-gold); color: white; }

    /* Navbar link underline animé */
    .nav-link{ position: relative; display: inline-block; padding: 6px 4px; }
    .nav-link::after{ content: ''; position: absolute; left:50%; transform: translateX(-50%); bottom:0; width:0; height:2px; background: var(--color-gold); transition: width 250ms ease; }
    .nav-link:hover::after{ width:60%; }

    /* Footer */
    .site-footer{ background: var(--color-forest); color: #F6EFE6; }
    .site-footer a{ color: #F6EFE6; transition: color 300ms ease; }
    .site-footer a:hover{ color: var(--color-gold); }

    /* Scrollbar élégante */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--color-cream); }
    ::-webkit-scrollbar-thumb { background: var(--color-gold); border-radius: 3px; }

    /* Mobile menu backdrop style */
    .mobile-drawer{ background: rgba(255,255,255,0.92); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border-bottom:1px solid rgba(201,168,76,0.18); }

  </style>

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
    x-data="{ scrolled: false, mobileOpen: false, init(){
      const isHome = window.location.pathname === '/'
        || window.location.pathname.endsWith('/public/')
        || window.location.pathname.endsWith('/public');

      if (!isHome) {
        this.scrolled = true;
      }

      window.addEventListener('scroll', () => {
        if (isHome) {
          this.scrolled = window.scrollY > 80;
        } else {
          this.scrolled = true;
        }
      });
    } }"
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
      <div style="display:flex; align-items:center; gap:8px;" class="hidden md:flex">
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
      <div style="display:flex;align-items:center;gap:10px;">
        <!-- Connexion -->
        <a href="<?= $base ?>/login"
          :style="scrolled ?
            'border:1.5px solid #C9A84C;color:#1B4332;background:transparent;font-family:Inter,sans-serif;font-size:14px;font-weight:500;padding:8px 20px;border-radius:50px;text-decoration:none;transition:all 0.3s;'
            :
            'border:1.5px solid rgba(255,255,255,0.6);color:white;background:transparent;font-family:Inter,sans-serif;font-size:14px;font-weight:500;padding:8px 20px;border-radius:50px;text-decoration:none;transition:all 0.3s;'"
        >
          Connexion
        </a>

        <!-- Commencer (toujours gold) -->
        <a href="<?= $base ?>/register" style="background:linear-gradient(135deg,#C9A84C,#A67C2E); color:white;font-family:Inter,sans-serif; font-size:14px;font-weight:600; padding:8px 22px;border-radius:50px; text-decoration:none; box-shadow:0 4px 16px rgba(201,168,76,0.35); transition:all 0.3s; white-space:nowrap;">
          Commencer
        </a>

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
        <a href="<?= $base ?>/login" style="display:block; text-align:center; border:1.5px solid #C9A84C; color:#1B4332; font-family:Inter,sans-serif; font-size:14px; padding:10px; border-radius:12px; text-decoration:none;">Connexion</a>
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
            <li><a href="<?= $base ?>/login">Connexion</a></li>
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
