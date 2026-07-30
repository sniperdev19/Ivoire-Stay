<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Template Factures SaaS -->

<?php $pageJs = 'saas-invoices'; ?>
<div x-data="invoicesPage('<?= $base_url ?>')"
 x-init="init()" @keydown.enter.window="loadInvoices()" @keydown.escape.window="showModal=false">

  <!-- Blur-gate wrapper -->
  <div style="position:relative;min-height:520px;">

    <!-- Contenu (flouté si plan insuffisant) -->
    <div :style="upgradeRequired ? 'filter:blur(6px);pointer-events:none;user-select:none;' : ''">

      <!-- En-tête -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <div>
          <h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Facturation</h1>
          <p style="margin:6px 0 0;color:#9CA3AF;">Gérer vos factures</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <button class="btn-saas-secondary" @click="openCreate()">+ Nouvelle facture</button>
        </div>
      </div>

      <!-- KPI -->
      <div class="saas-kpi-grid">
        <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Total factures</div><div style="font-size:20px;font-weight:800;" x-text="invoices.length"></div></div>
        <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Montant total TTC</div><div style="font-size:18px;font-weight:800;color:#1B4332;" x-text="formatPrice(invoices.reduce((s,i)=> s + (i.amount_ttc||0),0))"></div></div>
        <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Payées</div><div style="font-size:18px;font-weight:800;color:#16a34a;" x-text="invoices.filter(i=>i.status==='paid').length"></div></div>
      </div>

      <!-- Filtres -->
      <div class="saas-card" style="padding:12px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input class="saas-input" placeholder="Rechercher client ou n° facture" x-model="search" style="flex:1;min-width:220px;" />
        <select class="saas-input" x-model="statusFilter" style="width:160px;"><option value="">Tous</option><option value="draft">Brouillon</option><option value="sent">Envoyée</option><option value="paid">Payée</option></select>
        <button class="btn-saas-primary" @click="page=1; loadInvoices()">Filtrer</button>
      </div>

      <!-- Table -->
      <div class="saas-card" style="padding:0;overflow:hidden;">
        <template x-if="loading || upgradeRequired">
          <div style="padding:12px;">
            <template x-for="i in [1,2,3,4,5]" :key="i">
              <div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);">
                <div class="cl-shimmer" style="width:140px;height:14px;border-radius:8px;"></div>
                <div class="cl-shimmer" style="height:14px;width:220px;border-radius:8px;flex:1;"></div>
                <div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div>
                <div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div>
                <div class="cl-shimmer" style="width:40px;height:14px;border-radius:8px;"></div>
              </div>
            </template>
          </div>
        </template>

        <template x-if="!loading && !upgradeRequired">
          <div style="overflow-x:auto;">
            <table class="saas-table" style="width:100%;">
              <thead>
                <tr>
                  <th>N° Facture</th>
                  <th>Client</th>
                  <th>Montant HT</th>
                  <th>TVA</th>
                  <th>Montant TTC</th>
                  <th>Statut</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <template x-if="invoices.length === 0">
                  <tr><td colspan="8" style="text-align:center;padding:36px;color:#9CA3AF;">
                    <div style="display:inline-block;text-align:center;"><svg xmlns="http://www.w3.org/2000/svg" style="width:36px;height:36px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6M9 17h6"/></svg><div style="margin-top:8px;font-weight:600;color:#111827;">Aucune facture</div></div>
                  </td></tr>
                </template>
                <template x-for="inv in invoices" :key="inv.id">
                  <tr>
                    <td><div style="font-family:Cormorant,serif;font-size:15px;color:#C9A84C;font-weight:700;" x-text="inv.invoice_number"></div></td>
                    <td><div style="font-weight:600;color:#111827;" x-text="inv.client_name"></div><div style="font-size:12px;color:#9CA3AF;" x-text="inv.client_email"></div></td>
                    <td x-text="formatPrice(inv.amount_ht)"></td>
                    <td x-text="(inv.tax_rate??0) + '%' "></td>
                    <td style="font-weight:800;color:#1B4332;" x-text="formatPrice(inv.amount_ttc)"></td>
                    <td><span :class="statusCfg(inv.status).badge" x-text="statusCfg(inv.status).label"></span></td>
                    <td x-text="formatDate(inv.created_at)"></td>
                    <td style="white-space:nowrap;">
                      <button class="btn-saas-secondary" @click.stop="downloadPdf(inv.id)" title="PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                      </button>
                      <button class="btn-saas-secondary" @click.stop="openEdit(inv)">Modifier</button>
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
          <h2 style="margin:0 0 10px;font-size:20px;font-weight:800;color:#111827;">Facturation</h2>
          <p style="color:#6B7280;font-size:13px;margin:0 auto 24px;line-height:1.6;">Créez et gérez vos factures, exportez-les en PDF et suivez vos encaissements. Disponible à partir du plan <strong style="color:#1B4332;">Pro</strong>.</p>
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
    <div class="saas-modal" style="max-width:560px;" @click.stop>
      <div class="saas-modal-header">
        <h2 x-text="editing ? 'Modifier la facture' : 'Nouvelle facture'"></h2>
        <button type="button" class="saas-modal-close" @click="showModal=false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:12px;">
          <!-- Création : sélection de la réservation -->
          <template x-if="!editing">
            <div>
              <label class="saas-label">Réservation *</label>
              <select class="saas-input" x-model="form.booking_id">
                <option value="">Sélectionner une réservation</option>
                <template x-for="b in bookingsWithoutInvoice" :key="b.id">
                  <option :value="b.id" x-text="'#' + b.id + ' — ' + (b.client_name ?? 'Client') + ' (' + (b.check_in ?? '') + ' → ' + (b.check_out ?? '') + ')'"></option>
                </template>
              </select>
              <div x-show="bookingsWithoutInvoice.length === 0" style="font-size:12px;color:#9CA3AF;margin-top:4px;">Aucune réservation sans facture disponible.</div>
            </div>
          </template>
          <!-- Taux TVA & montant HT pour création manuelle -->
          <div style="display:grid;grid-template-columns:1fr 120px;gap:12px;">
            <div x-show="!editing">
              <label class="saas-label">Montant HT (optionnel — calculé auto)</label>
              <input class="saas-input" type="number" x-model.number="form.amount_ht" placeholder="Laissez vide pour utiliser le montant de la réservation" />
            </div>
            <div x-show="editing">
              <label class="saas-label">Montant HT</label>
              <input class="saas-input" type="number" x-model.number="form.amount_ht" />
            </div>
            <div><label class="saas-label">Taux TVA (%)</label><input class="saas-input" type="number" x-model.number="form.tax_rate" /></div>
          </div>
          <div x-show="editing" style="display:grid;grid-template-columns:1fr;gap:12px;">
            <div><label class="saas-label">Montant TTC</label><input class="saas-input" type="number" x-model.number="form.amount_ttc" /></div>
          </div>
          <div style="background:rgba(201,168,76,0.06);padding:10px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;" x-show="!editing && form.amount_ht > 0">
            <div style="font-size:13px;color:#6B7280;">Montant TTC estimé</div>
            <div style="font-weight:800;color:#1B4332;" x-text="formatPrice(amountTtc)"></div>
          </div>
          <div><label class="saas-label">Statut</label><select class="saas-input" x-model="form.status"><option value="draft">Brouillon</option><option value="sent">Envoyée</option><option value="paid">Payée</option></select></div>
          <template x-if="formError"><div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;" x-text="formError"></div></template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="saveInvoice()" :disabled="submitting"> <span x-show="!submitting">Enregistrer</span><div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div></button>
      </div>
    </div>
  </div>

</div>
