/* ============================================================
   Afristay — Page SaaS : Factures (src/templates/saas/invoices.php)
   ============================================================ */

function invoicesPage(baseUrl) {
  return {
  ...saasHelpers,

  invoices: [], loading: true, error: null, upgradeRequired: false,
  page: 1, perPage: 15, total: 0,
  search: '', statusFilter: '',
  showModal: false, editing: null,
  submitting: false, formError: null,
  bookingsWithoutInvoice: [],

  form: { booking_id: '', tax_rate: 18, notes: '', status: 'draft', amount_ht: '', amount_ttc: '' },

  async init() {
    if (planUpgradeRequired('invoices')) { this.upgradeRequired = true; this.loading = false; return; }
    await this.loadInvoices();
  },

  async loadInvoices() {
    this.loading = true; this.error = null;
    try {
      const url = baseUrl + '/api/invoices?establishment_id=' + this.estId()
        + '&page=' + this.page
        + '&per_page=' + this.perPage
        + (this.search       ? '&search=' + encodeURIComponent(this.search) : '')
        + (this.statusFilter ? '&status=' + this.statusFilter : '');
      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.upgrade_required) { this.upgradeRequired = true; return; }
      if (data.success) {
        this.invoices = data.data?.invoices ?? data.data ?? [];
        this.total    = data.data?.total ?? this.invoices.length;
      } else {
        this.invoices = [];
        this.total = 0;
        this.error = data.message ?? 'Impossible de charger les factures.';
      }
    } catch(e) {
      this.invoices = [];
      this.total = 0;
      this.error = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
      this.loading = false;
    }
  },

  async openCreate() {
    this.editing = null;
    this.form = { booking_id: '', tax_rate: 18, notes: '', status: 'draft', amount_ht: '', amount_ttc: '' };
    this.formError = null;
    // Charger les réservations sans facture
    try {
      const res  = await fetch(baseUrl + '/api/bookings?establishment_id=' + this.estId() + '&per_page=100', { headers: this.apiHeaders() });
      const data = await res.json();
      const allBookings = data.success ? (data.data?.bookings ?? data.data?.data ?? data.data ?? []) : [];
      const invoicedIds = this.invoices.map(i => i.booking_id);
      this.bookingsWithoutInvoice = allBookings.filter(b => !invoicedIds.includes(b.id));
    } catch(e) {
      this.bookingsWithoutInvoice = [];
    }
    this.showModal = true;
  },
  openEdit(inv) {
    this.editing = inv;
    this.form = {
      booking_id:  inv.booking_id ?? '',
      amount_ht:   inv.amount_ht  ?? '',
      amount_ttc:  inv.amount_ttc ?? '',
      tax_rate:    inv.tax_rate   ?? 18,
      notes:       inv.notes      ?? '',
      status:      inv.status     ?? 'draft',
    };
    this.formError = null;
    this.bookingsWithoutInvoice = [];
    this.showModal = true;
  },

  get amountTtc() {
    const ht  = parseFloat(this.form.amount_ht) || 0;
    const tax = parseFloat(this.form.tax_rate)  || 0;
    return Math.round(ht * (1 + tax / 100));
  },

  async saveInvoice() {
    this.submitting = true; this.formError = null;
    try {
      let url, method, payload;
      if (this.editing) {
        // Mise à jour : statut, taux TVA, montants
        url    = baseUrl + '/api/invoices/' + this.editing.id;
        method = 'PUT';
        payload = { status: this.form.status, tax_rate: this.form.tax_rate, amount_ht: this.form.amount_ht, amount_ttc: this.form.amount_ttc };
      } else {
        // Création : booking_id requis
        if (!this.form.booking_id) return (this.formError = 'Veuillez sélectionner une réservation.', this.submitting = false);
        url    = baseUrl + '/api/invoices';
        method = 'POST';
        payload = { booking_id: this.form.booking_id, tax_rate: this.form.tax_rate, amount: this.form.amount_ht || undefined };
      }
      const res  = await fetch(url, {
        method, headers: this.apiHeaders(),
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        this.showModal = false;
        await this.loadInvoices();
      } else {
        this.formError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.formError = 'Erreur réseau.';
    } finally {
      this.submitting = false;
    }
  },

  downloadPdf(id) {
    window.open(baseUrl + '/api/invoices/' + id + '/pdf?token=' + localStorage.getItem('token'), '_blank');
  },

  get totalPages() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
  async goToPage(p) {
    if (p < 1 || p > this.totalPages) return;
    this.page = p;
    await this.loadInvoices();
  },

  statusCfg(s) {
    return {
      draft: { label: 'Brouillon', badge: 'badge badge-info' },
      sent:  { label: 'Envoyée',   badge: 'badge badge-warning' },
      paid:  { label: 'Payée',     badge: 'badge badge-success' },
    }[s] ?? { label: s, badge: 'badge' };
  },
  };
}
