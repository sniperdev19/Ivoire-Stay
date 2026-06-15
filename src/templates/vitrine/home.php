<?php
// Contenu injecté dans le layout : sections statiques de la page d'accueil
// Sections : HERO, avantages, carrousel, CTA final
?>

<section class="relative min-h-screen overflow-hidden" style="background-image:url('<?= $base_url ?? rtrim(APP_URL, '/') ?>/assets/bg_home.jpg'); background-size:cover; background-position:center; background-attachment:fixed;">
  <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(15,67,39,0.82) 0%, rgba(15,67,39,0.58) 45%, rgba(15,43,32,0.35) 100%);"></div>
  <div class="relative z-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-h-screen flex items-center pt-28 pb-20">
      <div class="w-full grid grid-cols-1 lg:grid-cols-[58%_42%] gap-10 items-center">
        <div class="text-white">
          <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 mb-6 backdrop-blur-md">
            <span class="text-[13px] uppercase tracking-[0.25em] text-white/80">Nouveauté partagée</span>
          </div>
          <h1 class="font-display text-[2.9rem] md:text-[4.5rem] leading-tight tracking-[-0.03em] mb-6">
            Réservez des séjours<br>
            mémorables en Côte d'Ivoire
          </h1>
          <p class="max-w-2xl text-base md:text-lg text-white/80 leading-8 mb-10">
            Ivoire Stay connecte les voyageurs aux meilleurs établissements locaux, avec paiement Mobile Money et réservation instantanée.
          </p>
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/search" class="btn-gold inline-flex items-center justify-center px-8 py-4">
              Explorer les destinations
            </a>
            <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/tarifs" class="btn-outline-gold inline-flex items-center justify-center px-8 py-4">
              Voir les tarifs SaaS
            </a>
          </div>
          <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-[24px] bg-white/10 border border-white/15 p-5 backdrop-blur-md">
              <div class="text-[2rem] font-semibold text-white">500+</div>
              <div class="mt-3 text-sm text-white/70 uppercase tracking-[0.2em]">Établissements</div>
            </div>
            <div class="rounded-[24px] bg-white/10 border border-white/15 p-5 backdrop-blur-md">
              <div class="text-[2rem] font-semibold text-white">12 000+</div>
              <div class="mt-3 text-sm text-white/70 uppercase tracking-[0.2em]">Réservations</div>
            </div>
            <div class="rounded-[24px] bg-white/10 border border-white/15 p-5 backdrop-blur-md">
              <div class="text-[2rem] font-semibold text-white">4.9</div>
              <div class="mt-3 text-sm text-white/70 uppercase tracking-[0.2em]">Note moyenne</div>
            </div>
          </div>
        </div>

        <div class="relative">
          <div class="glass-card-strong border border-white/15 p-8 rounded-[32px] shadow-2xl">
            <div class="mb-6">
              <div class="font-display text-2xl text-[#1B4332] mb-2">Trouvez votre séjour en un instant</div>
              <p class="text-sm text-[#1B4332]/80 leading-6">Recherchez par destination, type d'hébergement et dates.</p>
            </div>
            <form class="space-y-4">
              <div>
                <label class="block text-xs uppercase tracking-[0.24em] text-[#C9A84C] mb-2">Destination</label>
                <input type="text" placeholder="Abidjan, Yamoussoukro..." class="w-full rounded-[20px] border border-[#C9A84C]/20 bg-white/90 py-4 px-4 text-sm text-[#1B4332]" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs uppercase tracking-[0.24em] text-[#C9A84C] mb-2">Arrivée</label>
                  <input type="date" class="w-full rounded-[20px] border border-[#C9A84C]/20 bg-white/90 py-4 px-4 text-sm text-[#1B4332]" />
                </div>
                <div>
                  <label class="block text-xs uppercase tracking-[0.24em] text-[#C9A84C] mb-2">Départ</label>
                  <input type="date" class="w-full rounded-[20px] border border-[#C9A84C]/20 bg-white/90 py-4 px-4 text-sm text-[#1B4332]" />
                </div>
              </div>
              <div>
                <label class="block text-xs uppercase tracking-[0.24em] text-[#C9A84C] mb-2">Type</label>
                <select class="w-full rounded-[20px] border border-[#C9A84C]/20 bg-white/90 py-4 px-4 text-sm text-[#1B4332]">
                  <option>Tous les types</option>
                  <option>Hôtel</option>
                  <option>Résidence</option>
                  <option>Villa</option>
                </select>
              </div>
              <button type="button" onclick="window.location.href='<?= $base_url ?? rtrim(APP_URL, '/') ?>/search'" class="btn-gold w-full py-4 rounded-[20px]">
                Rechercher un hébergement
              </button>
            </form>
          </div>
          <div class="absolute -right-6 bottom-6 hidden xl:block">
            <div class="rounded-full bg-[#C9A84C]/10 border border-[#C9A84C]/20 w-24 h-24"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section aria-hidden="true" class="w-full overflow-hidden">
  <svg viewBox="0 0 1440 120" class="w-full block" preserveAspectRatio="none">
    <path d="M0 80C180 120 360 40 540 60C720 80 900 120 1080 80C1260 40 1440 80 1440 80V120H0V80Z" fill="#F0EBE1" />
  </svg>
</section>

<section class="relative bg-[#F0EBE1] py-[90px]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <div class="inline-flex rounded-full border border-[#C9A84C]/20 bg-white/80 px-4 py-2 text-xs uppercase tracking-[0.28em] text-[#1B4332]/80">Nos atouts</div>
      <h2 class="font-display text-3xl md:text-5xl text-[#1B4332] mt-6">Une expérience pensée pour l'Afrique</h2>
      <p class="mt-4 max-w-2xl mx-auto text-base text-[#4A5568]">Ivoire Stay offre une plateforme simple, rapide et sécurisée pour les établissements et les voyageurs.</p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <div class="glass-card p-8 rounded-[28px] border border-[#1B4332]/10">
        <div class="text-[#C9A84C] mb-4">
          <span class="text-3xl font-semibold">1</span>
        </div>
        <h3 class="font-display text-xl text-[#1B4332] mb-3">Réservation instantanée</h3>
        <p class="text-sm text-[#4A5568] leading-6">Confirmez un séjour en quelques secondes, même avec Mobile Money.</p>
      </div>
      <div class="glass-card p-8 rounded-[28px] border border-[#1B4332]/10">
        <div class="text-[#C9A84C] mb-4">
          <span class="text-3xl font-semibold">2</span>
        </div>
        <h3 class="font-display text-xl text-[#1B4332] mb-3">Paiement sécurisé</h3>
        <p class="text-sm text-[#4A5568] leading-6">Toutes les données sont protégées, pour les voyageurs et les établissements.</p>
      </div>
      <div class="glass-card p-8 rounded-[28px] border border-[#1B4332]/10">
        <div class="text-[#C9A84C] mb-4">
          <span class="text-3xl font-semibold">3</span>
        </div>
        <h3 class="font-display text-xl text-[#1B4332] mb-3">Gestion centralisée</h3>
        <p class="text-sm text-[#4A5568] leading-6">Gérez chambres, réservations et facturation depuis un même tableau de bord.</p>
      </div>
      <div class="glass-card p-8 rounded-[28px] border border-[#1B4332]/10">
        <div class="text-[#C9A84C] mb-4">
          <span class="text-3xl font-semibold">4</span>
        </div>
        <h3 class="font-display text-xl text-[#1B4332] mb-3">Établissements vérifiés</h3>
        <p class="text-sm text-[#4A5568] leading-6">Chaque établissement est validé avant d’être publié sur la plateforme.</p>
      </div>
    </div>
  </div>
</section>

<section class="relative overflow-hidden py-[90px]" style="background-image:url('<?= $base_url ?>/assets/back_destination.jpg'); background-size:cover; background-position:center;">
  <style>
    .dest-slide-item { flex-shrink: 0; cursor: pointer; transition: all 0.6s cubic-bezier(0.4,0,0.2,1); }
    .dest-img-wrap { overflow: hidden; position: relative; transition: all 0.6s cubic-bezier(0.4,0,0.2,1); }
    .dest-img-wrap img { width:100%; height:100%; object-fit:cover; display:block; transition: transform 0.7s cubic-bezier(0.4,0,0.2,1); }
    .dest-slide-item:hover .dest-img-wrap img { transform: scale(1.07); }
    .dest-hover-desc {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      padding: 28px 24px 24px 24px;
      background: linear-gradient(to top,
        rgba(10,30,20,0.97) 0%,
        rgba(10,30,20,0.75) 55%,
        transparent 100%);
      opacity: 0;
      transform: translateY(8px);
      transition: opacity 0.4s ease, transform 0.4s ease;
      display: flex; flex-direction: column;
      justify-content: flex-end;
    }
    .dest-slide-item:hover .dest-hover-desc {
      opacity: 1;
      transform: translateY(0);
    }
    .dest-nav-btn {
      width:48px; height:48px; border-radius:50%;
      background:rgba(255,255,255,0.12);
      backdrop-filter:blur(12px);
      border:1px solid rgba(255,255,255,0.25);
      cursor:pointer; display:flex; align-items:center;
      justify-content:center; transition:all 0.3s; color:white;
      flex-shrink:0;
    }
    .dest-nav-btn:hover {
      background:#C9A84C; border-color:#C9A84C;
      transform:scale(1.1);
    }
    .d-dot {
      height:4px; border-radius:2px;
      background:rgba(255,255,255,0.3);
      cursor:pointer; transition:all 0.4s ease;
    }
    .d-dot.d-dot-active {
      width:32px!important; background:#C9A84C;
    }
    .dest-grid-card {
      position: relative; overflow: hidden;
      border-radius: 20px; cursor: pointer;
      transition: transform 0.35s ease, box-shadow 0.35s ease;
    }
    .dest-grid-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 48px rgba(0,0,0,0.35);
    }
    .dest-grid-card img {
      width:100%; height:100%; object-fit:cover; display:block;
      transition: transform 0.5s ease;
    }
    .dest-grid-card:hover img { transform:scale(1.05); }
    .dest-grid-overlay {
      position:absolute; inset:0;
      background:linear-gradient(to top,
        rgba(10,30,20,0.90) 0%,
        rgba(10,30,20,0.35) 50%,
        transparent 100%);
      transition: background 0.4s ease;
    }
    .dest-grid-card:hover .dest-grid-overlay {
      background:linear-gradient(to top,
        rgba(10,30,20,0.97) 0%,
        rgba(10,30,20,0.55) 60%,
        rgba(10,30,20,0.1) 100%);
    }
    .dest-grid-content {
      position:absolute; bottom:0; left:0; right:0;
      padding:20px;
    }
  </style>

  <div style="position:relative;z-index:3;margin-bottom:-2px;">
    <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" style="display:block;width:100%;" preserveAspectRatio="none">
      <path d="M0,60 C360,0 1080,80 1440,20 L1440,0 L0,0 Z" fill="#FAF7F2"/>
    </svg>
  </div>

  <div style="position:absolute; inset:0; z-index:0; background-image:url('<?= $base_url ?>/assets/back_destination.jpg'); background-size:cover; background-position:center; background-attachment:fixed;"></div>
  <div style="position:absolute; inset:0; z-index:1; background:linear-gradient(160deg, rgba(10,30,20,0.88) 0%, rgba(15,43,32,0.80) 50%, rgba(10,30,20,0.90) 100%);"></div>

  <div style="position:relative;z-index:2; padding:60px 0 100px 0;">
    <div style="text-align:center;margin-bottom:60px;padding:0 24px;">
      <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(201,168,76,0.15);border:1px solid rgba(201,168,76,0.4);color:#C9A84C;border-radius:50px;padding:7px 20px;font-family:Inter,sans-serif;font-size:13px;letter-spacing:0.06em;margin-bottom:20px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Destinations phares
      </div>
      <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(38px,5vw,60px);color:white;font-weight:600;margin:0 0 14px 0;line-height:1.1;">Explorez la <span style="color:#C9A84C;font-style:italic;">Côte d'Ivoire</span></h2>
      <p style="font-family:Inter,sans-serif;font-size:17px;color:rgba(255,255,255,0.65);max-width:560px;margin:0 auto;line-height:1.7;">De la lagune d'Abidjan aux plages de Sassandra, découvrez plus de <strong style="color:#C9A84C;">13 destinations</strong> soigneusement référencées sur notre plateforme.</p>
    </div>

    <div x-data="{
      current: 0,
      paused: false,
      timer: null,
      items: [
        { name:'Abidjan', region:'District Autonome', count:142, tag:'Ville & Affaires', desc:'Capitale économique entre lagune, gratte-ciels et vie nocturne trépidante. Hub incontournable de l\'Afrique de l\'Ouest.', img:'<?= $base_url ?>/assets/carrouss1.jpg' },
        { name:'Yamoussoukro', region:'Centre', count:38, tag:'Culture & Histoire', desc:'Capitale politique, terre de la Basilique Notre-Dame de la Paix et de sérénité ivoirienne.', img:'<?= $base_url ?>/assets/carrouss2.jpg' },
        { name:'Grand-Bassam', region:'Sud-Comoé', count:27, tag:'Plage & Patrimoine', desc:'Ancienne capitale coloniale classée UNESCO, plages dorées, galeries d\'art et fruits de mer.', img:'<?= $base_url ?>/assets/carrouss3.jpg' },
        { name:'San-Pédro', region:'Bas-Sassandra', count:19, tag:'Nature & Évasion', desc:'Port moderne, forêt tropicale et eaux turquoise du Sud-Ouest ivoirien.', img:'<?= $base_url ?>/assets/carrouss4.jpg' },
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
      radiusFor(pos) {
        return pos==='center' ? 'border-radius:28px;' : pos.includes('1') ? 'border-radius:22px;' : 'border-radius:16px;';
      },
      shadowFor(pos) {
        return pos==='center' ? 'box-shadow:0 40px 80px rgba(0,0,0,0.55);' : pos.includes('1') ? 'box-shadow:0 20px 48px rgba(0,0,0,0.35);' : 'box-shadow:0 8px 24px rgba(0,0,0,0.2);';
      }
    }"
    x-init="startAuto()"
  >
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
                <a :href="'<?= $base_url ?>/search?city=' + encodeURIComponent(dest.name)" style="display:inline-flex;align-items:center;gap:8px;background:#C9A84C;color:white;font-family:Inter,sans-serif;font-size:14px;font-weight:600;padding:11px 26px;border-radius:50px;text-decoration:none;box-shadow:0 4px 16px rgba(201,168,76,0.45);transition:all 0.3s;" >
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

  <div style="position:relative;z-index:3;margin-top:-2px;">
    <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" style="display:block;width:100%;" preserveAspectRatio="none">
      <path d="M0,20 C480,80 960,0 1440,60 L1440,80 L0,80 Z" fill="#FAF7F2"/>
    </svg>
  </div>
</section>
<section aria-hidden="true" class="w-full overflow-hidden">
  <svg viewBox="0 0 1440 120" class="w-full block" preserveAspectRatio="none">
    <path d="M0 80C180 120 360 40 540 60C720 80 900 120 1080 80C1260 40 1440 80 1440 80V120H0V80Z" fill="#1B4332" />
  </svg>
</section>

<section class="relative bg-[#1B4332] py-[90px]">
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(201,168,76,0.15),_transparent_40%)] pointer-events-none"></div>
  <div class="relative max-w-5xl mx-auto px-4 text-center">
    <h2 class="font-display text-4xl md:text-5xl text-white">Transformez votre établissement avec Ivoire Stay</h2>
    <p class="mt-4 text-base text-white/75 max-w-2xl mx-auto">Gérez vos réservations, vos clients et vos paiements depuis une interface conçue pour le marché ivoirien.</p>
    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="btn-gold px-8 py-4">Démarrer gratuitement</a>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/tarifs" class="btn-outline-gold px-8 py-4">Voir les tarifs</a>
    </div>
  </div>
</section>

