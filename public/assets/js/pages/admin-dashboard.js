/* ============================================================
   Afristay — Admin plateforme : Vue d'ensemble (src/templates/admin/dashboard.php)
   ============================================================ */

function adminDashboardPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    overview: null,
    showBookingsTable: false,
    showEstabTable:    false,

    async init() {
      await this.loadOverview();
    },

    async loadOverview() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/overview', { headers: this.apiHeaders() });
        const data = await res.json();
        this.overview = data.success ? data.data : null;
      } catch(e) {
        this.overview = null;
      } finally {
        this.loading = false;
      }
    },

    planLabel(p) {
      return { starter: 'Starter', pro: 'Pro', business: 'Business' }[p] ?? p;
    },

    // Couleurs catégorielles validées (dataviz skill : node scripts/validate_palette.js
    // "#2a78d6,#eb6834,#1baf7a" --mode light --pairs all → ALL CHECKS PASS). Les teintes
    // indigo/bleu déjà utilisées ailleurs pour les badges de plan (#2563EB/#6366F1)
    // échouent ce même contrôle (ΔE 1.9 en vision daltonienne — quasi indiscernables),
    // d'où ce jeu de couleurs dédié pour ce graphique.
    get planRows() {
      const b = this.overview?.plan_breakdown ?? {};
      return [
        { key: 'starter',  label: 'Starter',  value: b.starter  ?? 0, color: '#2a78d6' },
        { key: 'pro',      label: 'Pro',       value: b.pro      ?? 0, color: '#eb6834' },
        { key: 'business', label: 'Business',  value: b.business ?? 0, color: '#1baf7a' },
      ];
    },
    planBarPct(value) {
      const max = Math.max(1, ...this.planRows.map(p => p.value));
      return Math.round((value / max) * 100);
    },

    maxOf(series) {
      if (!series || !series.length) return 0;
      return Math.max(1, ...series.map(s => s.count));
    },
    barHeightPct(count, max) {
      if (!max) return 0;
      return Math.round((count / max) * 100);
    },
    monthLabel(ym) {
      if (!ym) return '';
      const [y, m] = ym.split('-');
      const mois = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
      return mois[parseInt(m, 10) - 1] + ' ' + y.slice(2);
    },
  };
}
