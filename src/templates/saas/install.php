<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Installer l'application – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base_url ?>/manifest.webmanifest">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon.png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <!-- pwa.js d'abord (sans defer) : capture beforeinstallprompt + enregistre le SW -->
  <script src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script src="<?= $base_url ?>/assets/js/pages/install.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/alpine.min.js"></script>
</head>
<body>

  <div class="lg-left">
    <div class="lg-ghost">AS</div>
    <div class="lg-radial"></div>

    <div class="lg-brand">
      <a class="lg-brand-inner" href="<?= $base_url ?>/">
        <div class="lg-brand-sq">AS</div>
        <div class="lg-brand-text">
          <strong>Afri <span style="color:#C9A84C;">Stay</span></strong>
          <span>SaaS Hôtelier</span>
        </div>
      </a>
      <a class="lg-nav-link" href="<?= $base_url ?>/">← Retour au site</a>
    </div>

    <div class="lg-left-body">
      <div class="lg-tag">Dernière étape</div>
      <h2 class="lg-headline">Installez<br><em>l'application.</em></h2>
      <div class="lg-rule"></div>
      <p class="lg-desc">
        Votre compte est prêt. Installez Afristay sur cet appareil
        pour accéder à votre tableau de bord hôtelier. C'est
        obligatoire, une seule fois par appareil.
      </p>
    </div>
  </div>

  <div class="lg-separator">
    <div class="lg-separator-dot"></div>
  </div>

  <div class="lg-right" x-data="installPage('<?= $base_url ?>')" x-init="init()">

    <!-- ═══ Déjà dans l'app : confirmation + suite ═══ -->
    <div class="lg-gate" x-show="isApp" x-cloak>

      <div class="lg-gate-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>

      <div class="lg-gate-eyebrow">
        <div class="lg-gate-eyebrow-line"></div>
        <span>Application installée</span>
        <div class="lg-gate-eyebrow-line"></div>
      </div>

      <h1 class="lg-gate-title">
        C'est prêt,<br><em>bienvenue.</em>
      </h1>

      <p class="lg-gate-desc">
        L'application est installée. Vous pouvez maintenant vous connecter.
      </p>

      <button class="lg-btn lg-btn-gold lg-gate-install-btn" type="button" @click="goToLogin()">
        <span>Aller à la connexion</span>
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </button>

    </div>

    <!-- ═══ Pas encore installée : instructions par plateforme ═══ -->
    <div class="lg-gate" x-show="!isApp" x-cloak>

      <div class="lg-gate-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0
            0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
        </svg>
      </div>

      <div class="lg-gate-eyebrow">
        <div class="lg-gate-eyebrow-line"></div>
        <span>Installation requise</span>
        <div class="lg-gate-eyebrow-line"></div>
      </div>

      <h1 class="lg-gate-title">
        Installez<br><em>Afristay.</em>
      </h1>

      <p class="lg-gate-desc">
        L'espace hôtelier n'est accessible que depuis l'application
        installée sur votre appareil (Android, iPhone/iPad ou PC).
      </p>

      <button class="lg-btn lg-btn-gold lg-gate-install-btn" type="button"
        @click="install()"
        x-show="installFeedback !== 'ios'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span x-text="installFeedback === 'unavailable' ? 'Application déjà installée ?' : 'Installer l\'application'"></span>
      </button>

      <div x-show="installFeedback === 'unavailable'"
        style="font-size:12px;color:rgba(27,67,50,0.5);margin-top:-4px;text-align:center;">
        Ouvrez dans Chrome ou Edge pour installer l'application, ou suivez les instructions Safari ci-dessous.
      </div>

      <div class="lg-gate-steps">
        <div class="lg-gate-step">
          <span class="lg-gate-step-num">1</span>
          <div>
            <strong>Chrome / Edge (Android &amp; PC)</strong>
            Menu <em>⋮</em> → <em>« Installer l'application »</em>
          </div>
        </div>
        <div class="lg-gate-step">
          <span class="lg-gate-step-num">2</span>
          <div>
            <strong>Safari (iPhone / iPad)</strong>
            Icône Partager <em>□↑</em> → <em>« Sur l'écran d'accueil »</em>
          </div>
        </div>
      </div>

      <div x-show="installFeedback === 'ios'"
        x-transition
        style="background:rgba(201,168,76,0.06);border:1px solid rgba(201,168,76,0.2);border-radius:14px;padding:16px 20px;margin-top:4px;">
        <p style="font-family:'Inter',sans-serif;font-size:13px;color:#1B4332;font-weight:600;margin-bottom:8px;">
          Installer sur iPhone / iPad
        </p>
        <ol style="font-family:'Inter',sans-serif;font-size:13px;color:rgba(27,67,50,0.65);line-height:1.8;padding-left:18px;">
          <li>Appuyez sur l'icône <strong>Partager</strong> <em>□↑</em> en bas de Safari</li>
          <li>Faites défiler et choisissez <strong>« Sur l'écran d'accueil »</strong></li>
          <li>Appuyez sur <strong>« Ajouter »</strong>, puis rouvrez Afristay depuis l'icône créée</li>
        </ol>
        <button type="button" @click="installFeedback = null"
          style="margin-top:10px;font-size:12px;color:rgba(27,67,50,0.45);background:none;border:none;cursor:pointer;text-decoration:underline;">
          Fermer
        </button>
      </div>

      <a class="lg-vitrine-link" href="<?= $base_url ?>/login">
        Déjà installée ? Ouvrez l'application puis connectez-vous →
      </a>

    </div>

  </div>

</body>
</html>
