<?php
// Page autonome de connexion SaaS.
/** @var string $base_url URL de base de l'app, injectée par Response::render(). */
$base_url = $base_url ?? rtrim(APP_URL, '/');
// Vient-on de finaliser une inscription ? (install obligatoire ensuite)
$justRegistered = isset($_GET['registered']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion – Ivoire Stay</title>

  <!-- PWA -->
  <meta name="theme-color" content="#0F2B20">
  <link rel="manifest" href="<?= $base_url ?>/manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Ivoire Stay">

  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <script defer src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/pages/login.js"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="pwaGate()">
  <div class="auth-bg" style="--auth-bg:url('<?= $base_url ?>/assets/bg_auth.jpg')"></div>
  <div class="auth-overlay"></div>
  <div class="deco-blob a"></div>
  <div class="deco-blob b"></div>
  <div class="deco-blob c"></div>

  <section class="info-left">
    <div class="float-card">
      <div class="float-card-title">Avis client</div>
      <p>"Ivoire Stay a transformé la gestion de mon hôtel. Je gagne 3h par jour et mes clients sont ravis."</p>
      <div style="display:flex; align-items:center; gap:10px; margin-top:14px;">
        <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#C9A84C,#A67C2E); display:flex; align-items:center; justify-content:center; color:white; font-size:11px; font-weight:700;">AK</div>
        <div>
          <div style="font-size:12px; font-weight:600; color:white;">Ama Kouassi</div>
          <div style="font-size:11px; color:rgba(255,255,255,0.45);">Hôtel Le Plateau, Abidjan</div>
        </div>
      </div>
    </div>
    <div class="float-card">
      <div class="float-card-title">En chiffres</div>
      <div class="stat-row"><span>Hôteliers actifs</span><span class="stat-val">500+</span></div>
      <div class="stat-row"><span>Réservations traitées</span><span class="stat-val">12K+</span></div>
      <div class="stat-row"><span>Uptime garanti</span><span class="stat-val">99.9%</span></div>
    </div>
  </section>

  <section class="info-right">
    <div class="float-card">
      <div class="float-card-title">Pourquoi Ivoire Stay ?</div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9.5 12.5l2.5 2 4-5"/></svg></span><span>Données sécurisées &amp; chiffrées</span></div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></span><span>Confirmation de réservation en temps réel</span></div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2"/><path d="M12 8V7m0 1v8m0 0v1"/><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span><span>Paiement Mobile Money intégré</span></div>
      <div class="info-item"><span class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M4.22 4.22l15.56 15.56"/></svg></span><span>Support disponible 24h/24</span></div>
    </div>
    <div class="float-card">
      <div class="float-card-title">Plan Starter</div>
      <p>Démarrez gratuitement. 10 chambres incluses, réservations illimitées.</p>
      <a href="<?= $base_url ?>/register" target="_blank" rel="noopener">Créer un compte gratuit →</a>
    </div>
  </section>

  <!-- FORMULAIRE DE CONNEXION — uniquement dans l'app installée (standalone) -->
  <template x-if="standalone">
  <section class="form-float" x-data="loginForm('<?= $base_url ?>')">
    <div class="form-title">
      <div class="form-icon-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;color:#C9A84C;"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/><path d="M17 10V8a5 5 0 00-10 0v2"/></svg>
      </div>
      <h1>Connexion</h1>
      <p>Accédez à votre espace hôtelier</p>
    </div>
    <template x-if="error"><div class="alert-err" x-text="error"></div></template>

    <div class="field-group">
      <label class="field-label" for="loginEmail">Adresse email</label>
      <div class="field-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/><path d="M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <input id="loginEmail" class="field-input" type="email" placeholder="votre@email.ci" x-model="form.email" @keydown.enter="submit()">
      </div>
    </div>

    <div class="field-group">
      <label class="field-label" for="loginPassword">Mot de passe</label>
      <div class="field-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/><path d="M17 10V8a5 5 0 00-10 0v2"/></svg>
        <input id="loginPassword" class="field-input with-action" :type="showPass ? 'text' : 'password'" placeholder="••••••••" x-model="form.password" @keydown.enter="submit()">
        <button type="button" class="field-toggle" @click="showPass = !showPass" x-text="showPass ? 'Masquer' : 'Afficher'"></button>
      </div>
    </div>

    <div class="field-group" style="text-align:right;">
      <a class="auth-nav-link back" href="<?= $base_url ?>/login">Mot de passe oublié ?</a>
    </div>

    <button class="btn-submit" type="button" @click="submit()" :disabled="loading">
      <span x-show="!loading">Se connecter</span>
      <span x-show="loading">Connexion...</span>
      <div x-show="loading" class="spinner"></div>
    </button>

    <div class="form-footer-link">Pas encore de compte ? <a href="<?= $base_url ?>/register" target="_blank" rel="noopener">Créer un compte →</a></div>
  </section>
  </template>

  <!-- ÉCRAN D'INSTALLATION — affiché hors de l'app (navigateur / vitrine) -->
  <template x-if="!standalone">
  <section class="form-float">
    <div class="install-card">
    <div class="form-title">
      <div class="form-icon-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;color:#C9A84C;">
          <path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
        </svg>
      </div>
      <h1><?= $justRegistered ? 'Compte créé' : 'Application requise' ?></h1>
      <p><?= $justRegistered
            ? "Dernière étape : installez l'application pour accéder à votre espace."
            : "Pour votre sécurité, la connexion se fait uniquement depuis l'application Ivoire Stay installée." ?></p>
    </div>

    <?php if ($justRegistered): ?>
    <div class="alert-ok">✓ Votre compte hôtelier a bien été créé.</div>
    <?php endif; ?>

    <!-- Bouton d'installation natif (Android / PC — Chrome, Edge…) -->
    <button class="btn-submit" type="button" x-show="installable" @click="install()">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
        <path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
      </svg>
      Installer l'application
    </button>

    <!-- iOS Safari : pas d'invite auto → instructions manuelles -->
    <div class="pwa-steps" x-show="isIOS && !installable" x-cloak>
      <div class="pwa-step"><span>1</span> Touchez l'icône <strong>Partager</strong> <span class="ios-share">⬆️</span> en bas de Safari.</div>
      <div class="pwa-step"><span>2</span> Choisissez <strong>« Sur l'écran d'accueil »</strong>.</div>
      <div class="pwa-step"><span>3</span> Ouvrez <strong>Ivoire Stay</strong> depuis l'écran d'accueil.</div>
    </div>

    <!-- Android / PC sans invite disponible (ou navigateur non compatible) -->
    <div class="pwa-steps" x-show="!isIOS && !installable" x-cloak>
      <div class="pwa-step"><span>1</span> Ouvrez le menu de votre navigateur <strong>(⋮)</strong>.</div>
      <div class="pwa-step"><span>2</span> Choisissez <strong>« Installer l'application »</strong> ou <strong>« Ajouter à l'écran d'accueil »</strong>.</div>
      <div class="pwa-step"><span>3</span> Lancez <strong>Ivoire Stay</strong> pour vous connecter.</div>
    </div>

    <div class="form-footer-link" x-show="!justInstalled">
      Déjà installée ? Ouvrez l'app <strong style="color:#C9A84C;">Ivoire Stay</strong> sur votre appareil.
    </div>
    <div class="form-footer-link" x-show="justInstalled" x-cloak style="color:#4ade80;">
      ✓ Installée ! Ouvrez l'application <strong>Ivoire Stay</strong> depuis votre écran d'accueil pour vous connecter.
    </div>
    </div>
  </section>
  </template>
</body>
</html>
