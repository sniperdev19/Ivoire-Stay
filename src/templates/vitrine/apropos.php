<?php $base_url = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="about-page">

  <section class="ab-hero">
    <div class="ab-hero-left">
      <div class="ab-hero-ghost">01</div>
      <div class="ab-hero-num">Section · 01</div>
      <h1 class="ab-hero-title">Notre<br><em>Histoire</em></h1>
      <div class="ab-hero-rule"></div>
      <p class="ab-hero-sub">Les hôteliers africains méritent des outils de gestion aussi puissants que les meilleures
        plateformes mondiales.</p>
    </div>
    <div class="ab-hero-right">
      <span class="ab-pill">Notre histoire</span>
      <div class="ab-quote-block">
        <span class="ab-quote-mark">"</span>
        <blockquote class="ab-quote-text">Simplifier l'hôtellerie ivoirienne</blockquote>
        <p class="ab-quote-author">Fondé en Yamoussoukro · 2026</p>
      </div>
    </div>
  </section>

  <section class="ab-mission">
    <div class="ab-mission-visual">
      <img src="<?= $base_url ?>/assets/img/vitrine/apropos-bureau.jpg" alt="Bureau hôtelier" />
      <div class="ab-mission-img-badge">Fondé en 2026 · Yamoussoukro CI</div>
    </div>
    <div class="ab-mission-content">
      <div class="ab-eyebrow">
        <div class="ab-eyebrow-rule"></div>
        <span>Notre mission</span>
      </div>
      <h2 class="ab-mission-title">Une plateforme née <em>en Afrique,</em><br>pour l'Afrique</h2>
      <div class="ab-mission-pull">
        <p>Les hôteliers ivoiriens géraient encore leurs réservations sur des cahiers. Afristay change cela.</p>
      </div>
      <p class="ab-mission-body">Notre SaaS permet à n'importe quel établissement de gérer chambres, réservations,
        clients et finances en un seul endroit. Paiement Mobile Money, interface 100 % en français.</p>
      <a href="<?= $base_url ?>/register" class="ab-btn-dark">Démarrer gratuitement →</a>
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
          <div class="ab-member-initials">BE</div>
          <h3 class="ab-member-name">Botchi<em>Emmanuel</em></h3>
        </div>
        <div class="ab-member-right">
          <div class="ab-member-role">CEO &amp; dev Backend &amp; Fondateur</div>
          <p class="ab-member-desc">Entrepreneur tech depuis 03 ans, membre de Prime Tech Group</p>
        </div>
      </div>
      <div class="ab-team-member">
        <div class="ab-member-left">
          <div class="ab-member-initials">KF</div>
          <h3 class="ab-member-name">Kouadio <em>Fréjus</em></h3>
        </div>
        <div class="ab-member-right">
          <div class="ab-member-role">Dev Frontend</div>
          <p class="ab-member-desc">Développeuse fullstack, passionnée par les solutions africaines</p>
        </div>
      </div>
      <!-- <div class="ab-team-member">
        <div class="ab-member-left">
          <div class="ab-member-initials">MB</div>
          <h3 class="ab-member-name">Marcel <em>Bamba</em></h3>
        </div>
        <div class="ab-member-right">
          <div class="ab-member-role">Head of Growth</div>
          <p class="ab-member-desc">Spécialiste marketing digital et hôtellerie en Côte d'Ivoire</p>
        </div>
      </div> -->
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
      <h2 class="ab-cta-title">L'avenir de l'hôtellerie<br>ivoirienne, c'est <em>maintenant.</em></h2>
      <p class="ab-cta-sub">Que vous soyez hôtelier ou voyageur, Afristay a quelque chose pour vous. Rejoignez des
        centaines d'établissements qui nous font confiance.</p>
      <div class="ab-cta-btns">
        <a href="<?= $base_url ?>/register" class="ab-btn-cta-p">Créer mon compte</a>
        <a href="<?= $base_url ?>/contact" class="ab-btn-cta-o">Nous contacter</a>
      </div>
    </div>
    <div class="ab-cta-deco">AS</div>
  </section>

</div>