<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Invitation d'équipe – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192.png" type="image/png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <script src="<?= $base_url ?>/assets/js/pages/team-invite.js"></script>
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
      <h2 class="lg-headline">Rejoignez<br><em>votre équipe.</em></h2>
      <div class="lg-rule"></div>
      <p class="lg-desc">
        Créez votre accès réceptionniste pour gérer les réservations
        et encaisser les paiements.
      </p>
    </div>
  </div>

  <div class="lg-separator">
    <div class="lg-separator-dot"></div>
  </div>

  <div class="lg-right" x-data="teamInvitePage('<?= $base_url ?>')" x-init="init()">

    <section class="lg-form-wrap">

      <a class="lg-mobile-back" href="<?= $base_url ?>/login">← Retour à la connexion</a>

      <div class="lg-form-header">
        <div class="lg-form-eyebrow">
          <div class="lg-form-eyebrow-line"></div>
          <span>Invitation</span>
        </div>
        <h1 class="lg-form-title" x-show="establishmentName">
          Rejoignez <em x-text="establishmentName"></em>
        </h1>
        <h1 class="lg-form-title" x-show="!establishmentName && !loadingInfo && !infoError">
          Créez votre <em>accès équipe.</em>
        </h1>
        <p class="lg-form-sub" x-show="email">Compte lié à <span x-text="email"></span></p>
      </div>

      <div x-show="loadingInfo" style="display:flex;justify-content:center;padding:20px 0;">
        <svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:24px;height:24px;stroke:#C9A84C;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="4" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="4" stroke-linecap="round"></path></svg>
      </div>

      <template x-if="infoError">
        <div class="lg-alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="infoError"></span>
        </div>
      </template>

      <template x-if="error">
        <div class="lg-alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="error"></span>
        </div>
      </template>

      <template x-if="done">
        <div class="lg-alert" style="background:rgba(22,101,52,0.08);border-color:rgba(22,101,52,0.25);color:#166534;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>Compte créé avec succès. Connectez-vous depuis l'application installée sur votre appareil.</span>
        </div>
      </template>

      <template x-if="!loadingInfo && !infoError && !done">
        <div>
          <div class="lg-field">
            <label class="lg-label">Nom complet</label>
            <div class="lg-input-wrap">
              <span class="lg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </span>
              <input class="lg-input" type="text" placeholder="Ex : Awa Koné" x-model="name" @keydown.enter="submit()">
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
                placeholder="••••••••" x-model="password" @keydown.enter="submit()">
              <button type="button" class="lg-toggle-btn" @click="showPass = !showPass"
                x-text="showPass ? 'Masquer' : 'Afficher'"></button>
            </div>
          </div>

          <button class="lg-btn lg-btn-gold" type="button" @click="submit()" :disabled="loading">
            <div class="lg-spinner" x-show="loading"></div>
            <span x-show="!loading">Créer mon compte</span>
            <span x-show="loading">Création…</span>
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
