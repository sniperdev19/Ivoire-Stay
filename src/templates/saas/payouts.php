<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Template Retraits SaaS -->

<?php $pageJs = 'saas-payouts'; $pageCss = 'saas-payouts'; ?>
<div x-data="payoutsPage('<?= $base_url ?>')"
 x-init="init()" @keydown.escape.window="showModal=false">

  <!-- Blur-gate wrapper -->
  <div style="position:relative;min-height:520px;">

    <!-- Contenu (flouté si plan insuffisant) -->
    <div :style="upgradeRequired ? 'filter:blur(6px);pointer-events:none;user-select:none;' : ''">

      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
        <div><h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Retraits</h1><p style="margin:6px 0 0;color:#9CA3AF;">Solde et retraits des paiements en ligne encaissés pour votre compte</p></div>
        <div><button class="btn-saas-primary" @click="openModal()" :disabled="!balance || balance.available_balance <= 0">+ Demander un retrait</button></div>
      </div>

      <!-- KPIs -->
      <div class="payout-kpi-grid">
        <div class="kpi-card">
          <div class="kpi-top"><div class="kpi-icon kpi-icon-gray"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10a4 4 0 004 4h10a4 4 0 004-4v-2M13 12h8m0 0l-3-3m3 3l-3 3"/></svg></div></div>
          <div class="kpi-body"><div class="kpi-value" x-text="formatPrice(balance?.gross_online_collected ?? 0)"></div><div class="kpi-label">Encaissé en ligne</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top"><div class="kpi-icon kpi-icon-red"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div></div>
          <div class="kpi-body"><div class="kpi-value" style="color:#DC2626;" x-text="formatPrice(balance?.total_commission ?? 0)"></div><div class="kpi-label">Commission plateforme</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top"><div class="kpi-icon kpi-icon-amber"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div></div>
          <div class="kpi-body"><div class="kpi-value" style="color:#D97706;" x-text="formatPrice(balance?.reserved_or_paid ?? 0)"></div><div class="kpi-label">En attente / déjà retiré</div></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-top"><div class="kpi-icon kpi-icon-green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10a4 4 0 004 4h10a4 4 0 004-4v-2M13 12h8m0 0l-3-3m3 3l-3 3"/></svg></div></div>
          <div class="kpi-body"><div class="kpi-value" style="color:#16a34a;" x-text="formatPrice(balance?.available_balance ?? 0)"></div><div class="kpi-label">Solde disponible</div></div>
        </div>
      </div>

      <!-- Historique des demandes -->
      <div class="saas-card" style="padding:0;overflow:hidden;margin-bottom:24px;">
        <div style="padding:16px 20px;border-bottom:1px solid rgba(0,0,0,0.06);font-weight:700;color:#111827;">Historique de vos demandes</div>
        <div style="overflow-x:auto;">
          <table class="saas-table" style="width:100%;">
            <thead><tr><th>Date</th><th>Montant</th><th>Mobile Money</th><th>Statut</th></tr></thead>
            <tbody>
              <template x-for="r in requests" :key="r.id">
                <tr>
                  <td x-text="formatDate(r.requested_at)"></td>
                  <td style="font-weight:700;color:#111827;" x-text="formatPrice(r.amount)"></td>
                  <td><span x-text="operatorLabel(r.mobile_money_operator)"></span> · <span style="color:#9CA3AF;" x-text="r.mobile_money_number"></span></td>
                  <td><span :class="statusCfg(r.status).badge" x-text="statusCfg(r.status).label"></span></td>
                </tr>
              </template>
              <template x-if="requests.length === 0">
                <tr><td colspan="4" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucune demande de retrait pour le moment.</td></tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /contenu flouté -->

    <!-- Overlay upgrade (par-dessus le flou) -->
    <div x-show="!loading && upgradeRequired"
         style="position:absolute;inset:0;z-index:10;background:rgba(248,250,252,0.55);">
      <div style="display:flex;align-items:center;justify-content:center;min-height:100%;padding:24px;">
        <div style="background:white;border-radius:20px;padding:36px 40px;text-align:center;max-width:380px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,0.18);border:1px solid rgba(0,0,0,0.06);">
          <div style="width:64px;height:64px;border-radius:18px;background:#0F2B20;display:grid;place-items:center;margin:0 auto 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:30px;height:30px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a4 4 0 00-8 0v2M5 9h14l1 11H4L5 9z"/></svg>
          </div>
          <div style="display:inline-block;background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;margin-bottom:14px;letter-spacing:0.5px;">PLAN PRO REQUIS</div>
          <h2 style="margin:0 0 10px;font-size:20px;font-weight:800;color:#111827;">Retraits des paiements en ligne</h2>
          <p style="color:#6B7280;font-size:13px;margin:0 auto 24px;line-height:1.6;">Suivez votre solde et demandez le retrait des paiements en ligne encaissés pour votre établissement. Disponible à partir du plan <strong style="color:#1B4332;">Pro</strong>.</p>
          <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= $base_url ?>/saas/settings" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#1B4332;color:white;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Passer au Pro →</a>
            <a href="<?= $base_url ?>/saas" style="display:inline-flex;align-items:center;padding:10px 20px;background:white;color:#374151;border:1px solid #E5E7EB;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Tableau de bord</a>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /blur-gate wrapper -->

  <!-- Modal demande de retrait (hors du blur) -->
  <div x-cloak x-show="showModal" class="saas-modal-bg" @keydown.escape.window="showModal=false" @click.self="showModal=false">
    <div class="saas-modal" style="max-width:460px;" @click.stop>
      <div class="saas-modal-header">
        <h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Demander un retrait</h2>
        <button type="button" class="saas-modal-close" @click="showModal=false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <p style="margin:0 0 16px;font-size:13px;color:#6B7280;">Solde disponible : <strong style="color:#16a34a;" x-text="formatPrice(balance?.available_balance ?? 0)"></strong></p>
        <div style="display:grid;gap:14px;">
          <div>
            <label class="saas-label">Montant à retirer (FCFA)</label>
            <input type="number" min="1" class="saas-input" x-model.number="form.amount" placeholder="Ex : 25000" />
          </div>
          <div>
            <label class="saas-label">Opérateur Mobile Money</label>
            <select class="saas-input" x-model="form.mobile_money_operator">
              <option value="orange">Orange Money</option>
              <option value="wave">Wave</option>
              <option value="mtn">MTN Mobile Money</option>
            </select>
          </div>
          <div>
            <label class="saas-label">Numéro Mobile Money</label>
            <input type="tel" class="saas-input" x-model="form.mobile_money_number" placeholder="+225 07 00 00 00" />
          </div>
          <template x-if="formError"><div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;font-size:13px;" x-text="formError"></div></template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="submitRequest()" :disabled="submitting">
          <span x-show="!submitting">Envoyer la demande</span>
          <div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div>
        </button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="saas-toast" x-show="toast" style="display:grid;">
    <div class="toast-box" :class="toast?.type === 'error' ? 'error' : ''">
      <div style="font-size:14px;font-weight:600;" x-text="toast?.msg"></div>
    </div>
  </div>

</div>
