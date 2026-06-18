<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>
<style>
:root{
  --gold: #C9A84C; --forest: #1B4332; --cream: #FAF7F2; --mid: #2D6A4F;
}
.home-page { overflow-x: hidden; font-family: 'Inter', sans-serif; }

/* Section 1 - Hero */
.hm-hero { position: relative; min-height: 100vh; background-size: cover; background-position: center; background-attachment: fixed; display:flex; flex-direction:column; justify-content:space-between; overflow:hidden; }
.hm-hero-overlay { position:absolute; inset:0; background: linear-gradient(135deg, rgba(10,30,20,0.90) 0%, rgba(15,43,32,0.72) 50%, rgba(10,30,20,0.50) 100%); pointer-events:none; }
.hm-hero-grid { position:relative; z-index:2; display:grid; grid-template-columns:55fr 45fr; gap:0; flex:1; padding:120px 80px 48px; align-items:center; }
.hm-hero-left { position:relative; padding-right:64px; }
.hm-hero-ghost { position:absolute; top:-60px; left:-20px; font-family:'Cormorant Garamond',serif; font-size:260px; font-weight:700; color:rgba(201,168,76,0.05); line-height:1; pointer-events:none; user-select:none; }
.hm-pill { display:inline-block; padding:6px 16px; border:1px solid rgba(201,168,76,0.4); color:var(--gold); font-family:'Inter',sans-serif; font-size:10px; letter-spacing:0.3em; text-transform:uppercase; border-radius:3px; }
.hm-hero-title { font-family:'Cormorant Garamond',serif; font-size:88px; font-weight:300; color:white; line-height:0.9; margin-bottom:24px; }
.hm-hero-title em { color:var(--gold); font-style:italic; display:block; }
.hm-hero-rule { width:48px; height:1px; background:var(--gold); margin-bottom:20px; }
.hm-hero-sub { font-family:'Inter',sans-serif; font-size:15px; color:rgba(255,255,255,0.6); line-height:1.8; max-width:400px; margin-bottom:32px; }
.hm-hero-btns { display:flex; gap:14px; flex-wrap:wrap; }
.hm-btn-p { padding:14px 32px; background:var(--cream); color:var(--forest); border-radius:999px; font-family:'Inter',sans-serif; font-size:14px; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:8px; }
.hm-btn-g { padding:14px 32px; border:1px solid rgba(255,255,255,0.28); color:rgba(255,255,255,0.85); border-radius:999px; font-family:'Inter',sans-serif; font-size:14px; text-decoration:none; }

/* Search widget */
.hm-hero-right { position:relative; z-index:2; display:flex; align-items:center; justify-content:flex-end; }
.hm-search-card { background: rgba(250,247,242,0.97); backdrop-filter: blur(24px); border-radius:28px; padding:36px 36px 40px; width:100%; max-width:420px; box-shadow: 0 40px 80px rgba(0,0,0,0.3); }
.hm-search-title { font-family:'Cormorant Garamond',serif; font-size:28px; color:var(--forest); font-weight:400; margin-bottom:6px; }
.hm-search-sub { font-family:'Inter',sans-serif; font-size:13px; color:rgba(27,67,50,0.55); line-height:1.6; }
.hm-search-rule { width:32px; height:1px; background:var(--gold); margin-top:14px; }
.hm-search-form { display:flex; flex-direction:column; gap:16px; }
.hm-field { display:flex; flex-direction:column; gap:7px; }
.hm-field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.hm-label { font-family:'Inter',sans-serif; font-size:9px; text-transform:uppercase; letter-spacing:0.3em; color:var(--gold); }
.hm-input { width:100%; padding:12px 16px; border:1px solid rgba(27,67,50,0.12); border-radius:12px; background:white; color:var(--forest); font-family:'Inter',sans-serif; font-size:13px; outline:none; }
.hm-input:focus { border-color:var(--gold); }
.hm-select { cursor:pointer; background-position:right 12px center; padding-right:36px; }
.hm-search-btn { display:flex; align-items:center; justify-content:center; gap:10px; width:100%; padding:15px 24px; background:var(--forest); color:white; border:none; border-radius:999px; font-family:'Inter',sans-serif; font-size:14px; font-weight:600; cursor:pointer; }

/* Hero stats strip */
.hm-hero-strip { position:relative; z-index:2; display:grid; grid-template-columns:repeat(4,1fr); background:rgba(6,14,9,0.82); backdrop-filter:blur(20px); border-top:1px solid rgba(201,168,76,0.12); }
.hm-hero-stat { padding:18px 28px; border-right:0.5px solid rgba(255,255,255,0.06); display:flex; flex-direction:column; gap:4px; }
.hm-hero-stat strong { font-family:'Cormorant Garamond',serif; font-size:30px; font-weight:700; color:white; }
.hm-hero-stat strong span { color:var(--gold); font-size:18px; }
.hm-hero-stat span { font-family:'Inter',sans-serif; font-size:9px; text-transform:uppercase; letter-spacing:0.28em; color:rgba(255,255,255,0.38); }

/* Section 2 - Avantages */
.hm-avantages { background:var(--cream); padding:80px 80px 72px; }
.hm-av-header { display:flex; align-items:center; gap:16px; margin-bottom:40px; }
.hm-av-rule { flex:1; height:0.5px; background:rgba(27,67,50,0.1); }
.hm-av-tag { font-family:'Inter',sans-serif; font-size:10px; letter-spacing:0.35em; text-transform:uppercase; color:var(--gold); white-space:nowrap; }
.hm-av-intro { display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:end; margin-bottom:56px; padding-bottom:48px; border-bottom:0.5px solid rgba(27,67,50,0.08); }
.hm-av-title { font-family:'Cormorant Garamond',serif; font-size:64px; font-weight:300; color:var(--forest); }
.hm-av-title em { color:var(--gold); font-style:italic; display:block; }
.hm-av-sub { font-family:'Inter',sans-serif; font-size:15px; color:rgba(27,67,50,0.6); line-height:1.8; }
.hm-av-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:0; }
.hm-av-item { padding:0 36px 0 0; border-right:0.5px solid rgba(27,67,50,0.08); display:flex; flex-direction:column; gap:12px; }
.hm-av-item:last-child { border-right:none; padding-right:0; }
.hm-av-item:not(:first-child) { padding-left:36px; }
.hm-av-num { font-family:'Cormorant Garamond',serif; font-size:64px; font-weight:700; color:rgba(201,168,76,0.12); }
.hm-av-bar { width:24px; height:2px; background:var(--gold); margin-bottom:4px; }
.hm-av-name { font-family:'Cormorant Garamond',serif; font-size:26px; color:var(--forest); font-weight:400; }
.hm-av-desc { font-family:'Inter',sans-serif; font-size:13px; color:rgba(27,67,50,0.58); line-height:1.75; }

/* Section 3 - Destinations (carousel) wrapper */
.hm-dest { position:relative; overflow:hidden; padding:80px 0 100px; background-size:cover; background-position:center; background-attachment:fixed; }
.hm-dest-header { text-align:center; padding:0 80px; margin-bottom:56px; position:relative; z-index:2; }
.hm-dest-header-top { display:flex; align-items:center; gap:16px; justify-content:center; margin-bottom:20px; }
.hm-dest-rule { width:48px; height:0.5px; background:rgba(201,168,76,0.4); }
.hm-dest-tag { font-family:'Inter',sans-serif; font-size:10px; letter-spacing:0.35em; text-transform:uppercase; color:rgba(201,168,76,0.75); }
.hm-dest-title { font-family:'Cormorant Garamond',serif; font-size:72px; font-weight:400; color:white; line-height:1.0; margin-bottom:16px; }
.hm-dest-title em { color:var(--gold); font-style:italic; }
.hm-dest-sub { font-family:'Inter',sans-serif; font-size:15px; color:rgba(255,255,255,0.6); max-width:520px; margin:0 auto; line-height:1.8; }

/* Carousel inner styles (moved from inline <style>) */
.dest-slide-item { flex-shrink:0; cursor:pointer; transition:all 0.6s cubic-bezier(0.4,0,0.2,1); }
.dest-img-wrap { overflow:hidden; position:relative; transition:all 0.6s cubic-bezier(0.4,0,0.2,1); }
.dest-img-wrap img { width:100%; height:100%; object-fit:cover; display:block; transition: transform 0.7s cubic-bezier(0.4,0,0.2,1); }
.dest-slide-item:hover .dest-img-wrap img { transform:scale(1.07); }
.dest-hover-desc { position:absolute; bottom:0; left:0; right:0; padding:28px 24px 24px 24px; background: linear-gradient(to top, rgba(10,30,20,0.97) 0%, rgba(10,30,20,0.75) 55%, transparent 100%); opacity:0; transform:translateY(8px); transition:opacity 0.4s ease, transform 0.4s ease; display:flex; flex-direction:column; }
.dest-slide-item:hover .dest-hover-desc { opacity:1; transform:translateY(0); }
.dest-nav-btn { width:48px; height:48px; border-radius:50%; background:rgba(255,255,255,0.12); backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,0.25); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.3s; color:white; flex-shrink:0; }
.dest-nav-btn:hover { background:var(--gold); border-color:var(--gold); transform:scale(1.1); }
.d-dot { height:4px; border-radius:2px; background:rgba(255,255,255,0.3); cursor:pointer; transition:all 0.4s ease; }
.d-dot.d-dot-active { width:32px!important; background:var(--gold); }
.dest-grid-card { position:relative; overflow:hidden; border-radius:20px; cursor:pointer; transition:transform 0.35s ease, box-shadow 0.35s ease; }
.dest-grid-card:hover { transform:translateY(-6px); box-shadow:0 20px 48px rgba(0,0,0,0.35); }
.dest-grid-card img { width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.5s ease; }
.dest-grid-card:hover img { transform:scale(1.05); }
.dest-grid-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(10,30,20,0.90) 0%, rgba(10,30,20,0.35) 50%, transparent 100%); transition:background 0.4s ease; }
.dest-grid-content { position:absolute; bottom:0; left:0; right:0; padding:20px; }

/* Section 4 - How */
.hm-how { display:grid; grid-template-columns:420px 1fr; background:var(--forest); min-height:520px; }
.hm-how-left { position:relative; padding:72px 52px; border-right:0.5px solid rgba(201,168,76,0.1); display:flex; flex-direction:column; justify-content:flex-end; overflow:hidden; }
.hm-how-ghost { position:absolute; top:-20px; left:-10px; font-family:'Cormorant Garamond',serif; font-size:200px; font-weight:700; color:rgba(201,168,76,0.05); }
.hm-how-rule { width:32px; height:1px; background:var(--gold); }
.hm-how-tag { font-family:'Inter',sans-serif; font-size:9px; letter-spacing:0.35em; text-transform:uppercase; color:rgba(201,168,76,0.6); }
.hm-how-title { font-family:'Cormorant Garamond',serif; font-size:64px; font-weight:300; color:white; line-height:0.95; }
.hm-how-title em { color:var(--gold); font-style:italic; }
.hm-how-sub { font-family:'Inter',sans-serif; font-size:13px; color:rgba(255,255,255,0.45); line-height:1.75; max-width:280px; }
.hm-how-btn { display:inline-block; padding:12px 28px; background:var(--gold); color:white; border-radius:999px; font-family:'Inter',sans-serif; font-size:13px; font-weight:600; text-decoration:none; }
.hm-how-right { padding:72px 64px; display:flex; flex-direction:column; justify-content:center; }
.hm-how-step { display:grid; grid-template-columns:80px 1fr; gap:20px; align-items:start; padding:28px 0; }
.hm-how-step-num { font-family:'Cormorant Garamond',serif; font-size:52px; font-weight:700; color:rgba(201,168,76,0.15); }
.hm-how-step-title { font-family:'Cormorant Garamond',serif; font-size:28px; color:white; font-weight:400; margin-bottom:8px; }
.hm-how-step-desc { font-family:'Inter',sans-serif; font-size:13px; color:rgba(255,255,255,0.45); line-height:1.75; }
.hm-how-divider { height:0.5px; background:rgba(255,255,255,0.06); margin-left:100px; }

/* Section 5 - Testimonials */
.hm-testi { background:var(--cream); padding:80px 80px; border-top:0.5px solid rgba(27,67,50,0.08); }
.hm-testi-header { display:flex; align-items:center; gap:16px; margin-bottom:52px; }
.hm-testi-rule { flex:1; height:0.5px; background:rgba(27,67,50,0.1); }
.hm-testi-tag { font-family:'Inter',sans-serif; font-size:10px; letter-spacing:0.35em; text-transform:uppercase; color:var(--gold); }
.hm-testi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
.hm-testi-item { background:white; border-radius:20px; padding:36px 32px; border:0.5px solid rgba(27,67,50,0.08); display:flex; flex-direction:column; gap:20px; }
.hm-testi-featured { background:var(--forest); border-color:transparent; }
.hm-testi-mark { font-family:'Cormorant Garamond',serif; font-size:64px; color:var(--gold); }
.hm-testi-quote { font-family:'Cormorant Garamond',serif; font-size:20px; font-style:italic; color:var(--forest); line-height:1.5; }
.hm-testi-featured .hm-testi-quote { color:white; }
.hm-testi-author { display:flex; align-items:center; gap:12px; padding-top:16px; border-top:0.5px solid rgba(27,67,50,0.08); }
.hm-testi-avatar { width:40px; height:40px; border-radius:50%; background:rgba(201,168,76,0.15); display:grid; place-items:center; font-family:'Inter',sans-serif; font-size:11px; font-weight:600; color:var(--gold); }
.hm-testi-name { display:block; font-family:'Inter',sans-serif; font-size:13px; font-weight:600; color:var(--forest); }
.hm-testi-role { display:block; font-family:'Inter',sans-serif; font-size:11px; color:rgba(27,67,50,0.45); margin-top:2px; }
.hm-testi-featured .hm-testi-name { color:white; }
.hm-testi-featured .hm-testi-role { color:rgba(255,255,255,0.4); }

/* Section 6 - CTA */
.hm-cta { background:var(--cream); display:grid; grid-template-columns:6px 1fr; border-top:0.5px solid rgba(27,67,50,0.08); }
.hm-cta-bar { background:var(--gold); }
.hm-cta-body { padding:80px 80px; position:relative; overflow:hidden; }
.hm-cta-deco { position:absolute; right:60px; top:50%; transform:translateY(-50%); font-family:'Cormorant Garamond',serif; font-size:240px; font-weight:700; color:rgba(27,67,50,0.04); }
.hm-cta-eyebrow { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
.hm-cta-dot { width:7px; height:7px; border-radius:50%; background:var(--gold); }
.hm-cta-eyebrow span { font-family:'Inter',sans-serif; font-size:10px; letter-spacing:0.35em; text-transform:uppercase; color:rgba(27,67,50,0.42); }
.hm-cta-title { font-family:'Cormorant Garamond',serif; font-size:80px; font-weight:400; color:var(--forest); line-height:1.0; max-width:16ch; margin-bottom:20px; }
.hm-cta-title em { color:var(--gold); font-style:italic; }
.hm-cta-sub { font-family:'Inter',sans-serif; font-size:15px; color:rgba(27,67,50,0.58); line-height:1.9; max-width:520px; margin-bottom:32px; }
.hm-cta-btns { display:flex; gap:14px; flex-wrap:wrap; }
.hm-cta-btn-p { padding:16px 40px; background:var(--forest); color:white; border-radius:999px; font-family:'Inter',sans-serif; font-size:14px; font-weight:600; text-decoration:none; }
.hm-cta-btn-o { padding:16px 40px; border:1px solid rgba(27,67,50,0.22); color:var(--forest); border-radius:999px; font-family:'Inter',sans-serif; font-size:14px; text-decoration:none; }

/* Responsive */
@media (max-width:1200px){ .hm-hero-grid{ padding:100px 40px 40px } .hm-hero-title{ font-size:68px } .hm-avantages{ padding:64px 40px } .hm-av-intro{ grid-template-columns:1fr } .hm-av-title{ font-size:52px } .hm-testi{ padding:64px 40px } .hm-how{ grid-template-columns:320px 1fr } .hm-cta-body{ padding:64px 40px } .hm-cta-title{ font-size:60px } }
@media (max-width:1024px){ .hm-hero-grid{ grid-template-columns:1fr; gap:40px } .hm-hero-left{ padding-right:0 } .hm-hero-right{ justify-content:flex-start } .hm-search-card{ max-width:100% } .hm-hero-strip{ grid-template-columns:repeat(2,1fr) } .hm-av-grid{ grid-template-columns:repeat(2,1fr) } .hm-av-item:nth-child(2n){ border-right:none } .hm-dest-title{ font-size:52px } .hm-testi-grid{ grid-template-columns:1fr; max-width:520px; margin:0 auto } .hm-how{ grid-template-columns:1fr } .hm-how-left{ border-right:none; border-bottom:0.5px solid rgba(201,168,76,0.1) } }
@media (max-width:768px){ .hm-hero-grid{ padding:80px 24px 32px } .hm-hero-title{ font-size:52px } .hm-hero-strip{ grid-template-columns:repeat(2,1fr) } .hm-avantages{ padding:48px 24px } .hm-av-grid{ grid-template-columns:1fr } .hm-av-item{ border-right:none; padding:20px 0; border-bottom:0.5px solid rgba(27,67,50,0.07) } .hm-av-item:last-child{ border-bottom:none } .hm-dest-header{ padding:0 24px } .hm-dest-title{ font-size:40px } .hm-how-left{ padding:48px 24px } .hm-how-right{ padding:40px 24px } .hm-how-title{ font-size:48px } .hm-testi{ padding:48px 24px } .hm-cta-body{ padding:48px 24px } .hm-cta-title{ font-size:44px } .hm-cta-deco{ display:none } .hm-cta-btns{ flex-direction:column } .hm-hero-btns{ flex-direction:column } }
</style>

<div class="home-page">

<!-- SECTION 1 - HERO -->
<section class="hm-hero" style="background-image:url('<?= $base ?>/assets/bg_home.jpg')">
  <div class="hm-hero-overlay"></div>
  <div class="hm-hero-grid">
    <div class="hm-hero-left">
      <div class="hm-hero-ghost">IS</div>
      <div class="hm-hero-eyebrow"><span class="hm-pill">Nouveauté partagée</span></div>
      <h1 class="hm-hero-title">Réservez des séjours<br><em>mémorables</em><br>en Côte d'Ivoire</h1>
      <div class="hm-hero-rule"></div>
      <p class="hm-hero-sub">Ivoire Stay connecte les voyageurs aux meilleurs établissements locaux, avec paiement Mobile Money et réservation instantanée.</p>
      <div class="hm-hero-btns">
        <a href="<?= $base ?>/search" class="hm-btn-p">Explorer les destinations <span>→</span></a>
        <a href="<?= $base ?>/tarifs" class="hm-btn-g">Voir les tarifs SaaS</a>
      </div>
    </div>

    <div class="hm-hero-right">
      <div class="hm-search-card">
        <div class="hm-search-header">
          <div class="hm-search-title">Trouvez votre séjour</div>
          <p class="hm-search-sub">Destination, type d'hébergement et dates.</p>
          <div class="hm-search-rule"></div>
        </div>
        <form class="hm-search-form">
          <div class="hm-field">
            <label class="hm-label">Destination</label>
            <input type="text" class="hm-input" placeholder="Abidjan, Yamoussoukro…">
          </div>
          <div class="hm-field-row">
            <div class="hm-field">
              <label class="hm-label">Arrivée</label>
              <input type="date" class="hm-input">
            </div>
            <div class="hm-field">
              <label class="hm-label">Départ</label>
              <input type="date" class="hm-input">
            </div>
          </div>
          <div class="hm-field">
            <label class="hm-label">Type</label>
            <select class="hm-input hm-select">
              <option>Tous les types</option>
              <option>Hôtel</option>
              <option>Résidence</option>
              <option>Villa</option>
            </select>
          </div>
          <button type="button" onclick="window.location.href='<?= $base ?>/search'" class="hm-search-btn">Rechercher un hébergement
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="hm-hero-strip">
    <div class="hm-hero-stat"><strong>500<span>+</span></strong><span>Établissements</span></div>
    <div class="hm-hero-stat"><strong>12 000<span>+</span></strong><span>Réservations</span></div>
    <div class="hm-hero-stat"><strong>4.9<span>/5</span></strong><span>Note moyenne</span></div>
    <div class="hm-hero-stat"><strong>3<span> villes</span></strong><span>Couverture nationale</span></div>
  </div>
</section>

<!-- SECTION 2 - AVANTAGES -->
<section class="hm-avantages">
  <div class="hm-av-header">
    <div class="hm-av-rule"></div>
    <span class="hm-av-tag">Nos atouts</span>
    <div class="hm-av-rule"></div>
  </div>
  <div class="hm-av-intro">
    <h2 class="hm-av-title">Une expérience pensée<br><em>pour l'Afrique</em></h2>
    <p class="hm-av-sub">Ivoire Stay offre une plateforme simple, rapide et sécurisée pour les établissements et les voyageurs.</p>
  </div>
  <div class="hm-av-grid">
    <div class="hm-av-item">
      <div class="hm-av-num">01</div>
      <div class="hm-av-content"><div class="hm-av-bar"></div><h3 class="hm-av-name">Réservation instantanée</h3><p class="hm-av-desc">Confirmez un séjour en quelques secondes, même avec Mobile Money.</p></div>
    </div>
    <div class="hm-av-item">
      <div class="hm-av-num">02</div>
      <div class="hm-av-content"><div class="hm-av-bar"></div><h3 class="hm-av-name">Paiement sécurisé</h3><p class="hm-av-desc">Toutes les données sont protégées, pour les voyageurs et les établissements.</p></div>
    </div>
    <div class="hm-av-item">
      <div class="hm-av-num">03</div>
      <div class="hm-av-content"><div class="hm-av-bar"></div><h3 class="hm-av-name">Gestion centralisée</h3><p class="hm-av-desc">Gérez chambres, réservations et facturation depuis un même tableau de bord.</p></div>
    </div>
    <div class="hm-av-item">
      <div class="hm-av-num">04</div>
      <div class="hm-av-content"><div class="hm-av-bar"></div><h3 class="hm-av-name">Établissements vérifiés</h3><p class="hm-av-desc">Chaque établissement est validé avant d'être publié sur la plateforme.</p></div>
    </div>
  </div>
</section>

<!-- SECTION 3 - DESTINATIONS (conserver intégralement le bloc Alpine.js) -->
<section class="hm-dest" style="background-image:url('<?= $base ?>/assets/back_destination.jpg')">
  <div class="hm-dest-header">
    <div class="hm-dest-header-top">
      <div class="hm-dest-rule"></div>
      <span class="hm-dest-tag">Destinations phares</span>
      <div class="hm-dest-rule"></div>
    </div>
    <h2 class="hm-dest-title">Explorez la <em>Côte d'Ivoire</em></h2>
    <p class="hm-dest-sub">De la lagune d'Abidjan aux plages de Sassandra, découvrez plus de <strong>13 destinations</strong> soigneusement référencées sur notre plateforme.</p>
  </div>

  <!-- Début bloc Alpine.js conservé intégralement -->
  <div style="position:relative;z-index:2; padding:60px 0 100px 0;">
    <div x-data="{
      current: 0,
      paused: false,
      timer: null,
      items: [
        { name:'Abidjan', region:'District Autonome', count:142, tag:'Ville & Affaires', desc:'Capitale économique entre lagune, gratte-ciels et vie nocturne trépidante. Hub incontournable de l\'Afrique de l\'Ouest.', img:'<?= $base ?>/assets/carrouss1.jpg' },
        { name:'Yamoussoukro', region:'Centre', count:38, tag:'Culture & Histoire', desc:'Capitale politique, terre de la Basilique Notre-Dame de la Paix et de sérénité ivoirienne.', img:'<?= $base ?>/assets/carrouss2.jpg' },
        { name:'Grand-Bassam', region:'Sud-Comoé', count:27, tag:'Plage & Patrimoine', desc:'Ancienne capitale coloniale classée UNESCO, plages dorées, galeries d\'art et fruits de mer.', img:'<?= $base ?>/assets/carrouss3.jpg' },
        { name:'San-Pédro', region:'Bas-Sassandra', count:19, tag:'Nature & Évasion', desc:'Port moderne, forêt tropicale et eaux turquoise du Sud-Ouest ivoirien.', img:'<?= $base ?>/assets/carrouss4.jpg' },
        { name:'Assinie', region:'Sud-Comoé', count:31, tag:'Plage & Détente', desc:'Station balnéaire prisée entre océan Atlantique et lagune Aby. Cocotiers et sable blanc.', img:'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800' }
      ],
      get total() { return this.items.length; },
      idx(offset) { return (this.current + offset + this.total) % this.total; },
      goTo(i) { this.current = i; },
      goNext() { this.current = this.idx(1); },
      goPrev() { this.current = this.idx(-1); },
      startAuto() { this.timer = setInterval(() => { if (!this.paused) this.goNext(); }, 3800); },
      stopAuto() { this.paused = true; },
      resumeAuto() { this.paused = false; },
      styleFor(pos) {
        const styles = {
          center:'width:440px;height:520px;z-index:10;opacity:1;transform:scale(1) translateY(0);',
          r1:'width:320px;height:440px;z-index:7;opacity:0.82;transform:scale(0.93) translateY(18px);',
          l1:'width:320px;height:440px;z-index:7;opacity:0.82;transform:scale(0.93) translateY(18px);',
          r2:'width:210px;height:320px;z-index:4;opacity:0.5;transform:scale(0.83) translateY(32px);',
          l2:'width:210px;height:320px;z-index:4;opacity:0.5;transform:scale(0.83) translateY(32px);'
        };
        return styles[pos] ?? 'width:0;opacity:0;';
      },
      radiusFor(pos) { return pos==='center' ? 'border-radius:28px;' : pos.includes('1') ? 'border-radius:22px;' : 'border-radius:16px;'; },
      shadowFor(pos) { return pos==='center' ? 'box-shadow:0 40px 80px rgba(0,0,0,0.55);' : pos.includes('1') ? 'box-shadow:0 20px 48px rgba(0,0,0,0.35);' : 'box-shadow:0 8px 24px rgba(0,0,0,0.2);'; }
    }" x-init="startAuto()">

      <div style="display:flex;align-items:center;justify-content:center;gap:16px;padding:20px 40px 48px 40px;min-height:580px;overflow:visible;">
        <template x-for="(dest, i) in items" :key="dest.name">
          <div class="dest-slide-item relative" 
            @mouseenter="stopAuto()" @mouseleave="resumeAuto()"
            @click="goTo(i)"
            :style="styleFor(i===current?'center':i===idx(1)?'r1':i===idx(-1)?'l1':i===idx(2)?'r2':i===idx(-2)?'l2':'hidden') + radiusFor(i===current?'center':i===idx(1)?'r1':i===idx(-1)?'l1':i===idx(2)?'r2':i===idx(-2)?'l2':'hidden') + shadowFor(i===current?'center':i===idx(1)?'r1':i===idx(-1)?'l1':i===idx(2)?'r2':i===idx(-2)?'l2':'hidden') + 'overflow:visible;position:relative;'"
          >
            <div class="dest-img-wrap" style="height:100%;">
              <img :src="dest.img" :alt="dest.name" />
              <div class="dest-hover-desc">
                <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(201,168,76,0.25);border:1px solid rgba(201,168,76,0.45);color:#C9A84C;border-radius:50px;padding:5px 14px;font-family:Inter,sans-serif;font-size:12px;font-weight:600;letter-spacing:0.05em;margin-bottom:12px;width:fit-content;" x-text="dest.tag"></div>
                <div style="font-family:'Cormorant Garamond',serif;font-size:42px;color:white;font-weight:700;line-height:1;margin-bottom:6px;" x-text="dest.name"></div>
                <div style="font-family:Inter,sans-serif;font-size:13px;color:#C9A84C;font-weight:600;margin-bottom:12px;" x-text="dest.count+' établissements disponibles'"></div>
                <div style="font-family:Inter,sans-serif;font-size:14px;color:rgba(255,255,255,0.82);line-height:1.65;margin-bottom:20px;" x-text="dest.desc"></div>
                <a :href="'<?= $base ?>/search?city=' + encodeURIComponent(dest.name)" style="display:inline-flex;align-items:center;gap:8px;background:#C9A84C;color:white;font-family:Inter,sans-serif;font-size:14px;font-weight:600;padding:11px 26px;border-radius:50px;text-decoration:none;box-shadow:0 4px 16px rgba(201,168,76,0.45);transition:all 0.3s;" >
                  Explorer
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                  </svg>
                </a>
              </div>
              <div style="position:absolute;bottom:24px;left:24px;pointer-events:none;">
                <div style="font-family:'Cormorant Garamond',serif;font-size:34px;color:white;font-weight:700;line-height:1;text-shadow:0 2px 8px rgba(0,0,0,0.5);" x-text="dest.name"></div>
                <div style="font-family:Inter,sans-serif;font-size:11px;color:#C9A84C;font-weight:500;text-shadow:0 1px 4px rgba(0,0,0,0.5);" x-text="dest.count+' établissements'"></div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <div style="display:flex;align-items:center;justify-content:center;gap:20px;margin-top:8px;">
        <button class="dest-nav-btn" @click="goPrev()" @mouseenter="stopAuto()" @mouseleave="resumeAuto()">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div style="display:flex;gap:8px;align-items:center;">
          <template x-for="(d,i) in items" :key="i">
            <div class="d-dot" :class="i===current?'d-dot-active':''" :style="i===current?'width:32px;':'width:8px;'" @click="goTo(i)"></div>
          </template>
        </div>
        <button class="dest-nav-btn" @click="goNext()" @mouseenter="stopAuto()" @mouseleave="resumeAuto()">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>

      <div style="display:flex;justify-content:center;gap:20px;margin-top:20px;flex-wrap:wrap;padding:0 24px;">
        <template x-for="(d,i) in items" :key="i">
          <span @click="goTo(i)" :style="i===current ? 'font-family:Cormorant Garamond,serif;font-size:17px;color:#C9A84C;font-weight:700;cursor:pointer;padding-bottom:3px;border-bottom:2px solid #C9A84C;transition:all 0.3s;' : 'font-family:Cormorant Garamond,serif;font-size:17px;color:rgba(255,255,255,0.4);font-weight:400;cursor:pointer;transition:all 0.3s;'" x-text="d.name"></span>
        </template>
      </div>

    </div>
  </div>
  <!-- Fin bloc Alpine.js conservé -->

</section>

<!-- SECTION 4 - HOW IT WORKS -->
<section class="hm-how">
  <div class="hm-how-left">
    <div class="hm-how-ghost">03</div>
    <div class="hm-how-content">
      <div class="hm-how-rule"></div>
      <span class="hm-how-tag">Mode d'emploi</span>
      <h2 class="hm-how-title">Comment<br>ça <em>marche ?</em></h2>
      <p class="hm-how-sub">Réservez un hébergement en moins de 2 minutes, depuis n'importe quel appareil.</p>
      <a href="<?= $base ?>/search" class="hm-how-btn">Commencer maintenant →</a>
    </div>
  </div>
  <div class="hm-how-right">
    <div class="hm-how-step">
      <div class="hm-how-step-num">01</div>
      <div class="hm-how-step-body"><h3 class="hm-how-step-title">Choisissez votre destination</h3><p class="hm-how-step-desc">Parcourez nos destinations ivoiriennes et filtrez par type d'hébergement, dates et budget.</p></div>
    </div>
    <div class="hm-how-divider"></div>
    <div class="hm-how-step">
      <div class="hm-how-step-num">02</div>
      <div class="hm-how-step-body"><h3 class="hm-how-step-title">Réservez en quelques clics</h3><p class="hm-how-step-desc">Sélectionnez votre chambre, renseignez vos dates et confirmez votre réservation instantanément.</p></div>
    </div>
    <div class="hm-how-divider"></div>
    <div class="hm-how-step">
      <div class="hm-how-step-num">03</div>
      <div class="hm-how-step-body"><h3 class="hm-how-step-title">Payez avec Mobile Money</h3><p class="hm-how-step-desc">Orange Money, MTN Money ou Wave — réglez votre séjour en toute sécurité, sans carte bancaire.</p></div>
    </div>
  </div>
</section>

<!-- SECTION 5 - TESTIMONIALS -->
<section class="hm-testi">
  <div class="hm-testi-header">
    <div class="hm-testi-rule"></div>
    <span class="hm-testi-tag">Ils nous font confiance</span>
    <div class="hm-testi-rule"></div>
  </div>
  <div class="hm-testi-grid">
    <div class="hm-testi-item">
      <div class="hm-testi-mark">"</div>
      <blockquote class="hm-testi-quote">Une plateforme qui a transformé la gestion de mon hôtel. Les réservations ont augmenté de 40% en 3 mois.</blockquote>
      <div class="hm-testi-author"><div class="hm-testi-avatar">KK</div><div><strong class="hm-testi-name">Kouamé Kobenan</strong><span class="hm-testi-role">Directeur, Hôtel Lagune · Abidjan</span></div></div>
    </div>
    <div class="hm-testi-item hm-testi-featured">
      <div class="hm-testi-mark">"</div>
      <blockquote class="hm-testi-quote">Réservation en 2 minutes, paiement Wave sans friction. Exactement ce qu'il fallait pour le marché ivoirien.</blockquote>
      <div class="hm-testi-author"><div class="hm-testi-avatar">AY</div><div><strong class="hm-testi-name">Aya Yao</strong><span class="hm-testi-role">Voyageuse fréquente · Yamoussoukro</span></div></div>
    </div>
    <div class="hm-testi-item">
      <div class="hm-testi-mark">"</div>
      <blockquote class="hm-testi-quote">Le support est réactif et la plateforme intuitive. Je gère mes 3 résidences depuis un seul tableau de bord.</blockquote>
      <div class="hm-testi-author"><div class="hm-testi-avatar">MB</div><div><strong class="hm-testi-name">Marcel Bamba</strong><span class="hm-testi-role">Propriétaire, Résidences Bamba · Bassam</span></div></div>
    </div>
  </div>
</section>

<!-- SECTION 6 - CTA FINAL -->
<section class="hm-cta">
  <div class="hm-cta-bar"></div>
  <div class="hm-cta-body">
    <div class="hm-cta-deco">IS</div>
    <div class="hm-cta-eyebrow"><div class="hm-cta-dot"></div><span>Rejoignez Ivoire Stay</span></div>
    <h2 class="hm-cta-title">Transformez votre<br>établissement <em>dès aujourd'hui.</em></h2>
    <p class="hm-cta-sub">Gérez réservations, clients et paiements depuis une interface conçue pour le marché ivoirien. Gratuit pour commencer.</p>
    <div class="hm-cta-btns">
      <a href="<?= $base ?>/register" class="hm-cta-btn-p">Démarrer gratuitement →</a>
      <a href="<?= $base ?>/tarifs" class="hm-cta-btn-o">Voir les tarifs</a>
    </div>
  </div>
</section>

</div><!-- /.home-page -->
