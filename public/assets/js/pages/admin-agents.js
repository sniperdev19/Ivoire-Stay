/* ============================================================
   Afristay — Admin plateforme : Agents commerciaux (src/templates/admin/agents.php)
   Fonctionnalité temporaire, cf. AGENTS_ENABLED dans config/config.php.
   ============================================================ */

function adminAgentsPage(baseUrl) {
  return {
    ...saasHelpers,

    loadingAgents: true,
    agents: [],

    loadingPayouts: true,
    payouts: [],
    filter: 'pending',
    toast: null,

    loadingBonuses: true,
    bonuses: [],

    rejectTarget: null,
    rejectNotes: '',
    rejectError: null,
    rejectSubmitting: false,

    async init() {
      await Promise.all([this.loadAgents(), this.loadPayouts(), this.loadBonuses()]);
    },

    async loadAgents() {
      this.loadingAgents = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/agents', { headers: this.apiHeaders() });
        const data = await res.json();
        this.agents = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.agents = [];
      } finally {
        this.loadingAgents = false;
      }
    },

    async loadPayouts() {
      this.loadingPayouts = true;
      try {
        let url = baseUrl + '/api/admin/agent-payouts';
        if (this.filter) url += '?status=' + this.filter;
        const res  = await fetch(url, { headers: this.apiHeaders() });
        const data = await res.json();
        this.payouts = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.payouts = [];
      } finally {
        this.loadingPayouts = false;
      }
    },

    async loadBonuses() {
      this.loadingBonuses = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/agent-bonuses', { headers: this.apiHeaders() });
        const data = await res.json();
        this.bonuses = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.bonuses = [];
      } finally {
        this.loadingBonuses = false;
      }
    },

    bonusTypeLabel(type) {
      return {
        first_to_5:      '🏁 Premier arrivé (5 établissements)',
        first_business:  '💎 Premier client Business',
        fast_conversion: '⚡ Conversion rapide',
        monthly_top:     '🏆 Top agent du mois',
      }[type] ?? type;
    },

    async markPaid(id) {
      if (!confirm("Confirmer que ce versement a bien été envoyé sur le Mobile Money de l'agent ?")) return;
      try {
        const res  = await fetch(baseUrl + '/api/admin/agent-payouts/' + id + '/pay', { method: 'POST', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          await this.loadPayouts();
          this.showToast('Versement marqué comme payé.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch (e) {
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
        const res  = await fetch(baseUrl + '/api/admin/agent-payouts/' + this.rejectTarget + '/reject', {
          method: 'POST', headers: this.apiHeaders(),
          body: JSON.stringify({ admin_notes: this.rejectNotes }),
        });
        const data = await res.json();
        if (data.success) {
          this.rejectTarget = null;
          await this.loadPayouts();
          this.showToast('Versement rejeté.', 'success');
        } else {
          this.rejectError = data.message ?? 'Erreur.';
        }
      } catch (e) {
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
      return { orange: 'Orange Money', mtn: 'MTN Money', moov: 'Moov Money', wave: 'Wave' }[op] ?? op;
    },
  };
}
