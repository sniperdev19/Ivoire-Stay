/* ============================================================
   Afristay — Admin plateforme : Vue d'ensemble (src/templates/admin/dashboard.php)
   ============================================================ */

function adminDashboardPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    overview: null,

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
  };
}
