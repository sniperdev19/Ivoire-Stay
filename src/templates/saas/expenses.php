<?php
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!-- Template Dépenses SaaS -->

<div x-data="{
  expenses: [], loading: true, error: null,
  showModal: false, editing: null, submitting: false, formError: null,
  categoryFilter: '', deleteConfirm: null,

  form: { label:'', amount:'', category:'maintenance', date:'', notes:'' },

  categories: [ { value:'maintenance', label:'Maintenance' }, { value:'salaries', label:'Salaires' }, { value:'supplies', label:'Fournitures' }, { value:'utilities', label:'Énergie / Eau' }, { value:'marketing', label:'Marketing' }, { value:'other', label:'Autre' } ],

  /* fallbackExpenses: [ { id:1, label:'Réparation climatisation', amount:45000, category:'maintenance', date:'2026-06-05', notes:'' }, { id:2, label:'Salaire femme de ménage', amount:80000, category:'salaries', date:'2026-06-01', notes:'Mois de mai' }, { id:3, label:'Facture électricité', amount:32000, category:'utilities', date:'2026-06-08', notes:'' }, { id:4, label:'Produits ménagers', amount:15000, category:'supplies', date:'2026-06-10', notes:'' } ], */

  apiHeaders() { const token = localStorage.getItem('token') ?? ''; return { 'Content-Type':'application/json', 'Authorization':'Bearer ' + token }; },
  estId() {
    let id = localStorage.getItem('establishment_id');
    if (id && id !== 'null' && id !== 'undefined') return id;
    try { const list = JSON.parse(localStorage.getItem('establishments') || '[]'); if (Array.isArray(list) && list.length>0) { id = list[0].id ?? list[0].establishment_id; if (id) { localStorage.setItem('establishment_id', String(id)); return String(id); } } } catch(e) {}
    try { const user = JSON.parse(localStorage.getItem('user') || '{}'); if (user.establishment_id) { localStorage.setItem('establishment_id', String(user.establishment_id)); return String(user.establishment_id); } } catch(e) {}
    return '1';
  },

  async init() { await this.loadExpenses(); },

  async loadExpenses() {
    this.loading = true; this.error = null;
    try {
      const res = await fetch('<?= $base_url ?>/api/expenses?establishment_id='+this.estId()+(this.categoryFilter? '&category='+this.categoryFilter:''), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.expenses = data.data?.expenses ?? data.data ?? [];
        // Ne PAS activer le fallback si l'API répond avec succès
      } else {
        console.warn('API error:', data.message);
        // Laisser le tableau vide — ne pas charger le fallback
        this.expenses = [];
      }
    } catch(e) {
      this.expenses = [];
      console.error('Network error:', e);
      this.error = 'Impossible de charger les dépenses. Veuillez réessayer.';
    }
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
      const url = this.editing ? '<?= $base_url ?>/api/expenses/'+this.editing.id : '<?= $base_url ?>/api/expenses';
      const res = await fetch(url, { method: this.editing? 'PUT':'POST', headers: this.apiHeaders(), body: JSON.stringify({ ...this.form, establishment_id: this.estId() }) });
      const data = await res.json();
      if (data.success) { this.showModal=false; await this.loadExpenses(); } else { this.formError = data.message ?? 'Erreur.'; }
    } catch(e) { this.formError='Erreur réseau.'; }
    finally { this.submitting=false; }
  },

  async deleteExpense(id) {
    try {
      const res = await fetch('<?= $base_url ?>/api/expenses/'+id, { method:'DELETE', headers:this.apiHeaders() });
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
}"
 x-init="init()" @keydown.escape.window="showModal=false">

  <!-- Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div><h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Dépenses</h1><p style="margin:6px 0 0;color:#9CA3AF;">Suivi des charges opérationnelles</p></div>
    <div><button class="btn-saas-secondary" @click="openCreate()">+ Nouvelle dépense</button></div>
  </div>

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px;">
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Total dépenses</div><div style="font-size:18px;font-weight:800;" x-text="formatPrice(totalExpenses)"></div></div>
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Ce mois</div><div style="font-size:18px;font-weight:800;" x-text="formatPrice(expenses.filter(e=> new Date(e.date).getMonth() === new Date().getMonth() && new Date(e.date).getFullYear()=== new Date().getFullYear()).reduce((s,e)=>s+(e.amount||0),0))"></div></div>
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Catégorie principale</div><div style="font-size:18px;font-weight:800;" x-text="byCategory[0]?.cat ?? '-' "></div></div>
  </div>

  <!-- Filters -->
  <div class="saas-card" style="padding:10px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <select class="saas-input" x-model="categoryFilter"><option value="">Toutes catégories</option><template x-for="c in categories" :key="c.value"><option :value="c.value" x-text="c.label"></option></template></select>
    <button class="btn-saas-primary" @click="loadExpenses()">Filtrer</button>
  </div>

  <!-- Table -->
  <div class="saas-card" style="padding:0;overflow:hidden;">
    <template x-if="loading">
      <div style="padding:12px;"><template x-for="i in [1,2,3,4,5]" :key="i"><div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);"><div class="cl-shimmer" style="width:120px;height:14px;border-radius:8px;"></div><div class="cl-shimmer" style="height:14px;width:220px;border-radius:8px;flex:1;"></div><div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div></div></template></div>
    </template>

    <template x-if="!loading">
      <div style="overflow-x:auto;">
        <table class="saas-table" style="width:100%;">
          <thead><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Montant</th><th>Notes</th><th>Actions</th></tr></thead>
          <tbody>
            <template x-for="exp in expenses" :key="exp.id">
              <tr>
                <td x-text="formatDate(exp.date)"></td>
                <td style="color:#111827;font-weight:600;" x-text="exp.label"></td>
                <td><div style="display:inline-block;padding:6px 8px;border-radius:8px;color:white;font-weight:700;" :style="'background:'+catColor(exp.category)" x-text="catLabel(exp.category)"></div></td>
                <td style="font-weight:800;" x-text="formatPrice(exp.amount)"></td>
                <td style="font-size:12px;color:#9CA3AF;" x-text="(exp.notes||'').length>30? (exp.notes.slice(0,30)+'...'): (exp.notes||'')"></td>
                <td style="white-space:nowrap;">
                  <button class="btn-saas-secondary" @click.stop="openEdit(exp)">Modifier</button>
                  <button class="btn-saas-danger" @click.stop="deleteConfirm = exp.id">Supprimer</button>
                </td>
              </tr>
              <tr x-show="deleteConfirm === exp.id"><td colspan="6"><div style="background:rgba(220,38,38,0.06);padding:10px;border-radius:8px;display:flex;gap:8px;align-items:center;justify-content:space-between;">
                <div style="color:#DC2626;font-weight:600;">Confirmer la suppression ?</div>
                <div style="display:flex;gap:8px;"><button class="btn-saas-danger" @click="deleteExpense(exp.id)">Oui</button><button class="btn-saas-secondary" @click="deleteConfirm=null">Non</button></div>
              </div></td></tr>
            </template>
          </tbody>
        </table>
      </div>
    </template>
  </div>

  <!-- Modal -->
  <div x-show="showModal" class="saas-modal-bg" @keydown.escape.window="showModal=false" @click.self="showModal=false">
    <div class="saas-modal" style="max-width:620px;" @click.stop>
      <div class="saas-modal-header"><h2 x-text="editing? 'Modifier la dépense':'Nouvelle dépense'"></h2></div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:12px;grid-template-columns:1fr 1fr;">
          <div style="grid-column:span 2;"><label class="saas-label">Libellé</label><input class="saas-input" x-model="form.label" /></div>
          <div><label class="saas-label">Montant</label><input class="saas-input" type="number" x-model.number="form.amount" /></div>
          <div><label class="saas-label">Date</label><input class="saas-input" type="date" x-model="form.date" /></div>
          <div style="grid-column:span 2;"><label class="saas-label">Catégorie</label><select class="saas-input" x-model="form.category"><template x-for="c in categories" :key="c.value"><option :value="c.value" x-text="c.label"></option></template></select></div>
          <div style="grid-column:span 2;"><label class="saas-label">Notes</label><textarea class="saas-input" rows="2" x-model="form.notes"></textarea></div>
          <template x-if="formError"><div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;" x-text="formError"></div></template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="saveExpense()" :disabled="submitting"><span x-show="!submitting">Enregistrer</span><div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div></button>
      </div>
    </div>
  </div>

</div>
<?php ?>
<div class="expenses">
    <h2>Dépenses</h2>
    <div id="expensesList">Liste des dépenses...</div>
</div>
