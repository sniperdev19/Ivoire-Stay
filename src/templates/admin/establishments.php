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

  <!-- ═══ Occupation du mois en cours : totaux, carte par ville, top établissements ═══ -->
  <div x-show="!analyticsLoading" style="margin-bottom:20px;">
    <!-- .saas-kpi-grid (pas de grid-template-columns en style inline) : une règle globale de
         saas-responsive.css force à 1 colonne tout grid-template-columns inline sous 560px,
         donc un inline repeat(3,1fr) ici s'empilait plein écran au lieu de rester groupé. -->
    <div class="saas-kpi-grid">
      <div class="saas-card" style="padding:16px;border-top:3px solid #6366F1;">
        <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Établissements actifs</div>
        <div style="font-size:26px;font-weight:800;color:#111827;" x-text="analytics?.totals?.active_establishments ?? 0"></div>
      </div>
      <div class="saas-card" style="padding:16px;border-top:3px solid #256ABF;">
        <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Occupation moyenne (<span x-text="monthLabel(analytics?.month)"></span>)</div>
        <div style="font-size:26px;font-weight:800;color:#111827;" x-text="(analytics?.totals?.avg_occupancy ?? 0) + ' %'"></div>
      </div>
      <div class="saas-card" style="padding:16px;border-top:3px solid #16A34A;">
        <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Chiffre d'affaires du mois</div>
        <div style="font-size:20px;font-weight:800;color:#16A34A;" x-text="formatPrice(analytics?.totals?.total_revenue ?? 0)"></div>
      </div>
    </div>

    <div class="admin-analytics-grid">
      <div class="saas-card" style="padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <h3 style="font-size:13px;font-weight:700;color:#111827;margin:0;">Occupation par ville</h3>
          <div style="display:flex;align-items:center;gap:6px;font-size:10px;color:#9CA3AF;">
            <span>0%</span>
            <span style="display:flex;">
              <template x-for="c in occupancyLegendSteps" :key="c"><span :style="'width:14px;height:8px;background:' + c + ';'"></span></template>
            </span>
            <span>100%</span>
          </div>
        </div>
        <!-- Toujours affichée (vue par défaut Côte d'Ivoire) même sans établissement géolocalisé —
             buildMap() ajoute les marqueurs uniquement quand des coordonnées existent, cf. admin-establishments.js. -->
        <div id="admin-occupancy-map" style="height:360px;border-radius:12px;overflow:hidden;"></div>
        <template x-if="citiesWithGeo.length === 0">
          <p style="margin:10px 0 0;font-size:11px;color:#9CA3AF;">Aucun établissement géolocalisé pour le moment. La carte affiche la vue par défaut.</p>
        </template>
        <template x-if="citiesWithoutGeo.length > 0">
          <div style="margin-top:10px;font-size:11px;color:#9CA3AF;">
            Sans position sur la carte :
            <template x-for="c in citiesWithoutGeo" :key="c.city">
              <span style="display:inline-block;margin-right:8px;"><strong x-text="c.city"></strong> (<span x-text="c.occupancy"></span>%)</span>
            </template>
          </div>
        </template>
      </div>

      <div class="saas-card" style="padding:20px;">
        <h3 style="font-size:13px;font-weight:700;color:#111827;margin:0 0 14px;">Top établissements (occupation)</h3>
        <div style="display:grid;gap:10px;">
          <template x-for="(e, i) in (analytics?.top_establishments ?? [])" :key="e.id">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="flex-shrink:0;width:22px;height:22px;border-radius:6px;background:#F3F4F6;color:#6B7280;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;" x-text="i + 1"></div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="e.name"></div>
                <div style="font-size:11px;color:#9CA3AF;" x-text="e.city"></div>
              </div>
              <div style="flex-shrink:0;font-size:12px;font-weight:700;padding:3px 9px;border-radius:9999px;color:#fff;" :style="'background:' + occupancyColor(e.occupancy) + ';'" x-text="e.occupancy + '%'"></div>
            </div>
          </template>
          <div x-show="(analytics?.top_establishments ?? []).length === 0" style="color:#9CA3AF;font-size:13px;text-align:center;padding:20px 0;">
            Pas encore de données ce mois-ci.
          </div>
        </div>
      </div>
    </div>
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
                <span x-show="e.banned_at" class="admin-status-pill admin-status-inactive" style="margin-left:4px;">Banni</span>
              </td>
              <td style="color:#9CA3AF;font-size:13px;" x-text="formatDate(e.created_at)"></td>
              <td style="text-align:right;">
                <button type="button" class="btn-saas-secondary" :class="e.is_active ? '' : 'action-btn-success'"
                  @click="toggleActive(e)" :disabled="savingId === e.id">
                  <span x-text="e.is_active ? 'Désactiver' : 'Réactiver'"></span>
                </button>
                <button type="button" class="btn-saas-secondary" :class="e.banned_at ? 'action-btn-success' : ''" style="margin-left:6px;"
                  @click="toggleBan(e)" :disabled="savingId === e.id">
                  <span x-text="e.banned_at ? 'Débannir' : 'Bannir'"></span>
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
