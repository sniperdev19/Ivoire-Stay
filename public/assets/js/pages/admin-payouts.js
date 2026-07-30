/* ============================================================
   Afristay — Admin plateforme : Retraits (src/templates/admin/payouts.php)
   ============================================================ */

function adminPayoutsPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    requests: [],
    filter: 'pending',
    toast: null,

    rejectTarget: null,
    rejectNotes: '',
    rejectError: null,
    rejectSubmitting: false,

    async init() {
      await this.loadRequests();
    },

    async loadRequests() {
      this.loading = true;
      try {
        let url = baseUrl + '/api/payouts?scope=all';
        if (this.filter) url += '&status=' + this.filter;
        const res  = await fetch(url, { headers: this.apiHeaders() });
        const data = await res.json();
        this.requests = data.success ? (data.data ?? []) : [];
      } catch(e) {
        this.requests = [];
      } finally {
        this.loading = false;
      }
    },

    async markPaid(id) {
      if (!confirm('Confirmer que ce retrait a bien été envoyé sur le Mobile Money de l\'établissement ?')) return;
      try {
        const res  = await fetch(baseUrl + '/api/payouts/' + id + '/pay', { method: 'POST', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          await this.loadRequests();
          this.showToast('Retrait marqué comme payé.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch(e) {
        this.showToast('Erreur réseau.', 'error');
      }
    },

    openReject(id) {
      this.rejectTarget = id;
      this.rejectNotes  = '';
      this.rejectError  = null;
    },

    async confirmReject() {
      if (!this.rejectNotes.trim()) { this.rejectError = 'Un motif est requis.'; return; }
      this.rejectSubmitting = true;
      try {
        const res  = await fetch(baseUrl + '/api/payouts/' + this.rejectTarget + '/reject', {
          method: 'POST', headers: this.apiHeaders(),
          body: JSON.stringify({ admin_notes: this.rejectNotes }),
        });
        const data = await res.json();
        if (data.success) {
          this.rejectTarget = null;
          await this.loadRequests();
          this.showToast('Demande rejetée.', 'success');
        } else {
          this.rejectError = data.message ?? 'Erreur.';
        }
      } catch(e) {
        this.rejectError = 'Erreur réseau.';
      } finally {
        this.rejectSubmitting = false;
      }
    },

    statusCfg(s) {
      return {
        pending:  { label: 'En attente', badge: 'badge badge-warning' },
        paid:     { label: 'Payé',       badge: 'badge badge-success' },
        rejected: { label: 'Rejeté',     badge: 'badge badge-danger'  },
      }[s] ?? { label: s, badge: 'badge' };
    },

    operatorLabel(op) {
      return { orange: 'Orange Money', wave: 'Wave', mtn: 'MTN Mobile Money' }[op] ?? op;
    },
  };
}
