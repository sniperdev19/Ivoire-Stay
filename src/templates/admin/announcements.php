<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-announcements'; ?>
<div x-data="adminAnnouncementsPage('<?= $base_url ?>')" x-init="init()">

  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Annonces vitrine</h1>
    <p style="margin:6px 0 0;color:#9CA3AF;">Bandeau affiché en haut de la vitrine publique — une seule annonce active à la fois est affichée.</p>
  </div>

  <div class="saas-card" style="max-width:640px;margin-bottom:24px;">
    <div style="margin-bottom:16px;">
      <label class="saas-label">Titre</label>
      <input type="text" class="saas-input" x-model="form.title" maxlength="200" placeholder="Ex : Nouveau : réservez en 2 clics">
    </div>
    <div style="margin-bottom:8px;">
      <label class="saas-label">Message</label>
      <textarea class="saas-input" rows="3" x-model="form.message" placeholder="Détail affiché dans le bandeau"></textarea>
    </div>

    <template x-if="error">
      <div style="margin-top:6px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;font-size:13px;" x-text="error"></div>
    </template>

    <div style="margin-top:16px;display:flex;justify-content:flex-end;">
      <button type="button" class="btn-saas-primary" @click="create()" :disabled="creating || !form.title.trim() || !form.message.trim()">
        <span x-show="!creating">+ Créer et activer</span>
        <span x-show="creating">Création…</span>
      </button>
    </div>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead><tr><th>Statut</th><th>Titre</th><th>Message</th><th>Créée le</th><th style="text-align:right;">Actions</th></tr></thead>
        <tbody>
          <template x-for="a in announcements" :key="a.id">
            <tr>
              <td>
                <span class="admin-status-pill" :class="a.is_active == 1 ? 'admin-status-active' : 'admin-status-inactive'">
                  <span x-text="a.is_active == 1 ? 'Active' : 'Inactive'"></span>
                </span>
              </td>
              <td style="font-weight:600;color:#111827;" x-text="a.title"></td>
              <td style="max-width:320px;color:#6B7280;font-size:13px;" x-text="a.message"></td>
              <td style="color:#9CA3AF;font-size:13px;" x-text="formatDate(a.created_at)"></td>
              <td style="text-align:right;">
                <button type="button" class="btn-saas-secondary" :class="a.is_active == 1 ? '' : 'action-btn-success'" @click="toggleActive(a)">
                  <span x-text="a.is_active == 1 ? 'Désactiver' : 'Activer'"></span>
                </button>
                <button type="button" class="btn-saas-danger" style="margin-left:6px;" @click="deleteAnnouncement(a)">Supprimer</button>
              </td>
            </tr>
          </template>
          <template x-if="!loading && announcements.length === 0">
            <tr><td colspan="5" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucune annonce pour l'instant.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

</div>
