<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vérification email – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192.png" type="image/png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <script src="<?= $base_url ?>/assets/js/pages/verify-email.js"></script>
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
      <a class="lg-nav-link" href="<?= $base_url ?>/login">← Retour à la connexion</a>
    </div>

    <div class="lg-left-body">
      <div class="lg-tag">Espace professionnel</div>
      <h2 class="lg-headline">Confirmez<br><em>votre adresse.</em></h2>
      <div class="lg-rule"></div>
      <p class="lg-desc">
        Une dernière étape pour sécuriser votre compte et recevoir
        toutes vos notifications importantes.
      </p>
    </div>
  </div>

  <div class="lg-separator">
    <div class="lg-separator-dot"></div>
  </div>

  <div class="lg-right" x-data="verifyEmailPage('<?= $base_url ?>')" x-init="init()">

    <section class="lg-form-wrap">

      <a class="lg-mobile-back" href="<?= $base_url ?>/login">← Retour à la connexion</a>

      <div class="lg-form-header">
        <div class="lg-form-eyebrow">
          <div class="lg-form-eyebrow-line"></div>
          <span>Vérification</span>
        </div>
        <h1 class="lg-form-title">
          Confirmation de<br><em>votre email.</em>
        </h1>
      </div>

      <template x-if="loading">
        <div style="display:flex;align-items:center;gap:10px;color:rgba(27,67,50,0.5);font-family:'Inter',sans-serif;font-size:14px;">
          <div class="lg-spinner" style="border-color:rgba(27,67,50,0.2);border-top-color:#1B4332;"></div>
          <span>Vérification en cours…</span>
        </div>
      </template>

      <template x-if="!loading && error">
        <div class="lg-alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="error"></span>
        </div>
      </template>

      <template x-if="!loading && done">
        <div class="lg-alert" style="background:rgba(22,101,52,0.08);border-color:rgba(22,101,52,0.25);color:#166534;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>Email confirmé avec succès.</span>
        </div>
      </template>

      <button class="lg-btn lg-btn-gold" type="button" @click="window.location.href = '<?= $base_url ?>/login'" x-show="!loading">
        <span>Aller à la connexion →</span>
      </button>

      <p class="lg-form-footer" style="margin-top:20px;">
        <a href="<?= $base_url ?>/login">← Retour à la connexion</a>
      </p>

    </section>

  </div>

</body>
</html>
