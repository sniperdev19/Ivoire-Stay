<?php ?>
<style>
:root {
  --gold: #C9A84C;
  --forest: #1B4332;
  --cream: #FAF7F2;
  --mid: #2D6A4F;
}
.contact-page { overflow-x: hidden; font-family: 'Inter', sans-serif; }

.ct-hero {
  position: relative;
  background: var(--forest);
  overflow: hidden;
  min-height: 520px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.ct-hero-bg-word {
  position: absolute;
  right: -40px;
  bottom: -40px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 280px;
  font-weight: 700;
  color: rgba(201, 168, 76, 0.04);
  line-height: 1;
  pointer-events: none;
  user-select: none;
  letter-spacing: -0.02em;
}
.ct-hero-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  min-height: 520px;
  position: relative;
  z-index: 1;
}
.ct-hero-left {
  border-right: 0.5px solid rgba(201, 168, 76, 0.12);
  padding: 64px 48px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.ct-hero-num {
  font-family: 'Cormorant Garamond', serif;
  font-size: 160px;
  font-weight: 700;
  color: rgba(201, 168, 76, 0.1);
  line-height: 1;
  user-select: none;
}
.ct-hero-left-bottom { display: flex; flex-direction: column; gap: 20px; }
.ct-pill {
  display: inline-block;
  padding: 6px 16px;
  border: 1px solid rgba(201, 168, 76, 0.4);
  color: var(--gold);
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  border-radius: 3px;
  width: fit-content;
}
.ct-hero-tagline {
  font-family: 'Cormorant Garamond', serif;
  font-size: 28px;
  font-weight: 300;
  color: rgba(255, 255, 255, 0.55);
  font-style: italic;
  line-height: 1.3;
}
.ct-hero-right {
  padding: 64px 80px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.ct-hero-rule-h {
  width: 48px;
  height: 1px;
  background: var(--gold);
  margin-bottom: 28px;
}
.ct-hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 100px;
  font-weight: 300;
  color: white;
  line-height: 0.88;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.ct-hero-title em { color: var(--gold); font-style: italic; }
.ct-hero-meta {
  display: flex;
  align-items: center;
  gap: 32px;
  margin-top: 48px;
  flex-wrap: wrap;
}
.ct-hero-meta-item { display: flex; flex-direction: column; gap: 5px; }
.ct-hero-meta-label {
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: rgba(201, 168, 76, 0.6);
}
.ct-hero-meta-val {
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.75);
}
.ct-hero-meta-sep {
  width: 1px;
  height: 32px;
  background: rgba(255, 255, 255, 0.1);
}

.ct-main {
  display: grid;
  grid-template-columns: 280px 1fr 240px;
  min-height: 700px;
  background: var(--cream);
  border-top: 0.5px solid rgba(27, 67, 50, 0.08);
}
.ct-info-panel {
  background: var(--forest);
  padding: 52px 36px;
  display: flex;
  flex-direction: column;
  gap: 32px;
}
.ct-info-header { display: flex; flex-direction: column; gap: 10px; }
.ct-info-rule {
  width: 28px;
  height: 1px;
  background: var(--gold);
}
.ct-info-tag {
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(201, 168, 76, 0.6);
}
.ct-info-list { display: flex; flex-direction: column; gap: 0; flex: 1; }
.ct-info-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 18px 0;
}
.ct-info-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(201, 168, 76, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--gold);
}
.ct-info-icon svg { width: 16px; height: 16px; }
.ct-info-item > div { display: flex; flex-direction: column; gap: 3px; }
.ct-info-label {
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.25em;
  color: rgba(201, 168, 76, 0.55);
}
.ct-info-value {
  font-family: 'Cormorant Garamond', serif;
  font-size: 16px;
  color: white;
  font-weight: 400;
}
.ct-info-sub {
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  color: rgba(255, 255, 255, 0.35);
}
.ct-info-divider {
  height: 0.5px;
  background: rgba(255, 255, 255, 0.06);
}
.ct-social-block {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: auto;
  padding-top: 24px;
  border-top: 0.5px solid rgba(255, 255, 255, 0.06);
}
.ct-social-label {
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: rgba(201, 168, 76, 0.5);
}
.ct-social-links { display: flex; flex-direction: column; gap: 8px; }
.ct-social-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border: 0.5px solid rgba(201, 168, 76, 0.15);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.6);
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  text-decoration: none;
  transition: background 0.2s, color 0.2s;
}
.ct-social-link:hover { background: rgba(201, 168, 76, 0.1); color: white; }
.ct-social-link span {
  color: var(--gold);
  font-size: 14px;
  width: 18px;
  text-align: center;
}

.ct-form-panel {
  padding: 52px 64px;
  border-right: 0.5px solid rgba(27, 67, 50, 0.08);
}
.ct-form-header { margin-bottom: 40px; }
.ct-form-num {
  font-family: 'Cormorant Garamond', serif;
  font-size: 80px;
  font-weight: 700;
  color: rgba(201, 168, 76, 0.08);
  line-height: 1;
  margin-bottom: 4px;
  user-select: none;
}
.ct-form-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 48px;
  font-weight: 300;
  color: var(--forest);
  line-height: 1.0;
}
.ct-form-title em { color: var(--gold); font-style: italic; }
.ct-form-rule {
  width: 40px;
  height: 1px;
  background: var(--gold);
  margin-top: 20px;
}
.ct-form { display: flex; flex-direction: column; gap: 20px; }
.ct-field { display: flex; flex-direction: column; gap: 8px; }
.ct-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ct-label {
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: var(--gold);
}
.ct-input {
  width: 100%;
  padding: 14px 18px;
  border: 1px solid rgba(27, 67, 50, 0.12);
  border-radius: 12px;
  background: white;
  color: var(--forest);
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
  appearance: none;
  -webkit-appearance: none;
}
.ct-input:focus { border-color: var(--gold); }
.ct-input::placeholder { color: rgba(27, 67, 50, 0.3); }
.ct-select {
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231B4332' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 16px;
  padding-right: 40px;
}
.ct-textarea { resize: vertical; min-height: 120px; }
.ct-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
  padding: 16px 32px;
  background: var(--forest);
  color: white;
  border: none;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
  margin-top: 4px;
}
.ct-submit:hover { background: #0d2118; transform: translateY(-2px); }
.ct-submit svg { width: 18px; height: 18px; }

.ct-success {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 48px 32px;
  gap: 16px;
}
.ct-success-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(27, 67, 50, 0.08);
  display: grid;
  place-items: center;
  color: var(--forest);
}
.ct-success-icon svg { width: 28px; height: 28px; }
.ct-success-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 40px;
  color: var(--forest);
  font-weight: 400;
}
.ct-success-sub {
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  color: rgba(27, 67, 50, 0.6);
  line-height: 1.7;
  max-width: 360px;
}
.ct-success-btn {
  padding: 12px 28px;
  border: 1px solid rgba(27, 67, 50, 0.2);
  color: var(--forest);
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  background: transparent;
  cursor: pointer;
  margin-top: 8px;
}

.ct-side-panel {
  padding: 52px 32px;
  display: flex;
  flex-direction: column;
  gap: 28px;
}
.ct-side-header { display: flex; flex-direction: column; gap: 10px; }
.ct-side-rule {
  width: 24px;
  height: 1px;
  background: var(--gold);
}
.ct-side-tag {
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(27, 67, 50, 0.4);
}
.ct-hours { display: flex; flex-direction: column; gap: 0; }
.ct-hours-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 0.5px solid rgba(27, 67, 50, 0.06);
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  color: rgba(27, 67, 50, 0.7);
}
.ct-hours-row span:last-child { font-weight: 500; }
.ct-hours-closed { opacity: 0.35; }
.ct-side-note {
  display: flex;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(201, 168, 76, 0.06);
  border-radius: 10px;
  border: 0.5px solid rgba(201, 168, 76, 0.15);
}
.ct-side-note-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--gold);
  flex-shrink: 0;
  margin-top: 4px;
}
.ct-side-note p {
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  color: rgba(27, 67, 50, 0.6);
  line-height: 1.7;
}
.ct-side-cta {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: auto;
}
.ct-side-btn-p {
  padding: 12px 20px;
  background: var(--forest);
  color: white;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  text-align: center;
  transition: background 0.2s;
}
.ct-side-btn-p:hover { background: #0d2118; }
.ct-side-btn-o {
  padding: 12px 20px;
  border: 1px solid rgba(27, 67, 50, 0.18);
  color: var(--forest);
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  text-decoration: none;
  text-align: center;
  transition: border-color 0.2s;
}
.ct-side-btn-o:hover { border-color: var(--forest); }

.ct-faq {
  display: grid;
  grid-template-columns: 380px 1fr;
  background: var(--forest);
  min-height: 520px;
}
.ct-faq-left {
  position: relative;
  padding: 64px 48px;
  border-right: 0.5px solid rgba(201, 168, 76, 0.1);
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  overflow: hidden;
}
.ct-faq-ghost {
  position: absolute;
  top: -20px;
  left: -10px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 180px;
  font-weight: 700;
  color: rgba(201, 168, 76, 0.05);
  line-height: 1;
  pointer-events: none;
  user-select: none;
}
.ct-faq-left-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.ct-faq-rule {
  width: 32px;
  height: 1px;
  background: var(--gold);
}
.ct-faq-tag {
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: rgba(201, 168, 76, 0.6);
}
.ct-faq-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 64px;
  font-weight: 300;
  color: white;
  line-height: 0.95;
}
.ct-faq-title em { color: var(--gold); font-style: italic; }
.ct-faq-sub {
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.4);
  line-height: 1.75;
  max-width: 280px;
}
.ct-faq-right {
  padding: 48px 64px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.ct-faq-item {
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.06);
  padding: 20px 0;
}
.ct-faq-q {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 22px;
  color: white;
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
  padding: 0;
}
.ct-faq-icon {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 0.5px solid rgba(201, 168, 76, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--gold);
  font-size: 20px;
  font-weight: 300;
  flex-shrink: 0;
  transition: transform 0.25s ease;
}
.ct-faq-open .ct-faq-icon { transform: rotate(45deg); }
.ct-faq-a {
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.5);
  line-height: 1.8;
  margin-top: 12px;
  max-width: 520px;
}

@media (max-width: 1200px) {
  .ct-main { grid-template-columns: 240px 1fr 200px; }
  .ct-form-panel { padding: 48px 40px; }
  .ct-hero-right { padding: 48px 48px; }
  .ct-hero-title { font-size: 72px; }
}
@media (max-width: 1024px) {
  .ct-hero-grid { grid-template-columns: 1fr; }
  .ct-hero-left { border-right: none; border-bottom: 0.5px solid rgba(201,168,76,0.12); padding: 40px; }
  .ct-hero-title { font-size: 64px; }
  .ct-main { grid-template-columns: 1fr; }
  .ct-info-panel { border-right: none; border-bottom: 0.5px solid rgba(255,255,255,0.06); }
  .ct-form-panel { border-right: none; }
  .ct-side-panel { border-top: 0.5px solid rgba(27,67,50,0.08); }
  .ct-faq { grid-template-columns: 1fr; }
  .ct-faq-left { border-right: none; border-bottom: 0.5px solid rgba(201,168,76,0.1); min-height: auto; }
  .ct-faq-right { padding: 40px; }
}
@media (max-width: 768px) {
  .ct-hero-left { padding: 32px 24px; }
  .ct-hero-right { padding: 32px 24px; }
  .ct-hero-title { font-size: 52px; }
  .ct-hero-meta { gap: 16px; }
  .ct-info-panel { padding: 36px 24px; }
  .ct-form-panel { padding: 36px 24px; }
  .ct-field-row { grid-template-columns: 1fr; }
  .ct-side-panel { padding: 36px 24px; }
  .ct-faq-left { padding: 40px 24px; }
  .ct-faq-title { font-size: 48px; }
  .ct-faq-right { padding: 32px 24px; }
  .ct-faq-q { font-size: 18px; }
}
</style>

<div class="contact-page">

<section class="ct-hero">
  <div class="ct-hero-bg-word">Contact</div>
  <div class="ct-hero-grid">
    <div class="ct-hero-left">
      <div class="ct-hero-num">01</div>
        <div class="ct-hero-left-bottom">
          <span class="ct-pill">Parlons de votre projet</span>
          <p class="ct-hero-tagline">Notre équipe<br>répond sous 24h.</p>
        </div>
    </div>
    <div class="ct-hero-right">
      <div class="ct-hero-rule-h"></div>
      <h1 class="ct-hero-title">Parlons<br>de votre<br><em>projet.</em></h1>
      <div class="ct-hero-meta">
        <div class="ct-hero-meta-item">
          <span class="ct-hero-meta-label">Bureau</span>
          <span class="ct-hero-meta-val">Cocody, Abidjan</span>
        </div>
        <div class="ct-hero-meta-sep"></div>
        <div class="ct-hero-meta-item">
          <span class="ct-hero-meta-label">Disponibilité</span>
          <span class="ct-hero-meta-val">Lun – Ven · 8h–18h</span>
        </div>
        <div class="ct-hero-meta-sep"></div>
        <div class="ct-hero-meta-item">
          <span class="ct-hero-meta-label">Réponse</span>
          <span class="ct-hero-meta-val">Sous 24h</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ct-main">
  <div class="ct-info-panel">
    <div class="ct-info-header">
      <div class="ct-info-rule"></div>
      <span class="ct-info-tag">Nous trouver</span>
    </div>
    <div class="ct-info-list">
      <div class="ct-info-item">
        <div class="ct-info-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1118 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        <div>
          <span class="ct-info-label">Notre bureau</span>
          <strong class="ct-info-value">Cocody, Abidjan</strong>
          <span class="ct-info-sub">Côte d'Ivoire</span>
        </div>
      </div>
      <div class="ct-info-divider"></div>
      <div class="ct-info-item">
        <div class="ct-info-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 5a2 2 0 012-2h2.5a1 1 0 01.94.658l1 2.5a1 1 0 01-.217 1.053L7.5 9.5a11.042 11.042 0 005.657 5.657l1.789-1.789a1 1 0 011.053-.217l2.5 1a1 1 0 01.658.94V19a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/>
          </svg>
        </div>
        <div>
          <span class="ct-info-label">Appelez-nous</span>
          <strong class="ct-info-value">+225 01 23 45 67 89</strong>
          <span class="ct-info-sub">Lun – Ven · 8h–18h</span>
        </div>
      </div>
      <div class="ct-info-divider"></div>
      <div class="ct-info-item">
        <div class="ct-info-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div>
          <span class="ct-info-label">écrivez-nous</span>
          <strong class="ct-info-value">support@ivoire-stay.ci</strong>
          <span class="ct-info-sub">Réponse sous 24h</span>
        </div>
      </div>
    </div>
    <div class="ct-social-block">
      <span class="ct-social-label">Réseaux sociaux</span>
      <div class="ct-social-links">
        <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>" class="ct-social-link">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s .784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
          </svg>
          LinkedIn
        </a>
        <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>" class="ct-social-link">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l -5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l 4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
          </svg>
          Twitter / X
        </a>
        <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>" class="ct-social-link">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <rect x="2" y="2" width="20" height="20" rx="5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>
          </svg>
          Instagram
        </a>
      </div>
    </div>
  </div>

  <div class="ct-form-panel" x-data="{ sent: false }">
    <div class="ct-form-header">
      <div class="ct-form-num">02</div>
      <h2 class="ct-form-title">Envoyez-nous<br>un <em>message</em></h2>
      <div class="ct-form-rule"></div>
    </div>
    <form class="ct-form" @submit.prevent="sent = true" x-show="!sent">
      <div class="ct-field">
        <label class="ct-label">Nom complet</label>
        <input type="text" class="ct-input" placeholder="Votre nom complet">
      </div>
      <div class="ct-field-row">
        <div class="ct-field">
          <label class="ct-label">Email</label>
          <input type="email" class="ct-input" placeholder="votre@email.com">
        </div>
        <div class="ct-field">
          <label class="ct-label">Téléphone</label>
          <input type="tel" class="ct-input" placeholder="+225 01 23 45 67 89">
        </div>
      </div>
      <div class="ct-field">
        <label class="ct-label">Sujet</label>
        <select class="ct-input ct-select">
          <option>Question générale</option>
          <option>Support technique</option>
          <option>Partenariat hôtelier</option>
          <option>Presse & médias</option>
        </select>
      </div>
      <div class="ct-field">
        <label class="ct-label">Message</label>
        <textarea class="ct-input ct-textarea" rows="5" placeholder="Décrivez votre besoin en détail..."></textarea>
      </div>
      <button type="submit" class="ct-submit">
        Envoyer le message →
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="5" y1="12" x2="19" y2="12"/>
          <polyline points="12 5 19 12 12 19"/>
        </svg>
      </button>
    </form>
    <div class="ct-success" x-show="sent" x-transition>
      <div class="ct-success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <h3 class="ct-success-title">Message envoyé !</h3>
      <p class="ct-success-sub">Nous avons bien reçu votre message et vous répondrons sous 24h ouvrées.</p>
      <button type="button" class="ct-success-btn" @click="sent = false">Envoyer un autre message</button>
    </div>
  </div>

  <div class="ct-side-panel">
    <div class="ct-side-header">
      <div class="ct-side-rule"></div>
      <span class="ct-side-tag">Disponibilités</span>
    </div>
    <div class="ct-hours">
      <div class="ct-hours-row">
        <span>Lundi – Vendredi</span>
        <span>8h00 – 18h00</span>
      </div>
      <div class="ct-hours-row">
        <span>Samedi</span>
        <span>9h00 - 13h00</span>
      </div>
      <div class="ct-hours-row ct-hours-closed">
        <span>Dimanche</span>
        <span>Fermé</span>
      </div>
    </div>
    <div class="ct-side-note">
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="ct-side-btn-p">Créer un compte →</a>
      <p>Pour les urgences techniques, notre support est disponible 24h/24 par email.</p>
    </div>
    <div class="ct-side-cta">
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="ct-side-btn-p">Créer un compte </a>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/pricing" class="ct-side-btn-o">Voir les tarifs</a>
    </div>
  </div>
</section>

<section class="ct-faq" x-data="{ open: null }">
  <div class="ct-faq-left">
    <div class="ct-faq-ghost">FAQ</div>
      <div class="ct-faq-left-content">
        <div class="ct-faq-rule"></div>
        <span class="ct-faq-tag">Questions fréquentes</span>
        <h2 class="ct-faq-title">Toutes<br>vos <em>réponses.</em></h2>
        <p class="ct-faq-sub">Retrouvez les réponses aux questions les plus fréquentes sur Ivoire Stay.</p>
      </div>
  </div>
  <div class="ct-faq-right">
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 1 }">
      <button class="ct-faq-q" type="button" @click="open = open === 1 ? null : 1">
        <span>Ivoire Stay est-il gratuit ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 1" x-transition>
        Oui ! Notre plan Starter est 100% gratuit et vous permet de gérer jusqu'à 10 chambres sans limitation de durée.
      </div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 2 }">
      <button class="ct-faq-q" type="button" @click="open = open === 2 ? null : 2">
        <span>Quels modes de paiement acceptez-vous ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 2" x-transition>
        Orange Money, MTN Money, Wave et virements bancaires pour les abonnements Pro et Business.
      </div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 3 }">
      <button class="ct-faq-q" type="button" @click="open = open === 3 ? null : 3">
        <span>Puis-je gérer plusieurs établissements ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 3" x-transition>
        Oui, avec notre plan Business vous gérez un nombre illimité d'établissements depuis un seul dashboard.
      </div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 4 }">
      <button class="ct-faq-q" type="button" @click="open = open === 4 ? null : 4">
        <span>Y a-t-il une application mobile ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 4" x-transition>
        Notre dashboard SaaS est une PWA optimisée mobile. Une app native est en développement.
      </div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 5 }">
      <button class="ct-faq-q" type="button" @click="open = open === 5 ? null : 5">
        <span>Comment fonctionne le support ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 5" x-transition>
        Email et chat 24h/24. Les clients Pro et Business bénéficient d'un support prioritaire avec SLA garanti.
      </div>
    </div>
  </div>
</section>

</div>
