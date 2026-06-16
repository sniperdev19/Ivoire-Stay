/* ============================================================
   Ivoire Stay — Page recherche (vitrine/search.php)
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
    filters: { city: '', type: '', check_in: '', check_out: '' },
    results: [],
    destinations: [],
    loading: false,
    error: null,
    totalCount: 0,
    viewMode: 'grid',

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
        if (this.filters.city)      q.set('city',      this.filters.city);
        if (this.filters.type)      q.set('type',      this.filters.type);
        if (this.filters.check_in)  q.set('check_in',  this.filters.check_in);
        if (this.filters.check_out) q.set('check_out', this.filters.check_out);
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

    clearFilters() {
      this.filters = { city: '', type: '', check_in: '', check_out: '' };
      window.history.replaceState({}, '', window.location.pathname);
      this.search();
    },

    photoUrl(r) {
      const fb = 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600';
      const u = r?.photos?.[0]?.url ?? r?.photo ?? r?.cover_photo ?? null;
      if (!u) return fb;
      if (u.startsWith('http')) return u;
      return this.base + '/' + u.replace(/^\/+/, '');
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
      window.location.href = this.base + '/property/' + id;
    },
    isFeatured(idx) { return idx === 0; },
  };
}
