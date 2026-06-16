<?php
$base = $base_url ?? rtrim(APP_URL, '/');
$pageCss = 'search';
$pageJs  = 'search';
?>

<div class="search-page"
  x-data="searchPage('<?= $base ?>')"
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

