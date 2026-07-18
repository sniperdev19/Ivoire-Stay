<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mot de passe oublié – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192.png" type="image/png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <script src="<?= $base_url ?>/assets/js/pages/forgot-password.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/alpine.min.js"></script>
</head>
<body>

  <div class="lg-left">
    <div class="lg-ghost">IS</div>
    <div class="lg-radial"></div>

    <div class="lg-brand">
      <a class="lg-brand-inner" href="<?= $base_url ?>/">
        <div class="lg-brand-sq">IS</div>
        <div class="lg-brand-text">
          <strong>Afristay</strong>
          <span>SaaS Hôtelier</span>
        </div>
      </a>
      <a class="lg-nav-link" href="<?= $base_url ?>/login">← Retour à la connexion</a>
    </div>

    <div class="lg-left-body">
      <div class="lg-tag">Espace professionnel</div>
      <h2 class="lg-headline">Récupérez<br><em>votre accès.</em></h2>
      <div class="lg-rule"></div>
      <p class="lg-desc">
        Entrez l'adresse email de votre compte, nous vous envoyons
        un lien pour choisir un nouveau mot de passe.
      </p>
    </div>
  </div>

  <div class="lg-separator">
    <div class="lg-separator-dot"></div>
  </div>

  <div class="lg-right" x-data="forgotPasswordPage('<?= $base_url ?>')">

    <section class="lg-form-wrap">

      <div class="lg-form-header">
        <div class="lg-form-eyebrow">
          <div class="lg-form-eyebrow-line"></div>
          <span>Mot de passe oublié</span>
        </div>
        <h1 class="lg-form-title">
          Réinitialiser<br><em>votre mot de passe.</em>
        </h1>
        <p class="lg-form-sub">Un lien de réinitialisation valable 1 heure vous sera envoyé par email</p>
      </div>

      <template x-if="error">
        <div class="lg-alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="error"></span>
        </div>
      </template>

      <template x-if="sent">
        <div class="lg-alert" style="background:rgba(22,101,52,0.08);border-color:rgba(22,101,52,0.25);color:#166534;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="message"></span>
        </div>
      </template>

      <template x-if="!sent">
        <div>
          <div class="lg-field">
            <label class="lg-label">Adresse email</label>
            <div class="lg-input-wrap">
              <span class="lg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
              </span>
              <input class="lg-input" type="email" placeholder="votre@email.ci"
                x-model="email" @keydown.enter="submit()">
            </div>
          </div>

          <button class="lg-btn lg-btn-gold" type="button" @click="submit()" :disabled="loading">
            <div class="lg-spinner" x-show="loading"></div>
            <span x-show="!loading">Envoyer le lien</span>
            <span x-show="loading">Envoi en cours…</span>
          </button>
        </div>
      </template>

      <p class="lg-form-footer" style="margin-top:20px;">
        <a href="<?= $base_url ?>/login">← Retour à la connexion</a>
      </p>

    </section>

  </div>

</body>
</html>
