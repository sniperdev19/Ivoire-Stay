<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>

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
      <p class="hm-hero-sub">Afristay connecte les voyageurs aux meilleurs établissements locaux, avec paiement Mobile Money et réservation instantanée.</p>
      <div class="hm-hero-btns">
        <a href="<?= $base ?>/search" class="hm-btn-p">Explorer les destinations →</a>
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
        <form class="hm-search-form"
              x-data="{ city:'', check_in:'', check_out:'', type:'' }"
              @submit.prevent="
                const p = new URLSearchParams();
                if (city)      p.set('city', city);
                if (check_in)  p.set('check_in', check_in);
                if (check_out) p.set('check_out', check_out);
                if (type)      p.set('type', type);
                window.location.href = '<?= $base ?>/search' + (p.toString() ? '?' + p.toString() : '');
              ">
          <div class="hm-field">
            <label class="hm-label">Destination</label>
            <input type="text" class="hm-input" placeholder="Abidjan, Yamoussoukro…" x-model="city">
          </div>
          <div class="hm-field-row">
            <div class="hm-field">
              <label class="hm-label">Arrivée</label>
              <input type="date" class="hm-input" x-model="check_in">
            </div>
            <div class="hm-field">
              <label class="hm-label">Départ</label>
              <input type="date" class="hm-input" x-model="check_out">
            </div>
          </div>
          <div class="hm-field">
            <label class="hm-label">Type</label>
            <select class="hm-input hm-select" x-model="type">
              <option value="">Tous les types</option>
              <option value="hotel">Hôtel</option>
              <option value="residence">Résidence</option>
              <option value="villa">Villa</option>
              <option value="appartement">Appartement</option>
            </select>
          </div>
          <button type="submit" class="hm-search-btn">
            Rechercher un hébergement
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>
      </div>
    </div>
  </div>

  <?php $stats = $stats ?? ['establishments' => 0, 'rooms' => 0, 'bookings' => 0, 'cities' => 0]; ?>
  <div class="hm-hero-strip">
    <div class="hm-hero-stat"><strong><?= number_format($stats['establishments'], 0, ',', ' ') ?></strong><span>Établissements</span></div>
    <div class="hm-hero-stat"><strong><?= number_format($stats['rooms'], 0, ',', ' ') ?></strong><span>Chambres</span></div>
    <div class="hm-hero-stat"><strong><?= number_format($stats['bookings'], 0, ',', ' ') ?></strong><span>Réservations</span></div>
    <div class="hm-hero-stat"><strong><?= number_format($stats['cities'], 0, ',', ' ') ?><span> villes</span></strong><span>Couverture nationale</span></div>
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
    <p class="hm-av-sub">Afristay offre une plateforme simple, rapide et sécurisée pour les établissements et les voyageurs.</p>
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

<!-- SECTION 3 - DESTINATIONS -->
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

  <div style="position:relative;z-index:2; padding:60px 0 100px 0;">
    <div x-data="homeCarousel([
        { name:'Abidjan', region:'District Autonome', count:142, tag:'Ville & Affaires', desc:'Capitale économique entre lagune, gratte-ciels et vie nocturne trépidante. Hub incontournable de l\'Afrique de l\'Ouest.', img:'<?= $base ?>/assets/carrouss1.jpg' },
        { name:'Yamoussoukro', region:'Centre', count:38, tag:'Culture & Histoire', desc:'Capitale politique, terre de la Basilique Notre-Dame de la Paix et de sérénité ivoirienne.', img:'<?= $base ?>/assets/carrouss2.jpg' },
        { name:'Grand-Bassam', region:'Sud-Comoé', count:27, tag:'Plage & Patrimoine', desc:'Ancienne capitale coloniale classée UNESCO, plages dorées, galeries d\'art et fruits de mer.', img:'<?= $base ?>/assets/carrouss3.jpg' },
        { name:'San-Pédro', region:'Bas-Sassandra', count:19, tag:'Nature & Évasion', desc:'Port moderne, forêt tropicale et eaux turquoise du Sud-Ouest ivoirien.', img:'<?= $base ?>/assets/carrouss4.jpg' },
        { name:'Assinie', region:'Sud-Comoé', count:31, tag:'Plage & Détente', desc:'Station balnéaire prisée entre océan Atlantique et lagune Aby. Cocotiers et sable blanc.', img:'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800' }
    ])" x-init="startAuto()">

      <div class="dest-carousel-track">
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
                <a :href="'<?= $base ?>/search?city=' + encodeURIComponent(dest.name)" style="display:inline-flex;align-items:center;gap:8px;background:#C9A84C;color:white;font-family:Inter,sans-serif;font-size:14px;font-weight:600;padding:11px 26px;border-radius:50px;text-decoration:none;box-shadow:0 4px 16px rgba(201,168,76,0.45);transition:all 0.3s;">
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
    <div class="hm-cta-eyebrow"><div class="hm-cta-dot"></div><span>Rejoignez Afristay</span></div>
    <h2 class="hm-cta-title">Transformez votre<br>établissement <em>dès aujourd'hui.</em></h2>
    <p class="hm-cta-sub">Gérez réservations, clients et paiements depuis une interface conçue pour le marché ivoirien. Gratuit pour commencer.</p>
    <div class="hm-cta-btns">
      <a href="<?= $base ?>/register" class="hm-cta-btn-p">Démarrer gratuitement →</a>
      <a href="<?= $base ?>/tarifs" class="hm-cta-btn-o">Voir les tarifs</a>
    </div>
  </div>
</section>

</div>
