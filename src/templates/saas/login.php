<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base_url ?>/manifest.webmanifest">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon.png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/login.css">
  <!-- pwa.js d'abord (sans defer) : capture beforeinstallprompt + enregistre le SW -->
  <script src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script src="<?= $base_url ?>/assets/js/pages/login.js"></script>
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
      <div class="lg-tag">Espace professionnel</div>
      <h2 class="lg-headline">Votre hôtel,<br><em>enfin simple.</em></h2>
      <div class="lg-rule"></div>
      <p class="lg-desc">
        Gérez vos réservations, chambres et finances
        depuis une interface pensée pour le marché ivoirien.
      </p>

      <div class="lg-testimonial">
        <span class="lg-quote-mark">"</span>
        <p class="lg-quote-text">
          Afristay a transformé la gestion de mon hôtel.
          Je gagne 3h par jour et mes clients sont ravis.
        </p>
        <div class="lg-quote-author">
          <div class="lg-author-av">AK</div>
          <div>
            <span class="lg-author-name">Ama Kouassi</span>
            <span class="lg-author-role">Hôtel Le Plateau, Abidjan</span>
          </div>
        </div>
      </div>

      <div class="lg-stats">
        <div class="lg-stat">
          <span class="lg-stat-val">500+</span>
          <span class="lg-stat-label">Hôteliers</span>
        </div>
        <div class="lg-stat">
          <span class="lg-stat-val">12K+</span>
          <span class="lg-stat-label">Réservations</span>
        </div>
        <div class="lg-stat">
          <span class="lg-stat-val">99.9%</span>
          <span class="lg-stat-label">Uptime</span>
        </div>
      </div>
    </div>
  </div>

  <div class="lg-separator">
    <div class="lg-separator-dot"></div>
  </div>

  <!-- Panneau droit : gate PWA ou formulaire selon le contexte -->
  <div class="lg-right" x-data="loginPage('<?= $base_url ?>')" x-init="init()">

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
        <span>Accès sécurisé · Espace hôtelier</span>
        <div class="lg-gate-eyebrow-line"></div>
      </div>

      <h1 class="lg-gate-title">
        Connexion via<br><em>l'application.</em>
      </h1>

      <p class="lg-gate-desc">
        Pour protéger votre espace hôtelier, la connexion est
        uniquement disponible depuis l'application Afristay
        installée sur votre appareil.
      </p>

      <!-- Bouton install (Chrome/Edge) ou instructions iOS -->
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

      <!-- Feedback non-compatible -->
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

      <!-- Instructions iOS affichées après clic -->
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

    <!-- ═══ ÉCRAN VERROUILLÉ : empreinte déjà activée sur cet appareil ═══
         Deux temps façon écran de verrouillage mobile : d'abord un écran
         "verrouillé" plein cadre, puis un bas-de-page qui présente le
         capteur au clic sur "Déverrouiller". Toujours réversible vers le
         formulaire classique via "Utiliser mes identifiants". -->
    <div class="lg-lock" x-show="isApp && biometricFirstMode" x-cloak>
      <div class="lg-lock-radial"></div>

      <!-- Zone haute toujours montée (flex:1, jamais retirée du flux) : évite que le
           bas-de-page ne se retrouve seul face à un grand vide une fois relevé — cf.
           bug de rendu constaté le 2026-08-11. Son contenu bascule entre le cadenas
           (verrouillé) et un simple monogramme de marque (bas-de-page relevé). -->
      <div class="lg-lock-top">
        <div class="lg-lock-face" x-show="!lockRevealed" x-transition.opacity>
          <div class="lg-lock-badge">
            <div class="lg-lock-ring lg-lock-ring-1"></div>
            <div class="lg-lock-ring lg-lock-ring-2"></div>
            <span class="lg-lock-dot lg-lock-dot-1"></span>
            <span class="lg-lock-dot lg-lock-dot-2"></span>
            <span class="lg-lock-dot lg-lock-dot-3"></span>
            <span class="lg-lock-dot lg-lock-dot-4"></span>
            <span class="lg-lock-dot lg-lock-dot-5"></span>
            <span class="lg-lock-dot lg-lock-dot-6"></span>
            <div class="lg-lock-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <defs>
                  <linearGradient id="lgLockGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#EAD08A"/>
                    <stop offset="100%" stop-color="#B4872F"/>
                  </linearGradient>
                </defs>
                <path fill="url(#lgLockGrad)" fill-rule="evenodd" clip-rule="evenodd" d="M12 1.5a5 5 0 00-5 5v3.25H6A2.25 2.25 0 003.75 12v8A2.25 2.25 0 006 22.25h12A2.25 2.25 0 0020.25 20v-8A2.25 2.25 0 0018 9.75h-1V6.5a5 5 0 00-5-5zm3.25 8.25V6.5a3.25 3.25 0 10-6.5 0v3.25h6.5zM12 14a1.75 1.75 0 00-.875 3.267V19a.875.875 0 001.75 0v-1.733A1.75 1.75 0 0012 14z"/>
              </svg>
            </div>
          </div>
          <div class="lg-lock-title">Afri<em>Stay</em> verrouillé</div>
          <p class="lg-lock-subtitle">Déverrouillez pour accéder à votre espace hôtelier sécurisé</p>
          <button type="button" class="lg-lock-unlock" @click="lockRevealed = true">Déverrouiller</button>
        </div>

        <div class="lg-lock-mark" x-show="lockRevealed" x-transition.opacity>
          <div class="lg-lock-mark-badge">
            <div class="lg-lock-ring lg-lock-ring-1"></div>
            <div class="lg-lock-ring lg-lock-ring-2"></div>
            <span class="lg-lock-mark-sq">AS</span>
          </div>
          <div class="lg-lock-mark-text">Espace hôtelier sécurisé</div>
        </div>
      </div>

      <div class="lg-lock-sheet" x-show="lockRevealed" x-transition>
        <div class="lg-lock-sheet-brand">Afri<em>Stay</em></div>
        <p class="lg-lock-sheet-sub">Déverrouillez pour accéder à votre espace hôtelier</p>

        <button type="button" class="lg-bio-fallback lg-lock-fallback" @click="useCredentialsInstead()">Utiliser mes identifiants</button>

        <div class="lg-lock-sheet-badge">
          <div class="lg-lock-ring lg-lock-ring-1"></div>
          <div class="lg-lock-ring lg-lock-ring-2"></div>
          <button type="button" class="lg-bio-btn" @click="loginWithBiometric()" :disabled="biometricLoading">
            <div class="lg-spinner" x-show="biometricLoading"></div>
            <svg x-show="!biometricLoading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5c0-2.76 2.24-5 5-5s5 2.24 5 5v1.5m-9-1.5c0-.5.05-1 .15-1.46M17 13v1a5 5 0 01-9.9 1M12 8.5v6M9.5 9.8v6.7M14.5 9.8V15"/>
            </svg>
          </button>
        </div>

        <template x-if="error">
          <div class="lg-alert" style="margin-top:20px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-text="error"></span>
          </div>
        </template>
      </div>

    </div>

    <!-- ═══ FORMULAIRE : app installée ═══ -->
    <section class="lg-form-wrap" x-show="isApp && !biometricFirstMode" x-cloak>

      <div class="lg-form-header">
        <div class="lg-form-eyebrow">
          <div class="lg-form-eyebrow-line"></div>
          <span>Connexion · Espace hôtelier</span>
        </div>
        <h1 class="lg-form-title">
          Bon retour<br><em>parmi nous.</em>
        </h1>
        <p class="lg-form-sub">Accédez à votre tableau de bord en quelques secondes</p>
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
        <label class="lg-label">Adresse email</label>
        <div class="lg-input-wrap">
          <span class="lg-input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          </span>
          <input class="lg-input" type="email" placeholder="votre@email.ci" autocomplete="off"
            x-model="form.email" @keydown.enter="submit()">
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
          <input class="lg-input has-toggle" :type="showPass ? 'text' : 'password'" autocomplete="off"
            placeholder="••••••••" x-model="form.password" @keydown.enter="submit()">
          <button type="button" class="lg-toggle-btn" @click="showPass = !showPass"
            x-text="showPass ? 'Masquer' : 'Afficher'"></button>
        </div>
      </div>

      <div class="lg-field-options">
        <a href="<?= $base_url ?>/forgot-password" class="lg-forgot">Mot de passe oublié ?</a>
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
        Pas encore de compte ?
        <a href="<?= $base_url ?>/register">Créer un compte gratuit →</a>
      </p>

    </section>

  </div>

</body>
</html>
