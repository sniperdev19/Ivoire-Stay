<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Devenir agent – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base_url ?>/manifest-agent.webmanifest">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192-agent.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon-agent.png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <script src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script src="<?= $base_url ?>/assets/js/pages/agent-register.js"></script>
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
      <div class="lg-tag">Devenir agent commercial</div>
      <h2 class="lg-headline">Recommandez,<br><em>soyez récompensé.</em></h2>
      <div class="lg-rule"></div>
      <p class="lg-desc">
        Aidez les gérants d'hôtels et résidences à s'inscrire sur Afristay.
        Scannez leur QR code une fois inscrits, et touchez un versement Mobile
        Money tous les 5 abonnements Pro ou Business.
      </p>
    </div>
  </div>

  <div class="lg-separator">
    <div class="lg-separator-dot"></div>
  </div>

  <div class="lg-right" x-data="agentRegisterPage('<?= $base_url ?>')" x-init="init()">

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
        <span>Inscription · Espace agents</span>
        <div class="lg-gate-eyebrow-line"></div>
      </div>

      <h1 class="lg-gate-title">
        Inscription via<br><em>l'application.</em>
      </h1>

      <p class="lg-gate-desc">
        L'espace agent est uniquement accessible depuis l'application
        Afristay installée sur votre appareil.
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
          <span>Inscription · Espace agents</span>
        </div>
        <h1 class="lg-form-title">
          Devenez<br><em>agent Afristay.</em>
        </h1>
        <p class="lg-form-sub">Quatre champs suffisent pour démarrer</p>
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
        <label class="lg-label">Nom complet</label>
        <div class="lg-input-wrap">
          <input class="lg-input" type="text" placeholder="Ex. Adama Koné"
            x-model="form.nom">
        </div>
      </div>

      <div class="lg-field">
        <label class="lg-label">Numéro de téléphone</label>
        <div class="lg-input-wrap">
          <input class="lg-input" type="tel" placeholder="07 XX XX XX XX"
            x-model="form.numero">
        </div>
      </div>

      <div class="lg-field">
        <label class="lg-label">Opérateur Mobile Money</label>
        <div class="lg-input-wrap">
          <select class="lg-input" x-model="form.operateur_money">
            <option value="">Choisir…</option>
            <option value="orange">Orange Money</option>
            <option value="mtn">MTN Money</option>
            <option value="moov">Moov Money</option>
            <option value="wave">Wave</option>
          </select>
        </div>
      </div>

      <div class="lg-field">
        <label class="lg-label">Mot de passe</label>
        <div class="lg-input-wrap">
          <input class="lg-input has-toggle" :type="showPass ? 'text' : 'password'"
            placeholder="••••••••" x-model="form.password">
          <button type="button" class="lg-toggle-btn" @click="showPass = !showPass"
            x-text="showPass ? 'Masquer' : 'Afficher'"></button>
        </div>
      </div>

      <div class="lg-field">
        <label class="lg-label">Confirmer le mot de passe</label>
        <div class="lg-input-wrap">
          <input class="lg-input" :type="showPass ? 'text' : 'password'"
            placeholder="••••••••" x-model="form.password_confirm" @keydown.enter="submit()">
        </div>
      </div>

      <button class="lg-btn lg-btn-gold" type="button" @click="submit()" :disabled="loading">
        <div class="lg-spinner" x-show="loading"></div>
        <span x-show="!loading">Créer mon compte agent</span>
        <span x-show="loading">Création en cours…</span>
      </button>

      <p class="lg-form-footer" style="margin-top:20px;">
        Déjà agent ?
        <a href="<?= $base_url ?>/agent/login">Se connecter →</a>
      </p>

    </section>

  </div>

</body>
</html>
