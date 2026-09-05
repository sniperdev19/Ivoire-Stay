<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion agent – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base_url ?>/manifest-agent.webmanifest">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192-agent.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon-agent.png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <!-- pwa.js d'abord (sans defer) : capture beforeinstallprompt + enregistre le SW -->
  <script src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script src="<?= $base_url ?>/assets/js/pages/agent-login.js"></script>
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
          <span>Espace agents</span>
        </div>
      </a>
      <a class="lg-nav-link" href="<?= $base_url ?>/">← Retour au site</a>
    </div>

    <div class="lg-left-body">
      <div class="lg-tag">Agent commercial</div>
      <h2 class="lg-headline">Faites signer,<br><em>gagnez plus.</em></h2>
      <div class="lg-rule"></div>
      <p class="lg-desc">
        Démarchez des hôtels et résidences, faites-les inscrire sur Afristay,
        et scannez leur QR code pour être crédité de chaque parrainage.
      </p>
    </div>
  </div>

  <div class="lg-separator">
    <div class="lg-separator-dot"></div>
  </div>

  <!-- Panneau droit : gate PWA ou formulaire selon le contexte -->
  <div class="lg-right" x-data="agentLoginPage('<?= $base_url ?>')" x-init="init()">

    <a class="lg-mobile-back" href="<?= $base_url ?>/">← Retour au site</a>

    <!-- ═══ GATE : navigateur normal (app non installée) ═══ -->
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
        <span>Accès sécurisé · Espace agents</span>
        <div class="lg-gate-eyebrow-line"></div>
      </div>

      <h1 class="lg-gate-title">
        Connexion via<br><em>l'application.</em>
      </h1>

      <p class="lg-gate-desc">
        Pour protéger votre espace agent, la connexion est
        uniquement disponible depuis l'application Afristay
        installée sur votre appareil.
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
        Ouvrez dans Chrome ou Edge pour installer l'application.
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
          <li>Appuyez sur <strong>« Ajouter »</strong></li>
        </ol>
        <button type="button" @click="installFeedback = null"
          style="margin-top:10px;font-size:12px;color:rgba(27,67,50,0.45);background:none;border:none;cursor:pointer;text-decoration:underline;">
          Fermer
        </button>
      </div>

      <a class="lg-vitrine-link" href="<?= $base_url ?>/">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Retour à la vitrine publique
      </a>

    </div>

    <!-- ═══ FORMULAIRE : app installée ═══ -->
    <section class="lg-form-wrap" x-show="isApp" x-cloak>

      <div class="lg-form-header">
        <div class="lg-form-eyebrow">
          <div class="lg-form-eyebrow-line"></div>
          <span>Connexion · Espace agents</span>
        </div>
        <h1 class="lg-form-title">
          Bon retour<br><em>parmi nous.</em>
        </h1>
        <p class="lg-form-sub">Accédez à votre tableau de bord agent en quelques secondes</p>
      </div>

      <template x-if="error">
        <div class="lg-alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="error"></span>
        </div>
      </template>

      <div class="lg-field">
        <label class="lg-label">Numéro de téléphone</label>
        <div class="lg-input-wrap">
          <span class="lg-input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
            </svg>
          </span>
          <input class="lg-input" type="tel" placeholder="07 XX XX XX XX"
            x-model="form.numero" @keydown.enter="submit()">
        </div>
      </div>

      <div class="lg-field">
        <label class="lg-label">Mot de passe</label>
        <div class="lg-input-wrap">
          <span class="lg-input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v3h8z"/>
            </svg>
          </span>
          <input class="lg-input has-toggle" :type="showPass ? 'text' : 'password'"
            placeholder="••••••••" x-model="form.password" @keydown.enter="submit()">
          <button type="button" class="lg-toggle-btn" @click="showPass = !showPass"
            x-text="showPass ? 'Masquer' : 'Afficher'"></button>
        </div>
      </div>

      <button class="lg-btn lg-btn-gold" type="button" @click="submit()" :disabled="loading">
        <div class="lg-spinner" x-show="loading"></div>
        <span x-show="!loading">Se connecter</span>
        <span x-show="loading">Connexion en cours…</span>
        <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </button>

      <!-- Raccourci facultatif : empreinte/Face ID/Windows Hello, en plus du mot de passe ci-dessus (jamais un remplacement) -->
      <button class="lg-btn lg-btn-secondary" style="margin-top:10px;"
        type="button" x-show="biometricAvailable" @click="loginWithBiometric()" :disabled="loading || biometricLoading">
        <div class="lg-spinner" x-show="biometricLoading"></div>
        <svg x-show="!biometricLoading" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5c0-2.76 2.24-5 5-5s5 2.24 5 5v1.5m-9-1.5c0-.5.05-1 .15-1.46M17 13v1a5 5 0 01-9.9 1M12 8.5v6M9.5 9.8v6.7M14.5 9.8V15"/>
        </svg>
        <span x-show="!biometricLoading">Se connecter avec l'empreinte</span>
        <span x-show="biometricLoading">Vérification…</span>
      </button>

      <p class="lg-form-footer" style="margin-top:20px;">
        Pas encore agent ?
        <a href="<?= $base_url ?>/agent/register">Créer un compte agent →</a>
      </p>

    </section>

  </div>

</body>
</html>
