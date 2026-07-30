<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-payouts'; ?>
<div x-data="adminPayoutsPage('<?= $base_url ?>')" x-init="init()" @keydown.escape.window="rejectTarget=null">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Retraits</h1>
      <p style="margin:6px 0 0;color:#9CA3AF;">Demandes de retrait des paiements en ligne, tous établissements</p>
    </div>
    <select class="saas-input" style="width:auto;" x-model="filter" @change="loadRequests()">
      <option value="pending">En attente</option>
      <option value="paid">Payées</option>
      <option value="rejected">Rejetées</option>
      <option value="">Toutes</option>
    </select>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead><tr><th>Établissement</th><th>Date</th><th>Montant</th><th>Mobile Money</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
          <template x-for="r in requests" :key="r.id">
            <tr>
              <td style="font-weight:600;color:#111827;" x-text="r.establishment_name"></td>
              <td x-text="formatDate(r.requested_at)"></td>
              <td style="font-weight:700;color:#111827;" x-text="formatPrice(r.amount)"></td>
              <td><span x-text="operatorLabel(r.mobile_money_operator)"></span> · <span style="color:#9CA3AF;" x-text="r.mobile_money_number"></span></td>
              <td><span :class="statusCfg(r.status).badge" x-text="statusCfg(r.status).label"></span></td>
              <td style="white-space:nowrap;">
                <template x-if="r.status === 'pending'">
                  <span>
                    <button class="btn-saas-secondary" @click="markPaid(r.id)">Marquer payé</button>
                    <button class="btn-saas-danger" @click="openReject(r.id)">Rejeter</button>
                  </span>
                </template>
                <span x-show="r.status === 'rejected' && r.admin_notes" style="font-size:12px;color:#9CA3AF;" x-text="r.admin_notes"></span>
              </td>
            </tr>
          </template>
          <template x-if="!loading && requests.length === 0">
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucune demande.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal rejet -->
  <div x-cloak x-show="rejectTarget !== null" class="saas-modal-bg" @keydown.escape.window="rejectTarget=null" @click.self="rejectTarget=null">
    <div class="saas-modal" style="max-width:420px;" @click.stop>
      <div class="saas-modal-header"><h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Rejeter la demande</h2></div>
      <div class="saas-modal-body">
        <label class="saas-label">Motif du rejet</label>
        <textarea class="saas-input" rows="3" x-model="rejectNotes" placeholder="Ex : numéro Mobile Money invalide, à recontacter…"></textarea>
        <template x-if="rejectError"><div style="margin-top:10px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;font-size:13px;" x-text="rejectError"></div></template>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="rejectTarget=null">Annuler</button>
        <button class="btn-saas-danger" @click="confirmReject()" :disabled="rejectSubmitting">
          <span x-show="!rejectSubmitting">Rejeter</span>
          <span x-show="rejectSubmitting">Envoi…</span>
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
