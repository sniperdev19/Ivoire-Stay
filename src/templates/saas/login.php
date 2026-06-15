<?php
// Page autonome de connexion SaaS.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion – Ivoire Stay</title>
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
  .form-float { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 50; width: 100%; max-width: 460px; padding: 0 20px; }
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
  .info-left { position: fixed; left: 40px; bottom: 48px; z-index: 40; width: 260px; display:flex; flex-direction:column; gap:12px; }
  .info-right { position: fixed; right: 40px; top: 90px; z-index: 40; width: 260px; display:flex; flex-direction:column; gap:12px; }
  .float-card { background: rgba(12,30,20,0.55); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 18px 20px; }
  .float-card-title { font-size: 12px; font-weight: 700; color: #C9A84C; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 12px; }
  .float-card p { font-size: 12px; color: rgba(255,255,255,0.6); line-height: 1.65; }
  .float-card a { color: #C9A84C; text-decoration: none; font-weight: 600; font-size: 12px; }
  .info-item { display:flex; align-items:flex-start; gap:10px; margin-bottom:10px; }
  .info-item:last-child { margin-bottom:0; }
  .info-icon { width:26px; height:26px; flex-shrink:0; border-radius:8px; background: rgba(201,168,76,0.12); border:1px solid rgba(201,168,76,0.2); display:flex; align-items:center; justify-content:center; margin-top:1px; }
  .info-icon svg { width:12px; height:12px; color:#C9A84C; }
  .info-item span { font-size:12px; color: rgba(255,255,255,0.65); line-height:1.5; }
  .stat-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; }
  .stat-row + .stat-row { border-top:1px solid rgba(255,255,255,0.05); }
  .stat-row span:first-child { font-size:11px; color: rgba(255,255,255,0.5); }
  .stat-val { font-family:'Cormorant Garamond', serif; font-size:18px; font-weight:700; color:#C9A84C; }
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
      <a href="<?= $base_url ?>/register">Créer un compte gratuit →</a>
    </div>
  </section>

  <section class="form-float" x-data="{ form:{ email:'', password:'' }, loading:false, error:null, showPass:false, async submit(){ this.error=null; if(!this.form.email.trim()||!this.form.password){ this.error='Veuillez remplir tous les champs.'; return;} this.loading=true; try{ const res=await fetch('<?= $base_url ?>/api/auth/login',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(this.form) }); const data=await res.json(); if(data.success){ localStorage.setItem('token',data.data.token); localStorage.setItem('user',JSON.stringify(data.data.user)); localStorage.setItem('establishments',JSON.stringify(data.data.establishments ?? [])); const e=data.data.establishments ?? []; if(e.length) localStorage.setItem('establishment_id', e[0].id); window.location.href='<?= $base_url ?>/saas'; } else { this.error=data.message ?? 'Email ou mot de passe incorrect.'; } } catch(e){ this.error='Erreur réseau. Veuillez réessayer.'; } finally{ this.loading=false; } } }">
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

    <div class="form-footer-link">Pas encore de compte ?<a href="<?= $base_url ?>/register">Créer un compte →</a></div>
  </section>
</body>
</html>
