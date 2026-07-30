/* ============================================================
   Afristay — Page SaaS : Paiements (src/templates/saas/payments.php)
   ============================================================ */

function paymentsPage(baseUrl) {
  return {
  ...saasHelpers,

  payments: [], loading: true, error: null, upgradeRequired: false,
  showModal: false, editing: null, submitting: false, formError: null,
  methodFilter: '', statusFilter: '',
  invoices: [],

  form: { booking_id: '', invoice_id: '', amount: '', method: 'mobile_money', type: 'full', notes: '' },

  async init() {
    if (planUpgradeRequired('payments')) { this.upgradeRequired = true; this.loading = false; return; }
    await Promise.all([this.loadPayments(), this.loadInvoices()]);
  },

  async loadInvoices() {
    try {
      const res  = await fetch(baseUrl + '/api/invoices?establishment_id=' + this.estId() + '&per_page=200', { headers: this.apiHeaders() });
      const data = await res.json();
      this.invoices = data.success ? (data.data?.invoices ?? data.data ?? []) : [];
    } catch(e) { this.invoices = []; }
  },

  async loadPayments() {
    this.loading = true; this.error = null;
    try {
      const url = baseUrl + '/api/payments?establishment_id=' + this.estId()
        + (this.methodFilter ? '&method=' + this.methodFilter : '')
        + (this.statusFilter ? '&status=' + this.statusFilter : '');
      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.upgrade_required) { this.upgradeRequired = true; return; }
      if (data.success) {
        this.payments = data.data?.payments ?? data.data ?? [];
      } else {
        this.payments = [];
        this.error = data.message ?? 'Impossible de charger les paiements.';
      }
    } catch(e) {
      this.payments = [];
      this.error = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
      this.loading = false;
    }
  },

  openCreate() {
    this.editing = null;
    this.form = { booking_id: '', invoice_id: '', amount: '', method: 'mobile_money', type: 'full', notes: '' };
    this.formError = null;
    this.showModal = true;
  },

  openEdit(p) {
    this.editing = p;
    this.form = { booking_id: p.booking_id, invoice_id: p.invoice_id, amount: p.amount, method: p.method, type: p.type, notes: p.notes ?? '' };
    this.formError = null;
    this.showModal = true;
  },

  // Quand on choisit une facture, remplir auto le booking_id et le montant restant
  onInvoiceChange() {
    const inv = this.invoices.find(i => i.id == this.form.invoice_id);
    if (inv) {
      this.form.booking_id = inv.booking_id;
      this.form.amount     = inv.amount_ttc ?? inv.amount_ht ?? '';
    }
  },

  async savePayment() {
    if (!this.form.amount || this.form.amount <= 0) return this.formError = 'Montant requis.';
    if (!this.editing && !this.form.invoice_id)     return this.formError = 'Veuillez sélectionner une facture.';
    if (!this.editing && !this.form.booking_id)     return this.formError = 'Réservation introuvable. Sélectionnez d\'abord une facture.';
    this.submitting = true; this.formError = null;
    try {
      const url = this.editing ? baseUrl + '/api/payments/' + this.editing.id : baseUrl + '/api/payments';
      let payload;
      if (this.editing) {
        payload = { amount: this.form.amount, method: this.form.method, type: this.form.type };
      } else {
        payload = {
          booking_id: this.form.booking_id,
          invoice_id: this.form.invoice_id,
          amount:     this.form.amount,
          method:     this.form.method,
          type:       this.form.type,
          notes:      this.form.notes,
        };
      }
      const res  = await fetch(url, {
        method: this.editing ? 'PUT' : 'POST',
        headers: this.apiHeaders(),
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        this.showModal = false;
        await this.loadPayments();
      } else {
        this.formError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.formError = 'Erreur réseau.';
    } finally {
      this.submitting = false;
    }
  },

  methodLabel(m) { return { mobile_money:'Mobile Money', cash:'Espèces', card:'Carte', bank_transfer:'Virement' }[m] ?? m; },
  methodIcon(m)  { return { mobile_money:'#16a34a', cash:'#D97706', card:'#2563EB', bank_transfer:'#7C3AED' }[m] ?? '#9CA3AF'; },
  typeLabel(t)   { return { full:'Paiement complet', deposit:'Acompte', partial:'Partiel' }[t] ?? t; },
  statusCfg(s) {
    return {
      completed: { label: 'Complété',   badge: 'badge badge-success' },
      pending:   { label: 'En attente', badge: 'badge badge-warning' },
      refunded:  { label: 'Remboursé',  badge: 'badge badge-info' },
    }[s] ?? { label: s, badge: 'badge' };
  },
  };
}
