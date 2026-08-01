<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-dashboard'; ?>
<div x-data="adminDashboardPage('<?= $base_url ?>')" x-init="init()">

  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Vue d'ensemble</h1>
    <p style="margin:6px 0 0;color:#9CA3AF;">Statistiques globales de la plateforme AfriStay</p>
  </div>

  <div x-show="loading" style="color:#9CA3AF;font-size:13px;">Chargement…</div>

  <div x-show="!loading" class="admin-kpi-grid">
    <div class="saas-card" style="padding:16px;border-top:3px solid #6366F1;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Établissements</div>
      <div style="font-size:26px;font-weight:800;color:#111827;" x-text="overview?.total_establishments ?? 0"></div>
    </div>

    <div class="saas-card" style="padding:16px;border-top:3px solid #16A34A;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Revenu mensuel récurrent (estimé)</div>
      <div style="font-size:20px;font-weight:800;color:#16A34A;" x-text="formatPrice(overview?.estimated_mrr ?? 0)"></div>
    </div>

    <div class="saas-card" style="padding:16px;border-top:3px solid #D97706;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Réservations (plateforme)</div>
      <div style="font-size:26px;font-weight:800;color:#111827;" x-text="overview?.total_bookings ?? 0"></div>
    </div>

    <div class="saas-card" style="padding:16px;border-top:3px solid #2563EB;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Répartition par plan</div>
      <div style="display:grid;gap:10px;">
        <template x-for="p in planRows" :key="p.key">
          <div>
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
              <span style="color:#374151;font-weight:500;" x-text="p.label"></span>
              <strong style="color:#111827;" x-text="p.value"></strong>
            </div>
            <div style="height:6px;border-radius:99px;background:#F3F4F6;overflow:hidden;">
              <div :style="'height:100%;border-radius:99px;background:' + p.color + ';width:' + planBarPct(p.value) + '%;'"
                :title="p.label + ' : ' + p.value + ' établissement(s)'"></div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- ═══ Tendances (6 derniers mois) ═══ -->
  <div x-show="!loading" class="admin-trend-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
    <div class="saas-card" style="padding:20px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:13px;font-weight:700;color:#111827;margin:0;">Réservations par mois</h3>
        <button type="button" @click="showBookingsTable = !showBookingsTable" style="font-size:11px;color:#6366F1;background:none;border:none;cursor:pointer;font-weight:600;">
          <span x-text="showBookingsTable ? 'Voir le graphique' : 'Voir en tableau'"></span>
        </button>
      </div>

      <div x-show="!showBookingsTable" class="chart-cols">
        <template x-for="(b, i) in (overview?.bookings_by_month ?? [])" :key="i">
          <div class="chart-col">
            <span class="chart-col-value" x-text="b.count"></span>
            <div class="chart-col-track">
              <div class="chart-bar-hover" :style="'width:100%;max-width:24px;border-radius:4px 4px 0 0;background:#2a78d6;transition:opacity .15s;height:' + barHeightPct(b.count, maxOf(overview?.bookings_by_month)) + '%;'"
                tabindex="0"
                :title="monthLabel(b.month) + ' : ' + b.count + ' réservation(s)'"></div>
            </div>
            <span class="chart-col-month" x-text="monthLabel(b.month)"></span>
          </div>
        </template>
      </div>

      <table x-show="showBookingsTable" class="saas-table" style="width:100%;">
        <thead><tr><th>Mois</th><th style="text-align:right;">Réservations</th></tr></thead>
        <tbody>
          <template x-for="(b, i) in (overview?.bookings_by_month ?? [])" :key="i">
            <tr><td x-text="monthLabel(b.month)"></td><td style="text-align:right;font-weight:600;" x-text="b.count"></td></tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="saas-card" style="padding:20px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 style="font-size:13px;font-weight:700;color:#111827;margin:0;">Nouveaux établissements par mois</h3>
        <button type="button" @click="showEstabTable = !showEstabTable" style="font-size:11px;color:#6366F1;background:none;border:none;cursor:pointer;font-weight:600;">
          <span x-text="showEstabTable ? 'Voir le graphique' : 'Voir en tableau'"></span>
        </button>
      </div>

      <div x-show="!showEstabTable" class="chart-cols">
        <template x-for="(e, i) in (overview?.establishments_by_month ?? [])" :key="i">
          <div class="chart-col">
            <span class="chart-col-value" x-text="e.count"></span>
            <div class="chart-col-track">
              <div class="chart-bar-hover" :style="'width:100%;max-width:24px;border-radius:4px 4px 0 0;background:#4a3aa7;transition:opacity .15s;height:' + barHeightPct(e.count, maxOf(overview?.establishments_by_month)) + '%;'"
                tabindex="0"
                :title="monthLabel(e.month) + ' : ' + e.count + ' établissement(s)'"></div>
            </div>
            <span class="chart-col-month" x-text="monthLabel(e.month)"></span>
          </div>
        </template>
      </div>

      <table x-show="showEstabTable" class="saas-table" style="width:100%;">
        <thead><tr><th>Mois</th><th style="text-align:right;">Nouveaux établissements</th></tr></thead>
        <tbody>
          <template x-for="(e, i) in (overview?.establishments_by_month ?? [])" :key="i">
            <tr><td x-text="monthLabel(e.month)"></td><td style="text-align:right;font-weight:600;" x-text="e.count"></td></tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

</div>
