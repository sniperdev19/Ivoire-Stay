/* ============================================================
   Ivoire Stay — Page SaaS : Dépenses (src/templates/saas/expenses.php)
   ============================================================ */

function expensesPage(baseUrl) {
  return {
  ...saasHelpers,

  expenses: [], loading: true, error: null, upgradeRequired: false,
  showModal: false, editing: null, submitting: false, formError: null,
  categoryFilter: '', deleteConfirm: null,

  form: { description: '', amount: '', category: 'maintenance', expense_date: '', notes: '' },

  categories: [
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'salaries',    label: 'Salaires' },
    { value: 'supplies',    label: 'Fournitures' },
    { value: 'utilities',   label: 'Énergie / Eau' },
    { value: 'marketing',   label: 'Marketing' },
    { value: 'other',       label: 'Autre' },
  ],

  async init() {
    if (planUpgradeRequired('expenses')) { this.upgradeRequired = true; this.loading = false; return; }
    await this.loadExpenses();
  },

  async loadExpenses() {
    this.loading = true; this.error = null;
    try {
      const url = baseUrl + '/api/expenses?establishment_id=' + this.estId()
        + (this.categoryFilter ? '&category=' + this.categoryFilter : '');
      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.upgrade_required) { this.upgradeRequired = true; return; }
      if (data.success) {
        this.expenses = data.data?.expenses ?? data.data ?? [];
      } else {
        this.expenses = [];
        this.error = data.message ?? 'Impossible de charger les dépenses.';
      }
    } catch(e) {
      this.expenses = [];
      this.error = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
      this.loading = false;
    }
  },

  openCreate() {
    this.editing = null;
    this.form = { description: '', amount: '', category: 'maintenance', expense_date: '', notes: '' };
    this.formError = null;
    this.showModal = true;
  },
  openEdit(exp) {
    this.editing = exp;
    this.form = { description: exp.description ?? exp.label ?? '', amount: exp.amount, category: exp.category, expense_date: exp.expense_date ?? exp.date ?? '', notes: exp.notes ?? '' };
    this.formError = null;
    this.showModal = true;
  },

  async saveExpense() {
    if (!this.form.description?.trim())    return this.formError = 'Libellé requis.';
    if (!this.form.amount || this.form.amount <= 0) return this.formError = 'Montant requis.';
    if (!this.form.expense_date)           return this.formError = 'Date requise.';
    this.submitting = true; this.formError = null;
    try {
      const url = this.editing ? baseUrl + '/api/expenses/' + this.editing.id : baseUrl + '/api/expenses';
      const res  = await fetch(url, {
        method: this.editing ? 'PUT' : 'POST',
        headers: this.apiHeaders(),
        body: JSON.stringify({ ...this.form, establishment_id: this.estId() })
      });
      const data = await res.json();
      if (data.success) {
        this.showModal = false;
        await this.loadExpenses();
      } else {
        this.formError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.formError = 'Erreur réseau.';
    } finally {
      this.submitting = false;
    }
  },

  async deleteExpense(id) {
    try {
      const res  = await fetch(baseUrl + '/api/expenses/' + id, { method: 'DELETE', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.expenses = this.expenses.filter(e => e.id != id);
        this.deleteConfirm = null;
      }
    } catch(e) {}
  },

  get totalExpenses() { return this.expenses.reduce((s, e) => s + (e.amount || 0), 0); },
  get byCategory() {
    const map = {};
    this.expenses.forEach(e => { map[e.category] = (map[e.category] || 0) + (e.amount || 0); });
    return Object.entries(map).map(([cat, total]) => ({ cat, total }));
  },
  };
}
