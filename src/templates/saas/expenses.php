<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Template Dépenses SaaS -->

<?php $pageJs = 'saas-expenses'; $pageCss = 'saas-expenses'; ?>
<div x-data="expensesPage('<?= $base_url ?>')"
 x-init="init()" @keydown.escape.window="showModal=false">

  <!-- Blur-gate wrapper -->
  <div style="position:relative;min-height:520px;">

    <!-- Contenu (flouté si plan insuffisant) -->
    <div :style="upgradeRequired ? 'filter:blur(6px);pointer-events:none;user-select:none;' : ''">

      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <div><h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Dépenses</h1><p style="margin:6px 0 0;color:#9CA3AF;">Suivi des charges opérationnelles</p></div>
        <div style="display:flex;gap:8px;">
          <button class="btn-saas-secondary" @click="openManageCategories()">Catégories</button>
          <button class="btn-saas-secondary" @click="openCreate()">+ Nouvelle dépense</button>
        </div>
      </div>

      <!-- KPIs -->
      <div class="saas-kpi-grid">
        <div class="kpi-card">
          <div class="kpi-top"><div class="kpi-icon kpi-icon-red"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10a4 4 0 004 4h10a4 4 0 004-4v-2M13 12h8m0 0l-3-3m3 3l-3 3"/></svg></div></div>
          <div class="kpi-body"><div class="kpi-value" x-text="formatPrice(totalExpenses)"></div><div class="kpi-label">Total dépenses</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top"><div class="kpi-icon kpi-icon-gold"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div></div>
          <div class="kpi-body"><div class="kpi-value" x-text="formatPrice(expenses.filter(e=> new Date(e.expense_date ?? e.date).getMonth() === new Date().getMonth() && new Date(e.expense_date ?? e.date).getFullYear()=== new Date().getFullYear()).reduce((s,e)=>s+(e.amount||0),0))"></div><div class="kpi-label">Ce mois</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top"><div class="kpi-icon kpi-icon-purple"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5.586a1 1 0 01.707.293l7.414 7.414a1 1 0 010 1.414l-7.586 7.586a1 1 0 01-1.414 0L4.293 12.293A1 1 0 014 11.586V6a3 3 0 013-3z"/></svg></div></div>
          <div class="kpi-body"><div class="kpi-value" x-text="byCategory[0]?.cat ?? '-' "></div><div class="kpi-label">Catégorie principale</div></div>
        </div>
      </div>

      <!-- Filters -->
      <div class="saas-card" style="padding:10px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select class="saas-input" x-model="categoryFilter"><option value="">Toutes catégories</option><template x-for="c in categories" :key="c.id"><option :value="c.name" x-text="c.name"></option></template></select>
        <button class="btn-saas-primary" @click="loadExpenses()">Filtrer</button>
      </div>

      <!-- Erreur -->
      <div x-show="error && !loading"
        style="padding:14px 16px;margin-bottom:12px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.15);border-radius:12px;color:#B91C1C;font-size:13px;"
        x-text="error">
      </div>

      <!-- Table -->
      <div class="saas-card" style="padding:0;overflow:hidden;">
        <template x-if="loading || upgradeRequired">
          <div style="padding:12px;"><template x-for="i in [1,2,3,4,5]" :key="i"><div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);"><div class="cl-shimmer" style="width:120px;height:14px;border-radius:8px;"></div><div class="cl-shimmer" style="height:14px;width:220px;border-radius:8px;flex:1;"></div><div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div></div></template></div>
        </template>

        <template x-if="!loading && !upgradeRequired">
          <div style="overflow-x:auto;">
            <table class="saas-table" style="width:100%;">
              <thead><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Montant</th><th>Notes</th><th>Actions</th></tr></thead>
              <tbody>
                <template x-for="exp in expenses" :key="exp.id">
                  <tr>
                    <td x-text="formatDate(exp.expense_date ?? exp.date)"></td>
                    <td style="color:#111827;font-weight:600;" x-text="exp.description ?? exp.label"></td>
                    <td><div style="display:inline-block;padding:6px 8px;border-radius:8px;color:white;font-weight:700;" :style="{ background: catColor(exp.category) }" x-text="exp.category"></div></td>
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
                <template x-if="expenses.length === 0">
                  <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">
                      Aucune dépense enregistrée. Cliquez sur « Nouvelle dépense » pour commencer.
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>
      </div>

    </div><!-- /contenu flouté -->

    <!-- Overlay upgrade (par-dessus le flou) -->
    <div x-show="!loading && upgradeRequired"
         style="position:absolute;inset:0;z-index:10;background:rgba(248,250,252,0.55);">
      <div style="display:flex;align-items:center;justify-content:center;min-height:100%;padding:24px;">
        <div style="background:white;border-radius:20px;padding:36px 40px;text-align:center;max-width:380px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,0.18);border:1px solid rgba(0,0,0,0.06);">
          <div style="width:64px;height:64px;border-radius:18px;background:#0F2B20;display:grid;place-items:center;margin:0 auto 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:30px;height:30px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div style="display:inline-block;background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;margin-bottom:14px;letter-spacing:0.5px;">PLAN PRO REQUIS</div>
          <h2 style="margin:0 0 10px;font-size:20px;font-weight:800;color:#111827;">Suivi des dépenses</h2>
          <p style="color:#6B7280;font-size:13px;margin:0 auto 24px;line-height:1.6;">Enregistrez vos charges, catégorisez vos dépenses et analysez vos coûts opérationnels. Disponible à partir du plan <strong style="color:#1B4332;">Pro</strong>.</p>
          <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= $base_url ?>/saas/settings" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#1B4332;color:white;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Passer au Pro →</a>
            <a href="<?= $base_url ?>/saas" style="display:inline-flex;align-items:center;padding:10px 20px;background:white;color:#374151;border:1px solid #E5E7EB;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Tableau de bord</a>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /blur-gate wrapper -->

  <!-- Modal (hors du blur) -->
  <div x-cloak x-show="showModal" class="saas-modal-bg" @keydown.escape.window="showModal=false" @click.self="showModal=false">
    <div class="saas-modal" style="max-width:620px;" @click.stop>
      <div class="saas-modal-header">
        <h2 x-text="editing? 'Modifier la dépense':'Nouvelle dépense'"></h2>
        <button type="button" class="saas-modal-close" @click="showModal=false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:12px;grid-template-columns:1fr 1fr;">
          <div style="grid-column:span 2;"><label class="saas-label">Libellé</label><input class="saas-input" x-model="form.description" /></div>
          <div><label class="saas-label">Montant</label><input class="saas-input" type="number" x-model.number="form.amount" /></div>
          <div><label class="saas-label">Date</label><input class="saas-input" type="date" x-model="form.expense_date" /></div>
          <div style="grid-column:span 2;"><label class="saas-label">Catégorie</label><select class="saas-input" x-model="form.category"><template x-for="c in categories" :key="c.id"><option :value="c.name" x-text="c.name"></option></template></select></div>
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

  <!-- Modal : Catégories de dépenses (hors du blur) -->
  <div x-cloak x-show="showCatModal" class="saas-modal-bg" @keydown.escape.window="showCatModal=false" @click.self="showCatModal=false">
    <div class="saas-modal" style="max-width:480px;" @click.stop>
      <div class="saas-modal-header">
        <h2>Catégories de dépenses</h2>
        <button type="button" class="saas-modal-close" @click="showCatModal=false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;">
          <template x-if="categories.length === 0">
            <p style="font-size:13px;color:#9CA3AF;text-align:center;padding:10px 0;">Aucune catégorie. Créez-en une ci-dessous.</p>
          </template>
          <template x-for="cat in categories" :key="cat.id">
            <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;background:#F9FAFB;">
              <div style="width:12px;height:12px;border-radius:4px;flex-shrink:0;" :style="{ background: cat.color }"></div>
              <div style="flex:1;font-size:13px;font-weight:600;color:#111827;" x-text="cat.name"></div>
              <button type="button" class="btn-saas-secondary" style="padding:4px 10px;font-size:12px;" @click="openEditCategory(cat)">Modifier</button>
              <template x-if="deleteCatConfirm !== cat.id">
                <button type="button" class="btn-saas-danger" style="padding:4px 10px;font-size:12px;" @click="deleteCatConfirm = cat.id">Supprimer</button>
              </template>
              <template x-if="deleteCatConfirm === cat.id">
                <div style="display:flex;gap:4px;">
                  <button type="button" class="btn-saas-danger" style="padding:4px 10px;font-size:12px;" @click="deleteCategory(cat)">Oui</button>
                  <button type="button" class="btn-saas-secondary" style="padding:4px 10px;font-size:12px;" @click="deleteCatConfirm = null">Non</button>
                </div>
              </template>
            </div>
          </template>
        </div>

        <div style="height:1px;background:rgba(0,0,0,0.06);"></div>

        <p style="font-size:12px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.03em;margin:14px 0 10px;" x-text="editingCat ? 'Modifier la catégorie' : 'Nouvelle catégorie'"></p>
        <div style="display:grid;gap:12px;">
          <div>
            <label class="saas-label">Nom</label>
            <input class="saas-input" x-model="catForm.name" placeholder="Ex : Blanchisserie" @keydown.enter="saveCategory()">
          </div>
          <div>
            <label class="saas-label">Couleur</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <template x-for="c in catPalette" :key="c">
                <button type="button" @click="catForm.color = c"
                  :style="{ background: c, outline: catForm.color === c ? '2px solid #111827' : 'none', outlineOffset: '2px' }"
                  style="width:26px;height:26px;border-radius:8px;border:none;cursor:pointer;"></button>
              </template>
            </div>
          </div>
          <div x-show="catFormError" style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;" x-text="catFormError"></div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" x-show="editingCat" @click="cancelEditCategory()" :disabled="catSubmitting">Annuler la modification</button>
        <button type="button" class="btn-saas-primary" @click="saveCategory()" :disabled="catSubmitting">
          <span x-show="!catSubmitting" x-text="editingCat ? 'Enregistrer' : 'Ajouter'"></span>
          <span x-show="catSubmitting">Enregistrement…</span>
        </button>
      </div>
    </div>
  </div>

</div>
