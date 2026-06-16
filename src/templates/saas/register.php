<?php
// Page autonome d'inscription SaaS.
/** @var string $base_url URL de base de l'app, injectée par Response::render(). */
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inscription – Ivoire Stay</title>

  <!-- PWA -->
  <meta name="theme-color" content="#0F2B20">
  <link rel="manifest" href="<?= $base_url ?>/manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Ivoire Stay">

  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/register.css">
  <script defer src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/pages/register.js"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
  <div class="auth-bg" style="--auth-bg:url('<?= $base_url ?>/assets/bg_auth.jpg')"></div>
  <div class="auth-overlay"></div>
  <div class="deco-blob a"></div>
  <div class="deco-blob b"></div>
  <div class="deco-blob c"></div>

  <nav class="auth-nav">
    <a class="auth-nav-brand" href="<?= $base_url ?>/">
      <img src="<?= $base_url ?>/assets/logo.png" alt="Ivoire Stay">
      <div class="auth-nav-brand-text">
        <span>Ivoire Stay</span>
        <span>SaaS Hôtelier</span>
      </div>
    </a>
    <div class="auth-nav-divider"></div>
    <div class="auth-nav-links">
      <a class="auth-nav-link" href="<?= $base_url ?>/login">Connexion</a>
      <a class="auth-nav-link primary" href="<?= $base_url ?>/register">S'inscrire</a>
      <a class="auth-nav-link back" href="<?= $base_url ?>/">← Site</a>
    </div>
  </nav>

  <section class="info-left">
    <div class="float-card">
      <div class="float-card-title">Déjà inscrit ?</div>
      <p>Si vous avez déjà un compte, accédez directement à votre espace.</p>
      <a class="auth-nav-link primary" href="<?= $base_url ?>/login">Se connecter →</a>
    </div>
    <div class="float-card">
      <div class="float-card-title">Ce qui vous attend</div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l2 2"/></svg></span><span><strong>Créez votre compte</strong><br>2 minutes suffisent</span></div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21h16V8H4v13z"/><path d="M9 21V9"/><path d="M15 21V9"/><path d="M4 8l8-5 8 5"/></svg></span><span><strong>Configurez votre établissement</strong><br>Hôtel ou résidence</span></div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9.5 12.5l2.5 2 4-5"/></svg></span><span><strong>Recevez vos premières réservations</strong><br>En quelques clics</span></div>
    </div>
  </section>

  <section class="info-right">
    <div class="float-card">
      <div class="float-card-title">Plans disponibles</div>
      <div class="plan-pill"><strong>STARTER</strong><span class="plan-badge badge-free">Gratuit</span></div>
      <div class="plan-pill"><strong>PRO</strong><span class="plan-badge badge-pro">9 000 FCFA/mois</span></div>
      <div class="plan-pill"><strong>BUSINESS</strong><span class="plan-badge badge-biz">20 000 FCFA/mois</span></div>
    </div>
    <div class="float-card">
      <div class="float-card-title">Nos engagements</div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9.5 12.5l2.5 2 4-5"/></svg></span><span>Données hébergées en Afrique</span></div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V5m0 3a5 5 0 015 5 5 5 0 01-5 5"/><path d="M8 12h4l2 2"/></svg></span><span>Mise en ligne en moins de 5 minutes</span></div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v1"/><path d="M12 8a4 4 0 00-4 4 4 4 0 004 4"/><path d="M7 12h5l2 2"/></svg></span><span>Annulation à tout moment</span></div>
    </div>
  </section>

  <section class="form-float" x-data="registerForm('<?= $base_url ?>')">
    <div class="form-title">
      <div class="form-icon-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;color:#C9A84C;"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/><path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2"/></svg>
      </div>
      <h1>Créer un compte</h1>
      <p>Démarrez gratuitement, sans carte bancaire</p>
    </div>
    <template x-if="error"><div class="alert-err" x-text="error"></div></template>
    <div class="stepper-wrap">
      <div class="s-circle" :class="step >= 1 ? 'on' : ''">1</div>
      <div class="s-line" :class="step >= 2 ? 'on' : ''"></div>
      <div class="s-circle" :class="step >= 2 ? 'on' : ''">2</div>
    </div>
    <div class="stepper-labels"><span>Compte</span><span>Établissement</span></div>

    <div x-show="step === 1" x-cloak style="display:grid; gap:14px 16px; grid-template-columns: repeat(2, minmax(0, 1fr));">
      <div class="field-group" style="margin-bottom:0;">
        <label class="field-label" for="registerName">Nom complet</label>
        <div class="field-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/><path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2"/></svg>
          <input id="registerName" class="field-input" type="text" placeholder="Votre nom complet" x-model="form.name">
        </div>
      </div>
      <div class="field-group" style="margin-bottom:0;">
        <label class="field-label" for="registerPhone">Téléphone</label>
        <div class="field-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.95.34 1.88.63 2.77a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.31-1.31a2 2 0 012.11-.45c.89.29 1.82.5 2.77.63a2 2 0 011.72 2z"/></svg>
          <input id="registerPhone" class="field-input" type="tel" placeholder="+225 01 23 45 67" x-model="form.phone">
        </div>
      </div>
      <div class="field-group" style="grid-column: span 2; margin-bottom:0;">
        <label class="field-label" for="registerEmail">Adresse email</label>
        <div class="field-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/><path d="M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <input id="registerEmail" class="field-input" type="email" placeholder="votre@email.ci" x-model="form.email">
        </div>
      </div>
      <div class="field-group" style="margin-bottom:0;">
        <label class="field-label" for="registerPassword">Mot de passe</label>
        <div class="field-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/><path d="M17 10V8a5 5 0 00-10 0v2"/></svg>
          <input id="registerPassword" class="field-input with-action" :type="showPass ? 'text' : 'password'" placeholder="••••••••" x-model="form.password">
          <button type="button" class="field-toggle" @click="showPass = !showPass" x-text="showPass ? 'Masquer' : 'Afficher'"></button>
        </div>
      </div>
      <div class="field-group" style="margin-bottom:0;">
        <label class="field-label" for="registerPasswordConfirm">Confirmer MDP</label>
        <div class="field-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/><path d="M17 10V8a5 5 0 00-10 0v2"/></svg>
          <input id="registerPasswordConfirm" class="field-input" type="password" placeholder="••••••••" x-model="form.password_confirm">
        </div>
      </div>
    </div>
    <button class="btn-submit" type="button" @click="validateStep1()" style="margin-top:20px;">Continuer →</button>

    <div x-show="step === 2" x-cloak style="display:grid; gap:18px;">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <div><div class="field-label" style="margin-bottom:0;">Votre établissement</div></div>
        <button type="button" class="field-toggle" @click="step = 1; error = null">← Retour</button>
      </div>
      <div class="field-group">
        <label class="field-label" for="establishmentName">Nom établissement</label>
        <div class="field-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21h16V8H4v13z"/><path d="M9 21V9"/><path d="M15 21V9"/><path d="M4 8l8-5 8 5"/></svg>
          <input id="establishmentName" class="field-input" type="text" placeholder="Nom de l'établissement" x-model="form.establishment_name">
        </div>
      </div>
      <div class="field-group">
        <label class="field-label">Type</label>
        <div class="toggle-group">
          <button type="button" class="type-btn" :class="form.establishment_type === 'hotel' ? 'active' : ''" @click="form.establishment_type = 'hotel'">Hôtel</button>
          <button type="button" class="type-btn" :class="form.establishment_type === 'residence' ? 'active' : ''" @click="form.establishment_type = 'residence'">Résidence</button>
        </div>
      </div>
      <button class="btn-submit" type="button" @click="submit()" :disabled="loading">
        <span x-show="!loading">Créer mon compte</span>
        <span x-show="loading">Création en cours...</span>
        <div x-show="loading" class="spinner"></div>
      </button>
    </div>

    <div class="form-footer-link">Déjà un compte ? <a href="<?= $base_url ?>/login">Se connecter →</a></div>
  </section>
</body>
</html>
