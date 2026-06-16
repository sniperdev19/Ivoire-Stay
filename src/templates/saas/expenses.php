<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Template Dépenses SaaS -->

<?php $pageJs = 'saas-expenses'; ?>
<div x-data="expensesPage('<?= $base_url ?>')"
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
