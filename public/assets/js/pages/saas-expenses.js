/* ============================================================
   Ivoire Stay — Page SaaS : Dépenses (src/templates/saas/expenses.php)
   ============================================================ */

function expensesPage(baseUrl) {
  return {
  expenses: [], loading: true, error: null,
  showModal: false, editing: null, submitting: false, formError: null,
  categoryFilter: '', deleteConfirm: null,

  form: { label:'', amount:'', category:'maintenance', date:'', notes:'' },

  categories: [ { value:'maintenance', label:'Maintenance' }, { value:'salaries', label:'Salaires' }, { value:'supplies', label:'Fournitures' }, { value:'utilities', label:'Énergie / Eau' }, { value:'marketing', label:'Marketing' }, { value:'other', label:'Autre' } ],

  fallbackExpenses: [ { id:1, label:'Réparation climatisation', amount:45000, category:'maintenance', date:'2026-06-05', notes:'' }, { id:2, label:'Salaire femme de ménage', amount:80000, category:'salaries', date:'2026-06-01', notes:'Mois de mai' }, { id:3, label:'Facture électricité', amount:32000, category:'utilities', date:'2026-06-08', notes:'' }, { id:4, label:'Produits ménagers', amount:15000, category:'supplies', date:'2026-06-10', notes:'' } ],

  apiHeaders() { return { 'Content-Type':'application/json', 'Authorization':'Bearer '+localStorage.getItem('token') }; },
  estId() { return localStorage.getItem('establishment_id')||'1'; },

  async init() { await this.loadExpenses(); },

  async loadExpenses() {
    this.loading = true; this.error = null;
    try {
      const res = await fetch(baseUrl + '/api/expenses?establishment_id='+this.estId()+(this.categoryFilter? '&category='+this.categoryFilter:''), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) this.expenses = data.data?.expenses ?? data.data ?? [];
      if (!this.expenses.length) this.expenses = this.fallbackExpenses;
    } catch(e) { this.expenses = this.fallbackExpenses; this.error = 'Données de démonstration.'; }
    finally { this.loading = false; }
  },

  openCreate() { this.editing=null; this.form={ label:'', amount:'', category:'maintenance', date:'', notes:'' }; this.formError=null; this.showModal=true; },
  openEdit(exp) { this.editing=exp; this.form={ label:exp.label, amount:exp.amount, category:exp.category, date:exp.date, notes:exp.notes??'' }; this.formError=null; this.showModal=true; },

  async saveExpense() {
    if (!this.form.label?.trim()) return this.formError='Libellé requis.';
    if (!this.form.amount || this.form.amount <= 0) return this.formError='Montant requis.';
    if (!this.form.date) return this.formError='Date requise.';
    this.submitting=true; this.formError=null;
    try {
      const url = this.editing ? baseUrl + '/api/expenses/'+this.editing.id : baseUrl + '/api/expenses';
      const res = await fetch(url, { method: this.editing? 'PUT':'POST', headers: this.apiHeaders(), body: JSON.stringify({ ...this.form, establishment_id: this.estId() }) });
      const data = await res.json();
      if (data.success) { this.showModal=false; await this.loadExpenses(); } else { this.formError = data.message ?? 'Erreur.'; }
    } catch(e) { this.formError='Erreur réseau.'; }
    finally { this.submitting=false; }
  },

  async deleteExpense(id) {
    try {
      const res = await fetch(baseUrl + '/api/expenses/'+id, { method:'DELETE', headers:this.apiHeaders() });
      const data = await res.json();
      if (data.success) { this.expenses = this.expenses.filter(e=> e.id != id); this.deleteConfirm = null; }
    } catch(e) { }
  },

  catLabel(c) { return this.categories.find(x=>x.value===c)?.label ?? c; },
  catColor(c) { return { maintenance:'#D97706', salaries:'#2563EB', supplies:'#059669', utilities:'#7C3AED', marketing:'#EC4899', other:'#6B7280' }[c] ?? '#9CA3AF'; },

  get totalExpenses() { return this.expenses.reduce((s,e)=> s + (e.amount||0), 0); },
  get byCategory() { const map={}; this.expenses.forEach(e=>{ map[e.category]= (map[e.category]||0) + (e.amount||0); }); return Object.entries(map).map(([cat,total])=>({cat,total})); },
  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p??0)+' FCFA'; },
  formatDate(d) { if(!d) return '-'; return new Date(d).toLocaleDateString('fr-FR',{ day:'numeric', month:'short', year:'numeric' }); }
  };
}
