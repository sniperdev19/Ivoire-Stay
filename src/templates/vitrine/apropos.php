<?php $base_url = $base_url ?? rtrim(APP_URL, '/'); ?>
<style>
:root {
  --gold: #C9A84C;
  --forest: #1B4332;
  --cream: #FAF7F2;
  --mid: #2D6A4F;
}
.about-page { overflow-x: hidden; }

/* HERO */
.ab-hero {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 520px;
}
.ab-hero-left {
  background: var(--forest);
  padding: 80px 60px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  position: relative;
  overflow: hidden;
}
.ab-hero-ghost {
  position: absolute;
  top: -20px;
  left: -10px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 280px;
  font-weight: 700;
  color: rgba(201, 168, 76, 0.06);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}
.ab-hero-num {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.4em;
  text-transform: uppercase;
  color: rgba(201, 168, 76, 0.55);
  margin-bottom: 16px;
  position: relative;
  z-index: 1;
}
.ab-hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 96px;
  font-weight: 300;
  color: white;
  line-height: 0.88;
  position: relative;
  z-index: 1;
}
.ab-hero-title em {
  color: var(--gold);
  font-style: italic;
  display: block;
}
.ab-hero-rule {
  width: 48px;
  height: 1px;
  background: var(--gold);
  margin: 28px 0;
  position: relative;
  z-index: 1;
}
.ab-hero-sub {
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  color: rgba(255, 255, 255, 0.55);
  line-height: 1.8;
  max-width: 360px;
  position: relative;
  z-index: 1;
}
.ab-hero-right {
  background: var(--cream);
  padding: 80px 60px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-left: 0.5px solid rgba(27, 67, 50, 0.1);
}
.ab-pill {
  display: inline-block;
  padding: 6px 16px;
  border: 1px solid var(--gold);
  color: var(--gold);
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  border-radius: 3px;
}
.ab-quote-block { margin-top: auto; }
.ab-quote-mark {
  display: block;
  font-family: 'Cormorant Garamond', serif;
  font-size: 96px;
  line-height: 0.6;
  color: var(--gold);
  margin-bottom: 16px;
}
.ab-quote-text {
  font-family: 'Cormorant Garamond', serif;
  font-size: 36px;
  font-weight: 300;
  color: var(--forest);
  line-height: 1.15;
  font-style: italic;
}
.ab-quote-author {
  margin-top: 20px;
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(27, 67, 50, 0.38);
}

/* MISSION */
.ab-mission {
  display: grid;
  grid-template-columns: 42fr 58fr;
  min-height: 580px;
  background: var(--cream);
  border-top: 0.5px solid rgba(27, 67, 50, 0.08);
}
.ab-mission-visual {
  position: relative;
  overflow: hidden;
  background: var(--forest);
}
.ab-mission-visual img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0.85;
}
.ab-mission-img-badge {
  position: absolute;
  bottom: 24px;
  left: 24px;
  padding: 8px 16px;
  background: var(--gold);
  color: white;
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  border-radius: 2px;
}
.ab-mission-content {
  padding: 72px 64px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 24px;
}
.ab-eyebrow {
  display: flex;
  align-items: center;
  gap: 12px;
}
.ab-eyebrow-rule {
  width: 28px;
  height: 1px;
  background: var(--gold);
}
.ab-eyebrow span {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: var(--gold);
}
.ab-mission-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 56px;
  font-weight: 400;
  color: var(--forest);
  line-height: 1.0;
}
.ab-mission-title em {
  color: var(--gold);
  font-style: italic;
}
.ab-mission-pull {
  border-left: 2px solid var(--gold);
  padding-left: 20px;
}
.ab-mission-pull p {
  font-family: 'Cormorant Garamond', serif;
  font-size: 20px;
  font-style: italic;
  color: var(--forest);
  line-height: 1.6;
}
.ab-mission-body {
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  color: rgba(27, 67, 50, 0.65);
  line-height: 1.9;
}
.ab-btn-dark {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 32px;
  background: var(--forest);
  color: white;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-weight: 600;
  border-radius: 999px;
  text-decoration: none;
  width: fit-content;
  transition: transform 0.2s, background 0.2s;
}
.ab-btn-dark:hover {
  background: #0d2118;
  transform: translateY(-2px);
}

/* STATS */
.ab-stats {
  background: var(--cream);
  padding: 80px 60px;
  border-top: 0.5px solid rgba(27, 67, 50, 0.08);
}
.ab-stats-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 48px;
}
.ab-stats-rule {
  flex: 1;
  height: 0.5px;
  background: rgba(27, 67, 50, 0.12);
}
.ab-stats-header span {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: var(--gold);
  white-space: nowrap;
}
.ab-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
}
.ab-stat-item {
  padding: 32px 36px;
  border-right: 0.5px solid rgba(27, 67, 50, 0.08);
  position: relative;
  overflow: hidden;
}
.ab-stat-item:last-child { border-right: none; }
.ab-stat-ghost {
  position: absolute;
  top: 8px;
  left: 20px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 100px;
  font-weight: 700;
  line-height: 1;
  color: transparent;
  -webkit-text-stroke: 1.5px rgba(27, 67, 50, 0.1);
  pointer-events: none;
  user-select: none;
}
.ab-stat-num {
  font-family: 'Cormorant Garamond', serif;
  font-size: 56px;
  font-weight: 700;
  line-height: 1;
  color: var(--forest);
  position: relative;
  z-index: 1;
  margin-top: 36px;
}
.ab-stat-num span {
  color: var(--gold);
  font-size: 28px;
}
.ab-stat-label {
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.22em;
  color: rgba(27, 67, 50, 0.42);
  margin-top: 10px;
  position: relative;
  z-index: 1;
}
.ab-stat-bar {
  width: 24px;
  height: 2px;
  background: var(--gold);
  margin-top: 16px;
  position: relative;
  z-index: 1;
}

/* TEAM */
.ab-team {
  background: var(--forest);
  padding: 72px 60px;
  position: relative;
  overflow: hidden;
}
.ab-team-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 48px;
}
.ab-team-rule {
  flex: 1;
  height: 0.5px;
  background: rgba(201, 168, 76, 0.15);
}
.ab-team-header span {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(201, 168, 76, 0.65);
  white-space: nowrap;
}
.ab-team-list { display: flex; flex-direction: column; }
.ab-team-member {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: end;
  padding: 28px 0;
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.06);
}
.ab-team-member:last-child { border-bottom: none; }
.ab-member-left { display: flex; flex-direction: column; gap: 6px; }
.ab-member-initials {
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  letter-spacing: 0.15em;
  color: rgba(201, 168, 76, 0.4);
  text-transform: uppercase;
}
.ab-member-name {
  font-family: 'Cormorant Garamond', serif;
  font-size: 56px;
  font-weight: 300;
  color: white;
  line-height: 1;
}
.ab-member-name em {
  font-style: italic;
  color: var(--gold);
}
.ab-member-right { text-align: right; }
.ab-member-role {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 8px;
}
.ab-member-desc {
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.38);
  line-height: 1.6;
  max-width: 280px;
  margin-left: auto;
}
.ab-team-deco {
  position: absolute;
  right: 40px;
  bottom: 0;
  font-family: 'Cormorant Garamond', serif;
  font-size: 200px;
  font-weight: 700;
  color: rgba(201, 168, 76, 0.04);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}

/* CTA */
.ab-cta {
  background: var(--cream);
  display: grid;
  grid-template-columns: 6px 1fr;
  position: relative;
  overflow: hidden;
  border-top: 0.5px solid rgba(27, 67, 50, 0.08);
}
.ab-cta-accent {
  background: var(--gold);
}
.ab-cta-body {
  padding: 80px 72px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: relative;
  z-index: 1;
}
.ab-cta-eyebrow {
  display: flex;
  align-items: center;
  gap: 10px;
}
.ab-cta-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--gold);
}
.ab-cta-eyebrow span {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(27, 67, 50, 0.42);
}
.ab-cta-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 72px;
  font-weight: 400;
  color: var(--forest);
  line-height: 1.0;
  max-width: 14ch;
}
.ab-cta-title em {
  color: var(--gold);
  font-style: italic;
}
.ab-cta-sub {
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  color: rgba(27, 67, 50, 0.6);
  line-height: 1.9;
  max-width: 520px;
}
.ab-cta-btns {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  margin-top: 8px;
}
.ab-btn-cta-p {
  padding: 16px 40px;
  background: var(--forest);
  color: white;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: transform 0.2s, background 0.2s;
}
.ab-btn-cta-p:hover { background: #0d2118; transform: translateY(-2px); }
.ab-btn-cta-o {
  padding: 16px 40px;
  border: 1px solid rgba(27, 67, 50, 0.22);
  color: var(--forest);
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  text-decoration: none;
  transition: border-color 0.2s;
}
.ab-btn-cta-o:hover { border-color: var(--forest); }
.ab-cta-deco {
  position: absolute;
  right: 40px;
  top: 50%;
  transform: translateY(-50%);
  font-family: 'Cormorant Garamond', serif;
  font-size: 200px;
  font-weight: 700;
  color: rgba(27, 67, 50, 0.05);
  line-height: 1;
  pointer-events: none;
  user-select: none;
  z-index: 0;
}

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
  .ab-hero { grid-template-columns: 1fr; }
  .ab-hero-right { border-left: none; border-top: 0.5px solid rgba(27,67,50,0.1); padding: 48px 40px; }
  .ab-hero-title { font-size: 72px; }
  .ab-mission { grid-template-columns: 1fr; }
  .ab-mission-visual { height: 320px; }
  .ab-stats { padding: 60px 40px; }
  .ab-stats-grid { grid-template-columns: repeat(2, 1fr); }
  .ab-stat-item:nth-child(2) { border-right: none; }
  .ab-stat-item { border-bottom: 0.5px solid rgba(27,67,50,0.08); }
  .ab-stat-item:nth-child(3), .ab-stat-item:nth-child(4) { border-bottom: none; }
  .ab-team { padding: 56px 40px; }
  .ab-member-name { font-size: 42px; }
  .ab-cta-body { padding: 60px 40px; }
  .ab-cta-title { font-size: 52px; }
}
@media (max-width: 768px) {
  .ab-nav { padding: 16px 24px; }
  .ab-hero-left, .ab-hero-right { padding: 48px 24px; }
  .ab-hero-title { font-size: 56px; }
  .ab-mission-content { padding: 48px 24px; }
  .ab-mission-title { font-size: 40px; }
  .ab-stats { padding: 48px 24px; }
  .ab-stats-grid { grid-template-columns: 1fr 1fr; }
  .ab-team { padding: 48px 24px; }
  .ab-team-member { grid-template-columns: 1fr; gap: 10px; }
  .ab-member-right { text-align: left; }
  .ab-member-desc { margin-left: 0; }
  .ab-member-name { font-size: 36px; }
  .ab-cta-body { padding: 48px 24px; }
  .ab-cta-title { font-size: 40px; }
  .ab-cta-btns { flex-direction: column; }
  .ab-cta-deco { display: none; }
}
</style>

<div class="about-page">

<!-- HERO -->
<section class="ab-hero">
  <div class="ab-hero-left">
    <div class="ab-hero-ghost">01</div>
    <div class="ab-hero-num">Section · 01</div>
    <h1 class="ab-hero-title">Notre<br><em>Histoire</em></h1>
    <div class="ab-hero-rule"></div>
    <p class="ab-hero-sub">Les hôteliers africains méritent des outils de gestion aussi puissants que les meilleures plateformes mondiales.</p>
  </div>
  <div class="ab-hero-right">
    <span class="ab-pill">Notre histoire</span>
    <div class="ab-quote-block">
      <span class="ab-quote-mark">"</span>
      <blockquote class="ab-quote-text">Simplifier l'hôtellerie ivoirienne</blockquote>
      <p class="ab-quote-author">Fondé en Abidjan · 2024</p>
    </div>
  </div>
</section>

<section class="ab-mission">
  <div class="ab-mission-visual">
    <img
      src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800"
      alt="Bureau hôtelier"
    />
    <div class="ab-mission-img-badge">Fondé en 2024 · Abidjan CI</div>
  </div>
  <div class="ab-mission-content">
    <div class="ab-eyebrow">
      <div class="ab-eyebrow-rule"></div>
      <span>Notre mission</span>
    </div>
    <h2 class="ab-mission-title">
      Une plateforme née <em>en Afrique,</em><br>pour l'Afrique
    </h2>
    <div class="ab-mission-pull">
      <p>Les hôteliers ivoiriens géraient encore leurs réservations sur des cahiers. Ivoire Stay change cela.</p>
    </div>
    <p class="ab-mission-body">
      Notre SaaS permet à n'importe quel établissement de gérer chambres, réservations, clients et finances en un seul endroit. Paiement Mobile Money, interface 100 % en français.
    </p>
    <a href="<?= $base_url ?>/register" class="ab-btn-dark">
      Démarrer gratuitement <span>→</span>
    </a>
  </div>
</section>

<section class="ab-stats">
  <div class="ab-stats-header">
    <div class="ab-stats-rule"></div>
    <span>Chiffres clés</span>
    <div class="ab-stats-rule"></div>
  </div>
  <div class="ab-stats-grid">
    <div class="ab-stat-item">
      <div class="ab-stat-ghost">500</div>
      <div class="ab-stat-num">500<span>+</span></div>
      <div class="ab-stat-label">Établissements partenaires</div>
      <div class="ab-stat-bar"></div>
    </div>
    <div class="ab-stat-item">
      <div class="ab-stat-ghost">12K</div>
      <div class="ab-stat-num">12 000<span>+</span></div>
      <div class="ab-stat-label">Réservations traitées</div>
      <div class="ab-stat-bar"></div>
    </div>
    <div class="ab-stat-item">
      <div class="ab-stat-ghost">4.9</div>
      <div class="ab-stat-num">4.9<span>/5</span></div>
      <div class="ab-stat-label">Satisfaction client</div>
      <div class="ab-stat-bar"></div>
    </div>
    <div class="ab-stat-item">
      <div class="ab-stat-ghost">3</div>
      <div class="ab-stat-num">3<span> villes</span></div>
      <div class="ab-stat-label">Couverture nationale</div>
      <div class="ab-stat-bar"></div>
    </div>
  </div>
</section>

<section class="ab-team">
  <div class="ab-team-header">
    <div class="ab-team-rule"></div>
    <span>Notre équipe</span>
    <div class="ab-team-rule"></div>
  </div>
  <div class="ab-team-list">
    <div class="ab-team-member">
      <div class="ab-member-left">
        <div class="ab-member-initials">KK</div>
        <h3 class="ab-member-name">Kouamé <em>Koffi</em></h3>
      </div>
      <div class="ab-member-right">
        <div class="ab-member-role">CEO & Fondateur</div>
        <p class="ab-member-desc">Entrepreneur tech depuis 10 ans, ex-ingénieur à Orange CI</p>
      </div>
    </div>
    <div class="ab-team-member">
      <div class="ab-member-left">
        <div class="ab-member-initials">AY</div>
        <h3 class="ab-member-name">Aya <em>Yao</em></h3>
      </div>
      <div class="ab-member-right">
        <div class="ab-member-role">CTO</div>
        <p class="ab-member-desc">Développeuse fullstack, passionnée par les solutions africaines</p>
      </div>
    </div>
    <div class="ab-team-member">
      <div class="ab-member-left">
        <div class="ab-member-initials">MB</div>
        <h3 class="ab-member-name">Marcel <em>Bamba</em></h3>
      </div>
      <div class="ab-member-right">
        <div class="ab-member-role">Head of Growth</div>
        <p class="ab-member-desc">Spécialiste marketing digital et hôtellerie en Côte d'Ivoire</p>
      </div>
    </div>
  </div>
  <div class="ab-team-deco">03</div>
</section>

<section class="ab-cta">
  <div class="ab-cta-accent"></div>
  <div class="ab-cta-body">
    <div class="ab-cta-eyebrow">
      <div class="ab-cta-dot"></div>
      <span>Rejoignez l'aventure</span>
    </div>
    <h2 class="ab-cta-title">
      L'avenir de l'hôtellerie<br>ivoirienne, c'est <em>maintenant.</em>
    </h2>
    <p class="ab-cta-sub">
      Que vous soyez hôtelier ou voyageur, Ivoire Stay a quelque chose pour vous. Rejoignez des centaines d'établissements qui nous font confiance.
    </p>
    <div class="ab-cta-btns">
      <a href="<?= $base_url ?>/register" class="ab-btn-cta-p">Créer mon compte</a>
      <a href="<?= $base_url ?>/contact" class="ab-btn-cta-o">Nous contacter</a>
    </div>
  </div>
  <div class="ab-cta-deco">IS</div>
</section>

</div><!-- /.about-page -->
