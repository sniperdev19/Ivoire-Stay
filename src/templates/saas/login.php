<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion – Ivoire Stay</title>
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
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
      0%, 100% { opacity: 0.06; }
      50%       { opacity: 0.12; }
    }

    html, body { height: 100%; overflow: hidden; }

    body {
      font-family: 'Inter', sans-serif;
      display: grid;
      grid-template-columns: 42fr 58fr;
      min-height: 100vh;
    }

    .lg-left {
      background: var(--forest);
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      height: 100vh;
    }

    .lg-ghost {
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

    .lg-radial {
      position: absolute;
      top: -120px;
      right: -80px;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,0.12) 0%, transparent 70%);
      pointer-events: none;
    }

    .lg-brand {
      position: relative;
      z-index: 2;
      padding: 36px 44px 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .lg-brand-inner {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .lg-brand-sq {
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
    .lg-brand-text { display: flex; flex-direction: column; }
    .lg-brand-text strong {
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px;
      font-weight: 600;
      color: white;
      line-height: 1;
    }
    .lg-brand-text span {
      font-size: 9px;
      font-weight: 500;
      color: rgba(255,255,255,0.35);
      text-transform: uppercase;
      letter-spacing: 0.15em;
    }
    .lg-nav-link {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: rgba(255,255,255,0.4);
      text-decoration: none;
      padding: 6px 14px;
      border: 0.5px solid rgba(255,255,255,0.1);
      border-radius: 999px;
      transition: all 0.2s;
    }
    .lg-nav-link:hover { color: white; border-color: rgba(255,255,255,0.25); }

    .lg-left-body {
      position: relative;
      z-index: 2;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 0 44px 52px;
    }
    .lg-tag {
      font-size: 9px;
      letter-spacing: 0.4em;
      text-transform: uppercase;
      color: rgba(201,168,76,0.65);
      margin-bottom: 16px;
    }
    .lg-headline {
      font-family: 'Cormorant Garamond', serif;
      font-size: 56px;
      font-weight: 300;
      color: white;
      line-height: 0.9;
      margin-bottom: 20px;
    }
    .lg-headline em {
      color: var(--gold);
      font-style: italic;
      display: block;
    }
    .lg-rule {
      width: 40px;
      height: 1px;
      background: var(--gold);
      margin-bottom: 20px;
    }
    .lg-desc {
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(255,255,255,0.42);
      line-height: 1.8;
      max-width: 280px;
      margin-bottom: 36px;
    }

    .lg-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      border-top: 0.5px solid rgba(255,255,255,0.07);
      padding-top: 24px;
      gap: 0;
    }
    .lg-stat { padding-right: 20px; }
    .lg-stat-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px;
      font-weight: 700;
      color: var(--gold);
      line-height: 1;
      display: block;
    }
    .lg-stat-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.25em;
      color: rgba(255,255,255,0.3);
      margin-top: 3px;
      display: block;
    }

    .lg-testimonial {
      margin-bottom: 32px;
      padding: 18px 20px;
      background: rgba(255,255,255,0.04);
      border: 0.5px solid rgba(255,255,255,0.08);
      border-radius: 14px;
    }
    .lg-quote-mark {
      font-family: 'Cormorant Garamond', serif;
      font-size: 40px;
      color: var(--gold);
      line-height: 0.6;
      display: block;
      margin-bottom: 8px;
    }
    .lg-quote-text {
      font-family: 'Cormorant Garamond', serif;
      font-size: 16px;
      font-style: italic;
      color: rgba(255,255,255,0.7);
      line-height: 1.5;
      margin-bottom: 14px;
    }
    .lg-quote-author {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .lg-author-av {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--gold), #A67C2E);
      display: grid;
      place-items: center;
      font-size: 10px;
      font-weight: 700;
      color: white;
      flex-shrink: 0;
    }
    .lg-author-name {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      font-weight: 600;
      color: rgba(255,255,255,0.7);
      display: block;
    }
    .lg-author-role {
      font-family: 'Inter', sans-serif;
      font-size: 10px;
      color: rgba(255,255,255,0.3);
      display: block;
    }

    .lg-separator {
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
    .lg-separator-dot {
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

    .lg-right {
      background: var(--cream);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 64px;
      height: 100vh;
      overflow-y: auto;
    }
    .lg-form-wrap {
      width: 100%;
      max-width: 400px;
      animation: fadeUp 0.5s ease;
    }

    .lg-form-header { margin-bottom: 36px; }
    .lg-form-eyebrow {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }
    .lg-form-eyebrow-line {
      width: 24px;
      height: 1px;
      background: var(--gold);
    }
    .lg-form-eyebrow span {
      font-size: 9px;
      letter-spacing: 0.35em;
      text-transform: uppercase;
      color: var(--gold);
    }
    .lg-form-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 52px;
      font-weight: 300;
      color: var(--forest);
      line-height: 0.9;
      margin-bottom: 10px;
    }
    .lg-form-title em { color: var(--gold); font-style: italic; }
    .lg-form-sub {
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(27,67,50,0.48);
    }

    .lg-field { display: flex; flex-direction: column; gap: 7px; margin-bottom: 18px; }
    .lg-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.35em;
      color: var(--gold);
    }
    .lg-input-wrap { position: relative; }
    .lg-input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(27,67,50,0.3);
      pointer-events: none;
    }
    .lg-input-icon svg { width: 15px; height: 15px; }
    .lg-input {
      width: 100%;
      padding: 14px 16px 14px 42px;
      border: 1px solid rgba(27,67,50,0.12);
      border-radius: 14px;
      background: white;
      color: var(--forest);
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .lg-input:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
    }
    .lg-input::placeholder { color: rgba(27,67,50,0.25); }
    .lg-input.has-toggle { padding-right: 80px; }
    .lg-toggle-btn {
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
    .lg-toggle-btn:hover { color: var(--forest); }

    .lg-field-options {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      margin-bottom: 24px;
      margin-top: -8px;
    }
    .lg-forgot {
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      color: rgba(27,67,50,0.4);
      text-decoration: none;
      transition: color 0.2s;
    }
    .lg-forgot:hover { color: var(--forest); }

    .lg-btn {
      width: 100%;
      padding: 16px 24px;
      background: var(--forest);
      color: white;
      border: none;
      border-radius: 14px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
      letter-spacing: 0.02em;
    }
    .lg-btn:hover:not(:disabled) {
      background: #0d2118;
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(15,43,32,0.28);
    }
    .lg-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .lg-btn-gold {
      background: linear-gradient(135deg, var(--gold), #A67C2E);
    }
    .lg-btn-gold:hover:not(:disabled) {
      background: linear-gradient(135deg, #D4B55A, #C9A84C);
      box-shadow: 0 8px 28px rgba(201,168,76,0.4);
    }

    .lg-spinner {
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }

    .lg-alert {
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
      margin-bottom: 20px;
    }
    .lg-alert svg { width: 14px; height: 14px; flex-shrink: 0; }

    .lg-form-footer {
      text-align: center;
      margin-top: 24px;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(27,67,50,0.42);
    }
    .lg-form-footer a {
      color: var(--forest);
      font-weight: 600;
      text-decoration: none;
      border-bottom: 1px solid rgba(27,67,50,0.2);
      padding-bottom: 1px;
      transition: border-color 0.2s;
    }
    .lg-form-footer a:hover { border-color: var(--forest); }

    .lg-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0;
    }
    .lg-divider-line { flex: 1; height: 0.5px; background: rgba(27,67,50,0.1); }
    .lg-divider span {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: rgba(27,67,50,0.3);
      text-transform: uppercase;
      letter-spacing: 0.2em;
    }

    .lg-vitrine-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 13px;
      border: 0.5px solid rgba(27,67,50,0.12);
      border-radius: 14px;
      color: rgba(27,67,50,0.6);
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      text-decoration: none;
      transition: all 0.2s;
      background: white;
    }
    .lg-vitrine-link:hover {
      border-color: var(--forest);
      color: var(--forest);
    }

    @media (max-width: 1024px) {
      body { grid-template-columns: 1fr; }
      .lg-left { display: none; }
      .lg-separator { display: none; }
      .lg-right {
        background: var(--forest);
        min-height: 100vh;
        height: auto;
      }
      .lg-form-title, .lg-form-eyebrow span { color: white; }
      .lg-form-title em { color: var(--gold); }
      .lg-form-sub { color: rgba(255,255,255,0.5); }
      .lg-input { background: rgba(255,255,255,0.07); color: white; border-color: rgba(255,255,255,0.12); }
      .lg-input::placeholder { color: rgba(255,255,255,0.25); }
      .lg-input:focus { border-color: var(--gold); background: rgba(255,255,255,0.1); }
      .lg-label { color: rgba(201,168,76,0.75); }
      .lg-input-icon { color: rgba(255,255,255,0.3); }
      .lg-toggle-btn { color: rgba(255,255,255,0.45); }
      .lg-forgot { color: rgba(255,255,255,0.4); }
      .lg-forgot:hover { color: white; }
      .lg-vitrine-link { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); }
      .lg-vitrine-link:hover { border-color: rgba(255,255,255,0.3); color: white; }
      .lg-divider-line { background: rgba(255,255,255,0.1); }
      .lg-divider span { color: rgba(255,255,255,0.3); }
      .lg-form-footer { color: rgba(255,255,255,0.4); }
      .lg-form-footer a { color: var(--gold); border-color: rgba(201,168,76,0.3); }
      .lg-alert { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.25); color: #fca5a5; }
    }

    @media (max-width: 520px) {
      .lg-right { padding: 40px 24px; }
    }
  </style>
</head>
<body>

  <div class="lg-left">
    <div class="lg-ghost">IS</div>
    <div class="lg-radial"></div>

    <div class="lg-brand">
      <a class="lg-brand-inner" href="<?= $base_url ?>/">
        <div class="lg-brand-sq">IS</div>
        <div class="lg-brand-text">
          <strong>Ivoire Stay</strong>
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
          Ivoire Stay a transformé la gestion de mon hôtel.
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

  <div class="lg-right">

    <section class="lg-form-wrap"
      x-data="{
        form: { email: '', password: '' },
        loading: false,
        error: null,
        showPass: false,
        async submit() {
          this.error = null;
          if (!this.form.email.trim() || !this.form.password) {
            this.error = 'Veuillez remplir tous les champs.';
            return;
          }
          this.loading = true;
          try {
            const res = await fetch('<?= $base_url ?>/api/auth/login', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(this.form)
            });
            const data = await res.json();
            if (data.success) {
              localStorage.setItem('token', data.data.token);
              localStorage.setItem('user', JSON.stringify(data.data.user));
              const estabs = data.data.establishments ?? [];
              localStorage.setItem('establishments', JSON.stringify(estabs));
              if (Array.isArray(estabs) && estabs.length > 0) {
                const estId = estabs[0].id ?? estabs[0].establishment_id;
                if (estId) localStorage.setItem('establishment_id', String(estId));
              } else {
                try {
                  const payload = JSON.parse(atob(data.data.token.split('.')[1]));
                  if (payload.establishment_id)
                    localStorage.setItem('establishment_id', String(payload.establishment_id));
                } catch (e) {}
              }
              window.location.href = '<?= $base_url ?>/saas';
            } else {
              this.error = data.message ?? 'Email ou mot de passe incorrect.';
            }
          } catch (e) {
            this.error = 'Erreur réseau. Veuillez réessayer.';
          } finally {
            this.loading = false;
          }
        }
      }">

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
          <input
            class="lg-input"
            type="email"
            placeholder="votre@email.ci"
            x-model="form.email"
            @keydown.enter="submit()">
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
          <input
            class="lg-input has-toggle"
            :type="showPass ? 'text' : 'password'"
            placeholder="••••••••"
            x-model="form.password"
            @keydown.enter="submit()">
          <button type="button" class="lg-toggle-btn" @click="showPass = !showPass"
            x-text="showPass ? 'Masquer' : 'Afficher'"></button>
        </div>
      </div>

      <div class="lg-field-options">
        <a class="lg-forgot" href="<?= $base_url ?>/login">Mot de passe oublié ?</a>
      </div>

      <button class="lg-btn lg-btn-gold" type="button" @click="submit()" :disabled="loading">
        <div class="lg-spinner" x-show="loading"></div>
        <span x-show="!loading">Se connecter</span>
        <span x-show="loading">Connexion en cours…</span>
        <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </button>

      <div class="lg-divider">
        <div class="lg-divider-line"></div>
        <span>ou</span>
        <div class="lg-divider-line"></div>
      </div>

      <a class="lg-vitrine-link" href="<?= $base_url ?>/">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Retour à la vitrine publique
      </a>

      <p class="lg-form-footer">
        Pas encore de compte ?
        <a href="<?= $base_url ?>/register">Créer un compte gratuit →</a>
      </p>

    </section>
  </div>

</body>
</html>
