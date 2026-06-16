/* ============================================================
   Ivoire Stay — Page SaaS : Factures (src/templates/saas/invoices.php)
   ============================================================ */

function invoicesPage(baseUrl) {
  return {
  invoices: [], loading: true, error: null,
  page: 1, perPage: 15, total: 0,
  search: '', statusFilter: '',
  showModal: false, editing: null,
  submitting: false, formError: null,
  rooms: [],

  form: { booking_id:'', room_id:'', client_name:'', client_email:'', amount_ht:'', tax_rate:18, notes:'', status:'draft' },

  fallbackInvoices: [
    { id:1, invoice_number:'INV-2026-001', client_name:'Kouamé Adou', client_email:'k@test.ci', amount_ht:84746, tax_rate:18, amount_ttc:100000, status:'paid', created_at:'2026-06-10', booking_id:1 },
    { id:2, invoice_number:'INV-2026-002', client_name:'Fatou Diallo', client_email:'f@test.ci', amount_ht:42373, tax_rate:18, amount_ttc:50000, status:'sent', created_at:'2026-06-12', booking_id:2 },
    { id:3, invoice_number:'INV-2026-003', client_name:'Marc Koffi', client_email:'m@test.ci', amount_ht:127119, tax_rate:18, amount_ttc:150000, status:'draft', created_at:'2026-06-14', booking_id:3 }
  ],

  apiHeaders() { return { 'Content-Type':'application/json', 'Authorization':'Bearer '+localStorage.getItem('token') }; },
  estId() { return localStorage.getItem('establishment_id')||'1'; },

  async init() { await this.loadInvoices(); },

  async loadInvoices() {
    this.loading = true; this.error = null;
    try {
      const res = await fetch(baseUrl + '/api/invoices?establishment_id='+this.estId()+'&page='+this.page+'&per_page='+this.perPage+(this.search? '&search='+encodeURIComponent(this.search):'')+(this.statusFilter? '&status='+this.statusFilter:''), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.invoices = data.data?.invoices ?? data.data ?? [];
        this.total = data.data?.total ?? this.invoices.length;
      }
      if (!this.invoices.length) this.invoices = this.fallbackInvoices;
    } catch(e) { this.invoices = this.fallbackInvoices; this.error = 'Données de démonstration affichées.'; }
    finally { this.loading = false; }
  },

  openCreate() { this.editing = null; this.form = { booking_id:'', room_id:'', client_name:'', client_email:'', amount_ht:'', tax_rate:18, notes:'', status:'draft' }; this.formError = null; this.showModal = true; },
  openEdit(inv) { this.editing = inv; this.form = { booking_id:inv.booking_id??'', room_id:inv.room_id??'', client_name:inv.client_name??'', client_email:inv.client_email??'', amount_ht:inv.amount_ht??'', tax_rate:inv.tax_rate??18, notes:inv.notes??'', status:inv.status??'draft' }; this.formError=null; this.showModal=true; },

  get amountTtc() { const ht = parseFloat(this.form.amount_ht)||0; const tax = parseFloat(this.form.tax_rate)||0; return Math.round(ht*(1+tax/100)); },

  async saveInvoice() {
    if (!this.form.client_name?.trim()) return this.formError='Nom client requis.';
    if (!this.form.amount_ht || this.form.amount_ht <= 0) return this.formError='Montant HT requis.';
    this.submitting = true; this.formError = null;
    try {
      const url = this.editing ? baseUrl + '/api/invoices/'+this.editing.id : baseUrl + '/api/invoices';
      const res = await fetch(url, { method: this.editing ? 'PUT':'POST', headers: this.apiHeaders(), body: JSON.stringify({ ...this.form, establishment_id: this.estId(), amount_ttc: this.amountTtc }) });
      const data = await res.json();
      if (data.success) { this.showModal = false; await this.loadInvoices(); } else { this.formError = data.message ?? 'Erreur.'; }
    } catch(e) { this.formError = 'Erreur réseau.'; }
    finally { this.submitting = false; }
  },

  downloadPdf(id) { window.open(baseUrl + '/api/invoices/'+id+'/pdf'+'?token='+localStorage.getItem('token'), '_blank'); },

  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p??0)+' FCFA'; },
  formatDate(d) { if(!d) return '-'; return new Date(d).toLocaleDateString('fr-FR',{ day:'numeric', month:'short', year:'numeric' }); },
  statusCfg(s) { return { draft:{label:'Brouillon', badge:'badge badge-info'}, sent:{label:'Envoyée', badge:'badge badge-warning'}, paid:{label:'Payée', badge:'badge badge-success'} }[s] ?? {label:s, badge:'badge'}; }
  };
}
