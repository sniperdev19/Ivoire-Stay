<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inscription – Ivoire Stay</title>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&display=swap');

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold: #C9A84C;
      --forest: #1B4332;
      --cream: #FAF7F2;
      --mid: #2D6A4F;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
      0%, 100% { opacity: 0.05; }
      50%       { opacity: 0.1; }
    }

    html, body { height: 100%; }

    body {
      font-family: 'Inter', sans-serif;
      display: grid;
      grid-template-columns: 42fr 58fr;
      min-height: 100vh;
    }

    .rg-left {
      background: var(--forest);
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      height: 100vh;
      position: sticky;
      top: 0;
    }
    .rg-ghost {
      position: absolute;
      right: -40px;
      bottom: -60px;
      font-family: 'Cormorant Garamond', serif;
      font-size: 380px;
      font-weight: 700;
      color: rgba(201,168,76,0.06);
      line-height: 1;
      user-select: none;
      pointer-events: none;
      animation: pulse 6s ease-in-out infinite;
    }
    .rg-radial {
      position: absolute;
      top: -80px;
      left: -60px;
      width: 360px;
      height: 360px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,0.1) 0%, transparent 70%);
      pointer-events: none;
    }

    .rg-brand {
      position: relative;
      z-index: 2;
      padding: 36px 44px 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .rg-brand-inner {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .rg-brand-sq {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--gold), #A67C2E);
      display: grid;
      place-items: center;
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px;
      font-weight: 600;
      color: white;
    }
    .rg-brand-text strong {
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px;
      font-weight: 600;
      color: white;
      line-height: 1;
      display: block;
    }
    .rg-brand-text span {
      font-size: 9px;
      color: rgba(255,255,255,0.35);
      text-transform: uppercase;
      letter-spacing: 0.15em;
    }
    .rg-nav-link {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: rgba(255,255,255,0.4);
      text-decoration: none;
      padding: 6px 14px;
      border: 0.5px solid rgba(255,255,255,0.1);
      border-radius: 999px;
      transition: all 0.2s;
    }
    .rg-nav-link:hover { color: white; border-color: rgba(255,255,255,0.25); }

    .rg-left-body {
      position: relative;
      z-index: 2;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 0 44px 48px;
    }
    .rg-tag {
      font-size: 9px;
      letter-spacing: 0.4em;
      text-transform: uppercase;
      color: rgba(201,168,76,0.65);
      margin-bottom: 16px;
    }
    .rg-headline {
      font-family: 'Cormorant Garamond', serif;
      font-size: 52px;
      font-weight: 300;
      color: white;
      line-height: 0.9;
      margin-bottom: 18px;
    }
    .rg-headline em { color: var(--gold); font-style: italic; display: block; }
    .rg-rule { width: 40px; height: 1px; background: var(--gold); margin-bottom: 18px; }
    .rg-desc {
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(255,255,255,0.42);
      line-height: 1.8;
      max-width: 280px;
      margin-bottom: 32px;
    }

    .rg-plans { display: flex; flex-direction: column; gap: 8px; margin-bottom: 28px; }
    .rg-plan {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      border-radius: 10px;
      border: 0.5px solid rgba(255,255,255,0.07);
      background: rgba(255,255,255,0.04);
    }
    .rg-plan-name {
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      font-weight: 600;
      color: rgba(255,255,255,0.7);
    }
    .rg-plan-price {
      font-family: 'Cormorant Garamond', serif;
      font-size: 16px;
      color: var(--gold);
      font-weight: 400;
    }
    .rg-plan-badge {
      font-size: 9px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 999px;
    }
    .badge-free { background: rgba(22,163,74,0.15); color: #4ade80; }
    .badge-pro  { background: rgba(201,168,76,0.15); color: var(--gold); }
    .badge-biz  { background: rgba(52,211,153,0.12); color: #34d399; }

    .rg-steps { display: flex; flex-direction: column; gap: 12px; }
    .rg-step {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }
    .rg-step-dot {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: rgba(201,168,76,0.1);
      border: 0.5px solid rgba(201,168,76,0.25);
      display: grid;
      place-items: center;
      flex-shrink: 0;
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      font-weight: 700;
      color: var(--gold);
    }
    .rg-step-text {
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      color: rgba(255,255,255,0.5);
      line-height: 1.55;
      padding-top: 3px;
    }
    .rg-step-text strong { color: rgba(255,255,255,0.75); font-weight: 600; display: block; }

    .rg-separator {
      position: fixed;
      left: 42%;
      top: 0;
      bottom: 0;
      width: 1px;
      background: linear-gradient(
        to bottom,
        transparent 0%,
        rgba(201,168,76,0.25) 20%,
        rgba(201,168,76,0.4) 50%,
        rgba(201,168,76,0.25) 80%,
        transparent 100%
      );
      z-index: 100;
    }
    .rg-separator-dot {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--gold);
      box-shadow: 0 0 16px rgba(201,168,76,0.6);
    }

    .rg-right {
      background: var(--cream);
      padding: 48px 64px;
      overflow-y: auto;
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
    }
    .rg-form-wrap {
      width: 100%;
      max-width: 440px;
      padding-top: 20px;
      animation: fadeUp 0.5s ease;
    }

    .rg-form-header { margin-bottom: 32px; }
    .rg-form-eyebrow {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }
    .rg-form-eyebrow-line { width: 24px; height: 1px; background: var(--gold); }
    .rg-form-eyebrow span {
      font-size: 9px;
      letter-spacing: 0.35em;
      text-transform: uppercase;
      color: var(--gold);
    }
    .rg-form-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 52px;
      font-weight: 300;
      color: var(--forest);
      line-height: 0.9;
      margin-bottom: 10px;
    }
    .rg-form-title em { color: var(--gold); font-style: italic; }
    .rg-form-sub {
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(27,67,50,0.48);
    }

    .rg-stepper {
      display: flex;
      align-items: center;
      margin-bottom: 28px;
    }
    .rg-step-circle {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      font-weight: 700;
      flex-shrink: 0;
      transition: all 0.3s;
      border: 1px solid rgba(27,67,50,0.15);
      color: rgba(27,67,50,0.3);
      background: transparent;
    }
    .rg-step-circle.active {
      background: var(--gold);
      border-color: var(--gold);
      color: white;
      box-shadow: 0 4px 14px rgba(201,168,76,0.35);
    }
    .rg-step-circle.done {
      background: var(--forest);
      border-color: var(--forest);
      color: white;
    }
    .rg-step-circle.done svg { width: 14px; height: 14px; stroke: white; stroke-width: 2.5; fill: none; }
    .rg-step-info {
      margin-left: 10px;
      flex: 1;
    }
    .rg-step-name {
      font-family: 'Inter', sans-serif;
      font-size: 10px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.2em;
    }
    .rg-step-name.active { color: var(--forest); }
    .rg-step-name.inactive { color: rgba(27,67,50,0.3); }
    .rg-connector {
      flex-shrink: 0;
      width: 48px;
      height: 1px;
      margin: 0 12px;
      transition: background 0.3s;
    }
    .rg-connector.done { background: var(--forest); }
    .rg-connector.pending { background: rgba(27,67,50,0.1); }

    .rg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .rg-grid-full { grid-column: 1 / -1; }
    .rg-field { display: flex; flex-direction: column; gap: 7px; }
    .rg-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.35em;
      color: var(--gold);
    }
    .rg-input-wrap { position: relative; }
    .rg-input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(27,67,50,0.3);
      pointer-events: none;
    }
    .rg-input-icon svg { width: 15px; height: 15px; }
    .rg-input {
      width: 100%;
      padding: 14px 16px 14px 42px;
      border: 1px solid rgba(27,67,50,0.12);
      border-radius: 12px;
      background: white;
      color: var(--forest);
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-input:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
    }
    .rg-input::placeholder { color: rgba(27,67,50,0.25); }
    .rg-input.has-toggle { padding-right: 80px; }
    .rg-toggle-btn {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: rgba(27,67,50,0.45);
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 6px;
      font-weight: 500;
    }
    .rg-toggle-btn:hover { color: var(--forest); }

    .rg-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
    .rg-type-btn {
      padding: 14px 16px;
      border-radius: 12px;
      border: 1px solid rgba(27,67,50,0.12);
      background: white;
      color: rgba(27,67,50,0.5);
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .rg-type-btn.active {
      background: rgba(201,168,76,0.08);
      border-color: rgba(201,168,76,0.4);
      color: var(--forest);
    }

    .rg-btn {
      width: 100%;
      padding: 16px 24px;
      border: none;
      border-radius: 12px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.2s;
      letter-spacing: 0.02em;
      margin-top: 24px;
    }
    .rg-btn-primary {
      background: linear-gradient(135deg, var(--gold), #A67C2E);
      color: white;
    }
    .rg-btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(201,168,76,0.4);
    }
    .rg-btn-dark {
      background: var(--forest);
      color: white;
    }
    .rg-btn-dark:hover:not(:disabled) {
      background: #0d2118;
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(15,43,32,0.28);
    }
    .rg-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

    .rg-back-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      color: rgba(27,67,50,0.45);
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px 0;
      margin-bottom: 20px;
      transition: color 0.2s;
    }
    .rg-back-btn:hover { color: var(--forest); }
    .rg-back-btn svg { width: 14px; height: 14px; }

    .rg-alert {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      background: rgba(239,68,68,0.06);
      border: 0.5px solid rgba(239,68,68,0.2);
      border-radius: 12px;
      color: #DC2626;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      margin-bottom: 18px;
    }
    .rg-alert svg { width: 14px; height: 14px; flex-shrink: 0; }

    .rg-form-footer {
      text-align: center;
      margin-top: 24px;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(27,67,50,0.42);
    }
    .rg-form-footer a {
      color: var(--forest);
      font-weight: 600;
      text-decoration: none;
      border-bottom: 1px solid rgba(27,67,50,0.2);
      padding-bottom: 1px;
    }
    .rg-form-footer a:hover { border-color: var(--forest); }

    .rg-free-note {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 16px;
      background: rgba(201,168,76,0.06);
      border: 0.5px solid rgba(201,168,76,0.15);
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      color: rgba(27,67,50,0.6);
      margin-top: 14px;
    }
    .rg-free-dot {
      width: 5px;
      height: 5px;
      border-radius: 50%;
      background: var(--gold);
      flex-shrink: 0;
    }

    .rg-section-divider {
      height: 0.5px;
      background: rgba(27,67,50,0.08);
      margin: 20px 0;
    }

    @media (max-width: 1024px) {
      body { grid-template-columns: 1fr; }
      .rg-left { display: none; }
      .rg-separator { display: none; }
      .rg-right { background: var(--forest); }
      .rg-form-title, .rg-form-eyebrow span, .rg-label { color: var(--gold); }
      .rg-form-title { color: white; }
      .rg-form-title em { color: var(--gold); }
      .rg-form-sub { color: rgba(255,255,255,0.5); }
      .rg-input { background: rgba(255,255,255,0.07); color: white; border-color: rgba(255,255,255,0.12); }
      .rg-input::placeholder { color: rgba(255,255,255,0.25); }
      .rg-input:focus { border-color: var(--gold); background: rgba(255,255,255,0.1); }
      .rg-input-icon { color: rgba(255,255,255,0.3); }
      .rg-toggle-btn { color: rgba(255,255,255,0.45); }
      .rg-type-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: rgba(255,255,255,0.45); }
      .rg-type-btn.active { background: rgba(201,168,76,0.12); border-color: rgba(201,168,76,0.35); color: white; }
      .rg-step-circle { border-color: rgba(255,255,255,0.15); color: rgba(255,255,255,0.3); }
      .rg-step-name.active { color: white; }
      .rg-step-name.inactive { color: rgba(255,255,255,0.3); }
      .rg-connector.pending { background: rgba(255,255,255,0.08); }
      .rg-back-btn { color: rgba(255,255,255,0.45); }
      .rg-back-btn:hover { color: white; }
      .rg-alert { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.25); color: #fca5a5; }
      .rg-free-note { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.4); }
      .rg-form-footer { color: rgba(255,255,255,0.4); }
      .rg-form-footer a { color: var(--gold); border-color: rgba(201,168,76,0.3); }
      .rg-section-divider { background: rgba(255,255,255,0.07); }
    }

    @media (max-width: 520px) {
      .rg-right { padding: 40px 24px; }
      .rg-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <div class="rg-left">
    <div class="rg-ghost">IS</div>
    <div class="rg-radial"></div>

    <div class="rg-brand">
      <a class="rg-brand-inner" href="<?= $base_url ?>/">
        <div class="rg-brand-sq">IS</div>
        <div class="rg-brand-text">
          <strong>Ivoire Stay</strong>
          <span>SaaS Hôtelier</span>
        </div>
      </a>
      <a class="rg-nav-link" href="<?= $base_url ?>/">← Retour au site</a>
    </div>

    <div class="rg-left-body">
      <div class="rg-tag">Inscription gratuite</div>
      <h2 class="rg-headline">Démarrez<br><em>aujourd'hui.</em></h2>
      <div class="rg-rule"></div>
      <p class="rg-desc">
        2 minutes suffisent pour configurer votre établissement
        et recevoir vos premières réservations.
      </p>

      <div class="rg-plans">
        <div class="rg-plan">
          <span class="rg-plan-name">STARTER</span>
          <span class="rg-plan-badge badge-free">Gratuit</span>
        </div>
        <div class="rg-plan">
          <span class="rg-plan-name">PRO</span>
          <span class="rg-plan-price">9 000 FCFA / mois</span>
        </div>
        <div class="rg-plan">
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
        <div class="rg-step">
          <div class="rg-step-dot">03</div>
          <div class="rg-step-text">
            <strong>Recevez vos réservations</strong>
            En ligne dès la configuration terminée
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="rg-separator">
    <div class="rg-separator-dot"></div>
  </div>

  <div class="rg-right">

    <section class="rg-form-wrap"
      x-data="{
        form: {
          name: '',
          email: '',
          phone: '',
          password: '',
          password_confirm: '',
          establishment_name: '',
          establishment_type: 'hotel'
        },
        step: 1,
        loading: false,
        error: null,
        showPass: false,
        validateStep1() {
          this.error = null;
          if (!this.form.name.trim()) return this.error = 'Nom complet requis.';
          if (!this.form.email.match(/^[^@]+@[^@]+\.[^@]+$/)) return this.error = 'Email invalide.';
          if (!this.form.phone.trim()) return this.error = 'Téléphone requis.';
          if (this.form.password.length < 8) return this.error = 'Mot de passe : 8 caractères minimum.';
          if (this.form.password !== this.form.password_confirm) return this.error = 'Les mots de passe ne correspondent pas.';
          this.step = 2;
        },
        async submit() {
          this.error = null;
          if (!this.form.establishment_name.trim()) {
            this.error = 'Nom de l\'établissement requis.';
            return;
          }
          this.loading = true;
          try {
            const res = await fetch('<?= $base_url ?>/api/auth/register', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(this.form)
            });
            const data = await res.json();
            if (data.success) {
              localStorage.setItem('token', data.data.token);
              localStorage.setItem('user', JSON.stringify(data.data.user));
              localStorage.setItem('establishments', JSON.stringify(data.data.establishments ?? []));
              const e = data.data.establishments ?? [];
              if (e.length) localStorage.setItem('establishment_id', e[0].id);
              window.location.href = '<?= $base_url ?>/saas';
            } else {
              this.error = data.message ?? 'Erreur lors de l\'inscription.';
            }
          } catch (e) {
            this.error = 'Erreur réseau. Veuillez réessayer.';
          } finally {
            this.loading = false;
          }
        }
      }">

      <div class="rg-form-header">
        <div class="rg-form-eyebrow">
          <div class="rg-form-eyebrow-line"></div>
          <span>Inscription · Espace hôtelier</span>
        </div>
        <h1 class="rg-form-title">Créer un<br><em>compte.</em></h1>
        <p class="rg-form-sub">Démarrez gratuitement, sans carte bancaire</p>
      </div>

      <!-- Stepper -->
      <div class="rg-stepper" x-show="true">
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
        <div class="rg-connector" :class="step > 1 ? 'done' : 'pending'"></div>
        <!-- Étape 2 -->
        <div class="rg-step-circle" :class="step === 2 ? 'active' : ''">
          <span>2</span>
        </div>
        <div class="rg-step-info">
          <div class="rg-step-name" :class="step === 2 ? 'active' : 'inactive'">Établissement</div>
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

      <!-- ÉTAPE 1 -->
      <div x-show="step === 1">
        <div class="rg-grid">

          <!-- Nom complet -->
          <div class="rg-field">
            <label class="rg-label">Nom complet</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <input type="text" class="rg-input" placeholder="Votre nom complet"
                x-model="form.name">
            </div>
          </div>

          <!-- Téléphone -->
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
              <input type="tel" class="rg-input" placeholder="+225 07 00 00 00 00"
                x-model="form.phone">
            </div>
          </div>

          <!-- Email (pleine largeur) -->
          <div class="rg-field rg-grid-full">
            <label class="rg-label">Adresse email</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2
                    0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
              </div>
              <input type="email" class="rg-input" placeholder="votre@email.ci"
                x-model="form.email">
            </div>
          </div>

          <!-- Mot de passe -->
          <div class="rg-field">
            <label class="rg-label">Mot de passe</label>
            <div class="rg-input-wrap">
              <div class="rg-input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0
                    00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <input :type="showPass ? 'text' : 'password'"
                class="rg-input has-toggle"
                placeholder="••••••••"
                x-model="form.password">
              <button type="button" class="rg-toggle-btn"
                @click="showPass = !showPass"
                x-text="showPass ? 'Masquer' : 'Afficher'">
              </button>
            </div>
          </div>

          <!-- Confirmer MDP -->
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
              <input type="password" class="rg-input" placeholder="••••••••"
                x-model="form.password_confirm">
            </div>
          </div>

        </div><!-- fin rg-grid -->

        <button class="rg-btn rg-btn-dark" type="button" @click="validateStep1()">
          Continuer
          <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </button>
      </div>

      <!-- ÉTAPE 2 -->
      <div x-show="step === 2">

        <button type="button" class="rg-back-btn" @click="step = 1; error = null">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
          </svg>
          Retour
        </button>

        <!-- Nom établissement -->
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

        <!-- Type établissement -->
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

        <div class="rg-free-note">
          <div class="rg-free-dot"></div>
          Plan Starter gratuit — jusqu'à 10 chambres, sans carte bancaire requise.
        </div>

        <button class="rg-btn rg-btn-primary" type="button"
          @click="submit()" :disabled="loading">
          <span x-show="!loading">Créer mon compte</span>
          <span x-show="loading">Création en cours…</span>
          <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg"
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

      <div class="rg-section-divider"></div>

      <div class="rg-form-footer">
        Déjà un compte ?
        <a href="<?= $base_url ?>/login">Se connecter →</a>
      </div>
    </section>
  </div>

</body>
</html>
