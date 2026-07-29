<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-backups'; ?>
<div x-data="adminBackupsPage('<?= $base_url ?>')" x-init="init()">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Sauvegardes</h1>
      <p style="margin:6px 0 0;color:#9CA3AF;">Dumps quotidiens de la base de données — 7 jours glissants</p>
    </div>
    <button type="button" class="btn-saas-primary" @click="createBackup()" :disabled="creating">
      <span x-show="!creating">+ Sauvegarder maintenant</span>
      <span x-show="creating" style="display:inline-flex;align-items:center;gap:8px;">
        <div style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div>
        Sauvegarde en cours…
      </span>
    </button>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead><tr><th>Date</th><th>Taille</th><th>Fichier</th><th>Actions</th></tr></thead>
        <tbody>
          <template x-for="b in backups" :key="b.filename">
            <tr>
              <td style="font-weight:600;color:#111827;" x-text="formatDateTime(b.created_at)"></td>
              <td x-text="formatSize(b.size)"></td>
              <td style="color:#9CA3AF;font-size:12px;" x-text="b.filename"></td>
              <td>
                <button class="btn-saas-secondary" @click="downloadBackup(b)">Télécharger</button>
              </td>
            </tr>
          </template>
          <template x-if="!loading && backups.length === 0">
            <tr><td colspan="4" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucune sauvegarde pour l'instant.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Toast -->
  <div class="saas-toast" x-show="toast" style="display:grid;">
    <div class="toast-box" :class="toast?.type === 'error' ? 'error' : ''">
      <div style="font-size:14px;font-weight:600;" x-text="toast?.msg"></div>
    </div>
  </div>

</div>
