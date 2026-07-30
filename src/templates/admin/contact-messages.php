<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-contact-messages'; ?>
<div x-data="adminContactMessagesPage('<?= $base_url ?>')" x-init="init()">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Messages de contact</h1>
      <p style="margin:6px 0 0;color:#9CA3AF;"><span x-text="messages.length"></span> message(s) reçu(s) via le formulaire /contact</p>
    </div>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead>
          <tr>
            <th>Statut</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Sujet</th>
            <th>Reçu le</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="m in messages" :key="m.id">
            <tr style="cursor:pointer;" @click="openMessage(m)">
              <td>
                <span class="badge" :class="m.read_at ? 'badge-info' : 'badge-warning'" x-text="m.read_at ? 'Lu' : 'Nouveau'"></span>
              </td>
              <td style="font-weight:600;color:#111827;" x-text="m.name"></td>
              <td x-text="m.email"></td>
              <td x-text="m.subject || '—'"></td>
              <td style="color:#9CA3AF;font-size:13px;" x-text="formatDateTime(m.created_at)"></td>
            </tr>
          </template>
          <template x-if="!loading && messages.length === 0">
            <tr><td colspan="5" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucun message reçu pour l'instant.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ═══ Modal détail message ═══ -->
  <div x-show="selected" x-cloak class="saas-modal-bg" @click.self="selected=null">
    <div class="saas-modal" style="max-width:520px;">
      <div class="saas-modal-header">
        <h2 style="font-size:16px;font-weight:700;margin:0;" x-text="selected?.subject || 'Message de contact'"></h2>
        <button type="button" class="saas-modal-close" @click="selected=null"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div class="modal-card" style="margin-bottom:14px;">
          <div class="modal-list-item"><span>Nom</span><strong x-text="selected?.name"></strong></div>
          <div class="modal-list-item"><span>Email</span><strong x-text="selected?.email"></strong></div>
          <div class="modal-list-item" x-show="selected?.phone"><span>Téléphone</span><strong x-text="selected?.phone"></strong></div>
          <div class="modal-list-item"><span>Reçu le</span><strong x-text="formatDateTime(selected?.created_at)"></strong></div>
        </div>
        <div style="background:#F9FAFB;border-radius:10px;padding:16px;font-size:13px;color:#374151;line-height:1.7;white-space:pre-wrap;" x-text="selected?.message"></div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-danger" @click="deleteMessage(selected)">Supprimer</button>
        <a class="btn-saas-secondary" :href="'mailto:' + selected?.email" style="text-decoration:none;">Répondre par email</a>
      </div>
    </div>
  </div>

</div>
