/* ============================================================
   Afristay — Page SaaS : Dépenses (src/templates/saas/expenses.php)
   ============================================================ */

function expensesPage(baseUrl) {
  return {
  ...saasHelpers,

  expenses: [], loading: true, error: null, upgradeRequired: false,
  showModal: false, editing: null, submitting: false, formError: null,
  categoryFilter: '', deleteConfirm: null,

  form: { description: '', amount: '', category: '', expense_date: '', notes: '' },

  // ── Catégories de dépenses (personnalisées par établissement) ────────────
  categories: [],
  catPalette: ['#D97706', '#2563EB', '#059669', '#7C3AED', '#EC4899', '#0D9488', '#DC2626', '#6B7280'],
  showCatModal: false,
  editingCat: null,
  catForm: { name: '', color: '#D97706' },
  catFormError: null,
  catSubmitting: false,
  deleteCatConfirm: null,

  async init() {
    if (planUpgradeRequired('expenses')) { this.upgradeRequired = true; this.loading = false; return; }
    await this.loadCategories();
    await this.loadExpenses();
  },

  async loadCategories() {
    this.categories = await this.loadExpenseCategoryColors(baseUrl);
  },

  openManageCategories() {
    this.showCatModal = true;
    this.cancelEditCategory();
  },

  openEditCategory(cat) {
    this.editingCat   = cat;
    this.catForm      = { name: cat.name, color: cat.color };
    this.catFormError = null;
  },

  cancelEditCategory() {
    this.editingCat   = null;
    this.catForm      = { name: '', color: this.catPalette[0] };
    this.catFormError = null;
  },

  async saveCategory() {
    if (!this.catForm.name?.trim()) { this.catFormError = 'Nom requis.'; return; }
    this.catSubmitting = true; this.catFormError = null;
    try {
      const url    = this.editingCat ? baseUrl + '/api/expense-categories/' + this.editingCat.id : baseUrl + '/api/expense-categories';
      const method = this.editingCat ? 'PUT' : 'POST';
      const res  = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(this.catForm) });
      const data = await res.json();
      if (data.success) {
        await this.loadCategories();
        this.cancelEditCategory();
      } else {
        this.catFormError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.catFormError = 'Erreur réseau.';
    } finally {
      this.catSubmitting = false;
    }
  },

  async deleteCategory(cat) {
    try {
      const res  = await fetch(baseUrl + '/api/expense-categories/' + cat.id, { method: 'DELETE', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.categories = this.categories.filter(c => c.id !== cat.id);
        this.deleteCatConfirm = null;
      } else {
        this.catFormError = data.message ?? 'Erreur de suppression.';
      }
    } catch(e) {
      this.catFormError = 'Erreur réseau.';
    }
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
    if (this.categories.length === 0) {
      this.showToast('Créez d\'abord une catégorie de dépense.', 'error');
      this.openManageCategories();
      return;
    }
    this.editing = null;
    this.form = { description: '', amount: '', category: this.categories[0]?.name ?? '', expense_date: '', notes: '' };
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

  get totalExpenses() { return this.expenses.reduce((s, e) => s + (Number(e.amount) || 0), 0); },
  get byCategory() {
    const map = {};
    this.expenses.forEach(e => { map[e.category] = (map[e.category] || 0) + (Number(e.amount) || 0); });
    return Object.entries(map).map(([cat, total]) => ({ cat, total }));
  },
  };
}
