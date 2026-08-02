<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-agents'; ?>
<div x-data="adminAgentsPage('<?= $base_url ?>')" x-init="init()" @keydown.escape.window="rejectTarget=null">

  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Agents commerciaux</h1>
    <p style="margin:6px 0 0;color:#9CA3AF;">Agents référents, versements par lot de 5 abonnements et primes ponctuelles</p>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;margin-bottom:24px;">
    <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;font-weight:600;color:#111827;">Agents</div>
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead><tr><th>Nom</th><th>Numéro</th><th>Opérateur</th><th>Établissements rattachés</th><th>Inscrit le</th></tr></thead>
        <tbody>
          <template x-for="a in agents" :key="a.id">
            <tr>
              <td style="font-weight:600;color:#111827;" x-text="a.nom"></td>
              <td x-text="a.numero"></td>
              <td x-text="operatorLabel(a.operateur_money)"></td>
              <td x-text="a.establishment_count"></td>
              <td x-text="formatDate(a.created_at)"></td>
            </tr>
          </template>
          <template x-if="!loadingAgents && agents.length === 0">
            <tr><td colspan="5" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucun agent inscrit.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:12px;">
    <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Versements</h2>
    <select class="saas-input" style="width:auto;" x-model="filter" @change="loadPayouts()">
      <option value="pending">En attente</option>
      <option value="paid">Payés</option>
      <option value="rejected">Rejetés</option>
      <option value="">Tous</option>
    </select>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;margin-bottom:24px;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead><tr><th>Agent</th><th>Motif</th><th>Date</th><th>Montant</th><th>Mobile Money</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
          <template x-for="p in payouts" :key="p.id">
            <tr>
              <td style="font-weight:600;color:#111827;" x-text="p.agent_nom"></td>
              <td x-text="p.label || (p.plan === 'pro' ? 'Pro' : 'Business')"></td>
              <td x-text="formatDate(p.created_at)"></td>
              <td style="font-weight:700;color:#111827;" x-text="formatPrice(p.amount)"></td>
              <td><span x-text="operatorLabel(p.mobile_money_operator)"></span> · <span style="color:#9CA3AF;" x-text="p.mobile_money_number"></span></td>
              <td><span :class="statusCfg(p.status).badge" x-text="statusCfg(p.status).label"></span></td>
              <td style="white-space:nowrap;">
                <template x-if="p.status === 'pending'">
                  <span>
                    <button class="btn-saas-secondary" @click="markPaid(p.id)">Marquer payé</button>
                    <button class="btn-saas-danger" @click="openReject(p.id)">Rejeter</button>
                  </span>
                </template>
                <span x-show="p.status === 'rejected' && p.admin_notes" style="font-size:12px;color:#9CA3AF;" x-text="p.admin_notes"></span>
              </td>
            </tr>
          </template>
          <template x-if="!loadingPayouts && payouts.length === 0">
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucun versement.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <div style="margin-bottom:12px;">
    <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Primes décernées</h2>
    <p style="margin:4px 0 0;color:#9CA3AF;font-size:13px;">Premier arrivé, premier client Business, conversion rapide, top du mois.</p>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead><tr><th>Agent</th><th>Prime</th><th>Montant</th><th>Décernée le</th></tr></thead>
        <tbody>
          <template x-for="b in bonuses" :key="b.id">
            <tr>
              <td style="font-weight:600;color:#111827;" x-text="b.agent_nom"></td>
              <td x-text="bonusTypeLabel(b.type)"></td>
              <td style="font-weight:700;color:#111827;" x-text="formatPrice(b.amount)"></td>
              <td style="color:#9CA3AF;font-size:13px;" x-text="formatDate(b.awarded_at)"></td>
            </tr>
          </template>
          <template x-if="!loadingBonuses && bonuses.length === 0">
            <tr><td colspan="4" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucune prime décernée pour l'instant.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal rejet -->
  <div x-cloak x-show="rejectTarget !== null" class="saas-modal-bg" @keydown.escape.window="rejectTarget=null" @click.self="rejectTarget=null">
    <div class="saas-modal" style="max-width:420px;" @click.stop>
      <div class="saas-modal-header"><h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Rejeter le versement</h2></div>
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
