<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-notifications'; ?>
<div x-data="adminNotificationsPage('<?= $base_url ?>')" x-init="init()">

  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Notifications</h1>
    <p style="margin:6px 0 0;color:#9CA3AF;">Envoyer une annonce à tous les propriétaires de la plateforme</p>
  </div>

  <div class="saas-card" style="max-width:560px;">
    <div style="margin-bottom:16px;">
      <label class="saas-label">Titre</label>
      <input type="text" class="saas-input" x-model="form.title" maxlength="150" placeholder="Ex : Maintenance programmée">
    </div>
    <div style="margin-bottom:8px;">
      <label class="saas-label">Message</label>
      <textarea class="saas-input" rows="5" x-model="form.message" maxlength="1000" placeholder="Détails de l'annonce (optionnel)"></textarea>
      <p style="margin:6px 0 0;font-size:11px;color:#9CA3AF;text-align:right;" x-text="form.message.length + ' / 1000'"></p>
    </div>

    <template x-if="error">
      <div style="margin-top:6px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;font-size:13px;" x-text="error"></div>
    </template>
    <template x-if="success">
      <div style="margin-top:6px;background:rgba(22,163,74,0.06);border:1px solid rgba(22,163,74,0.12);padding:10px;border-radius:8px;color:#16A34A;font-size:13px;" x-text="success"></div>
    </template>

    <div style="margin-top:16px;display:flex;justify-content:flex-end;">
      <button type="button" class="btn-saas-primary" @click="send()" :disabled="sending || !form.title.trim()">
        <span x-show="!sending">Envoyer à tous les propriétaires</span>
        <span x-show="sending" style="display:inline-flex;align-items:center;gap:8px;">
          <div style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div>
          Envoi en cours…
        </span>
      </button>
    </div>
  </div>

</div>
