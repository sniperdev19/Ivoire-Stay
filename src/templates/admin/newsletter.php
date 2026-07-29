<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-newsletter'; ?>
<div x-data="adminNewsletterPage('<?= $base_url ?>')" x-init="init()">

  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Newsletter</h1>
    <p style="margin:6px 0 0;color:#9CA3AF;"><span x-text="subscriberCount"></span> abonné(s) actif(s) (formulaire footer de la vitrine)</p>
  </div>

  <div class="saas-card" style="max-width:640px;margin-bottom:24px;">
    <div style="margin-bottom:16px;">
      <label class="saas-label">Sujet</label>
      <input type="text" class="saas-input" x-model="form.subject" maxlength="200" placeholder="Ex : Les nouveautés Afristay de ce mois-ci">
    </div>
    <div style="margin-bottom:8px;">
      <label class="saas-label">Message</label>
      <textarea class="saas-input" rows="8" x-model="form.body" placeholder="Contenu de la campagne (texte brut, mis en forme automatiquement)"></textarea>
    </div>

    <template x-if="error">
      <div style="margin-top:6px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;font-size:13px;" x-text="error"></div>
    </template>
    <template x-if="success">
      <div style="margin-top:6px;background:rgba(22,163,74,0.06);border:1px solid rgba(22,163,74,0.12);padding:10px;border-radius:8px;color:#16A34A;font-size:13px;" x-text="success"></div>
    </template>

    <div style="margin-top:16px;display:flex;justify-content:flex-end;">
      <button type="button" class="btn-saas-primary" @click="send()" :disabled="sending || !form.subject.trim() || !form.body.trim()">
        <span x-show="!sending">Envoyer à <span x-text="subscriberCount"></span> abonné(s)</span>
        <span x-show="sending" style="display:inline-flex;align-items:center;gap:8px;">
          <div style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div>
          Envoi en cours…
        </span>
      </button>
    </div>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;font-weight:600;color:#111827;">Campagnes envoyées</div>
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead><tr><th>Sujet</th><th>Destinataires</th><th>Envoyée le</th></tr></thead>
        <tbody>
          <template x-for="c in campaigns" :key="c.id">
            <tr>
              <td style="font-weight:600;color:#111827;" x-text="c.subject"></td>
              <td x-text="c.recipient_count"></td>
              <td style="color:#9CA3AF;font-size:13px;" x-text="formatDateTime(c.sent_at)"></td>
            </tr>
          </template>
          <template x-if="!loading && campaigns.length === 0">
            <tr><td colspan="3" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucune campagne envoyée pour l'instant.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

</div>
