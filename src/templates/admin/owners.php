<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-owners'; ?>
<div x-data="adminOwnersPage('<?= $base_url ?>')" x-init="init()">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Propriétaires</h1>
      <p style="margin:6px 0 0;color:#9CA3AF;"><span x-text="owners.length"></span> compte(s) propriétaire sur la plateforme</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <input type="text" class="saas-input" style="max-width:280px;" placeholder="Rechercher (nom, email)…" x-model="search" @input="ownersPage = 1">
      <button type="button" class="btn-saas-secondary" @click="exportCsv()">Exporter (CSV)</button>
    </div>
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead>
          <tr>
            <th>Propriétaire</th>
            <th>Contact</th>
            <th style="text-align:center;cursor:pointer;user-select:none;" @click="toggleSort('establishment_count')">
              Établissements <span x-text="sortIndicator('establishment_count')"></span>
            </th>
            <th style="cursor:pointer;user-select:none;" @click="toggleSort('created_at')">
              Inscrit le <span x-text="sortIndicator('created_at')"></span>
            </th>
          </tr>
        </thead>
        <tbody>
          <template x-for="o in pagedOwners" :key="o.id">
            <tr style="cursor:pointer;" @click="openOwner(o)">
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#6366F1,#4338CA);display:grid;place-items:center;color:white;font-size:12px;font-weight:700;flex-shrink:0;" x-text="initials(o.name)"></div>
                  <div>
                    <div style="display:flex;align-items:center;gap:6px;">
                      <span style="font-weight:600;color:#111827;" x-text="o.name"></span>
                      <span x-show="o.suspended_at" class="admin-status-pill admin-status-inactive">Suspendu</span>
                    </div>
                    <div x-show="!o.email_verified_at" style="font-size:11px;color:#D97706;font-weight:600;">Email non vérifié</div>
                  </div>
                </div>
              </td>
              <td>
                <div x-text="o.email"></div>
                <div style="font-size:12px;color:#9CA3AF;" x-text="o.phone ?? ''"></div>
              </td>
              <td style="text-align:center;font-weight:700;color:#111827;" x-text="o.establishment_count"></td>
              <td style="color:#9CA3AF;font-size:13px;" x-text="formatDate(o.created_at)"></td>
            </tr>
          </template>
          <template x-if="!loading && sortedOwners.length === 0">
            <tr><td colspan="4" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucun propriétaire trouvé.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
    <div x-show="ownersPageCount > 1" class="admin-pagination">
      <button type="button" class="btn-saas-secondary" :disabled="ownersPage <= 1" @click="ownersPage--">&larr;</button>
      <span>Page <strong x-text="ownersPage"></strong> / <span x-text="ownersPageCount"></span></span>
      <button type="button" class="btn-saas-secondary" :disabled="ownersPage >= ownersPageCount" @click="ownersPage++">&rarr;</button>
    </div>
  </div>

  <!-- ═══ Modal détail propriétaire ═══ -->
  <div x-show="selected" x-cloak class="saas-modal-bg" @click.self="selected=null">
    <div class="saas-modal" style="max-width:520px;">
      <div class="saas-modal-header">
        <h2 style="font-size:16px;font-weight:700;margin:0;" x-text="selected?.name"></h2>
        <button type="button" class="saas-modal-close" @click="selected=null"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div class="modal-card" style="margin-bottom:14px;">
          <div class="modal-list-item"><span>Email</span><strong x-text="selected?.email"></strong></div>
          <div class="modal-list-item"><span>Statut email</span>
            <strong :style="selected?.email_verified_at ? 'color:#16A34A;' : 'color:#D97706;'" x-text="selected?.email_verified_at ? 'Vérifié' : 'Non vérifié'"></strong>
          </div>
          <div class="modal-list-item" x-show="selected?.phone"><span>Téléphone</span><strong x-text="selected?.phone"></strong></div>
          <div class="modal-list-item"><span>Inscrit le</span><strong x-text="formatDate(selected?.created_at)"></strong></div>
          <div class="modal-list-item"><span>Statut du compte</span>
            <strong :style="selected?.suspended_at ? 'color:#DC2626;' : 'color:#16A34A;'" x-text="selected?.suspended_at ? 'Suspendu' : 'Actif'"></strong>
          </div>
        </div>

        <div style="display:flex;gap:8px;margin-bottom:16px;">
          <a class="btn-saas-secondary" style="flex:1;text-align:center;text-decoration:none;" :href="'mailto:' + selected?.email">Envoyer un email</a>
          <a x-show="selected?.phone" class="btn-saas-secondary" style="flex:1;text-align:center;text-decoration:none;" :href="'tel:' + selected?.phone">Appeler</a>
        </div>

        <div style="display:flex;margin-bottom:16px;">
          <button type="button" class="btn-saas-danger" style="flex:1;font-size:13px;"
                  :class="selected?.suspended_at ? 'action-btn-success' : ''"
                  @click="toggleSuspend(selected)" :disabled="suspendingId === selected?.id">
            <span x-text="selected?.suspended_at ? 'Réactiver ce compte' : 'Suspendre ce compte'"></span>
          </button>
        </div>

        <h3 style="font-size:13px;font-weight:700;color:#111827;margin:0 0 10px;">
          Établissements (<span x-text="ownerEstablishments.length"></span>)
        </h3>
        <div x-show="establishmentsLoading" style="color:#9CA3AF;font-size:13px;">Chargement…</div>
        <div style="display:grid;gap:8px;">
          <template x-for="e in ownerEstablishments" :key="e.id">
            <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#F9FAFB;border-radius:10px;">
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="e.name"></div>
                <div style="font-size:12px;color:#9CA3AF;" x-text="e.city || 'Ville non renseignée'"></div>
              </div>
              <span class="admin-plan-pill" :class="'admin-plan-' + e.plan" x-text="planLabel(e.plan)"></span>
              <span class="admin-status-pill" :class="e.is_active ? 'admin-status-active' : 'admin-status-inactive'" x-text="e.is_active ? 'Actif' : 'Désactivé'"></span>
            </div>
          </template>
          <div x-show="!establishmentsLoading && ownerEstablishments.length === 0" style="color:#9CA3AF;font-size:13px;text-align:center;padding:16px 0;">
            Aucun établissement pour ce propriétaire.
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
