<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Template Paiements SaaS -->

<?php $pageJs = 'saas-payments'; ?>
<div x-data="paymentsPage('<?= $base_url ?>')"
 x-init="init()" @keydown.escape.window="showModal=false">

  <!-- Blur-gate wrapper -->
  <div style="position:relative;min-height:520px;">

    <!-- Contenu (flouté si plan insuffisant) -->
    <div :style="upgradeRequired ? 'filter:blur(6px);pointer-events:none;user-select:none;' : ''">

      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <div><h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Paiements</h1><p style="margin:6px 0 0;color:#9CA3AF;">Enregistrer et gérer les paiements</p></div>
        <div><button class="btn-saas-secondary" @click="openCreate()">+ Enregistrer un paiement</button></div>
      </div>

      <!-- KPI row -->
      <div class="saas-kpi-grid">
        <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Total paiements</div><div style="font-size:18px;font-weight:800;" x-text="payments.length"></div></div>
        <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Complétés</div><div style="font-size:18px;font-weight:800;color:#16a34a;" x-text="payments.filter(p=>p.status==='completed').length"></div></div>
        <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">En attente</div><div style="font-size:18px;font-weight:800;color:#D97706;" x-text="payments.filter(p=>p.status==='pending').length"></div></div>
        <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Volume complété</div><div style="font-size:16px;font-weight:800;color:#1B4332;" x-text="formatPrice(payments.filter(p=>p.status==='completed').reduce((s,p)=>s+(p.amount||0),0))"></div></div>
      </div>

      <!-- Filters -->
      <div class="saas-card" style="padding:10px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select class="saas-input" x-model="methodFilter"><option value="">Méthode (Tous)</option><option value="mobile_money">Mobile Money</option><option value="cash">Espèces</option><option value="card">Carte</option><option value="bank_transfer">Virement</option></select>
        <select class="saas-input" x-model="statusFilter"><option value="">Statut (Tous)</option><option value="completed">Complété</option><option value="pending">En attente</option><option value="refunded">Remboursé</option></select>
        <button class="btn-saas-primary" @click="loadPayments()">Filtrer</button>
      </div>

      <!-- Erreur -->
      <div x-show="error && !loading"
        style="padding:14px 16px;margin-bottom:12px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.15);border-radius:12px;color:#B91C1C;font-size:13px;"
        x-text="error">
      </div>

      <!-- Table -->
      <div class="saas-card" style="padding:0;overflow:hidden;">
        <template x-if="loading || upgradeRequired">
          <div style="padding:12px;"><template x-for="i in [1,2,3,4,5]" :key="i"><div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);"><div class="cl-shimmer" style="width:100px;height:14px;border-radius:8px;"></div><div class="cl-shimmer" style="width:220px;height:14px;border-radius:8px;flex:1;"></div><div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div><div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div></div></template></div>
        </template>

        <template x-if="!loading && !upgradeRequired">
          <div style="overflow-x:auto;">
            <table class="saas-table" style="width:100%;">
              <thead><tr><th>Référence</th><th>Client</th><th>Montant</th><th>Méthode</th><th>Type</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
              <tbody>
                <template x-for="p in payments" :key="p.id">
                  <tr>
                    <td><div style="display:inline-block;padding:6px 8px;border-radius:8px;background:rgba(201,168,76,0.08);font-weight:700;color:#C9A84C;" x-text="p.reference"></div></td>
                    <td x-text="p.client_name"></td>
                    <td style="font-weight:800;" x-text="formatPrice(p.amount)"></td>
                    <td><div style="display:inline-flex;align-items:center;gap:8px;"><div style="width:10px;height:10px;border-radius:4px;" :style="{ background: methodIcon(p.method) }"></div><span x-text="methodLabel(p.method)"></span></div></td>
                    <td x-text="typeLabel(p.type)" style="color:#9CA3AF;"></td>
                    <td><span :class="statusCfg(p.status).badge" x-text="statusCfg(p.status).label"></span></td>
                    <td x-text="formatDate(p.created_at)"></td>
                    <td>
                      <button class="btn-saas-secondary" @click.stop="openEdit(p)">Modifier</button>
                    </td>
                  </tr>
                </template>
                <template x-if="payments.length === 0">
                  <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">
                      Aucun paiement enregistré. Cliquez sur « Enregistrer un paiement » pour commencer.
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
          <h2 style="margin:0 0 10px;font-size:20px;font-weight:800;color:#111827;">Gestion des paiements</h2>
          <p style="color:#6B7280;font-size:13px;margin:0 auto 24px;line-height:1.6;">Enregistrez et suivez tous les paiements de vos clients en temps réel. Disponible à partir du plan <strong style="color:#1B4332;">Pro</strong>.</p>
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
    <div class="saas-modal" style="max-width:540px;" @click.stop>
      <div class="saas-modal-header">
        <h2 x-text="editing? 'Modifier paiement':'Nouveau paiement'"></h2>
        <button type="button" class="saas-modal-close" @click="showModal=false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:12px;">
          <!-- Création : sélection facture (auto-remplit booking_id) -->
          <template x-if="!editing">
            <div>
              <label class="saas-label">Facture *</label>
              <select class="saas-input" x-model="form.invoice_id" @change="onInvoiceChange()">
                <option value="">Sélectionner une facture</option>
                <template x-for="inv in invoices.filter(i => i.status !== 'paid')" :key="inv.id">
                  <option :value="inv.id" x-text="inv.invoice_number + ' — ' + (inv.client_name ?? '') + ' — ' + formatPrice(inv.amount_ttc)"></option>
                </template>
              </select>
            </div>
          </template>
          <div><label class="saas-label">Montant</label><input class="saas-input" type="number" x-model.number="form.amount" /></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div><label class="saas-label">Méthode</label><select class="saas-input" x-model="form.method"><option value="mobile_money">Mobile Money</option><option value="cash">Espèces</option><option value="card">Carte</option><option value="bank_transfer">Virement</option></select></div>
            <div><label class="saas-label">Type</label><select class="saas-input" x-model="form.type"><option value="full">Paiement complet</option><option value="deposit">Acompte</option><option value="partial">Partiel</option></select></div>
          </div>
          <div><label class="saas-label">Notes</label><textarea class="saas-input" rows="2" x-model="form.notes"></textarea></div>
          <template x-if="formError"><div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;" x-text="formError"></div></template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="savePayment()" :disabled="submitting"> <span x-show="!submitting">Enregistrer</span><div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div></button>
      </div>
    </div>
  </div>

</div>
