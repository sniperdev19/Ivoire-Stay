<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-owners'; ?>
<div x-data="adminOwnersPage('<?= $base_url ?>')" x-init="init()">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Propriétaires</h1>
      <p style="margin:6px 0 0;color:#9CA3AF;"><span x-text="owners.length"></span> compte(s) propriétaire sur la plateforme</p>
    </div>
    <input type="text" class="saas-input" style="max-width:280px;" placeholder="Rechercher (nom, email)…" x-model="search">
  </div>

  <div class="saas-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
      <table class="saas-table" style="width:100%;">
        <thead>
          <tr>
            <th>Propriétaire</th>
            <th>Contact</th>
            <th style="text-align:center;">Établissements</th>
            <th>Inscrit le</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="o in filteredOwners" :key="o.id">
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#6366F1,#4338CA);display:grid;place-items:center;color:white;font-size:12px;font-weight:700;flex-shrink:0;" x-text="initials(o.name)"></div>
                  <span style="font-weight:600;color:#111827;" x-text="o.name"></span>
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
          <template x-if="!loading && filteredOwners.length === 0">
            <tr><td colspan="4" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucun propriétaire trouvé.</td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

</div>
