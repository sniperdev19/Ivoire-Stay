<?php $base_url = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="contact-page" x-data="contactPage()">

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
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg>
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
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5a2 2 0 012-2h2.5a1 1 0 01.94.658l1 2.5a1 1 0 01-.217 1.053L7.5 9.5a11.042 11.042 0 005.657 5.657l1.789-1.789a1 1 0 011.053-.217l2.5 1a1 1 0 01.658.94V19a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/></svg>
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
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div>
          <span class="ct-info-label">Écrivez-nous</span>
          <strong class="ct-info-value">support@ivoire-stay.ci</strong>
          <span class="ct-info-sub">Réponse sous 24h</span>
        </div>
      </div>
    </div>
    <div class="ct-social-block">
      <span class="ct-social-label">Réseaux sociaux</span>
      <div class="ct-social-links">
        <a href="<?= $base_url ?>" class="ct-social-link">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
          LinkedIn
        </a>
        <a href="<?= $base_url ?>" class="ct-social-link">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          Twitter / X
        </a>
        <a href="<?= $base_url ?>" class="ct-social-link">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
          Instagram
        </a>
      </div>
    </div>
  </div>

  <div class="ct-form-panel">
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
          <option>Presse &amp; médias</option>
        </select>
      </div>
      <div class="ct-field">
        <label class="ct-label">Message</label>
        <textarea class="ct-input ct-textarea" rows="5" placeholder="Décrivez votre besoin en détail..."></textarea>
      </div>
      <button type="submit" class="ct-submit">
        Envoyer le message →
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>
    <div class="ct-success" x-show="sent" x-transition>
      <div class="ct-success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
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
      <div class="ct-hours-row"><span>Lundi – Vendredi</span><span>8h00 – 18h00</span></div>
      <div class="ct-hours-row"><span>Samedi</span><span>9h00 - 13h00</span></div>
      <div class="ct-hours-row ct-hours-closed"><span>Dimanche</span><span>Fermé</span></div>
    </div>
    <div class="ct-side-note">
      <div class="ct-side-note-dot"></div>
      <p>Pour les urgences techniques, notre support est disponible 24h/24 par email.</p>
    </div>
    <div class="ct-side-cta">
      <a href="<?= $base_url ?>/register" class="ct-side-btn-p">Créer un compte →</a>
      <a href="<?= $base_url ?>/tarifs" class="ct-side-btn-o">Voir les tarifs</a>
    </div>
  </div>
</section>

<section class="ct-faq">
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
      <div class="ct-faq-a" x-show="open === 1" x-transition>Oui ! Notre plan Starter est 100% gratuit et vous permet de gérer jusqu'à 10 chambres sans limitation de durée.</div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 2 }">
      <button class="ct-faq-q" type="button" @click="open = open === 2 ? null : 2">
        <span>Quels modes de paiement acceptez-vous ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 2" x-transition>Orange Money, MTN Money, Wave et virements bancaires pour les abonnements Pro et Business.</div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 3 }">
      <button class="ct-faq-q" type="button" @click="open = open === 3 ? null : 3">
        <span>Puis-je gérer plusieurs établissements ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 3" x-transition>Oui, avec notre plan Business vous gérez un nombre illimité d'établissements depuis un seul dashboard.</div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 4 }">
      <button class="ct-faq-q" type="button" @click="open = open === 4 ? null : 4">
        <span>Y a-t-il une application mobile ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 4" x-transition>Notre dashboard SaaS est une PWA optimisée mobile. Une app native est en développement.</div>
    </div>
    <div class="ct-faq-item" :class="{ 'ct-faq-open': open === 5 }">
      <button class="ct-faq-q" type="button" @click="open = open === 5 ? null : 5">
        <span>Comment fonctionne le support ?</span>
        <span class="ct-faq-icon">+</span>
      </button>
      <div class="ct-faq-a" x-show="open === 5" x-transition>Email et chat 24h/24. Les clients Pro et Business bénéficient d'un support prioritaire avec SLA garanti.</div>
    </div>
  </div>
</section>

</div>
