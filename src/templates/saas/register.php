<?php
// Page autonome d'inscription SaaS.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inscription – Ivoire Stay</title>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@0,600;1,600&display=swap');
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { min-height: 100vh; font-family: 'Inter', sans-serif; overflow-x: hidden; }
  .auth-bg { position: fixed; inset: 0; z-index: 0; background-image: url('<?= $base_url ?>/assets/bg_auth.jpg'); background-size: cover; background-position: center; }
  .auth-overlay { position: fixed; inset: 0; z-index: 1; background: linear-gradient(160deg, rgba(8,20,14,0.55) 0%, rgba(12,35,22,0.42) 50%, rgba(8,20,14,0.58) 100%); }
  .auth-nav { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 200; display: inline-flex; align-items: center; gap: 24px; padding: 10px 20px 10px 14px; background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); border-radius: 50px; white-space: nowrap; }
  .auth-nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
  .auth-nav-brand img { height: 32px; width: auto; filter: brightness(0) invert(1); opacity: 0.95; }
  .auth-nav-brand-text { display: flex; flex-direction: column; gap: 1px; }
  .auth-nav-brand-text span:first-child { font-family: 'Cormorant Garamond', serif; font-size: 17px; font-weight: 600; color: white; line-height: 1; }
  .auth-nav-brand-text span:last-child { font-size: 9px; font-weight: 500; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.1em; }
  .auth-nav-divider { width: 1px; height: 20px; background: rgba(255,255,255,0.15); }
  .auth-nav-links { display: flex; align-items: center; gap: 8px; }
  .auth-nav-link { font-size: 12px; font-weight: 500; color: rgba(255,255,255,0.6); text-decoration: none; padding: 5px 12px; border-radius: 50px; border: 1px solid transparent; transition: all 0.25s; }
  .auth-nav-link:hover { color: white; border-color: rgba(255,255,255,0.2); }
  .auth-nav-link.primary { background: linear-gradient(135deg, #C9A84C, #A67C2E); color: white; font-weight: 600; border-color: transparent; box-shadow: 0 2px 10px rgba(201,168,76,0.3); }
  .auth-nav-link.back { font-size: 11px; color: rgba(255,255,255,0.35); padding: 4px 8px; }
  .deco-blob { position: fixed; pointer-events: none; z-index: 2; border-radius: 50%; }
  .deco-blob.a { width: 200px; height: 200px; top: 18%; left: 8%; background: rgba(201,168,76,0.08); filter: blur(2px); border: 1px solid rgba(201,168,76,0.18); }
  .deco-blob.b { width: 140px; height: 140px; bottom: 18%; right: 12%; background: rgba(255,255,255,0.04); filter: blur(2px); border: 1px solid rgba(255,255,255,0.12); }
  .deco-blob.c { width: 100px; height: 100px; top: 12%; right: 18%; background: rgba(201,168,76,0.06); filter: blur(1px); border: 1px solid rgba(201,168,76,0.16); }
  .form-float { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 50; width: 100%; max-width: 560px; padding: 0 20px; }
  .form-title { text-align: center; margin-bottom: 32px; }
  .form-icon-box { width: 52px; height: 52px; border-radius: 14px; background: rgba(201,168,76,0.15); border: 1px solid rgba(201,168,76,0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
  .form-title h1 { font-size: 28px; font-weight: 800; color: white; margin-bottom: 6px; text-shadow: 0 2px 24px rgba(0,0,0,0.6); letter-spacing: -0.02em; }
  .form-title p { font-size: 14px; color: rgba(255,255,255,0.55); text-shadow: 0 1px 8px rgba(0,0,0,0.4); }
  .field-group { margin-bottom: 20px; }
  .field-label { display: block; font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; text-shadow: 0 1px 4px rgba(0,0,0,0.4); }
  .field-wrap { position: relative; }
  .field-wrap svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: rgba(255,255,255,0.35); pointer-events: none; }
  .field-input { width: 100%; padding: 14px 16px 14px 42px; background: rgba(255,255,255,0.07); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.16); border-radius: 14px; color: white; font-size: 14px; transition: all 0.3s; }
  .field-input::placeholder { color: rgba(255,255,255,0.28); }
  .field-input:focus { outline: none; border-color: rgba(201,168,76,0.65); background: rgba(255,255,255,0.10); box-shadow: 0 0 0 3px rgba(201,168,76,0.1), 0 4px 20px rgba(0,0,0,0.2); }
  .field-input.with-action { padding-right: 80px; }
  .field-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: rgba(255,255,255,0.4); font-size: 12px; font-weight: 500; cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: color 0.2s; font-family: 'Inter', sans-serif; }
  .field-toggle:hover { color: rgba(255,255,255,0.75); }
  .btn-submit { width: 100%; padding: 15px 24px; background: linear-gradient(135deg, #C9A84C 0%, #A67C2E 100%); color: white; border: none; border-radius: 14px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; box-shadow: 0 4px 20px rgba(201,168,76,0.4), 0 1px 0 rgba(255,255,255,0.1) inset; font-family: 'Inter', sans-serif; margin-top: 8px; }
  .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(201,168,76,0.55); }
  .btn-submit:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }
  .form-footer-link { text-align: center; margin-top: 20px; font-size: 13px; color: rgba(255,255,255,0.4); text-shadow: 0 1px 4px rgba(0,0,0,0.3); }
  .form-footer-link a { color: #C9A84C; font-weight: 600; text-decoration: none; }
  .alert-err { background: rgba(220,38,38,0.1); border: 1px solid rgba(220,38,38,0.25); border-radius: 12px; padding: 11px 14px; margin-bottom: 18px; color: #fca5a5; font-size: 13px; display: flex; align-items: center; gap: 8px; }
  .spinner { width: 17px; height: 17px; border: 2px solid rgba(255,255,255,0.25); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .stepper-wrap { display: flex; align-items: center; justify-content: center; gap: 0; margin-bottom: 6px; }
  .s-circle { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; transition: all 0.35s; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.3); font-family: 'Inter', sans-serif; }
  .s-circle.on { background: linear-gradient(135deg, #C9A84C, #A67C2E); color: white; border-color: #C9A84C; box-shadow: 0 4px 14px rgba(201,168,76,0.45); }
  .s-line { width: 80px; height: 1px; background: rgba(255,255,255,0.1); margin: 0 8px; transition: background 0.35s; }
  .s-line.on { background: #C9A84C; }
  .stepper-labels { display: flex; justify-content: space-between; padding: 0 0px; margin-bottom: 24px; }
  .stepper-labels span { font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.07em; }
  .toggle-group { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px; }
  .type-btn { padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.45); font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 8px; }
  .type-btn.active { background: rgba(201,168,76,0.18); border-color: rgba(201,168,76,0.5); color: #C9A84C; }
  .info-left { position: fixed; left: 40px; bottom: 48px; z-index: 40; width: 260px; display:flex; flex-direction:column; gap:12px; }
  .info-right { position: fixed; right: 40px; top: 90px; z-index: 40; width: 260px; display:flex; flex-direction:column; gap:12px; }
  .float-card { background: rgba(12,30,20,0.55); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 18px 20px; }
  .float-card-title { font-size: 12px; font-weight: 700; color: #C9A84C; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 12px; }
  .float-card p { font-size: 12px; color: rgba(255,255,255,0.6); line-height: 1.65; }
  .float-card a { color: #C9A84C; text-decoration: none; font-weight: 600; font-size: 12px; }
  .plan-pill { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.04); margin-bottom: 6px; }
  .plan-pill:last-child { margin-bottom: 0; }
  .plan-pill strong { font-size: 12px; font-weight: 600; color: white; }
  .plan-badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 50px; }
  .badge-free { background: rgba(22,163,74,0.18); color: #4ade80; border: 1px solid rgba(22,163,74,0.25); }
  .badge-pro { background: rgba(201,168,76,0.18); color: #C9A84C; border: 1px solid rgba(201,168,76,0.3); }
  .badge-biz { background: rgba(52,211,153,0.12); color: #34d399; border: 1px solid rgba(52,211,153,0.2); }
  .info-item { display:flex; align-items:flex-start; gap:10px; margin-bottom:10px; }
  .info-item:last-child { margin-bottom:0; }
  .info-icon { width:26px; height:26px; flex-shrink:0; border-radius:8px; background: rgba(201,168,76,0.12); border:1px solid rgba(201,168,76,0.2); display:flex; align-items:center; justify-content:center; margin-top:1px; }
  .info-icon svg { width:12px; height:12px; color:#C9A84C; }
  .info-item span { font-size:12px; color: rgba(255,255,255,0.65); line-height:1.5; }
  @media (max-width:1200px) { .info-left, .info-right { display:none; } .form-float { padding:0 24px; } .auth-nav { left:16px; transform:none; } }
  @media (max-width:520px) { .form-float { padding:0 16px; } .form-title h1 { font-size:24px; } .auth-nav { top:12px; left:12px; transform:none; padding:8px 14px 8px 12px; gap:12px; } }
  </style>
</head>
<body>
  <div class="auth-bg"></div>
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

  <section class="form-float" x-data="{ form:{ name:'', email:'', phone:'', password:'', password_confirm:'', establishment_name:'', establishment_type:'hotel' }, step:1, loading:false, error:null, showPass:false, validateStep1(){ this.error=null; if(!this.form.name.trim()) return this.error='Nom complet requis.'; if(!this.form.email.match(/^[^@]+@[^@]+\.[^@]+$/)) return this.error='Email invalide.'; if(!this.form.phone.trim()) return this.error='Téléphone requis.'; if(this.form.password.length < 8) return this.error='Mot de passe : 8 caractères min.'; if(this.form.password !== this.form.password_confirm) return this.error='Mots de passe différents.'; this.step=2; }, async submit(){ this.error=null; if(!this.form.establishment_name.trim()){ this.error='Nom de l\'établissement requis.'; return; } this.loading=true; try{ const res=await fetch('<?= $base_url ?>/api/auth/register',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(this.form) }); const data=await res.json(); if(data.success){ localStorage.setItem('token',data.data.token); localStorage.setItem('user',JSON.stringify(data.data.user)); localStorage.setItem('establishments',JSON.stringify(data.data.establishments ?? [])); const e=data.data.establishments ?? []; if(e.length) localStorage.setItem('establishment_id', e[0].id); window.location.href='<?= $base_url ?>/saas'; } else { this.error=data.message ?? 'Erreur inscription.'; } } catch(e){ this.error='Erreur réseau.'; } finally{ this.loading=false; } } }">
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
