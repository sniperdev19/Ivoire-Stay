<?php ?>
<style>
:root {
  --gold: #C9A84C;
  --forest: #1B4332;
  --cream: #FAF7F2;
  --mid: #2D6A4F;
}
.pricing-page { overflow-x: hidden; }
.pr-hero {
  position: relative;
  background: var(--forest);
  overflow: hidden;
  padding: 72px 80px 0;
}
.pr-hero-bg-num {
  position: absolute;
  right: -10px;
  top: -20px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 380px;
  font-weight: 700;
  color: rgba(201, 168, 76, 0.04);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}
.pr-hero-top {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  position: relative;
  z-index: 1;
  padding-bottom: 48px;
}
.pr-hero-left {
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}
.pr-pill {
  display: inline-block;
  padding: 6px 16px;
  border: 1px solid rgba(201, 168, 76, 0.4);
  color: var(--gold);
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  border-radius: 3px;
  margin-bottom: 20px;
  width: fit-content;
}
.pr-hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 96px;
  font-weight: 300;
  color: white;
  line-height: 0.88;
}
.pr-hero-title em {
  color: var(--gold);
  font-style: italic;
  display: block;
}
.pr-hero-rule {
  width: 48px;
  height: 1px;
  background: var(--gold);
  margin: 24px 0;
}
.pr-hero-sub {
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  color: rgba(255, 255, 255, 0.5);
  line-height: 1.8;
  max-width: 340px;
}
.pr-hero-right {
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  gap: 20px;
}
.pr-toggle {
  display: inline-flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.07);
  border: 0.5px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  padding: 4px;
  width: fit-content;
}
.pr-toggle-btn {
  padding: 10px 26px;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  background: transparent;
  color: rgba(255, 255, 255, 0.5);
  transition: all 0.25s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}
.pr-toggle-active {
  background: white !important;
  color: var(--forest) !important;
}
.pr-badge {
  padding: 3px 10px;
  border-radius: 999px;
  background: var(--gold);
  color: var(--forest);
  font-size: 11px;
  font-weight: 700;
  font-family: 'Inter', sans-serif;
}
.pr-hero-desc {
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.4);
  max-width: 300px;
  line-height: 1.7;
}
.pr-hero-strip {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  border-top: 0.5px solid rgba(255, 255, 255, 0.06);
  position: relative;
  z-index: 1;
}
.pr-strip-plan {
  padding: 20px 28px;
  border-right: 0.5px solid rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.pr-strip-plan:last-child { border-right: none; }
.pr-strip-featured {
  background: rgba(201, 168, 76, 0.07);
  border-top: 1px solid rgba(201, 168, 76, 0.25);
}
.pr-strip-hot {
  display: block;
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 4px;
}
.pr-strip-name {
  font-family: 'Cormorant Garamond', serif;
  font-size: 32px;
  color: white;
  font-weight: 300;
  display: block;
}
.pr-strip-price { text-align: right; }
.pr-strip-price strong {
  font-family: 'Cormorant Garamond', serif;
  font-size: 26px;
  color: var(--gold);
  font-weight: 700;
  display: block;
  line-height: 1;
}
.pr-strip-price span {
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  color: rgba(255, 255, 255, 0.3);
}
.pr-plans {
  background: var(--cream);
  padding: 64px 80px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.pr-section-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 8px;
}
.pr-section-rule {
  flex: 1;
  height: 0.5px;
  background: rgba(27, 67, 50, 0.1);
}
.pr-section-tag {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: var(--gold);
  white-space: nowrap;
}
.pr-card-label {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: rgba(27, 67, 50, 0.35);
  margin-bottom: 10px;
}
.pr-card-name {
  font-family: 'Cormorant Garamond', serif;
  font-size: 56px;
  font-weight: 300;
  color: var(--forest);
  line-height: 1;
}
.pr-card-price {
  font-family: 'Cormorant Garamond', serif;
  font-size: 36px;
  font-weight: 700;
  color: var(--forest);
  margin-top: 8px;
  display: flex;
  align-items: baseline;
  gap: 8px;
  flex-wrap: wrap;
}
.pr-card-price em {
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-style: normal;
  font-weight: 400;
  color: rgba(27, 67, 50, 0.4);
}
.pr-card-desc {
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  color: rgba(27, 67, 50, 0.5);
  line-height: 1.7;
  margin-top: 10px;
}
.pr-feat-list { list-style: none; display: flex; flex-direction: column; gap: 10px; padding: 0; margin: 0; }
.pr-feat {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(27, 67, 50, 0.65);
}
.pr-feat-pro { color: rgba(255, 255, 255, 0.65); }
.pr-feat-disabled { opacity: 0.3; }
.pr-feat-check {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: rgba(27, 67, 50, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--forest);
}
.pr-feat-check svg { width: 10px; height: 10px; }
.pr-feat-ok { background: rgba(27, 67, 50, 0.1); }
.pr-feat-gold { background: rgba(201, 168, 76, 0.15); color: var(--gold); }
.pr-btn-outline {
  display: inline-block;
  padding: 12px 28px;
  border: 1px solid var(--forest);
  color: var(--forest);
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  width: fit-content;
  transition: background 0.2s, color 0.2s;
}
.pr-btn-outline:hover { background: var(--forest); color: white; }
.pr-btn-gold {
  display: inline-block;
  padding: 12px 28px;
  background: linear-gradient(135deg, var(--gold), #A67C2E);
  color: white;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  width: fit-content;
  transition: transform 0.2s;
}
.pr-btn-gold:hover { transform: translateY(-2px); }
.pr-card-starter {
  display: grid;
  grid-template-columns: 52fr 48fr;
  background: white;
  border-radius: 24px;
  border: 0.5px solid rgba(27, 67, 50, 0.08);
  overflow: hidden;
  min-height: 280px;
}
.pr-starter-left {
  padding: 40px 48px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.pr-starter-right {
  background: rgba(27, 67, 50, 0.03);
  padding: 36px 40px;
  border-left: 0.5px solid rgba(27, 67, 50, 0.07);
  display: flex;
  align-items: center;
}
.pr-card-pro-wrap { position: relative; margin-top: 8px; }
.pr-card-pro-hot {
  position: absolute;
  top: -14px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--gold);
  color: white;
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 6px 20px;
  border-radius: 999px;
  white-space: nowrap;
  z-index: 2;
}
.pr-card-pro {
  background: var(--forest);
  border-radius: 24px;
  border: 0.5px solid rgba(201, 168, 76, 0.2);
  overflow: hidden;
  display: grid;
  grid-template-columns: 55fr 45fr;
  min-height: 320px;
}
.pr-pro-left {
  padding: 48px 48px 40px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.pr-pro-right {
  background: rgba(255, 255, 255, 0.03);
  padding: 36px 36px;
  border-left: 0.5px solid rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
}
.pr-card-biz {
  display: grid;
  grid-template-columns: 52fr 48fr;
  background: white;
  border-radius: 24px;
  border: 0.5px solid rgba(27, 67, 50, 0.08);
  overflow: hidden;
  min-height: 280px;
}
.pr-biz-left {
  padding: 40px 48px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background: rgba(201, 168, 76, 0.04);
}
.pr-biz-right {
  background: var(--forest);
  padding: 36px 40px;
  display: flex;
  align-items: center;
}
.pr-table-section {
  background: var(--forest);
  padding: 64px 80px;
}
.pr-table-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 36px;
}
.pr-table-rule {
  flex: 1;
  height: 0.5px;
  background: rgba(201, 168, 76, 0.15);
}
.pr-table-tag {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(201, 168, 76, 0.65);
  white-space: nowrap;
}
.pr-table-wrap {
  overflow: hidden;
  border-radius: 20px;
  border: 0.5px solid rgba(255, 255, 255, 0.06);
}
.pr-table {
  width: 100%;
  border-collapse: collapse;
}
.pr-th-feature {
  padding: 16px 24px;
  text-align: left;
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.25);
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.06);
  background: rgba(255,255,255,0.02);
  width: 36%;
}
.pr-th {
  padding: 16px 20px;
  text-align: center;
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.38);
  font-weight: 600;
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.06);
  background: rgba(255,255,255,0.02);
}
.pr-th-pro {
  color: var(--gold);
  border-bottom: 1px solid rgba(201, 168, 76, 0.3);
  background: rgba(201, 168, 76, 0.05);
}
.pr-table tbody tr {
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.04);
  transition: background 0.15s;
}
.pr-table tbody tr:hover { background: rgba(255,255,255,0.02); }
.pr-table tbody tr:last-child { border-bottom: none; }
.pr-td-label {
  padding: 14px 24px;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.65);
}
.pr-td {
  padding: 14px 20px;
  text-align: center;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.45);
}
.pr-td-pro {
  background: rgba(201, 168, 76, 0.05);
  color: var(--gold);
  font-weight: 600;
}
.pr-td-yes { color: #4ade80; font-size: 16px; }
.pr-td-no { color: rgba(255, 255, 255, 0.18); font-size: 16px; }
.pr-faq {
  background: var(--cream);
  padding: 64px 80px;
  border-top: 0.5px solid rgba(27, 67, 50, 0.08);
}
.pr-faq-list { display: flex; flex-direction: column; gap: 12px; }
.pr-faq-item {
  border-bottom: 0.5px solid rgba(27, 67, 50, 0.08);
  padding: 20px 0;
  transition: background 0.2s;
}
.pr-faq-q {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 24px;
  color: var(--forest);
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
  padding: 0;
}
.pr-faq-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 0.5px solid rgba(27, 67, 50, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--gold);
  font-size: 20px;
  font-weight: 300;
  flex-shrink: 0;
  transition: transform 0.25s ease;
}
.pr-faq-open .pr-faq-icon { transform: rotate(45deg); }
.pr-faq-a {
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  color: rgba(27, 67, 50, 0.6);
  line-height: 1.8;
  margin-top: 12px;
  max-width: 640px;
}
.pr-cta {
  background: var(--cream);
  display: grid;
  grid-template-columns: 6px 1fr;
  border-top: 0.5px solid rgba(27, 67, 50, 0.08);
}
.pr-cta-bar { background: var(--gold); }
.pr-cta-body {
  padding: 80px 80px;
  position: relative;
  overflow: hidden;
}
.pr-cta-deco {
  position: absolute;
  right: 60px;
  top: 50%;
  transform: translateY(-50%);
  font-family: 'Cormorant Garamond', serif;
  font-size: 240px;
  font-weight: 700;
  color: rgba(27, 67, 50, 0.04);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}
.pr-cta-eyebrow {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}
.pr-cta-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--gold);
}
.pr-cta-eyebrow span {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(27, 67, 50, 0.42);
}
.pr-cta-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 80px;
  font-weight: 400;
  color: var(--forest);
  line-height: 1.0;
  max-width: 16ch;
  margin-bottom: 20px;
  position: relative;
  z-index: 1;
}
.pr-cta-title em { color: var(--gold); font-style: italic; }
.pr-cta-sub {
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  color: rgba(27, 67, 50, 0.58);
  line-height: 1.9;
  max-width: 520px;
  margin-bottom: 32px;
  position: relative;
  z-index: 1;
}
.pr-cta-btns {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}
.pr-cta-btn-p {
  padding: 16px 40px;
  background: var(--forest);
  color: white;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s, transform 0.2s;
}
.pr-cta-btn-p:hover { background: #0d2118; transform: translateY(-2px); }
.pr-cta-btn-o {
  padding: 16px 40px;
  border: 1px solid rgba(27, 67, 50, 0.22);
  color: var(--forest);
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  text-decoration: none;
  transition: border-color 0.2s;
}
.pr-cta-btn-o:hover { border-color: var(--forest); }
@media (max-width: 1100px) {
  .pr-hero { padding: 60px 40px 0; }
  .pr-plans { padding: 48px 40px; }
  .pr-table-section { padding: 48px 40px; }
  .pr-faq { padding: 48px 40px; }
  .pr-cta-body { padding: 60px 40px; }
  .pr-hero-top { grid-template-columns: 1fr; gap: 32px; }
  .pr-hero-title { font-size: 72px; }
  .pr-card-starter, .pr-card-biz { grid-template-columns: 1fr; }
  .pr-starter-right, .pr-biz-right { border-left: none; border-top: 0.5px solid rgba(27,67,50,0.08); }
  .pr-card-pro { grid-template-columns: 1fr; }
  .pr-pro-right { border-left: none; border-top: 0.5px solid rgba(255,255,255,0.06); }
  .pr-hero-strip { grid-template-columns: 1fr; }
  .pr-strip-plan { border-right: none; border-bottom: 0.5px solid rgba(255,255,255,0.06); }
}
@media (max-width: 768px) {
  .pr-hero { padding: 48px 24px 0; }
  .pr-hero-title { font-size: 52px; }
  .pr-plans { padding: 40px 24px; }
  .pr-table-section { padding: 40px 24px; }
  .pr-table-wrap { overflow-x: auto; }
  .pr-faq { padding: 40px 24px; }
  .pr-cta-body { padding: 48px 24px; }
  .pr-cta-title { font-size: 48px; }
  .pr-cta-deco { display: none; }
  .pr-card-name { font-size: 40px; }
  .pr-starter-left, .pr-pro-left, .pr-biz-left { padding: 28px 24px; }
  .pr-starter-right, .pr-pro-right, .pr-biz-right { padding: 24px 24px; }
}
</style>

<div class="pricing-page" x-data="{ annual: false }">

<section class="pr-hero">
  <div class="pr-hero-bg-num">€</div>
  <div class="pr-hero-top">
    <div class="pr-hero-left">
      <span class="pr-pill">Tarifs transparents</span>
      <h1 class="pr-hero-title">Choisissez<br>votre <em>plan</em></h1>
      <div class="pr-hero-rule"></div>
      <p class="pr-hero-sub">Démarrez gratuitement. Évoluez sans contrainte selon vos besoins réels.</p>
    </div>
    <div class="pr-hero-right">
      <div class="pr-toggle">
        <button @click="annual = false" :class="{ 'pr-toggle-active': !annual }" class="pr-toggle-btn" type="button">Mensuel</button>
        <button @click="annual = true" :class="{ 'pr-toggle-active': annual }" class="pr-toggle-btn" type="button">Annuel <span class="pr-badge">−20%</span></button>
      </div>
      <p class="pr-hero-desc">Passez à l'annuel et économisez jusqu'à 2 mois d'abonnement par an.</p>
    </div>
  </div>
  <div class="pr-hero-strip">
    <div class="pr-strip-plan">
      <span class="pr-strip-name">Starter</span>
      <div class="pr-strip-price">
        <strong>0 FCFA</strong>
        <span>/mois</span>
      </div>
    </div>
    <div class="pr-strip-plan pr-strip-featured">
      <div>
        <span class="pr-strip-hot">★ Populaire</span>
        <span class="pr-strip-name">Pro</span>
      </div>
      <div class="pr-strip-price">
        <strong x-text="annual ? '7 200 FCFA' : '9 000 FCFA'"></strong>
        <span>/mois</span>
      </div>
    </div>
    <div class="pr-strip-plan">
      <span class="pr-strip-name">Business</span>
      <div class="pr-strip-price">
        <strong x-text="annual ? '16 000 FCFA' : '20 000 FCFA'"></strong>
        <span>/mois</span>
      </div>
    </div>
  </div>
</section>

<section class="pr-plans">
  <div class="pr-section-header">
    <div class="pr-section-rule"></div>
    <span class="pr-section-tag">Nos plans</span>
    <div class="pr-section-rule"></div>
  </div>
  <div class="pr-card-starter">
    <div class="pr-starter-left">
      <div>
        <div class="pr-card-label">Plan · 01</div>
        <h2 class="pr-card-name">Starter</h2>
        <div class="pr-card-price">0 FCFA <em>/ mois · gratuit à vie</em></div>
        <p class="pr-card-desc">Pour démarrer sans risque et découvrir la plateforme.</p>
      </div>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="pr-btn-outline">Démarrer gratuitement →</a>
    </div>
    <div class="pr-starter-right">
      <ul class="pr-feat-list">
        <li class="pr-feat"><span class="pr-feat-check pr-feat-ok"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Jusqu'à 10 chambres</li>
        <li class="pr-feat"><span class="pr-feat-check pr-feat-ok"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Gestion des réservations</li>
        <li class="pr-feat"><span class="pr-feat-check pr-feat-ok"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>1 établissement</li>
        <li class="pr-feat"><span class="pr-feat-check pr-feat-ok"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Support email</li>
        <li class="pr-feat pr-feat-disabled"><span class="pr-feat-check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="3" x2="9" y2="9"/><line x1="9" y1="3" x2="3" y2="9"/></svg></span>Facturation PDF</li>
        <li class="pr-feat pr-feat-disabled"><span class="pr-feat-check"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="3" x2="9" y2="9"/><line x1="9" y1="3" x2="3" y2="9"/></svg></span>Rapports financiers</li>
      </ul>
    </div>
  </div>
  <div class="pr-card-pro-wrap">
    <div class="pr-card-pro-hot">★ Le plus populaire</div>
    <div class="pr-card-pro">
      <div class="pr-pro-left">
        <div>
          <div class="pr-card-label" style="color:rgba(201,168,76,0.55)">Plan · 02</div>
          <h2 class="pr-card-name" style="color:white">Pro</h2>
          <div class="pr-card-price" style="color:var(--gold)"><span x-text="annual ? '7 200 FCFA' : '9 000 FCFA'"></span><em style="color:rgba(255,255,255,0.3)">/ mois</em></div>
          <p class="pr-card-desc" style="color:rgba(255,255,255,0.4)">Le plan idéal pour développer votre établissement.</p>
        </div>
        <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="pr-btn-gold">Choisir Pro →</a>
      </div>
      <div class="pr-pro-right">
        <ul class="pr-feat-list">
          <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Chambres illimitées</li>
          <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Réservations illimitées</li>
          <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>1 établissement</li>
          <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Facturation & PDF</li>
          <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Paiements & dépenses</li>
          <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Rapports financiers</li>
          <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Support prioritaire</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="pr-card-biz">
    <div class="pr-biz-left">
      <div>
        <div class="pr-card-label" style="color:rgba(201,168,76,0.65)">Plan · 03</div>
        <h2 class="pr-card-name">Business</h2>
        <div class="pr-card-price"><span x-text="annual ? '16 000 FCFA' : '20 000 FCFA'"></span><em>/ mois</em></div>
        <p class="pr-card-desc">Pour les groupes hôteliers et chaînes multi-sites.</p>
      </div>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/contact" class="pr-btn-gold">Contacter l'équipe →</a>
    </div>
    <div class="pr-biz-right">
      <ul class="pr-feat-list">
        <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Tout le plan Pro inclus</li>
        <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Établissements illimités</li>
        <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Boost vitrine prioritaire</li>
        <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>API access</li>
        <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>Manager dédié</li>
        <li class="pr-feat pr-feat-pro"><span class="pr-feat-check pr-feat-gold"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="2,6 5,9 10,3"/></svg></span>SLA 99.9% uptime</li>
      </ul>
    </div>
  </div>
</section>

<section class="pr-table-section">
  <div class="pr-table-header">
    <div class="pr-table-rule"></div>
    <span class="pr-table-tag">Comparaison détaillée</span>
    <div class="pr-table-rule"></div>
  </div>
  <div class="pr-table-wrap">
    <table class="pr-table">
      <thead>
        <tr>
          <th class="pr-th-feature"></th>
          <th class="pr-th">Starter</th>
          <th class="pr-th pr-th-pro">Pro ★</th>
          <th class="pr-th">Business</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="pr-td-label">Chambres</td>
          <td class="pr-td">10 max</td>
          <td class="pr-td pr-td-pro">∞</td>
          <td class="pr-td">∞</td>
        </tr>
        <tr>
          <td class="pr-td-label">Établissements</td>
          <td class="pr-td">1</td>
          <td class="pr-td pr-td-pro">1</td>
          <td class="pr-td">∞</td>
        </tr>
        <tr>
          <td class="pr-td-label">Réservations</td>
          <td class="pr-td">∞</td>
          <td class="pr-td pr-td-pro">∞</td>
          <td class="pr-td">∞</td>
        </tr>
        <tr>
          <td class="pr-td-label">Facturation PDF</td>
          <td class="pr-td pr-td-no">✕</td>
          <td class="pr-td pr-td-pro pr-td-yes">✓</td>
          <td class="pr-td pr-td-yes">✓</td>
        </tr>
        <tr>
          <td class="pr-td-label">Rapports financiers</td>
          <td class="pr-td pr-td-no">✕</td>
          <td class="pr-td pr-td-pro pr-td-yes">✓</td>
          <td class="pr-td pr-td-yes">✓</td>
        </tr>
        <tr>
          <td class="pr-td-label">Multi-établissement</td>
          <td class="pr-td pr-td-no">✕</td>
          <td class="pr-td pr-td-pro pr-td-no" style="color:rgba(201,168,76,0.3)">✕</td>
          <td class="pr-td pr-td-yes">✓</td>
        </tr>
        <tr>
          <td class="pr-td-label">Boost vitrine</td>
          <td class="pr-td pr-td-no">✕</td>
          <td class="pr-td pr-td-pro pr-td-no" style="color:rgba(201,168,76,0.3)">✕</td>
          <td class="pr-td pr-td-yes">✓</td>
        </tr>
        <tr>
          <td class="pr-td-label">API access</td>
          <td class="pr-td pr-td-no">✕</td>
          <td class="pr-td pr-td-pro pr-td-no" style="color:rgba(201,168,76,0.3)">✕</td>
          <td class="pr-td pr-td-yes">✓</td>
        </tr>
        <tr>
          <td class="pr-td-label">Support</td>
          <td class="pr-td">Email</td>
          <td class="pr-td pr-td-pro">Prioritaire</td>
          <td class="pr-td">Dédié</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<section class="pr-faq" x-data="{ open: null }">
  <div class="pr-faq-header">
    <div class="pr-section-rule"></div>
    <span class="pr-section-tag">Questions fréquentes</span>
    <div class="pr-section-rule"></div>
  </div>
  <div class="pr-faq-list">
    <div class="pr-faq-item" :class="{ 'pr-faq-open': open === 1 }">
      <button class="pr-faq-q" type="button" @click="open = open === 1 ? null : 1">
        <span>Puis-je changer de plan à tout moment ?</span>
        <span class="pr-faq-icon">+</span>
      </button>
      <div class="pr-faq-a" x-show="open === 1" x-transition>Oui — upgrade ou downgrade instantané. Le changement prend effet immédiatement, sans frais supplémentaires.</div>
    </div>
    <div class="pr-faq-item" :class="{ 'pr-faq-open': open === 2 }">
      <button class="pr-faq-q" type="button" @click="open = open === 2 ? null : 2">
        <span>Y a-t-il des frais cachés ?</span>
        <span class="pr-faq-icon">+</span>
      </button>
      <div class="pr-faq-a" x-show="open === 2" x-transition>Non. Les prix affichés sont tout inclus. Seuls les SMS de confirmation sont facturés à l'usage.</div>
    </div>
    <div class="pr-faq-item" :class="{ 'pr-faq-open': open === 3 }">
      <button class="pr-faq-q" type="button" @click="open = open === 3 ? null : 3">
        <span>Comment fonctionne la période d'essai ?</span>
        <span class="pr-faq-icon">+</span>
      </button>
      <div class="pr-faq-a" x-show="open === 3" x-transition>Le plan Starter est gratuit à vie. Les plans payants n'ont pas de période d'essai mais vous pouvez annuler à tout moment sans engagement.</div>
    </div>
    <div class="pr-faq-item" :class="{ 'pr-faq-open': open === 4 }">
      <button class="pr-faq-q" type="button" @click="open = open === 4 ? null : 4">
        <span>Acceptez-vous le paiement Mobile Money ?</span>
        <span class="pr-faq-icon">+</span>
      </button>
      <div class="pr-faq-a" x-show="open === 4" x-transition>Oui — Orange Money, MTN Money et Wave sont acceptés pour tous les abonnements payants.</div>
    </div>
  </div>
</section>

<section class="pr-cta">
  <div class="pr-cta-bar"></div>
  <div class="pr-cta-body">
    <div class="pr-cta-deco">IS</div>
    <div class="pr-cta-eyebrow">
      <div class="pr-cta-dot"></div>
      <span>Passez à l'action</span>
    </div>
    <h2 class="pr-cta-title">Prêt à moderniser<br>votre <em>établissement ?</em></h2>
    <p class="pr-cta-sub">Rejoignez des centaines d'hôteliers ivoiriens qui font confiance à Ivoire Stay pour gérer leur activité au quotidien.</p>
    <div class="pr-cta-btns">
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="pr-cta-btn-p">Démarrer gratuitement →</a>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/contact" class="pr-cta-btn-o">Parler à un expert</a>
    </div>
  </div>
</section>

</div><!-- /.pricing-page -->