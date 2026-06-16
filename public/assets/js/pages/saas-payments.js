/* ============================================================
   Ivoire Stay — Page SaaS : Paiements (src/templates/saas/payments.php)
   ============================================================ */

function paymentsPage(baseUrl) {
  return {
  payments: [], loading: true, error: null,
  showModal: false, editing: null, submitting: false, formError: null,
  methodFilter:'', statusFilter:'',

  form: { booking_id:'', amount:'', method:'mobile_money', type:'full', status:'pending', reference:'', notes:'' },

  fallbackPayments: [
    { id:1, booking_id:1, client_name:'Kouamé Adou', amount:100000, method:'mobile_money', type:'full', status:'confirmed', reference:'MM-001', created_at:'2026-06-10' },
    { id:2, booking_id:2, client_name:'Fatou Diallo', amount:25000, method:'cash', type:'deposit', status:'confirmed', reference:'CASH-002', created_at:'2026-06-12' },
    { id:3, booking_id:3, client_name:'Marc Koffi', amount:150000, method:'mobile_money', type:'full', status:'pending', reference:'MM-003', created_at:'2026-06-14' }
  ],

  apiHeaders() { return { 'Content-Type':'application/json', 'Authorization':'Bearer '+localStorage.getItem('token') }; },
  estId() { return localStorage.getItem('establishment_id')||'1'; },

  async init() { await this.loadPayments(); },

  async loadPayments() {
    this.loading = true; this.error = null;
    try {
      const res = await fetch(baseUrl + '/api/payments?establishment_id='+this.estId()+(this.methodFilter? '&method='+this.methodFilter:'')+(this.statusFilter? '&status='+this.statusFilter:''), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) this.payments = data.data?.payments ?? data.data ?? [];
      if (!this.payments.length) this.payments = this.fallbackPayments;
    } catch(e) { this.payments = this.fallbackPayments; this.error = 'Données de démonstration.'; }
    finally { this.loading = false; }
  },

  openCreate() { this.editing=null; this.form={ booking_id:'', amount:'', method:'mobile_money', type:'full', status:'pending', reference:'', notes:'' }; this.formError=null; this.showModal=true; },

  async savePayment() {
    if (!this.form.amount || this.form.amount <= 0) return this.formError='Montant requis.';
    this.submitting = true; this.formError=null;
    try {
      const url = this.editing ? baseUrl + '/api/payments/'+this.editing.id : baseUrl + '/api/payments';
      const res = await fetch(url, { method: this.editing? 'PUT':'POST', headers: this.apiHeaders(), body: JSON.stringify({ ...this.form, establishment_id: this.estId() }) });
      const data = await res.json();
      if (data.success) { this.showModal=false; await this.loadPayments(); } else { this.formError = data.message ?? 'Erreur.'; }
    } catch(e) { this.formError='Erreur réseau.'; }
    finally { this.submitting=false; }
  },

  methodLabel(m) { return { mobile_money:'Mobile Money', cash:'Espèces', card:'Carte', bank_transfer:'Virement' }[m] ?? m; },
  methodIcon(m) { return { mobile_money:'#16a34a', cash:'#D97706', card:'#2563EB', bank_transfer:'#7C3AED' }[m] ?? '#9CA3AF'; },
  typeLabel(t) { return { full:'Paiement complet', deposit:'Acompte', partial:'Partiel' }[t] ?? t; },
  statusCfg(s) { return { confirmed:{label:'Confirmé', badge:'badge badge-success'}, pending:{label:'En attente', badge:'badge badge-warning'}, failed:{label:'Échoué', badge:'badge badge-danger'} }[s] ?? {label:s,badge:'badge'}; },
  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p??0)+' FCFA'; },
  formatDate(d) { if(!d) return '-'; return new Date(d).toLocaleDateString('fr-FR',{ day:'numeric', month:'short', year:'numeric' }); }
  };
}
