<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-establishments'; ?>
<div x-data="adminEstablishmentsPage('<?= $base_url ?>')" x-init="init()">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Établissements</h1>
      <p style="margin:6px 0 0;color:#9CA3AF;"><span x-text="establishments.length"></span> établissement(s) sur la plateforme</p>
    </div>
    <input type="text" class="saas-input" style="max-width:280px;" placeholder="Rechercher (nom, ville, propriétaire)…" x-model="search">
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead>
          <tr>
            <th>Établissement</th>
            <th>Propriétaire</th>
            <th>Ville</th>
            <th>Plan</th>
            <th>Statut</th>
            <th>Créé le</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="e in filteredEstablishments" :key="e.id">
            <tr>
              <td style="font-weight:600;color:#111827;" x-text="e.name"></td>
              <td>
                <div x-text="e.owner_name ?? '—'"></div>
                <div style="font-size:12px;color:#9CA3AF;" x-text="e.owner_email ?? ''"></div>
              </td>
              <td x-text="e.city ?? '—'"></td>
              <td>
                <select class="saas-input" style="width:auto;padding:6px 10px;font-size:12px;"
                  :value="e.plan" @change="changePlan(e, $event.target.value)" :disabled="savingId === e.id">
                  <option value="starter">Starter</option>
                  <option value="pro">Pro</option>
                  <option value="business">Business</option>
                </select>
              </td>
              <td>
                <span class="admin-status-pill" :class="e.is_active ? 'admin-status-active' : 'admin-status-inactive'">
                  <span x-text="e.is_active ? 'Actif' : 'Désactivé'"></span>
                </span>
              </td>
              <td style="color:#9CA3AF;font-size:13px;" x-text="formatDate(e.created_at)"></td>
              <td style="text-align:right;">
                <button type="button" class="btn-saas-secondary" :class="e.is_active ? '' : 'action-btn-success'"
                  @click="toggleActive(e)" :disabled="savingId === e.id">
                  <span x-text="e.is_active ? 'Désactiver' : 'Réactiver'"></span>
                </button>
              </td>
            </tr>
          </template>
          <template x-if="!loading && filteredEstablishments.length === 0">
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucun établissement trouvé.</td></tr>
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
