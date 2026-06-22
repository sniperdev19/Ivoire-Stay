/* ============================================================
   Ivoire Stay — Comptabilité : Factures + Paiements (billing.php)
   ============================================================ */

function billingPage(baseUrl, defaultTab) {
  return {
  ...saasHelpers,

  // ── état global ────────────────────────────────────────────
  activeTab: defaultTab || 'invoices',
  loading: true,
  error: null,
  upgradeRequired: false,

  // ── factures ───────────────────────────────────────────────
  invoices: [],
  invSearch: '', invStatusFilter: '',
  invPage: 1, invPerPage: 20, invTotal: 0,

  // ── paiements ─────────────────────────────────────────────
  payments: [],
  payMethodFilter: '', payStatusFilter: '',

  // ── modal partagé ─────────────────────────────────────────
  showModal: false,
  modalType: '',          // 'invoice' | 'payment'
  editing: null,
  submitting: false,
  formError: null,
  bookingsWithoutInvoice: [],

  invForm: { booking_id: '', tax_rate: 0, amount_ht: '', amount_ttc: '', status: 'draft' },
  payForm: { booking_id: '', invoice_id: '', amount: '', method: 'mobile_money', type: 'full', notes: '' },

  // ── init ──────────────────────────────────────────────────
  async init() {
    if (planUpgradeRequired('invoices') || planUpgradeRequired('payments')) {
      this.upgradeRequired = true; this.loading = false; return;
    }
    await this.loadAll();
  },

  async loadAll() {
    this.loading = true; this.error = null;
    try {
      await Promise.all([this.loadInvoices(), this.loadPayments()]);
    } finally {
      this.loading = false;
    }
  },

  // ── Factures ──────────────────────────────────────────────
  async loadInvoices() {
    try {
      const url = baseUrl + '/api/invoices?establishment_id=' + this.estId()
        + '&page=' + this.invPage + '&per_page=' + this.invPerPage
        + (this.invSearch       ? '&search=' + encodeURIComponent(this.invSearch) : '')
        + (this.invStatusFilter ? '&status=' + this.invStatusFilter : '');
      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.upgrade_required) { this.upgradeRequired = true; return; }
      if (data.success) {
        this.invoices  = data.data?.invoices ?? data.data ?? [];
        this.invTotal  = data.data?.total ?? this.invoices.length;
      } else {
        this.invoices = [];
        this.error = data.message ?? 'Impossible de charger les factures.';
      }
    } catch(e) { this.invoices = []; this.error = 'Erreur réseau.'; }
  },

  async openCreateInvoice() {
    this.editing  = null;
    this.modalType = 'invoice';
    this.invForm  = { booking_id: '', tax_rate: 0, amount_ht: '', amount_ttc: '', status: 'draft' };
    this.formError = null;
    try {
      const res  = await fetch(baseUrl + '/api/bookings?establishment_id=' + this.estId() + '&per_page=100', { headers: this.apiHeaders() });
      const data = await res.json();
      const all  = data.success ? (data.data?.bookings ?? data.data?.data ?? data.data ?? []) : [];
      const ids  = this.invoices.map(i => i.booking_id);
      this.bookingsWithoutInvoice = all.filter(b => !ids.includes(b.id));
    } catch(e) { this.bookingsWithoutInvoice = []; }
    this.showModal = true;
  },

  openEditInvoice(inv) {
    this.editing  = inv;
    this.modalType = 'invoice';
    this.invForm  = {
      booking_id: inv.booking_id ?? '',
      amount_ht:  inv.amount_ht  ?? '',
      amount_ttc: inv.amount_ttc ?? '',
      tax_rate:   inv.tax_rate   ?? 0,
      status:     inv.status     ?? 'draft',
    };
    this.formError = null;
    this.bookingsWithoutInvoice = [];
    this.showModal = true;
  },

  get invAmountTtc() {
    const ht  = parseFloat(this.invForm.amount_ht) || 0;
    const tax = parseFloat(this.invForm.tax_rate)  || 0;
    return Math.round(ht * (1 + tax / 100));
  },

  async saveInvoice() {
    this.submitting = true; this.formError = null;
    try {
      let url, method, payload;
      if (this.editing) {
        url    = baseUrl + '/api/invoices/' + this.editing.id;
        method = 'PUT';
        payload = { status: this.invForm.status, tax_rate: this.invForm.tax_rate, amount_ht: this.invForm.amount_ht, amount_ttc: this.invForm.amount_ttc };
      } else {
        if (!this.invForm.booking_id) { this.formError = 'Veuillez sélectionner une réservation.'; return; }
        url    = baseUrl + '/api/invoices';
        method = 'POST';
        payload = { booking_id: this.invForm.booking_id, tax_rate: this.invForm.tax_rate, amount: this.invForm.amount_ht || undefined };
      }
      const res  = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(payload) });
      const data = await res.json();
      if (data.success) { this.showModal = false; await this.loadInvoices(); }
      else this.formError = data.message ?? 'Erreur.';
    } catch(e) { this.formError = 'Erreur réseau.'; }
    finally { this.submitting = false; }
  },

  async downloadPdf(inv) {
    const invoiceNum = inv.invoice_number ?? ('facture-' + inv.id);
    try {
      const res = await fetch(baseUrl + '/api/invoices/' + inv.id + '/pdf', {
        headers: this.apiHeaders(),
      });
      if (!res.ok) { alert('Erreur lors de la génération du PDF.'); return; }
      const blob = await res.blob();
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href     = url;
      a.download = invoiceNum + '.pdf';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch(e) { alert('Erreur réseau lors du téléchargement.'); }
  },

  // ── Envoi facture par email ────────────────────────────────────────────
  sendEmailTarget: null,   // { id, email, name }
  sendEmailInput: '',
  sendEmailLoading: false,
  sendEmailError: null,
  sendEmailSuccess: false,

  openSendEmail(inv) {
    this.sendEmailTarget  = inv;
    this.sendEmailInput   = inv.client_email ?? '';
    this.sendEmailError   = null;
    this.sendEmailSuccess = false;
    this.sendEmailLoading = false;
    this.modalType        = 'send_email';
    this.showModal        = true;
  },

  async confirmSendEmail() {
    const email = (this.sendEmailInput || '').trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      this.sendEmailError = 'Adresse email invalide.'; return;
    }
    this.sendEmailLoading = true; this.sendEmailError = null;
    try {
      const res  = await fetch(baseUrl + '/api/invoices/' + this.sendEmailTarget.id + '/send', {
        method: 'POST',
        headers: this.apiHeaders(),
        body: JSON.stringify({ email }),
      });
      const data = await res.json();
      if (data.success) {
        this.sendEmailSuccess = true;
        await this.loadInvoices();
        setTimeout(() => { this.showModal = false; }, 1800);
      } else {
        this.sendEmailError = data.message ?? 'Erreur envoi.';
      }
    } catch(e) { this.sendEmailError = 'Erreur réseau.'; }
    finally { this.sendEmailLoading = false; }
  },

  get invTotalPages() { return Math.max(1, Math.ceil(this.invTotal / this.invPerPage)); },
  async invGoToPage(p) {
    if (p < 1 || p > this.invTotalPages) return;
    this.invPage = p; await this.loadInvoices();
  },

  invStatusCfg(s) {
    return {
      draft: { label: 'Brouillon', badge: 'badge badge-info' },
      sent:  { label: 'Envoyée',   badge: 'badge badge-warning' },
      paid:  { label: 'Payée',     badge: 'badge badge-success' },
    }[s] ?? { label: s ?? '—', badge: 'badge' };
  },

  // ── Paiements ─────────────────────────────────────────────
  async loadPayments() {
    try {
      const url = baseUrl + '/api/payments?establishment_id=' + this.estId()
        + (this.payMethodFilter ? '&method=' + this.payMethodFilter : '')
        + (this.payStatusFilter ? '&status=' + this.payStatusFilter : '');
      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.upgrade_required) { this.upgradeRequired = true; return; }
      if (data.success) {
        this.payments = data.data?.payments ?? data.data ?? [];
      } else {
        this.payments = [];
        this.error = data.message ?? 'Impossible de charger les paiements.';
      }
    } catch(e) { this.payments = []; this.error = 'Erreur réseau.'; }
  },

  openCreatePayment() {
    this.editing  = null;
    this.modalType = 'payment';
    this.payForm  = { booking_id: '', invoice_id: '', amount: '', method: 'mobile_money', type: 'full', notes: '' };
    this.formError = null;
    this.showModal = true;
  },

  openEditPayment(p) {
    this.editing  = p;
    this.modalType = 'payment';
    this.payForm  = { booking_id: p.booking_id, invoice_id: p.invoice_id, amount: p.amount, method: p.method, type: p.type, notes: p.notes ?? '' };
    this.formError = null;
    this.showModal = true;
  },

  onInvoiceChange() {
    const inv = this.invoices.find(i => i.id == this.payForm.invoice_id);
    if (inv) {
      this.payForm.booking_id = inv.booking_id;
      const remaining = (parseFloat(inv.amount_ttc) || 0) - (parseFloat(inv.paid_amount) || 0);
      this.payForm.amount = Math.max(0, remaining);
    }
  },

  get payRemaining() {
    const inv = this.invoices.find(i => i.id == this.payForm.invoice_id);
    if (!inv) return null;
    const ttc      = parseFloat(inv.amount_ttc)      || 0;
    const paid     = parseFloat(inv.paid_amount)     || 0;
    const entered  = parseFloat(this.payForm.amount) || 0;
    return {
      ttc,
      paid,
      remaining:  Math.max(0, ttc - paid),
      afterThis:  Math.max(0, ttc - paid - entered),
    };
  },

  async savePayment() {
    if (!this.payForm.amount || this.payForm.amount <= 0) { this.formError = 'Montant requis.'; return; }
    if (!this.editing && !this.payForm.invoice_id) { this.formError = 'Veuillez sélectionner une facture.'; return; }
    if (!this.editing && !this.payForm.booking_id) { this.formError = 'Réservation introuvable.'; return; }
    this.submitting = true; this.formError = null;
    try {
      const url     = this.editing ? baseUrl + '/api/payments/' + this.editing.id : baseUrl + '/api/payments';
      const payload = this.editing
        ? { amount: this.payForm.amount, method: this.payForm.method, type: this.payForm.type }
        : { booking_id: this.payForm.booking_id, invoice_id: this.payForm.invoice_id, amount: this.payForm.amount, method: this.payForm.method, type: this.payForm.type, notes: this.payForm.notes };
      const res  = await fetch(url, { method: this.editing ? 'PUT' : 'POST', headers: this.apiHeaders(), body: JSON.stringify(payload) });
      const data = await res.json();
      if (data.success) {
        this.showModal = false;
        await Promise.all([this.loadPayments(), this.loadInvoices()]);
      } else this.formError = data.message ?? 'Erreur.';
    } catch(e) { this.formError = 'Erreur réseau.'; }
    finally { this.submitting = false; }
  },

  methodLabel(m) { return { mobile_money:'Mobile Money', cash:'Espèces', card:'Carte', bank_transfer:'Virement' }[m] ?? m ?? '—'; },
  methodColor(m) { return { mobile_money:'#16a34a', cash:'#D97706', card:'#2563EB', bank_transfer:'#7C3AED' }[m] ?? '#9CA3AF'; },
  typeLabel(t)   { return { full:'Complet', deposit:'Acompte', partial:'Partiel' }[t] ?? t ?? '—'; },

  payStatusCfg(s) {
    return {
      completed: { label: 'Complété',   badge: 'badge badge-success' },
      pending:   { label: 'En attente', badge: 'badge badge-warning' },
      refunded:  { label: 'Remboursé',  badge: 'badge badge-info' },
    }[s] ?? { label: s ?? '—', badge: 'badge' };
  },

  // ── KPI calculés ──────────────────────────────────────────
  get kpi() {
    const totalTtc   = this.invoices.reduce((s, i) => s + (i.amount_ttc || 0), 0);
    const paidInv    = this.invoices.filter(i => i.status === 'paid').length;
    const pendingInv = this.invoices.filter(i => i.status !== 'paid' && i.status !== 'cancelled').length;
    const encaisse   = this.payments.filter(p => p.status === 'completed').reduce((s, p) => s + (p.amount || 0), 0);
    const enAttente  = this.payments.filter(p => p.status === 'pending').reduce((s, p) => s + (p.amount || 0), 0);
    return { totalTtc, paidInv, pendingInv, encaisse, enAttente };
  },
  };
}
