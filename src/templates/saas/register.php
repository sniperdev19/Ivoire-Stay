<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
$rgJsPath = BASE_PATH . '/public/assets/js/pages/register.js';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inscription – Afristay</title>
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192.png" type="image/png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/register.css">
  <style>
    /* ── Le wrapper x-data ne doit pas casser le grid body ────── */
    body > [x-data] { display: contents; }
    /* ── Highlights plan actif (panneau gauche) ─────────────── */
    .rg-plan-active {
      background: rgba(201,168,76,.1);
      border-color: rgba(201,168,76,.45) !important;
    }
    /* ── Stepper compact à 3 étapes ─────────────────────────── */
    .rg-stepper .rg-connector      { width: 28px; margin: 0 6px; }
    .rg-stepper .rg-step-info      { min-width: 0; margin-left: 6px; }
    .rg-stepper .rg-step-name      { white-space: nowrap; }
    /* ── Étape 3 : toggle facturation ───────────────────────── */
    .rg-billing-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
    .rg-billing-opt {
      display: flex; flex-direction: column; align-items: center;
      padding: 12px 8px; border: 1.5px solid rgba(27,67,50,.12);
      border-radius: 12px; cursor: pointer; background: transparent;
      position: relative; transition: border-color .2s, background .2s;
    }
    .rg-billing-opt.active { border-color: #C9A84C; background: rgba(201,168,76,.06); }
    .rg-billing-opt-name  { font-family: Inter,sans-serif; font-size: 13px; font-weight: 600; color: #1B4332; }
    .rg-billing-opt-price { font-family: Inter,sans-serif; font-size: 11px; color: rgba(27,67,50,.45); margin-top: 2px; }
    .rg-billing-discount  {
      position: absolute; top: -8px; right: 8px;
      background: #1B4332; color: #C9A84C; font-size: 10px;
      font-weight: 700; padding: 2px 6px; border-radius: 4px;
      font-family: Inter,sans-serif;
    }
    /* ── Étape 3 : modes de paiement ────────────────────────── */
    .rg-pay-opts { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
    .rg-pay-opt {
      display: flex; align-items: center; gap: 14px;
      padding: 14px 16px; border: 1.5px solid rgba(27,67,50,.12);
      border-radius: 12px; cursor: pointer;
      transition: border-color .2s, background .2s;
    }
    .rg-pay-opt.selected { border-color: #C9A84C; background: rgba(201,168,76,.06); }
    .rg-pay-radio {
      width: 18px; height: 18px; border-radius: 50%;
      border: 2px solid rgba(27,67,50,.25);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .rg-pay-radio-dot { display: none; width: 8px; height: 8px; border-radius: 50%; background: #C9A84C; }
    .rg-pay-opt.selected .rg-pay-radio       { border-color: #C9A84C; }
    .rg-pay-opt.selected .rg-pay-radio-dot   { display: block; }
    .rg-pay-icon {
      width: 40px; height: 40px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 11px; font-family: Inter,sans-serif;
      color: white; flex-shrink: 0;
    }
    .rg-pay-name { font-family: Inter,sans-serif; font-size: 14px; font-weight: 600; color: #1B4332; }
    .rg-pay-sub  { font-family: Inter,sans-serif; font-size: 12px; color: rgba(27,67,50,.45); }
    /* ── Étape 3 : récap prix ───────────────────────────────── */
    .rg-pay-summary {
      background: rgba(27,67,50,.04); border-radius: 12px;
      padding: 16px; margin-bottom: 16px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .rg-pay-summary-main  { font-family: Inter,sans-serif; font-size: 14px; font-weight: 600; color: #1B4332; }
    .rg-pay-summary-sub   { font-family: Inter,sans-serif; font-size: 12px; color: rgba(27,67,50,.45); margin-top: 2px; }
    .rg-pay-summary-amt   { font-family: 'Cormorant Garamond',serif; font-size: 28px; font-weight: 700; color: #1B4332; text-align: right; }
    .rg-pay-summary-period{ font-family: Inter,sans-serif; font-size: 11px; color: rgba(27,67,50,.4); text-align: right; }
    .rg-savings-note {
      background: rgba(201,168,76,.1); color: #7B5E12;
      font-family: Inter,sans-serif; font-size: 12px; font-weight: 600;
      padding: 6px 12px; border-radius: 6px;
      margin-bottom: 16px; text-align: center;
    }
    .rg-success-note {
      font-family: Inter,sans-serif; font-size: 13px; color: rgba(27,67,50,.55);
      background: rgba(27,67,50,.04); border-radius: 10px;
      padding: 12px 14px; margin-bottom: 20px; line-height: 1.6;
    }
    .rg-skip-link {
      text-align: center; margin-top: 12px;
      font-family: Inter,sans-serif; font-size: 12px; color: rgba(27,67,50,.38);
    }
    .rg-skip-link button { background: none; border: none; cursor: pointer; color: inherit; font-size: inherit; font-family: inherit; text-decoration: underline; }
    .rg-skip-link button:hover { color: #1B4332; }
    /* ── Étape 3 note dans step 2 ───────────────────────────── */
    .rg-paid-note {
      background: rgba(201,168,76,.07); border: 1px solid rgba(201,168,76,.25);
      border-radius: 10px; padding: 10px 14px; margin-bottom: 18px;
      display: flex; align-items: center; gap: 10px;
      font-family: Inter,sans-serif; font-size: 13px; color: #7B5E12;
    }
    .rg-paid-note-dot { width: 8px; height: 8px; border-radius: 50%; background: #C9A84C; flex-shrink: 0; }
  </style>
  <?php $rgJs = file_exists($rgJsPath) ? filemtime($rgJsPath) : 1; ?>
  <script src="<?= $base_url ?>/assets/js/pages/register.js?v=<?= $rgJs ?>"></script>
  <script defer src="<?= $base_url ?>/assets/js/alpine.min.js"></script>
</head>
<body>

<div x-data="registerPage('<?= $base_url ?>')" x-init="init()">

  <!-- ══════════════════════════════════════════
       PANNEAU GAUCHE
  ══════════════════════════════════════════ -->
  <div class="rg-left">
    <div class="rg-ghost">AS</div>
    <div class="rg-radial"></div>

    <div class="rg-brand">
      <a class="rg-brand-inner" href="<?= $base_url ?>/">
        <div class="rg-brand-sq">AS</div>
        <div class="rg-brand-text">
          <strong>Afri <span style="color:#C9A84C;">Stay</span></strong>
          <span>SaaS Hôtelier</span>
        </div>
      </a>
      <a class="rg-nav-link" href="<?= $base_url ?>/">← Retour au site</a>
    </div>

    <div class="rg-left-body">
      <div class="rg-tag" x-text="isPaid ? 'Plan ' + current.name : 'Inscription gratuite'">Inscription gratuite</div>
      <h2 class="rg-headline">Démarrez<br><em>aujourd'hui.</em></h2>
      <div class="rg-rule"></div>
      <p class="rg-desc">
        2 minutes suffisent pour configurer votre établissement
        et recevoir vos premières réservations.
      </p>

      <div class="rg-plans">
        <div class="rg-plan" :class="plan === 'starter' ? 'rg-plan-active' : ''">
          <span class="rg-plan-name">GRATUIT</span>
          <span class="rg-plan-badge badge-free">0 FCFA</span>
        </div>
        <div class="rg-plan" :class="plan === 'pro' ? 'rg-plan-active' : ''">
          <span class="rg-plan-name">PRO</span>
          <span class="rg-plan-price">9 000 FCFA / mois</span>
        </div>
        <div class="rg-plan" :class="plan === 'business' ? 'rg-plan-active' : ''">
          <span class="rg-plan-name">BUSINESS</span>
          <span class="rg-plan-price">20 000 FCFA / mois</span>
        </div>
      </div>

      <div class="rg-steps">
        <div class="rg-step">
          <div class="rg-step-dot">01</div>
          <div class="rg-step-text">
            <strong>Créez votre compte</strong>
            Nom, email, téléphone et mot de passe
          </div>
        </div>
        <div class="rg-step">
          <div class="rg-step-dot">02</div>
          <div class="rg-step-text">
            <strong>Configurez votre établissement</strong>
            Hôtel, résidence ou villa
          </div>
        </div>
        <div class="rg-step" x-show="isPaid">
          <div class="rg-step-dot">03</div>
          <div class="rg-step-text">
            <strong>Activez votre abonnement</strong>
            Paiement sécurisé Mobile Money
          </div>
        </div>
        <div class="rg-step">
          <div class="rg-step-dot" x-text="isPaid ? '04' : '03'">03</div>
          <div class="rg-step-text">
            <strong>Recevez vos réservations</strong>
            En ligne dès la configuration terminée
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════
       SÉPARATEUR
  ══════════════════════════════════════════ -->
  <div class="rg-separator">
    <div class="rg-separator-dot"></div>
  </div>

  <!-- ══════════════════════════════════════════
       PANNEAU DROIT — Formulaire
  ══════════════════════════════════════════ -->
  <div class="rg-right">
    <section class="rg-form-wrap">

      <a class="rg-mobile-back" href="<?= $base_url ?>/">← Retour au site</a>

      <div class="rg-form-header">
        <div class="rg-form-eyebrow">
          <div class="rg-form-eyebrow-line"></div>
          <span>Inscription · Espace hôtelier</span>
        </div>
        <h1 class="rg-form-title">Créer un<br><em>compte.</em></h1>
        <p class="rg-form-sub" x-text="isPaid ? 'Plan ' + current.name + ', paiement à la dernière étape' : 'Démarrez gratuitement, sans carte bancaire'">Démarrez gratuitement, sans carte bancaire</p>
      </div>

      <!-- Stepper -->
      <div class="rg-stepper">
        <!-- Étape 1 -->
        <div class="rg-step-circle" :class="step === 1 ? 'active' : step > 1 ? 'done' : ''">
          <template x-if="step > 1">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </template>
          <template x-if="step <= 1"><span>1</span></template>
        </div>
        <div class="rg-step-info">
          <div class="rg-step-name" :class="step === 1 ? 'active' : 'inactive'">Compte</div>
        </div>
        <!-- Connecteur 1→2 -->
        <div class="rg-connector" :class="step > 1 ? 'done' : 'pending'"></div>
        <!-- Étape 2 -->
        <div class="rg-step-circle" :class="step === 2 ? 'active' : step > 2 ? 'done' : ''">
          <template x-if="step > 2">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </template>
          <template x-if="step <= 2"><span>2</span></template>
        </div>
        <div class="rg-step-info">
          <div class="rg-step-name" :class="step === 2 ? 'active' : 'inactive'">Établissement</div>
        </div>
        <!-- Connecteur 2→3 (plans payants seulement) -->
        <div class="rg-connector" :class="step > 2 ? 'done' : 'pending'" x-show="isPaid"></div>
        <!-- Étape 3 (plans payants seulement) -->
        <div class="rg-step-circle" :class="step === 3 ? 'active' : ''" x-show="isPaid">
          <span>3</span>
        </div>
        <div class="rg-step-info" x-show="isPaid">
          <div class="rg-step-name" :class="step === 3 ? 'active' : 'inactive'">Paiement</div>
        </div>
      </div>

      <!-- Erreur -->
      <div class="rg-alert" x-show="error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="error"></span>
      </div>

      <!-- ────────────────────────────────────────
           ÉTAPE 1 — Compte
      ──────────────────────────────────────── -->
      <div x-show="step === 1">
        <div class="rg-grid">

          <div class="rg-field">
            <label class="rg-label">Nom complet</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <input type="text" class="rg-input" placeholder="Votre nom complet" x-model="form.name" @input="sanitizeName()">
            </div>
          </div>

          <div class="rg-field">
            <label class="rg-label">Téléphone</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257
                    1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1
                    0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
              </div>
              <input type="tel" class="rg-input" placeholder="+225 07 00 00 00 00" x-model="form.phone" @input="sanitizePhone()">
            </div>
            <p class="rg-field-hint">Commence par 01, 05 ou 07.</p>
          </div>

          <div class="rg-field rg-grid-full">
            <label class="rg-label">Adresse email</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
              </div>
              <input type="email" class="rg-input" placeholder="votre@email.ci" x-model="form.email">
            </div>
          </div>

          <div class="rg-field">
            <label class="rg-label">Mot de passe</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <input :type="showPass ? 'text' : 'password'" class="rg-input has-toggle"
                placeholder="••••••••" x-model="form.password">
              <button type="button" class="rg-toggle-btn"
                @click="showPass = !showPass"
                x-text="showPass ? 'Masquer' : 'Afficher'">
              </button>
            </div>
            <div class="rg-pwd-meter" x-show="form.password.length > 0" x-cloak>
              <div class="rg-pwd-meter-track">
                <template x-for="i in 4" :key="i">
                  <span class="rg-pwd-meter-seg"
                    :style="i <= passwordScore ? { background: passwordScoreColor } : {}"></span>
                </template>
              </div>
              <span class="rg-pwd-meter-label" :style="{ color: passwordScoreColor }" x-text="passwordScoreLabel"></span>
            </div>
          </div>

          <div class="rg-field">
            <label class="rg-label">Confirmer MDP</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955
                    11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29
                    9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </div>
              <input type="password" class="rg-input" placeholder="••••••••" x-model="form.password_confirm">
            </div>
            <p class="rg-field-hint" style="color:#65A30D;" x-show="passwordsMatch" x-cloak>✓ Les mots de passe correspondent.</p>
            <p class="rg-field-hint" style="color:#DC2626;" x-show="passwordsMismatch" x-cloak>Les mots de passe ne correspondent pas.</p>
          </div>

        </div>

        <button class="rg-btn rg-btn-dark" type="button" @click="validateStep1()">
          Continuer
          <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </button>
      </div>

      <!-- ────────────────────────────────────────
           ÉTAPE 2 — Établissement
      ──────────────────────────────────────── -->
      <div x-show="step === 2">

        <button type="button" class="rg-back-btn" @click="step = 1; error = null">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
          </svg>
          Retour
        </button>

        <div class="rg-field">
          <label class="rg-label">Nom de l'établissement</label>
          <div class="rg-input-wrap">
            <div class="rg-input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2
                  0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5
                  10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
            <input type="text" class="rg-input" placeholder="Hôtel Le Plateau, Villa Cocody…"
              x-model="form.establishment_name">
          </div>
        </div>

        <div class="rg-field">
          <label class="rg-label">Ville</label>
          <div class="rg-input-wrap">
            <div class="rg-input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <input type="text" class="rg-input" placeholder="Ex : Abidjan, Yamoussoukro…"
              x-model="form.establishment_city">
          </div>
          <p class="rg-field-hint">Nécessaire pour apparaître dans la recherche des voyageurs.</p>
        </div>

        <div class="rg-field">
          <label class="rg-label">Type d'établissement</label>
          <div class="rg-type-grid">
            <button type="button" class="rg-type-btn"
              :class="form.establishment_type === 'hotel' ? 'active' : ''"
              @click="form.establishment_type = 'hotel'">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
              </svg>
              Hôtel
            </button>
            <button type="button" class="rg-type-btn"
              :class="form.establishment_type === 'residence' ? 'active' : ''"
              @click="form.establishment_type = 'residence'">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2
                  2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0
                  011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
              </svg>
              Résidence
            </button>
          </div>
        </div>

        <!-- Note plan sélectionné -->
        <div class="rg-free-note" x-show="!isPaid">
          <div class="rg-free-dot"></div>
          Plan Starter sans abonnement, chambres illimitées, sans carte bancaire requise — paiement en ligne des
          réservations toujours activé, commission plateforme incluse.
        </div>
        <div class="rg-paid-note" x-show="isPaid">
          <div class="rg-paid-note-dot"></div>
          Plan <strong x-text="current.name"></strong> sélectionné. Le paiement se fera à l'étape suivante.
        </div>

        <button class="rg-btn rg-btn-primary" type="button"
          @click="submit()" :disabled="loading">
          <span x-show="!loading && !isPaid">Créer mon compte</span>
          <span x-show="!loading && isPaid">Continuer vers le paiement →</span>
          <span x-show="loading">Création en cours…</span>
          <svg x-show="!loading && !isPaid" xmlns="http://www.w3.org/2000/svg"
            style="width:16px;height:16px;" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
          <div x-show="loading" style="width:16px;height:16px;border-radius:50%;
            border:2px solid rgba(255,255,255,0.3);border-top-color:white;
            animation:spin 0.7s linear infinite;flex-shrink:0;"></div>
        </button>
      </div>

      <!-- ────────────────────────────────────────
           ÉTAPE 3 — Paiement (plans payants)
      ──────────────────────────────────────── -->
      <div x-show="step === 3">

        <div class="rg-success-note">
          ✓ Compte créé avec succès ! Finalisez votre abonnement <strong x-text="current.name"></strong> pour débloquer toutes les fonctionnalités.
        </div>

        <!-- Toggle facturation -->
        <div class="rg-label">Période de facturation</div>
        <div class="rg-billing-toggle">
          <button type="button" class="rg-billing-opt" :class="billing === 'monthly' ? 'active' : ''" @click="billing = 'monthly'">
            <span class="rg-billing-opt-name">Mensuel</span>
            <span class="rg-billing-opt-price" x-text="fmt(current.price.monthly) + ' / mois'"></span>
          </button>
          <button type="button" class="rg-billing-opt" :class="billing === 'yearly' ? 'active' : ''" @click="billing = 'yearly'" style="position:relative;">
            <span class="rg-billing-discount">−20%</span>
            <span class="rg-billing-opt-name">Annuel</span>
            <span class="rg-billing-opt-price" x-text="fmt(current.monthlyEq.yearly) + ' / mois'"></span>
          </button>
        </div>

        <!-- Badge économie -->
        <div class="rg-savings-note" x-show="billing === 'yearly'">
          Économie de <strong x-text="fmt(savings)"></strong> par rapport au mensuel
        </div>

        <!-- Modes de paiement -->
        <div class="rg-label">Mode de paiement</div>
        <div class="rg-pay-opts">
          <div class="rg-pay-opt" :class="payMethod === 'wave' ? 'selected' : ''" @click="payMethod = 'wave'">
            <div class="rg-pay-radio"><div class="rg-pay-radio-dot"></div></div>
            <div class="rg-pay-icon" style="background:#00B9F2;">WV</div>
            <div>
              <div class="rg-pay-name">Wave</div>
              <div class="rg-pay-sub">Sans frais de transaction</div>
            </div>
          </div>
        </div>

        <!-- Récap prix -->
        <div class="rg-pay-summary">
          <div>
            <div class="rg-pay-summary-main" x-text="'Plan ' + current.name"></div>
            <div class="rg-pay-summary-sub" x-text="billing === 'yearly' ? 'Facturation annuelle' : 'Facturation mensuelle'"></div>
          </div>
          <div>
            <div class="rg-pay-summary-amt" x-text="fmt(total)"></div>
            <div class="rg-pay-summary-period" x-text="billing === 'yearly' ? '/ an' : '/ mois'"></div>
          </div>
        </div>

        <!-- Erreur paiement -->
        <div class="rg-alert" x-show="payError" style="display:flex;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="payError"></span>
        </div>

        <!-- Bouton payer -->
        <button class="rg-btn rg-btn-primary" type="button" @click="pay()" :disabled="payLoading">
          <span x-show="!payLoading">
            Payer <span x-text="fmt(total)"></span> →
          </span>
          <span x-show="payLoading">Redirection en cours…</span>
          <div x-show="payLoading" style="width:16px;height:16px;border-radius:50%;
            border:2px solid rgba(255,255,255,0.3);border-top-color:white;
            animation:spin 0.7s linear infinite;flex-shrink:0;"></div>
        </button>

        <div class="rg-skip-link">
          <button type="button" @click="skipPayment()">Continuer avec le plan Starter (sans abonnement) →</button>
        </div>

      </div>

      <div class="rg-section-divider"></div>

      <div class="rg-form-footer">
        Déjà un compte ?
        <a href="<?= $base_url ?>/login">Se connecter →</a>
      </div>

    </section>
  </div>

</div>

</body>
</html>
