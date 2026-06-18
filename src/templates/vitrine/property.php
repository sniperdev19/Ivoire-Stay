<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="prop-page"
  x-data="{
    pid: <?= json_encode($property_id ?? null) ?>,
    base: '<?= $base ?>',
    loading: false, error: null,

    property: null, rooms: [], similar: [],
    activePhoto: 0, lightboxOpen: false,
    roomPhotoIdx: {},

    checkIn: '', checkOut: '',
    activeSection: 0, showBookingBar: false,


    async init(){
      this.loading = true; this.error = null;
      const id = this.pid;
      if (!id) { this.error = 'Identifiant établissement manquant.'; this.loading = false; return; }
      const p = new URLSearchParams(window.location.search);
      if (p.get('check_in')) this.checkIn = p.get('check_in');
      if (p.get('check_out')) this.checkOut = p.get('check_out');
      this.initScroll();
      await this.loadProperty(id);
      this.loading = false;
    },

    async publicFetch(path){
      const url = path.startsWith('http') ? path : this.base + path;
      const res = await fetch(url, { cache: 'no-store' });
      const txt = await res.text();
      if (!res.ok) {
        const clean = txt.replace(/<[^>]+>/g, '').trim();
        throw new Error(clean || ('Erreur serveur ' + res.status));
      }
      try { return JSON.parse(txt); } catch(e){ throw new Error('Réponse API invalide'); }
    },

    async loadProperty(id){
      this.error = null; this.loading = true;
      try{
        const data = await this.publicFetch('/api/public/property/' + id);
        if (!data?.success) { this.error = data?.message || 'Établissement introuvable.'; return; }
        this.property = data.data?.establishment ?? data.data ?? null;
        this.rooms = Array.isArray(data.data?.rooms) ? data.data.rooms : [];
        this.rooms.forEach(r => { if (r?.id) this.roomPhotoIdx[r.id] = 0; });
        await this.loadSimilar();
      } catch(e){ this.error = e.message || 'Erreur de chargement.'; }
      finally { this.loading = false; }
    },

    async loadSimilar(){
      if (!this.property?.city) return;
      try{
        const q = '/api/public/search?city=' + encodeURIComponent(this.property.city) + '&per_page=6';
        const data = await this.publicFetch(q);
        if (!data?.success) return;
        const all = data.data?.establishments ?? data.data ?? [];
        this.similar = all.filter(s => s.id != this.property.id).slice(0,4);
      } catch(e) { /* silent */ }
    },

    initScroll(){
      const sections = [
        document.getElementById('overview-section'),
        document.getElementById('rooms-section'),
        document.getElementById('similar-section'),
      ].filter(Boolean);
      window.addEventListener('scroll', () => {
        const top = window.scrollY + window.innerHeight * 0.35;
        this.activeSection = sections.findIndex(section => {
          const rect = section.getBoundingClientRect();
          const offset = rect.top + window.scrollY;
          return top >= offset && top < offset + rect.height;
        });
      }, { passive: true });
    },

    /* Gallery */
    get photos(){ return this.property?.photos ?? []; },
    get totalPhotos(){ return this.photos.length || 1; },
    mainPhotoUrl(){
      const base = this.base;
      const fb = 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200';

      // Priorité 1 : photo active de la galerie
      if (this.photos.length > 0 && this.photos[this.activePhoto]?.url) {
        return this.photos[this.activePhoto].url;
      }

      // Priorité 2 : cover_photo de l'établissement
      if (this.property?.cover_photo) {
        const cp = this.property.cover_photo;
        return cp.startsWith('http') ? cp : base + '/' + cp.replace(/^\/+/, '');
      }

      return fb;
    },
    prevPhoto(){ this.activePhoto = (this.activePhoto - 1 + this.totalPhotos) % this.totalPhotos; },
    nextPhoto(){ this.activePhoto = (this.activePhoto + 1) % this.totalPhotos; },
    setActivePhoto(i){ this.activePhoto = i % this.totalPhotos; },

    /* Room carousel helpers */
    roomPhotos(room){ const ph = room?.photos ?? []; return ph.length ? ph : [{ url: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600' }]; },
    roomPrev(id){ const total = (this.roomPhotos(this.rooms.find(r=>r.id==id)).length)||1; this.roomPhotoIdx[id] = ((this.roomPhotoIdx[id]||0) - 1 + total) % total; },
    roomNext(id){ const total = (this.roomPhotos(this.rooms.find(r=>r.id==id)).length)||1; this.roomPhotoIdx[id] = ((this.roomPhotoIdx[id]||0) + 1) % total; },
    roomTrackStyle(id){ const idx = this.roomPhotoIdx[id]||0; return 'transform: translateX(' + (-100 * idx) + '%)'; },

    bookRoom(room){
      const params = new URLSearchParams();
      if (this.checkIn) params.set('check_in', this.checkIn);
      if (this.checkOut) params.set('check_out', this.checkOut);
      const query = params.toString() ? '?' + params.toString() : '';
      window.location.href = this.base + '/booking/' + room.id + query;
    },

    formatPrice(p){ if (p == null) return '—'; return new Intl.NumberFormat('fr-FR').format(p) + ' FCFA'; },
    formatDate(value){ if (!value) return ''; const date = new Date(value); return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }); },
    getStars(n=4){ n = Math.round(n||4); return '★'.repeat(n) + '☆'.repeat(5-n); },
    minPrice(){ const arr = this.rooms.map(r=>r.base_price).filter(Boolean); return arr.length ? Math.min(...arr) : null; },

    scrollToRooms(){ document.getElementById('rooms-section')?.scrollIntoView({ behavior: 'smooth' }); },

    photoFallback(e){ e.target.src = 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600'; }
  }"
  x-init="init()"
  @keydown.escape.window="lightboxOpen = false"
>

  <!-- SECTION 1 — HERO ÉDITORIAL -->
  <section class="hero-section" x-show="!loading && !error">
    <div class="hero-cinematic" :style="`background-image:url(${mainPhotoUrl()})`">
      <div class="hero-overlay"></div>

      <!-- NAVIGATION -->
      <nav class="hero-nav">
        <div class="hero-nav-logo">
          <div class="hero-nav-logo-sq">H</div>
        </div>
        <div class="hero-nav-links">
          <a href="#">Accueil</a>
          <a href="#">Catalogue</a>
          <a href="#">Services</a>
          <a href="#">Contact</a>
        </div>
        <a class="hero-nav-back" :href="base + '/search'">← Retour</a>
      </nav>

      <!-- CONTENU CENTRÉ -->
      <div class="hero-center">
        <span class="hero-pill" x-text="(property?.type ?? 'Établissement') + ' · ' + getStars(property?.stars ?? 4)"></span>
        <h1 class="hero-title">
          <span x-text="property?.name ?? 'Votre séjour idéal'"></span>
        </h1>
        <p class="hero-sub" x-text="property?.description ? property.description.slice(0, 100) + '…' : 'Un cadre raffiné pensé pour votre confort absolu.'"></p>
        <div class="hero-btns">
          <button class="hero-btn-primary" @click="scrollToRooms()">
            Voir les chambres <span>›</span>
          </button>
          <button class="hero-btn-ghost" @click="lightboxOpen = true">
            Galerie photos
          </button>
        </div>
      </div>

      <!-- BANDE STATS EN BAS -->
      <div class="hero-stats-strip">
        <div class="hero-stat">
          <div class="hero-stat-rating">
            <div class="hero-stat-badge" x-text="property?.stars ?? 5"></div>
            <div>
              <span class="hero-stat-stars" x-text="getStars(property?.stars ?? 4)"></span>
              <span class="hero-stat-quote">"Établissement d'excellence"</span>
            </div>
          </div>
        </div>
        <div class="hero-stat">
          <span class="hero-stat-label">Localisation</span>
          <span class="hero-stat-val" x-text="property?.city ?? 'N/A'"></span>
          <span class="hero-stat-sub" x-text="property?.address ?? 'Côte d\'Ivoire'"></span>
        </div>
        <div class="hero-stat">
          <span class="hero-stat-label">À partir de</span>
          <strong class="hero-stat-price" x-text="formatPrice(minPrice())"></strong>
          <span class="hero-stat-sub">/ nuit</span>
        </div>
        <div class="hero-stat">
          <span class="hero-stat-label">Chambres</span>
          <span class="hero-stat-val"><span x-text="rooms.length || '—'"></span><small>+</small></span>
          <span class="hero-stat-sub">disponibles</span>
        </div>
      </div>

      <!-- NAVIGATION PHOTOS (coins) -->
      <div class="hero-photo-nav">
        <button type="button" @click="prevPhoto()" aria-label="Photo précédente">←</button>
        <span x-show="totalPhotos > 1" x-text="(activePhoto+1) + ' / ' + totalPhotos"></span>
        <button type="button" @click="nextPhoto()" aria-label="Photo suivante">→</button>
      </div>
    </div>
  </section>

  <!-- SECTION 2 — BANDE DE STATS -->
  <section class="stats-band" x-show="!loading && !error">
    <div class="stats-item">
      <div class="stats-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 21V5a2 2 0 012-2h3v18H6a2 2 0 01-2-2z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h6a2 2 0 012 2v16h-8V3z"/>
        </svg>
      </div>
      <div class="stats-copy">
        <strong x-text="rooms.length || 0"></strong>
        <span>Chambres</span>
      </div>
    </div>
    <div class="stats-divider"></div>
    <div class="stats-item">
      <div class="stats-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
        </svg>
      </div>
      <div class="stats-copy">
        <strong x-text="getStars(property?.stars ?? 4)"></strong>
        <span>Classement</span>
      </div>
    </div>
    <div class="stats-divider"></div>
    <div class="stats-item">
      <div class="stats-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1118 0z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
      </div>
      <div class="stats-copy">
        <strong x-text="property?.city ?? 'N/A'"></strong>
        <span>Localisation</span>
      </div>
    </div>
    <div class="stats-divider"></div>
    <div class="stats-item">
      <div class="stats-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c.742 0 1.45-.204 2.057-.56a4.992 4.992 0 012.527-1.02A2.16 2.16 0 0118.5 8.5c0-.557-.243-1.06-.63-1.414A2.098 2.098 0 0016 6H8a2.098 2.098 0 00-1.87.586A1.986 1.986 0 005.5 8.5c0 .58.223 1.13.617 1.535a4.992 4.992 0 012.527 1.02c.607.356 1.315.56 2.057.56z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13v5"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 16h6"/>
        </svg>
      </div>
      <div class="stats-copy">
        <strong>Établissement certifié</strong>
        <span>Vérification</span>
      </div>
    </div>
  </section>

  <!-- SECTION 3 — DESCRIPTION ASYMÉTRIQUE -->
  <section class="about-section" x-show="!loading && !error">
    <div class="about-grid">
      <div class="about-number">02</div>
      <div class="about-copy">
        <span class="section-tag">À PROPOS</span>
        <h2>Une expérience <em>authentique</em><br>au cœur de la ville</h2>
        <p x-text="property?.description ?? 'Un séjour confortable avec des prestations pensées pour votre détente et votre découverte.'"></p>
      </div>
      <div class="about-card">
        <div class="about-card-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21V8a2 2 0 012-2h10a2 2 0 012 2v13"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21V12h6v9"/>
          </svg>
          <span x-text="property?.phone ?? 'N/A'"></span>
        </div>
        <div class="about-card-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1118 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
          <span x-text="property?.address ?? property?.city ?? 'N/A'"></span>
        </div>
        <div class="about-card-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 21V5a2 2 0 012-2h12a2 2 0 012 2v16"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21V9h6v12"/>
          </svg>
          <span x-text="property?.type ?? 'Hébergement'"></span>
        </div>
        <div class="about-card-divider"></div>
        <div class="about-amenities">
          <template x-for="amenity in (property?.amenities ?? []).slice(0,6)" :key="amenity">
            <div class="about-amenity">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span x-text="amenity"></span>
            </div>
          </template>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 5 — CHAMBRES (ÉDITORIALE) -->
  <section id="rooms-section" class="rooms-section" x-show="!loading && !error">
    <div class="rooms-header">
      <div class="rooms-header-rule">
        <div class="rh-rule"></div>
        <span class="section-tag">SÉLECTION</span>
        <div class="rh-rule"></div>
      </div>
      <h2>Nos chambres <em>exclusives</em></h2>
      <p>Choisissez votre chambre selon vos besoins et profitez d'une réservation fluide.</p>
    </div>
    <div class="rooms-list">
      <template x-if="rooms.length === 0">
        <div class="prop-empty-state">
          <strong>Aucune chambre disponible</strong>
          <p>Cet établissement n'a pas encore de chambres publiées.</p>
        </div>
      </template>
      <template x-if="rooms.length > 0">
        <article class="room-featured">
          <div class="room-featured-visual">
            <div class="room-slider" :style="roomTrackStyle(rooms[0].id)">
              <template x-for="(photo, idx) in roomPhotos(rooms[0])" :key="idx">
                <img :src="photo.url" :alt="rooms[0].name" @error="photoFallback($event)">
              </template>
            </div>
            <div class="room-type-badge" x-text="rooms[0].type_name ?? rooms[0].room_type?.name ?? 'Chambre'"></div>
            <div class="room-highlight">Coup de cœur</div>
            <div class="room-feat-count" x-show="roomPhotos(rooms[0]).length > 1">
              <span x-text="(roomPhotoIdx[rooms[0].id] || 0) + 1"></span>
              <span> / </span>
              <span x-text="roomPhotos(rooms[0]).length"></span>
            </div>
            <button class="room-nav room-prev" @click.stop="roomPrev(rooms[0].id)">←</button>
            <button class="room-nav room-next" @click.stop="roomNext(rooms[0].id)">→</button>
          </div>
          <div class="room-featured-info">
            <div>
              <div class="room-label">CHAMBRE <span>01</span></div>
              <h3 x-text="rooms[0].name"></h3>
              <div class="room-divider"></div>
              <div class="room-features">
                <div class="room-feature"><span x-text="(rooms[0].capacity ?? 2) + ' personnes'"></span></div>
                <div class="room-feature"><span x-text="rooms[0].beds ?? rooms[0].type_name ?? 'Lit double'"></span></div>
                <div class="room-feature"><span x-text="rooms[0].floor ? 'Étage ' + rooms[0].floor : 'N/A'"></span></div>
              </div>
              <div class="room-tags">
                <template x-for="tag in (rooms[0].amenities ?? []).slice(0,5)" :key="tag">
                  <span x-text="tag"></span>
                </template>
              </div>
            </div>
            <div class="room-bottom">
              <div class="price-block">
                <span>À partir de</span>
                <strong x-text="formatPrice(rooms[0].base_price ?? rooms[0].price)"></strong>
                <small>/ nuit</small>
              </div>
              <div>
                <button class="room-reserve" type="button" @click="bookRoom(rooms[0])">Réserver cette chambre</button>
              </div>
            </div>
          </div>
        </article>
      </template>
      <div class="rooms-grid" x-show="rooms.length > 1">
        <template x-for="(room, index) in rooms.slice(1)" :key="room.id">
          <article class="room-grid-card">
            <div class="room-grid-visual">
              <div class="room-slider" :style="roomTrackStyle(room.id)">
                <template x-for="(photo, idx) in roomPhotos(room)" :key="idx">
                  <img :src="photo.url" :alt="room.name" @error="photoFallback($event)">
                </template>
              </div>
              <div class="room-type-badge" x-text="room.type_name ?? room.room_type?.name ?? 'Chambre'"></div>
              <div class="room-grid-nav">
                <button @click.stop="roomPrev(room.id)">←</button>
                <button @click.stop="roomNext(room.id)">→</button>
              </div>
            </div>
            <div class="room-grid-info">
              <div class="room-label">CHAMBRE <span x-text="String(index + 2).padStart(2, '0')"></span></div>
              <h3 x-text="room.name"></h3>
              <div class="room-tags">
                <template x-for="tag in (room.amenities ?? []).slice(0,4)" :key="tag">
                  <span x-text="tag"></span>
                </template>
              </div>
              <div class="room-grid-footer">
                <div class="price-block">
                  <span>À partir de</span>
                  <strong x-text="formatPrice(room.base_price ?? room.price)"></strong>
                  <small>/ nuit</small>
                </div>
                <button class="room-reserve" type="button" @click="bookRoom(room)">Réserver</button>
              </div>
            </div>
          </article>
        </template>
      </div>
    </div>
  </section>

  <!-- SECTION 6 — THUMBS GALERIE -->
  <section class="gallery-thumbs" x-show="!loading && !error && totalPhotos > 1">
    <div class="gallery-title">GALERIE PHOTOS</div>
    <div class="gallery-grid">
      <template x-for="(photo, idx) in photos" :key="idx">
        <button type="button" class="gallery-thumb" :class="{ 'active': idx === activePhoto }" @click="setActivePhoto(idx); lightboxOpen=true">
          <img :src="photo.url" @error="photoFallback($event)">
        </button>
      </template>
    </div>
  </section>

  <!-- SECTION 7 — SIMILAIRES -->
  <section id="similar-section" class="similar-section" x-show="!loading && !error && similar.length > 0">
    <div class="similar-header">
      <div class="similar-number">04</div>
      <span class="section-tag">SUGGESTIONS</span>
      <h2>Vous aimerez aussi</h2>
    </div>
    <div class="similar-grid">
      <template x-for="item in similar" :key="item.id">
        <a :href="base + '/property/' + item.id" class="similar-card">
          <div class="similar-card-image" :style="`background-image:url(${item.cover_photo ? (item.cover_photo.startsWith('http') ? item.cover_photo : base + '/' + item.cover_photo.replace(/^\/+/, '')) : (item.photos?.[0]?.url ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400')})`"></div>
          <div class="similar-card-body">
            <div class="similar-title" x-text="item.name"></div>
            <div class="similar-city" x-text="item.city"></div>
            <div class="similar-divider"></div>
            <div class="similar-meta">
              <span class="similar-price" x-text="formatPrice(item.min_price ?? item.base_price)"></span>
              <span class="similar-stars" x-text="getStars(item.stars ?? 4)"></span>
            </div>
          </div>
        </a>
      </template>
    </div>
  </section>

  <!-- MODAL LIGHTBOX -->
  <div class="prop-lightbox" x-show="lightboxOpen" style="display:none">
    <div class="prop-lightbox-backdrop" @click="lightboxOpen=false"></div>
    <div class="prop-lightbox-card">
      <button class="prop-lightbox-close" @click="lightboxOpen=false" aria-label="Fermer">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
      <img class="lightbox-image" :src="mainPhotoUrl()" alt="Photo agrandie">
      <div class="lightbox-thumbs">
        <template x-for="(photo, idx) in photos" :key="idx">
          <button class="lightbox-thumb" :class="{ 'active': idx===activePhoto }" @click="setActivePhoto(idx)"><img :src="photo.url" @error="photoFallback($event)"></button>
        </template>
      </div>
    </div>
  </div>

  <template x-if="loading">
    <div class="prop-banner prop-loading-banner">
      <div class="spinner"></div>
      <div>Chargement de la fiche propriété...</div>
    </div>
  </template>

  <template x-if="error">
    <div class="prop-banner prop-error-banner">
      <div class="prop-error-card">
        <strong>Erreur</strong>
        <p x-text="error"></p>
        <a :href="base + '/search'" class="prop-btn-secondary">Retour aux résultats</a>
      </div>
    </div>
  </template>

  <style>
  :root {
    --gold: #C9A84C;
    --forest: #1B4332;
    --cream: #FAF7F2;
    --text: #1B4332;
    --bg: #FAF7F2;
    --shadow: 0 24px 80px rgba(15,43,32,0.16);
  }
  .prop-page {
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: var(--text);
    background: var(--cream);
    overflow-x: hidden;
  }
  .prop-btn-primary {
    border: none;
    border-radius: 999px;
    padding: 16px 34px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: white;
    background: linear-gradient(135deg, var(--gold), #A67C2E);
    cursor: pointer;
    transition: transform 0.25s ease;
  }
  .prop-btn-primary:hover { transform: translateY(-2px); }
  .prop-btn-secondary {
    border: 1px solid rgba(27,67,50,0.12);
    background: transparent;
    color: var(--forest);
    padding: 16px 34px;
    border-radius: 999px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
  }
/* ── HERO CINÉMATIQUE ── */
.hero-section { width: 100%; }

.hero-cinematic {
  position: relative;
  width: 100%;
  min-height: 100vh;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: rgba(6, 14, 9, 0.58);
  pointer-events: none;
  z-index: 0;
}

/* NAV */
.hero-nav {
  position: relative;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28px 60px;
}
.hero-nav-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  color: white;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-decoration: none;
}
.hero-nav-logo-sq {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  background: var(--gold);
  color: white;
  font-size: 13px;
  font-weight: 700;
  display: grid;
  place-items: center;
}
.hero-nav-links {
  display: flex;
  gap: 36px;
}
.hero-nav-links a {
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: color 0.2s;
}
.hero-nav-links a:hover { color: white; }
.hero-nav-back {
  padding: 8px 20px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 999px;
  color: rgba(255, 255, 255, 0.85);
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  text-decoration: none;
  transition: background 0.2s;
}
.hero-nav-back:hover { background: rgba(255,255,255,0.1); }

/* CENTER CONTENT */
.hero-center {
  position: relative;
  z-index: 3;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 0 40px;
  gap: 0;
}
.hero-pill {
  display: inline-block;
  padding: 6px 16px;
  border: 1px solid rgba(201, 168, 76, 0.45);
  color: var(--gold);
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  border-radius: 3px;
  margin-bottom: 20px;
}
.hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 96px;
  font-weight: 300;
  color: white;
  line-height: 0.88;
  margin-bottom: 20px;
  max-width: 14ch;
}
.hero-sub {
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  color: rgba(255, 255, 255, 0.6);
  line-height: 1.8;
  margin-bottom: 32px;
  max-width: 440px;
}
.hero-btns {
  display: flex;
  gap: 14px;
  justify-content: center;
  flex-wrap: wrap;
}
.hero-btn-primary {
  padding: 14px 34px;
  background: var(--cream);
  color: var(--forest);
  border: none;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.2s, background 0.2s;
}
.hero-btn-primary:hover { transform: translateY(-2px); background: white; }
.hero-btn-primary span { font-size: 18px; font-weight: 300; }
.hero-btn-ghost {
  padding: 14px 34px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  color: rgba(255, 255, 255, 0.85);
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  background: transparent;
  cursor: pointer;
  transition: background 0.2s;
}
.hero-btn-ghost:hover { background: rgba(255,255,255,0.08); }

/* STATS STRIP */
.hero-stats-strip {
  position: relative;
  z-index: 3;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  background: rgba(6, 14, 9, 0.80);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-top: 1px solid rgba(201, 168, 76, 0.12);
}
.hero-stat {
  padding: 20px 28px;
  border-right: 0.5px solid rgba(255, 255, 255, 0.06);
}
.hero-stat:last-child { border-right: none; }
.hero-stat-label {
  display: block;
  font-family: 'Inter', sans-serif;
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  color: rgba(201, 168, 76, 0.8);
  margin-bottom: 4px;
}
.hero-stat-val {
  display: block;
  font-family: 'Cormorant Garamond', serif;
  font-size: 32px;
  font-weight: 400;
  color: white;
  line-height: 1;
}
.hero-stat-val small { font-size: 16px; color: rgba(255,255,255,0.4); }
.hero-stat-price {
  display: block;
  font-family: 'Cormorant Garamond', serif;
  font-size: 28px;
  font-weight: 600;
  color: white;
  line-height: 1;
}
.hero-stat-sub {
  display: block;
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  color: rgba(255, 255, 255, 0.38);
  margin-top: 3px;
}
.hero-stat-rating {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 100%;
}
.hero-stat-badge {
  min-width: 36px;
  height: 36px;
  border-radius: 8px;
  background: var(--gold);
  color: white;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  font-weight: 700;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.hero-stat-stars {
  display: block;
  color: var(--gold);
  font-size: 13px;
  letter-spacing: 2px;
  margin-bottom: 3px;
}
.hero-stat-quote {
  display: block;
  font-family: 'Inter', sans-serif;
  font-size: 10px;
  color: rgba(255, 255, 255, 0.45);
  font-style: italic;
  line-height: 1.4;
}

/* PHOTO NAV (bottom-right) */
.hero-photo-nav {
  position: absolute;
  bottom: 90px;
  right: 40px;
  z-index: 4;
  display: flex;
  align-items: center;
  gap: 10px;
}
.hero-photo-nav button {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid rgba(201, 168, 76, 0.25);
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  color: white;
  font-size: 15px;
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: background 0.2s;
}
.hero-photo-nav button:hover { background: rgba(255,255,255,0.2); }
.hero-photo-nav span {
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  color: rgba(255, 255, 255, 0.6);
  min-width: 40px;
  text-align: center;
}

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
  .hero-nav { padding: 22px 40px; }
  .hero-nav-links { display: none; }
  .hero-title { font-size: 72px; }
}
@media (max-width: 768px) {
  .hero-nav { padding: 18px 24px; }
  .hero-title { font-size: 52px; }
  .hero-sub { font-size: 14px; }
  .hero-center { padding: 0 24px; }
  .hero-stats-strip { grid-template-columns: repeat(2, 1fr); }
  .hero-stat { padding: 16px 20px; }
  .hero-stat:nth-child(2) { border-right: none; }
  .hero-photo-nav { right: 20px; bottom: 160px; }
}
  .stats-band {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    background: white;
    border-bottom: 0.5px solid rgba(27,67,50,0.08);
    padding: 0;
  }
  .stats-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 24px;
    color: var(--forest);
  }
  .stats-icon { width: 32px; height: 32px; display: grid; place-items: center; color: var(--gold); }
  .stats-copy strong {
    display: block;
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    color: var(--forest);
    font-weight: 600;
    line-height: 1;
  }
  .stats-copy span {
    font-family: 'Inter', sans-serif;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.3em;
    color: rgba(27,67,50,0.38);
    margin-top: 4px;
    display: block;
  }
  .stats-divider { width: 0.5px; background: rgba(27,67,50,0.08); }
  .about-section {
    width: 100%;
    background: var(--cream);
    padding: 100px 0;
  }
  .about-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 0;
    max-width: 1440px;
    margin: 0 auto;
    align-items: center;
  }
  .about-number {
    grid-column: 1 / 3;
    font-family: 'Cormorant Garamond', serif;
    font-size: 160px;
    color: rgba(201,168,76,0.1);
    text-align: right;
    padding-right: 32px;
  }
  .about-copy {
    grid-column: 3 / 7;
    align-self: end;
    padding-bottom: 24px;
  }
  .about-copy .section-tag {
    display: inline-block;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.35em;
    color: var(--gold);
  }
  .about-copy h2 {
    margin: 16px 0 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 64px;
    line-height: 1.0;
    color: var(--forest);
  }
  .about-copy h2 em { color: var(--gold); font-style: normal; }
  .about-copy p {
    margin-top: 32px;
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    line-height: 1.9;
    color: rgba(27,67,50,0.7);
    max-width: 600px;
  }
  .about-card {
    grid-column: 8 / 13;
    background: var(--forest);
    border-radius: 28px;
    padding: 36px;
    margin-left: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  .about-card-item {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
  }
  .about-card-item svg {
    width: 16px;
    height: 16px;
    color: var(--gold);
    flex-shrink: 0;
  }
  .about-card-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 10px 0;
  }
  .about-amenities {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
  .about-amenity {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: white;
  }
  .about-amenity svg { width: 14px; height: 14px; color: var(--gold); }
  .rooms-section { width: 100%; background: var(--cream); }
  .rooms-header {
    padding: 64px 80px 40px;
    max-width: 1440px;
    margin: 0 auto;
  }
  .rooms-header-rule {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
  }
  .rh-rule {
    flex: 1;
    height: 0.5px;
    background: rgba(27,67,50,0.12);
  }
  .rooms-header h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 72px;
    font-weight: 400;
    color: var(--forest);
    line-height: 1;
    margin-bottom: 12px;
  }
  .rooms-header h2 em { color: var(--gold); font-style: italic; }
  .rooms-header p {
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    color: rgba(27,67,50,0.55);
  }
  .rooms-list {
    max-width: 1440px;
    margin: 0 auto;
    display: grid;
    gap: 16px;
    padding: 0 20px 80px;
  }
  .room-featured {
    display: grid;
    grid-template-columns: 58fr 42fr;
    height: 500px;
    border-radius: 24px;
    overflow: hidden;
    background: white;
    box-shadow: 0 12px 48px rgba(15,43,32,0.1);
    margin-bottom: 16px;
  }
  .room-featured-visual,
  .room-grid-visual {
    position: relative;
    overflow: hidden;
  }
  .room-featured-visual {
    min-height: 500px;
  }
  .room-featured-visual .room-slider,
  .room-grid-visual .room-slider {
    display: flex;
    height: 100%;
    transition: transform 0.45s ease;
  }
  .room-featured-visual .room-slider img,
  .room-grid-visual .room-slider img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    flex-shrink: 0;
  }
  .room-feat-count {
    position: absolute;
    bottom: 16px;
    right: 16px;
    padding: 5px 12px;
    border-radius: 99px;
    background: rgba(8,22,15,0.7);
    color: white;
    font-size: 11px;
    backdrop-filter: blur(8px);
  }
  .room-featured-info {
    padding: 52px 52px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .room-featured-info h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 48px;
    color: var(--forest);
    line-height: 1;
    margin: 10px 0 0;
  }
  .rooms-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
  .room-grid-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 6px 24px rgba(15,43,32,0.07);
    display: flex;
    flex-direction: column;
  }
  .room-grid-visual {
    height: 260px;
  }
  .room-grid-nav {
    position: absolute;
    bottom: 12px;
    right: 12px;
    display: flex;
    gap: 6px;
    z-index: 2;
  }
  .room-grid-nav button {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid rgba(201,168,76,0.25);
    background: rgba(250,247,242,0.9);
    backdrop-filter: blur(8px);
    color: var(--forest);
    font-size: 13px;
    cursor: pointer;
    display: grid;
    place-items: center;
  }
  .room-grid-info {
    padding: 24px 28px 28px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .room-grid-info h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    color: var(--forest);
    line-height: 1;
    margin: 4px 0 0;
  }
  .room-grid-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 16px;
    border-top: 0.5px solid rgba(27,67,50,0.09);
    flex-wrap: wrap;
    gap: 12px;
  }
  .room-grid-footer .price-block strong {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    color: var(--forest);
    font-weight: 700;
    line-height: 1;
  }
  .room-grid-footer .room-reserve {
    min-height: 44px;
    padding: 0 24px;
  }
  .room-slider {
    display: flex;
    height: 100%;
    transition: transform 0.45s ease;
  }
  .room-slider img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    flex-shrink: 0;
  }
  .room-type-badge {
    position: absolute;
    top: 24px;
    left: 24px;
    z-index: 10;
    padding: 8px 16px;
    border-radius: 8px;
    background: rgba(250,247,242,0.92);
    color: var(--forest);
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    backdrop-filter: blur(12px);
  }
  .room-highlight {
    position: absolute;
    top: 24px;
    right: 24px;
    z-index: 10;
    padding: 8px 16px;
    border-radius: 8px;
    background: var(--gold);
    color: white;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
  }
  .room-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1px solid rgba(201,168,76,0.2);
    background: rgba(250,247,242,0.85);
    backdrop-filter: blur(8px);
    display: grid;
    place-items: center;
    cursor: pointer;
    z-index: 10;
  }
  .room-prev { left: 20px; }
  .room-next { right: 20px; }
  .room-nav svg { width: 18px; height: 18px; color: var(--forest); }
  .room-label {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.32em;
    color: var(--gold);
  }
  .room-label span { font-weight: 700; margin-left: 8px; }
  .room-divider {
    width: 60px;
    height: 1px;
    margin: 24px 0;
    background: linear-gradient(90deg, var(--gold), transparent);
  }
  .room-features {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    color: rgba(27,67,50,0.8);
    font-family: 'Inter', sans-serif;
    font-size: 13px;
  }
  .room-feature {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .room-feature svg { width: 16px; height: 16px; color: rgba(27,67,50,0.4); }
  .room-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 24px;
  }
  .room-tags span {
    padding: 8px 14px;
    border-radius: 6px;
    border: 1px solid rgba(27,67,50,0.1);
    background: rgba(27,67,50,0.06);
    color: var(--forest);
    font-family: 'Inter', sans-serif;
    font-size: 12px;
  }
  .room-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-top: 28px;
    border-top: 1px solid rgba(27,67,50,0.1);
    padding-top: 28px;
    flex-wrap: wrap;
  }
  .price-block { display: flex; flex-direction: column; gap: 8px; }
  .price-block span {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.35em;
    color: var(--gold);
  }
  .price-block strong {
    font-family: 'Cormorant Garamond', serif;
    font-size: 56px;
    color: var(--forest);
    line-height: 1;
    font-weight: 700;
  }
  .price-block small {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: rgba(27,67,50,0.5);
  }
  .room-reserve {
    min-height: 54px;
    border: none;
    border-radius: 999px;
    padding: 0 36px;
    background: linear-gradient(135deg, var(--gold), #A67C2E);
    color: white;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.25s ease;
  }
  .room-reserve:hover:not(:disabled) { transform: translateY(-2px); }
  .room-reserve:disabled { opacity: 0.55; cursor: not-allowed; }
  .room-note {
    width: 100%;
    margin-top: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    color: var(--gold);
  }
  .gallery-thumbs {
    width: 100%;
    background: var(--forest);
    padding: 48px 80px;
  }
  .gallery-title {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.35em;
    color: var(--gold);
    margin-bottom: 24px;
  }
  .gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
  }
  .gallery-thumb {
    height: 160px;
    border-radius: 16px;
    overflow: hidden;
    border: none;
    padding: 0;
    background: transparent;
    cursor: pointer;
    opacity: 0.65;
    transition: opacity 0.25s ease, box-shadow 0.25s ease;
  }
  .gallery-thumb.active { opacity: 1; box-shadow: 0 0 0 2px var(--gold); }
  .gallery-thumb:hover { opacity: 1; }
  .gallery-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .similar-section {
    width: 100%;
    background: var(--cream);
    padding: 80px 80px 100px;
  }
  .similar-header {
    position: relative;
    max-width: 1440px;
    margin: 0 auto 40px;
  }
  .similar-number {
    position: absolute;
    right: 80px;
    top: 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 160px;
    color: rgba(201,168,76,0.08);
    z-index: 0;
    pointer-events: none;
    user-select: none;
  }
  .similar-header .section-tag {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.35em;
    color: var(--gold);
    position: relative;
    z-index: 1;
  }
  .similar-header h2 {
    position: relative;
    z-index: 1;
    margin: 18px 0 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 64px;
    line-height: 1.0;
    color: var(--forest);
  }
  .similar-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 24px;
    max-width: 1440px;
    margin: 0 auto;
  }
  .similar-card {
    display: grid;
    border-radius: 24px;
    overflow: hidden;
    background: white;
    box-shadow: 0 8px 32px rgba(27,67,50,0.06);
    text-decoration: none;
    color: inherit;
    transition: transform 0.3s ease;
  }
  .similar-card:hover { transform: translateY(-4px); }
  .similar-card-image {
    width: 100%;
    min-height: 200px;
    background-size: cover;
    background-position: center;
  }
  .similar-card-body {
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .similar-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    color: var(--forest);
  }
  .similar-city {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: rgba(27,67,50,0.5);
    margin-top: 4px;
  }
  .similar-divider {
    height: 1px;
    background: rgba(27,67,50,0.08);
    margin: 14px 0;
  }
  .similar-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
  }
  .similar-price {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    color: var(--forest);
    font-weight: 700;
  }
  .similar-stars {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: var(--gold);
  }
  .prop-lightbox {
    position: fixed;
    inset: 0;
    z-index: 120;
    display: grid;
    place-items: center;
    padding: 24px;
  }
  .prop-lightbox-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(27,67,50,0.95);
    backdrop-filter: blur(12px);
  }
  .prop-lightbox-card {
    position: relative;
    width: min(1120px, 100%);
    max-height: 90vh;
    border-radius: 28px;
    background: rgba(27,67,50,0.95);
    backdrop-filter: blur(24px);
    box-shadow: 0 48px 120px rgba(27,67,50,0.3);
    overflow: hidden;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 24px;
  }
  .prop-lightbox-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    color: white;
    cursor: pointer;
    display: grid;
    place-items: center;
  }
  .lightbox-image {
    width: 100%;
    max-height: 72vh;
    object-fit: contain;
    border-radius: 20px;
  }
  .lightbox-thumbs {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 8px;
  }
  .lightbox-thumb {
    border: 2px solid transparent;
    border-radius: 16px;
    overflow: hidden;
    min-width: 96px;
    min-height: 64px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.25s ease, border-color 0.25s ease;
  }
  .lightbox-thumb.active { opacity: 1; border-color: var(--gold); }
  .lightbox-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .prop-banner {
    width: 100%;
    min-height: 68vh;
    display: grid;
    place-items: center;
    padding: 80px 24px;
    background: linear-gradient(180deg, rgba(250,247,242,0.96), rgba(250,247,242,1));
  }
  .spinner {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 4px solid rgba(27,67,50,0.15);
    border-top-color: var(--gold);
    animation: spin 0.8s linear infinite;
    margin-bottom: 18px;
  }
  .prop-error-card {
    display: grid;
    gap: 18px;
    justify-items: center;
    text-align: center;
    background: white;
    border-radius: 26px;
    padding: 32px 30px;
    box-shadow: 0 28px 80px rgba(15,43,32,0.12);
  }
  .prop-error-card strong { font-size: 24px; color: var(--forest); }
  .prop-error-card p { color: rgba(27,67,50,0.75); max-width: 420px; }
  @keyframes spin { to { transform: rotate(360deg); } }
  @media (max-width: 1100px) {
    .hero-body { padding: 0 40px 14px; }
    .hero-title { font-size: 64px; }
    .room-featured { grid-template-columns: 1fr; height: auto; }
    .room-featured-visual { height: 360px; }
    .room-featured-info { padding: 36px 32px 32px; }
    .rooms-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 768px) {
    .hero-body { padding: 0 24px 14px; }
    .hero-title { font-size: 48px; }
    .rooms-header { padding: 48px 24px 32px; }
    .rooms-header h2 { font-size: 44px; }
    .room-featured-info { padding: 28px 24px; }
    .rooms-grid { grid-template-columns: 1fr; }
    .room-grid-visual { height: 220px; }
  }
  </style>
</div>
