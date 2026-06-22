<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="search-page" x-data="searchPage('<?= $base ?>')" x-init="init()">

<section class="sr-hero">
  <div class="sr-hero-overlay"></div>
  <div class="sr-hero-ghost">Search</div>
  <div class="sr-hero-content">
    <div class="sr-hero-rule"></div>
    <span class="sr-hero-tag">Explorer les destinations</span>
    <h1 class="sr-hero-title">Trouvez votre<br><em>hébergement idéal.</em></h1>
    <div class="sr-search-bar">
      <div class="sr-sb-field">
        <label class="sr-sb-label">Destination</label>
        <input type="text" class="sr-sb-input" placeholder="Abidjan, Grand-Bassam…" x-model="filters.city">
      </div>
      <div class="sr-sb-field">
        <label class="sr-sb-label">Type</label>
        <select class="sr-sb-input sr-sb-select" x-model="filters.type">
          <template x-for="opt in typeOptions" :key="opt.value">
            <option :value="opt.value" x-text="opt.label"></option>
          </template>
        </select>
      </div>
      <div class="sr-sb-field">
        <label class="sr-sb-label">Arrivée</label>
        <input type="date" class="sr-sb-input" x-model="filters.check_in">
      </div>
      <button class="sr-sb-btn" @click="search()">Rechercher</button>
    </div>
  </div>
</section>

<div class="sr-filters-strip">
  <div class="sr-filter-group">
    <span class="sr-filter-label">Type</span>
    <template x-for="opt in typeOptions" :key="opt.value">
      <button class="sr-filter-btn"
              :class="filters.type === opt.value ? 'active' : ''"
              @click="applyType(opt.value)"
              type="button"
              x-text="opt.label">
      </button>
    </template>
  </div>
  <div class="sr-filters-right">
    <span class="sr-result-count" x-text="totalCount + ' établissement' + (totalCount > 1 ? 's' : '') + ' trouvé' + (totalCount > 1 ? 's' : '')"></span>
    <button class="sr-view-btn" :class="viewMode==='grid'?'active':''" @click="viewMode='grid'" title="Vue grille" type="button">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    </button>
    <button class="sr-view-btn" :class="viewMode==='list'?'active':''" @click="viewMode='list'" title="Vue liste" type="button">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
    </button>
  </div>
</div>

<div class="sr-body">

  <!-- SKELETON -->
  <div x-show="loading" :class="viewMode==='grid' ? 'sr-results-grid' : 'sr-results-list'">
    <template x-for="i in 6" :key="i">
      <div class="sr-skeleton">
        <div class="sr-skel-img"></div>
        <div class="sr-skel-body">
          <div class="sr-skel-line" style="width:70%;"></div>
          <div class="sr-skel-line" style="width:45%;"></div>
          <div class="sr-skel-line" style="width:55%;margin-top:6px;"></div>
        </div>
      </div>
    </template>
  </div>

  <!-- ERROR -->
  <div x-show="!loading && error" style="padding:40px;text-align:center;color:#DC2626;" x-text="error"></div>

  <!-- RESULTS GRID -->
  <div x-show="!loading && !error && results.length > 0 && viewMode==='grid'" class="sr-results-grid">
    <template x-for="(p, idx) in results" :key="p.id">
      <div class="sr-card" @click="goProperty(p.id)" style="cursor:pointer;">
        <div class="sr-card-img-wrap">
          <template x-if="photoUrl(p)">
            <img :src="photoUrl(p)" :alt="p.name" style="width:100%;height:100%;object-fit:cover;">
          </template>
          <template x-if="!photoUrl(p)">
            <div style="width:100%;height:100%;background:linear-gradient(135deg,rgba(27,67,50,0.3),rgba(201,168,76,0.15));display:flex;align-items:center;justify-content:center;">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:48px;height:48px;color:rgba(201,168,76,0.3);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
            </div>
          </template>
          <span class="sr-card-badge" :class="isFeatured(idx) ? 'sr-card-badge-featured' : ''"
                x-text="isFeatured(idx) ? 'Coup de ♥' : typeLabel(p.type)"></span>
        </div>
        <div class="sr-card-body">
          <div class="sr-card-name" x-text="p.name"></div>
          <div class="sr-card-loc">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span x-text="p.city + ', Côte d\'Ivoire'"></span>
          </div>
          <div class="sr-card-footer">
            <div class="sr-card-price">
              <span x-text="formatPrice(p.min_price)"></span><small>/nuit</small>
            </div>
            <a :href="base + '/property/' + p.id" class="sr-card-link" @click.stop>Voir →</a>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- RESULTS LIST -->
  <div x-show="!loading && !error && results.length > 0 && viewMode==='list'" class="sr-results-list">
    <template x-for="(p, idx) in results" :key="p.id">
      <div class="sr-card-list" @click="goProperty(p.id)" style="cursor:pointer;">
        <div class="sr-card-img-wrap">
          <template x-if="photoUrl(p)">
            <img :src="photoUrl(p)" :alt="p.name" style="width:100%;height:100%;object-fit:cover;">
          </template>
          <template x-if="!photoUrl(p)">
            <div style="width:100%;height:100%;background:linear-gradient(135deg,rgba(27,67,50,0.3),rgba(201,168,76,0.15));display:flex;align-items:center;justify-content:center;">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:48px;height:48px;color:rgba(201,168,76,0.3);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
            </div>
          </template>
        </div>
        <div class="sr-card-body">
          <div>
            <div class="sr-card-name" x-text="p.name"></div>
            <div class="sr-card-loc">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span x-text="p.city + ', Côte d\'Ivoire'"></span>
            </div>
            <p x-show="p.description" style="font-family:'Inter',sans-serif;font-size:13px;color:rgba(27,67,50,0.55);line-height:1.65;margin-top:8px;" x-text="p.description"></p>
            <p x-show="p.total_rooms" style="font-size:12px;color:rgba(27,67,50,0.4);margin-top:4px;">
              <span x-text="p.total_rooms"></span> chambre<span x-show="p.total_rooms > 1">s</span>
            </p>
          </div>
          <div class="sr-card-footer">
            <div class="sr-card-price">
              <span x-text="formatPrice(p.min_price)"></span><small>/nuit</small>
            </div>
            <a :href="base + '/property/' + p.id" class="sr-card-link" @click.stop>Voir les chambres →</a>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- EMPTY STATE -->
  <div x-show="!loading && !error && results.length === 0" class="sr-empty">
    <div class="sr-empty-icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </div>
    <h3 class="sr-empty-title">Aucun résultat</h3>
    <p class="sr-empty-sub">Essayez de modifier vos critères de recherche ou explorez d'autres destinations.</p>
    <button type="button" @click="clearFilters()" style="padding:12px 28px;background:var(--forest);color:white;border:none;border-radius:999px;font-family:'Inter',sans-serif;font-size:13px;cursor:pointer;margin-top:8px;">Voir tous les établissements</button>
  </div>

</div>

</div>
