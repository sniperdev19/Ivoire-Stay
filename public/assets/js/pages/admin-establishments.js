/* ============================================================
   Afristay — Admin plateforme : Établissements (src/templates/admin/establishments.php)
   ============================================================ */

function adminEstablishmentsPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    establishments: [],
    search: '',
    savingId: null,
    toast: null,

    async init() {
      await this.loadEstablishments();
    },

    async loadEstablishments() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/establishments', { headers: this.apiHeaders() });
        const data = await res.json();
        this.establishments = data.success ? (data.data ?? []) : [];
      } catch(e) {
        this.establishments = [];
      } finally {
        this.loading = false;
      }
    },

    get filteredEstablishments() {
      const q = (this.search || '').trim().toLowerCase();
      if (!q) return this.establishments;
      return this.establishments.filter(e =>
        `${e.name} ${e.city ?? ''} ${e.owner_name ?? ''}`.toLowerCase().includes(q)
      );
    },

    async toggleActive(estab) {
      const next  = estab.is_active ? 0 : 1;
      const label = next ? 'réactiver' : 'désactiver';
      if (!confirm(`Confirmer : ${label} « ${estab.name} » ?`)) return;

      this.savingId = estab.id;
      try {
        const res  = await fetch(baseUrl + '/api/establishments/' + estab.id, {
          method: 'PUT', headers: this.apiHeaders(), body: JSON.stringify({ is_active: next }),
        });
        const data = await res.json();
        if (data.success) {
          estab.is_active = next;
          this.showToast(next ? 'Établissement réactivé.' : 'Établissement désactivé.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch(e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.savingId = null;
      }
    },

    async changePlan(estab, newPlan) {
      const previous = estab.plan;
      if (newPlan === previous) return;

      this.savingId = estab.id;
      try {
        const res  = await fetch(baseUrl + '/api/establishments/' + estab.id, {
          method: 'PUT', headers: this.apiHeaders(), body: JSON.stringify({ plan: newPlan }),
        });
        const data = await res.json();
        if (data.success) {
          estab.plan = newPlan;
          this.showToast('Plan mis à jour.', 'success');
        } else {
          estab.plan = previous;
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch(e) {
        estab.plan = previous;
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.savingId = null;
      }
    },

    planLabel(p) { return { starter: 'Starter', pro: 'Pro', business: 'Business' }[p] ?? p; },
  };
}
