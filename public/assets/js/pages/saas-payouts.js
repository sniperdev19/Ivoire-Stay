/* ============================================================
   Afristay — Page SaaS : Retraits (src/templates/saas/payouts.php)
   Vue hôtelier uniquement — solde de son établissement, demandes de retrait
   et historique. La vue « toutes les demandes » (superadmin) vit désormais
   dans l'espace admin plateforme (src/templates/admin/payouts.php).
   ============================================================ */

function payoutsPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    upgradeRequired: false,
    balance: null,
    requests: [],
    toast: null,

    showModal: false,
    form: { amount: '', mobile_money_operator: 'orange', mobile_money_number: '' },
    formError: null,
    submitting: false,

    async init() {
      await this.loadBalance();
      await this.loadRequests();
    },

    async loadBalance() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/payouts/balance?establishment_id=' + this.estId(), { headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          this.balance = data.data;
        } else if (data.upgrade_required) {
          this.upgradeRequired = true;
        }
      } catch(e) {
        // silencieux : le solde reste vide, la page affiche les zéros par défaut
      } finally {
        this.loading = false;
      }
    },

    async loadRequests() {
      try {
        const res  = await fetch(baseUrl + '/api/payouts?establishment_id=' + this.estId(), { headers: this.apiHeaders() });
        const data = await res.json();
        this.requests = data.success ? (data.data ?? []) : [];
      } catch(e) {
        this.requests = [];
      }
    },

    openModal() {
      this.form = { amount: '', mobile_money_operator: 'orange', mobile_money_number: '' };
      this.formError = null;
      this.showModal = true;
    },

    async submitRequest() {
      this.formError = null;
      const amount = Number(this.form.amount) || 0;
      if (amount <= 0) { this.formError = 'Montant invalide.'; return; }
      if (this.balance && amount > this.balance.available_balance) {
        this.formError = 'Montant supérieur au solde disponible.';
        return;
      }
      if (!this.form.mobile_money_number.trim()) { this.formError = 'Numéro Mobile Money requis.'; return; }

      this.submitting = true;
      try {
        const res  = await fetch(baseUrl + '/api/payouts', {
          method: 'POST', headers: this.apiHeaders(),
          body: JSON.stringify({
            establishment_id:       this.estId(),
            amount,
            mobile_money_operator:  this.form.mobile_money_operator,
            mobile_money_number:    this.form.mobile_money_number.trim(),
          }),
        });
        const data = await res.json();
        if (data.success) {
          this.showModal = false;
          await this.loadBalance();
          await this.loadRequests();
          this.showToast('Demande de retrait envoyée.', 'success');
        } else {
          this.formError = data.message ?? 'Erreur lors de la demande.';
        }
      } catch(e) {
        this.formError = 'Erreur réseau.';
      } finally {
        this.submitting = false;
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
