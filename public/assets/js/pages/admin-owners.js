/* ============================================================
   Afristay — Admin plateforme : Propriétaires (src/templates/admin/owners.php)
   ============================================================ */

function adminOwnersPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    owners: [],
    search: '',

    async init() {
      await this.loadOwners();
    },

    async loadOwners() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/owners', { headers: this.apiHeaders() });
        const data = await res.json();
        this.owners = data.success ? (data.data ?? []) : [];
      } catch(e) {
        this.owners = [];
      } finally {
        this.loading = false;
      }
    },

    get filteredOwners() {
      const q = (this.search || '').trim().toLowerCase();
      if (!q) return this.owners;
      return this.owners.filter(o => `${o.name} ${o.email}`.toLowerCase().includes(q));
    },

    initials(name) {
      const parts = (name || '').trim().split(/\s+/).filter(Boolean);
      return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase();
    },
  };
}
