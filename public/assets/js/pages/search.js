/* ============================================================
   Afristay — Page recherche (vitrine/search.php)
   Filtres, appel API /public/search, rendu grille/liste.
   ============================================================ */

/**
 * Composant Alpine de la page de recherche.
 * @param {string} base  Préfixe d'URL de l'app (APP_URL).
 * Utilisé via x-data="searchPage('<?= $base ?>')".
 */
function searchPage(base) {
  base = (base || '').replace(/\/$/, '');
  return {
    base: base,
    filters: { city: '', type: '' },
    results: [],
    destinations: [],
    loading: false,
    error: null,
    totalCount: 0,
    viewMode: 'grid',
    typeOptions: [
      { value: '',            label: 'Tous',        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>' },
      { value: 'hotel',       label: 'Hôtel',       icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-4a1 1 0 011-1h4a1 1 0 011 1v4"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1"/></svg>' },
      { value: 'residence',   label: 'Résidence',   icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-4a1 1 0 011-1h4a1 1 0 011 1v4"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1"/></svg>' },
      { value: 'villa',       label: 'Villa',       icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg>' },
      { value: 'appartement', label: 'Appartement', icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-4a1 1 0 011-1h4a1 1 0 011 1v4"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1"/></svg>' },
    ],

    // Filtre "établissements proches" — géolocalisation navigateur, distance
    // calculée côté client (les résultats de l'API incluent déjà latitude/longitude).
    geoActive: false,
    geoLoading: false,
    geoError: null,
    userLat: null,
    userLng: null,
    maxDistanceKm: 1,

    get displayedResults() {
      if (!this.geoActive) return this.results;
      return this.results
        .map(p => ({ ...p, _distanceKm: this.distanceKm(p) }))
        .filter(p => p._distanceKm !== null && p._distanceKm <= this.maxDistanceKm)
        .sort((a, b) => a._distanceKm - b._distanceKm);
    },

    toggleNearby() {
      if (this.geoActive) {
        this.geoActive = false;
        this.userLat = null;
        this.userLng = null;
        this.geoError = null;
        return;
      }
      if (!navigator.geolocation) {
        this.geoError = 'Géolocalisation non disponible sur cet appareil.';
        return;
      }
      this.geoLoading = true;
      this.geoError = null;
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          this.userLat = pos.coords.latitude;
          this.userLng = pos.coords.longitude;
          this.geoActive = true;
          this.geoLoading = false;
        },
        () => {
          this.geoError = 'Localisation refusée ou indisponible.';
          this.geoLoading = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
      );
    },

    distanceKm(p) {
      if (p.latitude == null || p.longitude == null) return null;
      const toRad = (d) => d * Math.PI / 180;
      const R = 6371;
      const dLat = toRad(p.latitude - this.userLat);
      const dLon = toRad(p.longitude - this.userLng);
      const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(this.userLat)) * Math.cos(toRad(p.latitude)) * Math.sin(dLon / 2) ** 2;
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    },

    async publicFetch(path) {
      const url = path.startsWith('http') ? path : this.base + path;
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const txt = await res.text();
      if (!txt) return null;
      return JSON.parse(txt);
    },

    async init() {
      const p = new URLSearchParams(window.location.search);
      if (p.get('city')) this.filters.city = p.get('city');
      if (p.get('type')) this.filters.type = p.get('type');
      await this.loadDestinations();
      await this.search();
    },

    async loadDestinations() {
      try {
        const d = await this.publicFetch('/api/public/destinations');
        if (d?.success) {
          this.destinations = d.data?.destinations ?? d.data ?? [];
        }
        if (!this.destinations.length) this.setFallbackDest();
      } catch (e) {
        this.setFallbackDest();
      }
    },
    setFallbackDest() {
      this.destinations = [
        { city: 'Abidjan',      count: 142 },
        { city: 'Yamoussoukro', count: 38 },
        { city: 'Grand-Bassam', count: 27 },
        { city: 'Assinie',      count: 31 },
        { city: 'San-Pédro',    count: 19 },
        { city: 'Bouaké',       count: 11 },
      ];
    },

    async search() {
      this.error = null;
      this.loading = true;
      this.results = [];
      this.totalCount = 0;
      try {
        const q = new URLSearchParams();
        if (this.filters.city) q.set('city', this.filters.city);
        if (this.filters.type) q.set('type', this.filters.type);
        const qs = q.toString();
        window.history.replaceState({}, '', qs ? ('?' + qs) : window.location.pathname);

        const data = await this.publicFetch('/api/public/search?' + qs);
        if (!data?.success) throw new Error(data?.message || 'Erreur API');

        const payload = data.data ?? {};
        let items = [];
        if (Array.isArray(payload.rooms)) items = payload.rooms;
        else if (Array.isArray(payload.establishments)) items = payload.establishments;
        else if (Array.isArray(payload.results)) items = payload.results;
        else if (Array.isArray(payload)) items = payload;

        this.results = items;
        this.totalCount = payload.total ?? items.length;
      } catch (e) {
        this.error = e?.message || 'Impossible de charger les résultats.';
      } finally {
        this.loading = false;
      }
    },

    applyType(value) {
      this.filters.type = value;
      this.search();
    },

    clearFilters() {
      this.filters = { city: '', type: '' };
      this.geoActive = false;
      this.userLat = null;
      this.userLng = null;
      this.geoError = null;
      window.history.replaceState({}, '', window.location.pathname);
      this.search();
    },

    photoUrl(r) {
      const u = r?.cover_photo ?? null;
      if (!u) return null;
      if (u.startsWith('http')) return u;
      const clean = u.replace(/^\/+/, '');
      return this.base + '/' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
    },

    formatPrice(p) {
      if (p == null || p === '') return '—';
      return new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
    },

    typeLabel(t) {
      return { hotel: 'Hôtel', residence: 'Résidence', villa: 'Villa', appartement: 'Appartement' }[t] ?? (t ?? '');
    },

    goProperty(id) {
      window.location.href = this.base + '/property/' + id;
    },

    isFeatured(p) { return !!p.is_boosted_effective; },
  };
}

