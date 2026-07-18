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
      <div style="display:grid;gap:7px;">
        <div style="display:flex;justify-content:space-between;font-size:13px;">
          <span style="color:#6B7280;">Starter</span>
          <strong style="color:#111827;" x-text="overview?.plan_breakdown?.starter ?? 0"></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;">
          <span style="color:#2563EB;">Pro</span>
          <strong style="color:#111827;" x-text="overview?.plan_breakdown?.pro ?? 0"></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;">
          <span style="color:#6366F1;">Business</span>
          <strong style="color:#111827;" x-text="overview?.plan_breakdown?.business ?? 0"></strong>
        </div>
      </div>
    </div>
  </div>

</div>
