<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="search-page"
  x-data="{
    filters: { city:'', type:'', check_in:'', check_out:'' },
    results: [], destinations: [],
    loading: false, error: null,
    totalCount: 0, viewMode: 'grid',

    async publicFetch(path) {
      const url = path.startsWith('http')
        ? path : '<?= $base ?>' + path;
      const res = await fetch(url, { cache:'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const txt = await res.text();
      if (!txt) return null;
      return JSON.parse(txt);
    },

    async init() {
      const p = new URLSearchParams(window.location.search);
      if (p.get('city'))      this.filters.city      = p.get('city');
      if (p.get('type'))      this.filters.type      = p.get('type');
      if (p.get('check_in'))  this.filters.check_in  = p.get('check_in');
      if (p.get('check_out')) this.filters.check_out = p.get('check_out');
      await this.loadDestinations();
      if ([...p.keys()].length > 0) await this.search();
    },

    async loadDestinations() {
      try {
        const d = await this.publicFetch('/api/public/destinations');
        if (d?.success) {
          this.destinations = d.data?.destinations
            ?? d.data ?? [];
        }
        if (!this.destinations.length) this.setFallbackDest();
      } catch(e) { this.setFallbackDest(); }
    },
    setFallbackDest() {
      this.destinations = [
        { city:'Abidjan',       count:142 },
        { city:'Yamoussoukro',  count:38  },
        { city:'Grand-Bassam',  count:27  },
        { city:'Assinie',       count:31  },
        { city:'San-Pédro',     count:19  },
        { city:'Bouaké',        count:11  },
      ];
    },

    async search() {
      this.error = null; this.loading = true;
      this.results = []; this.totalCount = 0;
      try {
        const q = new URLSearchParams();
        if (this.filters.city)      q.set('city',      this.filters.city);
        if (this.filters.type)      q.set('type',      this.filters.type);
        if (this.filters.check_in)  q.set('check_in',  this.filters.check_in);
        if (this.filters.check_out) q.set('check_out', this.filters.check_out);
        const qs = q.toString();
        window.history.replaceState({}, '',
          qs ? ('?' + qs) : window.location.pathname);

        const data = await this.publicFetch(
          '/api/public/search?' + qs);
        if (!data?.success)
          throw new Error(data?.message || 'Erreur API');

        const payload = data.data ?? {};
        let items = [];
        if (Array.isArray(payload.rooms))
          items = payload.rooms;
        else if (Array.isArray(payload.establishments))
          items = payload.establishments;
        else if (Array.isArray(payload.results))
          items = payload.results;
        else if (Array.isArray(payload))
          items = payload;

        this.results    = items;
        this.totalCount = payload.total ?? items.length;
      } catch(e) {
        this.error = e?.message
          || 'Impossible de charger les résultats.';
      } finally { this.loading = false; }
    },

    clearFilters() {
      this.filters = { city:'', type:'', check_in:'', check_out:'' };
      window.history.replaceState({},
        '', window.location.pathname);
      this.search();
    },

    photoUrl(r) {
      const fb = 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600';
      const u = r?.photos?.[0]?.url
        ?? r?.photo ?? r?.cover_photo ?? null;
      if (!u) return fb;
      if (u.startsWith('http')) return u;
      return '<?= $base ?>/' + u.replace(/^\/+/, '');
    },
    formatPrice(p) {
      if (p == null || p === '') return '—';
      return new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
    },
    getStars(n = 4) {
      n = Math.round(n);
      return '★'.repeat(n) + '☆'.repeat(5 - n);
    },
    goProperty(id) {
      window.location.href = '<?= $base ?>' + '/property/' + id;
    },
    isFeatured(idx) { return idx === 0; }
  }"
  x-init="init()"
>

  <div class="search-hero">
    <div class="search-hero-content">
      <h1>Trouvez votre <span>hébergement idéal</span></h1>
      <p>Hôtels, résidences et villas partout en Côte d'Ivoire</p>

      <div class="search-bar">
        <div class="search-bar-field" style="min-width:180px;">
          <span class="search-bar-label">Destination</span>
          <div style="display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:14px;height:14px;color:rgba(255,255,255,0.4);flex-shrink:0;"
              fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0
                01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15
                11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <input type="text" class="search-bar-input"
              x-model="filters.city"
              placeholder="Abidjan, Bassam..."
              @keydown.enter="search()">
          </div>
        </div>

        <div class="search-bar-field" style="min-width:130px;">
          <span class="search-bar-label">Type</span>
          <div style="display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:14px;height:14px;color:rgba(255,255,255,0.4);flex-shrink:0;"
              fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14
                0h2m-2 0h-5m-9 0H3m2 0h5"/>
            </svg>
            <select class="search-bar-input"
              x-model="filters.type"
              style="cursor:pointer;">
              <option value="">Tous types</option>
              <option value="hotel">Hôtel</option>
              <option value="residence">Résidence</option>
              <option value="villa">Villa</option>
            </select>
          </div>
        </div>

        <div class="search-bar-field" style="min-width:140px;">
          <span class="search-bar-label">Arrivée</span>
          <div style="display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:14px;height:14px;color:rgba(255,255,255,0.4);flex-shrink:0;"
              fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0
                002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0
                002 2z"/>
            </svg>
            <input type="date" class="search-bar-input"
              x-model="filters.check_in">
          </div>
        </div>

        <div class="search-bar-field" style="min-width:140px;">
          <span class="search-bar-label">Départ</span>
          <div style="display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:14px;height:14px;color:rgba(255,255,255,0.4);flex-shrink:0;"
              fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0
                002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0
                002 2z"/>
            </svg>
            <input type="date" class="search-bar-input"
              x-model="filters.check_out"
              :min="filters.check_in">
          </div>
        </div>

        <button class="search-bar-btn" @click="search()">
          <svg xmlns="http://www.w3.org/2000/svg"
            style="width:16px;height:16px;" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2.5"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          Rechercher
        </button>
      </div>
    </div>
  </div>

  <div class="filter-strip">
    <div class="filter-strip-inner">

      <button class="filter-chip"
        :class="filters.city===''?'active':''"
        @click="filters.city=''; search()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            stroke-width="2"
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0
            012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2
            2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488
            V18a2 2 0 012-2h3.064"/>
        </svg>
        Toutes les villes
      </button>

      <div class="filter-divider"></div>

      <template x-for="dest in destinations.slice(0,5)"
        :key="dest.city">
        <button class="filter-chip"
          :class="filters.city===dest.city?'active':''"
          @click="filters.city=dest.city; search()">
          <span x-text="dest.city"></span>
          <span style="opacity:0.65;font-size:11px;"
            x-text="'('+dest.count+')'"></span>
        </button>
      </template>

      <div class="filter-divider"></div>

      <button class="filter-chip"
        :class="filters.type==='hotel'?'active':''"
        @click="filters.type==='hotel'
          ?(filters.type=''):(filters.type='hotel');
          search()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            stroke-width="2"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
        </svg>
        Hôtels
      </button>
      <button class="filter-chip"
        :class="filters.type==='residence'?'active':''"
        @click="filters.type==='residence'
          ?(filters.type=''):(filters.type='residence');
          search()">
        Résidences
      </button>
      <button class="filter-chip"
        :class="filters.type==='villa'?'active':''"
        @click="filters.type==='villa'
          ?(filters.type=''):(filters.type='villa');
          search()">
        Villas
      </button>

      <button
        class="filter-clear"
        x-show="filters.city||filters.type
          ||filters.check_in||filters.check_out"
        @click="clearFilters()">
        × Effacer tout
      </button>
    </div>
  </div>

  <div class="search-main">
    <aside class="search-sidebar">
      <div class="sidebar-section">
        <div class="sidebar-title">Destinations</div>
        <template x-for="dest in destinations" :key="dest.city">
          <button class="dest-btn"
            :class="filters.city===dest.city?'active':''"
            @click="filters.city=dest.city; search()">
            <span x-text="dest.city"></span>
            <span class="dest-count"
              x-text="dest.count"></span>
          </button>
        </template>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Type</div>
        <div class="type-grid">
          <button class="type-btn"
            :class="filters.type===''?'active':''"
            @click="filters.type=''; search()">Tous</button>
          <button class="type-btn"
            :class="filters.type==='hotel'?'active':''"
            @click="filters.type='hotel'; search()">Hôtel</button>
          <button class="type-btn"
            :class="filters.type==='residence'?'active':''"
            @click="filters.type='residence'; search()">
            Résidence</button>
          <button class="type-btn"
            :class="filters.type==='villa'?'active':''"
            @click="filters.type='villa'; search()">Villa</button>
        </div>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Budget (FCFA)</div>
        <div style="display:flex;gap:8px;">
          <input type="number" placeholder="Min"
            style="width:50%;padding:8px 10px;border-radius:8px;
              border:1px solid rgba(201,168,76,0.2);
              background:#FAFAFA;font-size:12px;
              font-family:Inter,sans-serif;"
            disabled>
          <input type="number" placeholder="Max"
            style="width:50%;padding:8px 10px;border-radius:8px;
              border:1px solid rgba(201,168,76,0.2);
              background:#FAFAFA;font-size:12px;
              font-family:Inter,sans-serif;"
            disabled>
        </div>
        <p style="font-size:10px;color:#9CA3AF;
          font-style:italic;margin-top:6px;">
          Filtre prix bientôt disponible
        </p>
      </div>

      <div class="sidebar-section"
        x-show="filters.check_in || filters.check_out">
        <div class="sidebar-title">Dates sélectionnées</div>
        <div style="display:flex;flex-direction:column;gap:6px;">
          <div x-show="filters.check_in"
            style="display:flex;justify-content:space-between;
              font-size:12px;">
            <span style="color:#9CA3AF;">Arrivée</span>
            <span style="font-weight:600;color:#1B4332;"
              x-text="new Date(filters.check_in)
                .toLocaleDateString('fr-FR',
                {day:'numeric',month:'short'})">
            </span>
          </div>
          <div x-show="filters.check_out"
            style="display:flex;justify-content:space-between;
              font-size:12px;">
            <span style="color:#9CA3AF;">Départ</span>
            <span style="font-weight:600;color:#1B4332;"
              x-text="new Date(filters.check_out)
                .toLocaleDateString('fr-FR',
                {day:'numeric',month:'short'})">
            </span>
          </div>
        </div>
      </div>
    </aside>

    <div class="results-zone">
      <div x-show="error"
        style="background:rgba(220,38,38,0.06);
          border:1px solid rgba(220,38,38,0.2);
          border-radius:14px;padding:14px 18px;
          display:flex;align-items:center;
          justify-content:space-between;gap:16px;
          color:#DC2626;font-size:13px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <svg xmlns="http://www.w3.org/2000/svg"
            style="width:16px;height:16px;flex-shrink:0;"
            fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round"
              stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0
              2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694
              -1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <span x-text="error"></span>
        </div>
        <button style="padding:6px 14px;border:1px solid
          rgba(220,38,38,0.3);border-radius:8px;
          background:white;color:#DC2626;font-size:12px;
          font-weight:600;cursor:pointer;"
          @click="search()">Réessayer</button>
      </div>

      <div class="results-header">
        <div class="results-count">
          <span x-show="loading" style="color:#9CA3AF;">
            Recherche en cours...
          </span>
          <span x-show="!loading && results.length > 0">
            <strong x-text="totalCount"></strong>
            établissement(s)
            <span x-show="filters.city"
              class="city-tag"
              x-text="filters.city">
            </span>
          </span>
          <span x-show="!loading && !results.length && !error"
            style="color:#9CA3AF;">
            Aucun résultat
          </span>
        </div>
        <div class="sort-view">
          <div class="view-btns">
            <button class="view-btn"
              :class="viewMode==='grid'?'active':''"
              @click="viewMode='grid'">
              <svg xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round"
                  stroke-linejoin="round" stroke-width="2"
                  d="M3 3h8v8H3zM13 3h8v8h-8z
                  M3 13h8v8H3zM13 13h8v8h-8z"/>
              </svg>
            </button>
            <button class="view-btn"
              :class="viewMode==='list'?'active':''"
              @click="viewMode='list'">
              <svg xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round"
                  stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div x-show="loading">
        <div class="results-grid">
          <div class="skel-card"
            style="grid-column:span 2;">
            <div class="skel"
              style="height:280px;border-radius:0;">
            </div>
            <div style="padding:16px;
              display:flex;flex-direction:column;gap:10px;">
              <div class="skel"
                style="height:18px;width:55%;"></div>
              <div class="skel"
                style="height:13px;width:35%;"></div>
              <div class="skel"
                style="height:13px;width:70%;"></div>
            </div>
          </div>
          <template x-for="i in 4">
            <div class="skel-card">
              <div class="skel"
                style="height:200px;border-radius:0;">
              </div>
              <div style="padding:16px;
                display:flex;flex-direction:column;gap:8px;">
                <div class="skel"
                  style="height:16px;width:60%;"></div>
                <div class="skel"
                  style="height:12px;width:40%;"></div>
                <div class="skel"
                  style="height:12px;width:80%;"></div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div x-show="!loading && !results.length && !error"
        class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg"
            style="width:36px;height:36px;color:#C9A84C;"
            fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round"
              stroke-linejoin="round" stroke-width="1.5"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14
              0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1
              m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5
              m-4 0h4"/>
          </svg>
        </div>
        <h3 style="font-family:'Cormorant Garamond',serif;
          font-size:26px;color:#1B4332;margin-bottom:8px;">
          Aucun établissement trouvé
        </h3>
        <p style="font-size:14px;color:#9CA3AF;
          max-width:360px;margin:0 auto 20px;
          line-height:1.6;">
          Modifiez vos critères ou explorez 
          une autre destination.
        </p>
        <button style="padding:11px 24px;
          background:linear-gradient(135deg,#C9A84C,#A67C2E);
          color:white;border:none;border-radius:12px;
          font-size:14px;font-weight:700;cursor:pointer;
          font-family:Inter,sans-serif;"
          @click="clearFilters()">
          Effacer les filtres
        </button>
      </div>

      <div x-show="!loading && results.length > 0">
        <div class="results-grid" x-show="viewMode==='grid'">
          <template x-for="(r, idx) in results" :key="r.id">
            <div class="result-card"
              :class="isFeatured(idx) ? 'featured' : ''"
              @click="goProperty(r.id)">
              <div class="card-img-wrap">
                <img :src="photoUrl(r)"
                  :alt="r.name ?? 'Établissement'"
                  class="card-img"
                  @error="$event.target.src=
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600'">
                <div class="card-badge-type"
                  x-text="(r.type??'hébergement')
                    .charAt(0).toUpperCase()
                    +(r.type??'hébergement').slice(1)">
                </div>
                <div class="card-badge-stars"
                  x-text="getStars(r.stars??4)">
                </div>
                <div class="card-badge-new"
                  x-show="idx === 0">
                  Coup de coeur
                </div>
              </div>
              <div class="card-body">
                <div class="card-name"
                  x-text="r.name??'Établissement'">
                </div>
                <div class="card-location">
                  <svg xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998
                      1.998 0 01-2.827 0l-4.244-4.243a8 8
                      0 1111.314 0z"/>
                  </svg>
                  <span x-text="r.city??r.address??'—'">
                  </span>
                </div>
                <div class="card-amenities">
                  <template
                    x-for="a in (r.amenities??[]).slice(0,3)"
                    :key="a">
                    <span class="amenity-tag" x-text="a">
                    </span>
                  </template>
                </div>
                <div class="card-footer">
                  <div class="card-price-wrap">
                    <div class="card-price-from">
                      À partir de
                    </div>
                    <div class="card-price-val"
                      x-text="formatPrice(r.base_price
                        ??r.price??r.min_price)">
                    </div>
                    <div class="card-price-night">/nuit</div>
                  </div>
                  <button class="card-cta"
                    @click.stop="goProperty(r.id)">
                    Voir →
                  </button>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="result-list" x-show="viewMode==='list'">
          <template x-for="r in results" :key="r.id">
            <div class="result-list-card"
              @click="goProperty(r.id)">
              <img :src="photoUrl(r)"
                :alt="r.name??'Établissement'"
                class="list-img"
                @error="$event.target.src=
                  'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400'">
              <div class="list-body">
                <div class="list-info">
                  <div style="font-family:'Cormorant Garamond',
                    serif;font-size:20px;font-weight:600;
                    color:#1B4332;margin-bottom:4px;"
                    x-text="r.name??'Établissement'">
                  </div>
                  <div style="font-size:12px;color:#9CA3AF;
                    display:flex;align-items:center;gap:4px;
                    margin-bottom:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg"
                      style="width:12px;height:12px;
                        color:#C9A84C;"
                      fill="none" viewBox="0 0 24 24"
                      stroke="currentColor">
                      <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998
                        1.998 0 01-2.827 0l-4.244-4.243a8
                        8 0 1111.314 0z"/>
                    </svg>
                    <span x-text="r.city??'—'"></span>
                  </div>
                  <div style="display:flex;flex-wrap:wrap;
                    gap:6px;">
                    <template
                      x-for="a in (r.amenities??[]).slice(0,4)"
                      :key="a">
                      <span class="amenity-tag"
                        x-text="a"></span>
                    </template>
                  </div>
                </div>
              </div>
              <div class="list-right">
                <div style="font-size:10px;color:#9CA3AF;
                  text-transform:uppercase;
                  letter-spacing:0.06em;">
                  À partir de
                </div>
                <div style="font-family:'Cormorant Garamond',
                  serif;font-size:22px;font-weight:700;
                  color:#1B4332;"
                  x-text="formatPrice(r.base_price
                    ??r.price??r.min_price)">
                </div>
                <button class="card-cta"
                  @click.stop="goProperty(r.id)"
                  style="width:100%;text-align:center;">
                  Voir →
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* ── PAGE SEARCH ── */
.search-page { min-height:100vh; background:#FAF7F2; }

/* ══ HERO SEARCH ══ */
.search-hero {
  position: relative;
  padding: 120px 24px 48px 24px;
  background: linear-gradient(135deg,
    #0F2B20 0%, #1B4332 50%, #0F2B20 100%);
  overflow: hidden;
}
.search-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1400')
    center/cover no-repeat;
  opacity: 0.12;
}
.search-hero-content {
  position: relative; z-index: 2;
  max-width: 900px; margin: 0 auto;
  text-align: center;
}
.search-hero h1 {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(28px, 4vw, 48px);
  color: white; font-weight: 600;
  margin-bottom: 8px; line-height: 1.15;
}
.search-hero h1 span { color: #C9A84C; font-style: italic; }
.search-hero p {
  font-size: 15px; color: rgba(255,255,255,0.6);
  margin-bottom: 32px;
}

/* Barre de recherche hero */
.search-bar {
  display: grid;
  grid-template-columns: 1fr auto auto auto auto;
  gap: 0;
  background: rgba(255,255,255,0.08);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.18);
  border-radius: 20px;
  overflow: hidden;
  max-width: 860px;
  margin: 0 auto;
}
.search-bar-field {
  padding: 18px 20px;
  border-right: 1px solid rgba(255,255,255,0.12);
  display: flex; flex-direction: column; gap: 4px;
}
.search-bar-field:last-of-type { border-right: none; }
.search-bar-label {
  font-size: 10px; font-weight: 700;
  color: #C9A84C; text-transform: uppercase;
  letter-spacing: 0.08em;
}
.search-bar-input {
  background: none; border: none; outline: none;
  color: white; font-size: 14px; font-weight: 500;
  font-family: 'Inter', sans-serif;
  width: 100%;
}
.search-bar-input::placeholder { color: rgba(255,255,255,0.4); }
.search-bar-input option { background: #1B4332; }
.search-bar-btn {
  padding: 18px 28px;
  background: linear-gradient(135deg, #C9A84C, #A67C2E);
  color: white; border: none; cursor: pointer;
  font-size: 15px; font-weight: 700;
  font-family: 'Inter', sans-serif;
  display: flex; align-items: center; gap: 8px;
  transition: all 0.3s; white-space: nowrap;
}
.search-bar-btn:hover { background: linear-gradient(135deg, #A67C2E, #C9A84C); }

/* ══ BARRE FILTRES RAPIDES ══ */
.filter-strip {
  position: sticky; top: 0; z-index: 40;
  background: rgba(250,247,242,0.97);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(201,168,76,0.15);
  padding: 12px 24px;
  box-shadow: 0 2px 12px rgba(27,67,50,0.06);
}
.filter-strip-inner {
  max-width: 1280px; margin: 0 auto;
  display: flex; align-items: center;
  gap: 8px; overflow-x: auto;
  scrollbar-width: none;
}
.filter-strip-inner::-webkit-scrollbar { display: none; }
.filter-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 16px; border-radius: 50px;
  font-size: 13px; font-weight: 500;
  white-space: nowrap; cursor: pointer;
  border: 1.5px solid rgba(201,168,76,0.25);
  background: white; color: #374151;
  transition: all 0.2s; font-family: 'Inter', sans-serif;
}
.filter-chip:hover {
  border-color: #C9A84C; color: #1B4332;
}
.filter-chip.active {
  background: #C9A84C; border-color: #C9A84C;
  color: white;
}
.filter-chip svg { width: 13px; height: 13px; }
.filter-divider {
  width: 1px; height: 24px; flex-shrink: 0;
  background: rgba(201,168,76,0.2);
}
.filter-clear {
  background: none; border: none; cursor: pointer;
  font-size: 12px; color: #C9A84C; font-weight: 600;
  padding: 7px 12px; font-family: 'Inter', sans-serif;
  white-space: nowrap;
}

/* ══ LAYOUT PRINCIPAL ══ */
.search-main {
  max-width: 1280px; margin: 0 auto;
  padding: 24px 24px 60px;
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 24px; align-items: start;
}
@media (max-width: 960px) {
  .search-main { grid-template-columns: 1fr; }
  .search-sidebar { display: none; }
  .search-bar { grid-template-columns: 1fr; border-radius: 16px; }
  .search-bar-field { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.12); }
}

/* ══ SIDEBAR ══ */
.search-sidebar {
  position: sticky; top: 68px;
  background: white;
  border-radius: 20px;
  border: 1px solid rgba(201,168,76,0.12);
  box-shadow: 0 2px 16px rgba(27,67,50,0.06);
  overflow: hidden;
}
.sidebar-section {
  padding: 16px 18px;
  border-bottom: 1px solid rgba(201,168,76,0.08);
}
.sidebar-section:last-child { border-bottom: none; }
.sidebar-title {
  font-size: 10px; font-weight: 700;
  color: #C9A84C; text-transform: uppercase;
  letter-spacing: 0.1em; margin-bottom: 10px;
}
.dest-btn {
  display: flex; align-items: center;
  justify-content: space-between;
  width: 100%; padding: 8px 10px;
  border-radius: 10px; border: none;
  background: transparent; cursor: pointer;
  font-size: 13px; color: #374151;
  font-family: 'Inter', sans-serif;
  transition: all 0.2s; text-align: left;
}
.dest-btn:hover { background: rgba(201,168,76,0.08); color: #1B4332; }
.dest-btn.active {
  background: rgba(201,168,76,0.12);
  color: #1B4332; font-weight: 600;
}
.dest-count {
  font-size: 11px; color: #9CA3AF;
  background: #F3F4F6; padding: 2px 6px;
  border-radius: 50px;
}
.dest-btn.active .dest-count { background: rgba(201,168,76,0.2); color: #C9A84C; }
.type-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
}
.type-btn {
  padding: 8px 10px; border-radius: 10px;
  border: 1.5px solid rgba(201,168,76,0.2);
  background: white; cursor: pointer;
  font-size: 12px; font-weight: 500;
  color: #6B7280; font-family: 'Inter', sans-serif;
  transition: all 0.2s; text-align: center;
}
.type-btn:hover { border-color: #C9A84C; color: #1B4332; }
.type-btn.active { background: #C9A84C; border-color: #C9A84C; color: white; }

/* ══ ZONE RÉSULTATS ══ */
.results-zone { min-width: 0; }
.results-header {
  display: flex; align-items: center;
  justify-content: space-between;
  margin-bottom: 18px; flex-wrap: wrap; gap: 10px;
}
.results-count { font-size: 14px; color: #6B7280; }
.results-count strong { color: #1B4332; font-size: 16px; }
.results-count .city-tag {
  display: inline-block;
  background: rgba(201,168,76,0.1);
  color: #C9A84C; font-weight: 600;
  padding: 2px 10px; border-radius: 50px;
  font-size: 13px; margin-left: 4px;
}
.sort-view {
  display: flex; align-items: center; gap: 8px;
}
.view-btns {
  display: flex; background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; padding: 3px; gap: 2px;
}
.view-btn {
  width: 30px; height: 30px; border-radius: 7px;
  border: none; cursor: pointer; background: transparent;
  color: #9CA3AF; display: flex; align-items: center;
  justify-content: center; transition: all 0.2s;
}
.view-btn svg { width: 15px; height: 15px; }
.view-btn.active { background: #1B4332; color: white; }

.results-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
@media (max-width: 1100px) {
  .results-grid { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 640px) {
  .results-grid { grid-template-columns: 1fr; }
}

.result-card {
  background: white; border-radius: 18px;
  border: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 2px 12px rgba(27,67,50,0.06);
  overflow: hidden; cursor: pointer;
  transition: all 0.3s ease;
  display: flex; flex-direction: column;
}
.result-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 40px rgba(27,67,50,0.13);
  border-color: rgba(201,168,76,0.2);
}

.result-card.featured {
  grid-column: span 2;
}
.result-card.featured .card-img { height: 280px; }
.result-card.featured .card-name { font-size: 22px; }

.card-img-wrap { position: relative; overflow: hidden; }
.card-img {
  width: 100%; height: 200px;
  object-fit: cover; display: block;
  transition: transform 0.5s ease;
}
.result-card:hover .card-img { transform: scale(1.04); }

.card-badge-type {
  position: absolute; top: 12px; left: 12px;
  background: rgba(27,67,50,0.88);
  color: white; font-size: 10px; font-weight: 600;
  padding: 4px 10px; border-radius: 50px;
  text-transform: uppercase; letter-spacing: 0.06em;
}
.card-badge-stars {
  position: absolute; top: 12px; right: 12px;
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(8px);
  color: #C9A84C; font-size: 11px; font-weight: 600;
  padding: 4px 10px; border-radius: 50px;
  border: 1px solid rgba(201,168,76,0.2);
}
.card-badge-new {
  position: absolute; bottom: 12px; left: 12px;
  background: #C9A84C; color: white;
  font-size: 10px; font-weight: 700;
  padding: 3px 10px; border-radius: 50px;
}

.card-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
.card-name {
  font-family: 'Cormorant Garamond', serif;
  font-size: 18px; font-weight: 600; color: #1B4332;
  margin-bottom: 4px; line-height: 1.2;
}
.card-location {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; color: #9CA3AF; margin-bottom: 10px;
}
.card-location svg { width: 12px; height: 12px; color: #C9A84C; flex-shrink: 0; }
.card-amenities { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
.amenity-tag {
  font-size: 10px; padding: 3px 8px; border-radius: 50px;
  background: rgba(201,168,76,0.1); color: #C9A84C;
  font-weight: 500;
}
.card-footer {
  display: flex; align-items: center;
  justify-content: space-between;
  margin-top: auto; padding-top: 12px;
  border-top: 1px solid rgba(0,0,0,0.05);
}
.card-price-wrap {}
.card-price-from { font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.06em; }
.card-price-val {
  font-family: 'Cormorant Garamond', serif;
  font-size: 20px; font-weight: 700; color: #1B4332;
}
.card-price-night { font-size: 10px; color: #9CA3AF; }
.card-cta {
  padding: 8px 16px; border-radius: 10px;
  background: linear-gradient(135deg, #C9A84C, #A67C2E);
  color: white; border: none; cursor: pointer;
  font-size: 12px; font-weight: 700;
  font-family: 'Inter', sans-serif;
  transition: all 0.2s;
}
.card-cta:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(201,168,76,0.4); }

.result-list { display: flex; flex-direction: column; gap: 12px; }
.result-list-card {
  background: white; border-radius: 16px;
  border: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 2px 10px rgba(27,67,50,0.05);
  overflow: hidden; cursor: pointer;
  display: flex; transition: all 0.3s;
}
.result-list-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(27,67,50,0.1);
  border-color: rgba(201,168,76,0.2);
}
.list-img { width: 200px; min-width: 200px; height: 150px; object-fit: cover; }
.list-body {
  flex: 1; padding: 16px;
  display: flex; align-items: center; gap: 16px;
}
.list-info { flex: 1; }
.list-right {
  display: flex; flex-direction: column;
  align-items: flex-end; gap: 8px;
  padding: 16px; border-left: 1px solid rgba(0,0,0,0.05);
  min-width: 150px;
}

.skel {
  background: linear-gradient(90deg,
    #f0ebe1 25%, #e8ddd0 50%, #f0ebe1 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.skel-card {
  border-radius: 18px; overflow: hidden;
  background: white;
  border: 1px solid rgba(0,0,0,0.05);
}

.empty-state {
  grid-column: 1/-1; text-align: center;
  padding: 80px 24px;
}
.empty-icon {
  width: 80px; height: 80px; border-radius: 50%;
  background: rgba(201,168,76,0.1);
  display: flex; align-items: center;
  justify-content: center; margin: 0 auto 20px;
}

.error-bar {
  grid-column: 1/-1;
  background: rgba(220,38,38,0.06);
  border: 1px solid rgba(220,38,38,0.2);
  border-radius: 14px; padding: 14px 18px;
  display: flex; align-items: center;
  justify-content: space-between; gap: 16px;
  color: #DC2626; font-size: 14px;
  margin-bottom: 16px;
}
.btn-retry {
  padding: 7px 16px; border: 1px solid rgba(220,38,38,0.3);
  border-radius: 8px; background: white; color: #DC2626;
  font-size: 12px; font-weight: 600; cursor: pointer;
  white-space: nowrap; font-family: 'Inter', sans-serif;
}
</style>
