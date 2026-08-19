<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageCss = 'saas-dashboard'; $pageJs = 'saas-dashboard'; ?>
<div x-data="dashboardPage('<?= $base_url ?>')"
 x-init="init()">

  <!-- En-tête du tableau de bord -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
    <div>
      <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 4px 0;">
        Bonjour,
        <span style="color:#C9A84C;" x-text="(JSON.parse(localStorage.getItem('user')||'{}').name||'').split(' ')[0]||'Hôtelier'"></span>
      </h1>
      <p style="font-size:13px;color:#9CA3AF;margin:0;">
        Voici le résumé de votre activité · <span x-text="stats?.month ?? 'Ce mois'"></span>
      </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="<?= $base_url ?>/saas/bookings" class="btn-saas-primary" style="display:inline-flex;align-items:center;gap:8px;padding:0 14px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nouvelle réservation
      </a>
      <a href="<?= $base_url ?>/saas/rooms" class="btn-saas-secondary" style="display:inline-flex;align-items:center;gap:8px;padding:0 14px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        Chambres
      </a>
    </div>
  </div>

  <!-- Skeleton de chargement -->
  <div x-show="loading">
    <div style="height:36px;width:260px;margin-bottom:24px;" class="db-shimmer"></div>
    <div class="db-kpi-grid" style="margin-bottom:20px;">
      <div class="db-shimmer" style="height:120px;"></div>
      <div class="db-shimmer" style="height:120px;"></div>
      <div class="db-shimmer" style="height:120px;"></div>
      <div class="db-shimmer" style="height:120px;"></div>
    </div>
    <div class="main-grid">
      <div class="db-shimmer" style="height:280px;"></div>
      <div class="db-shimmer" style="height:280px;"></div>
    </div>
  </div>

  <!-- Erreur / pas d'établissement -->
  <div x-show="!loading && error"
    style="padding:20px 24px;background:rgba(220,38,38,0.04);border:1px solid rgba(220,38,38,0.12);border-radius:14px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div style="color:#B91C1C;font-size:14px;" x-text="error"></div>
    <a href="<?= $base_url ?>/saas/settings"
      style="display:inline-block;padding:8px 16px;background:#1B4332;color:white;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
      Configurer l'établissement →
    </a>
  </div>

  <!-- Contenu principal -->
  <div x-show="!loading">

    <!-- KPI cards -->
    <div class="db-kpi-grid">

      <div class="db-kpi-card">
        <div class="db-kpi-top">
          <div class="db-kpi-icon db-kpi-icon-green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </div>
        </div>
        <div class="db-kpi-body">
          <div class="db-kpi-value" x-text="formatPrice(stats?.revenue ?? 0)"></div>
          <div class="db-kpi-label">Revenus ce mois</div>
        </div>
      </div>

      <div class="db-kpi-card">
        <div class="db-kpi-top">
          <div class="db-kpi-icon db-kpi-icon-gray">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 18v-11a2 2 0 012-2h4a2 2 0 012 2v11M4 13h16M20 18v-5a2 2 0 00-2-2h-6M4 18h16M4 21v-3M20 21v-3"/></svg>
          </div>
          <div class="db-kpi-pct" x-text="occupancyPct + '%' "></div>
        </div>
        <div class="db-kpi-body">
          <div class="db-kpi-label">Taux d'occupation</div>
          <div class="db-kpi-progress"><div class="db-kpi-progress-bar" :style="'width:' + occupancyPct + '%'"></div></div>
        </div>
      </div>

      <div class="db-kpi-card">
        <div class="db-kpi-top">
          <div class="db-kpi-icon db-kpi-icon-green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5a2 2 0 012-2h2a2 2 0 012 2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
          </div>
          <div class="db-kpi-badge">Actives</div>
        </div>
        <div class="db-kpi-body">
          <div class="db-kpi-value" x-text="stats?.active_bookings ?? 0"></div>
          <div class="db-kpi-label">Réservations actives</div>
        </div>
      </div>

      <div class="db-kpi-card">
        <div class="db-kpi-top">
          <div class="db-kpi-icon db-kpi-icon-purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h1m4-4h1m-1 4h1"/></svg>
          </div>
        </div>
        <div class="db-kpi-body">
          <div class="db-kpi-value"><span x-text="stats?.available_rooms ?? 0"></span><small> / <span x-text="stats?.total_rooms ?? 0"></span></small></div>
          <div class="db-kpi-label">Chambres disponibles</div>
        </div>
      </div>

    </div>

    <div class="main-grid">
      <div style="padding:0;overflow:hidden;background:#ffffff;border:1px solid rgba(0,0,0,0.06);border-radius:24px;box-shadow:0 14px 40px rgba(15,23,42,0.05);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 18px 12px;">
          <div>
            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 2px 0;">Réservations récentes</h3>
            <p style="font-size:11px;color:#9CA3AF;margin:0;">Dernières activités enregistrées</p>
          </div>
          <a href="<?= $base_url ?>/saas/bookings" class="btn-saas-secondary" style="font-size:12px;padding:6px 12px;">Voir tout →</a>
        </div>

        <div x-show="recentBookings.length === 0" style="padding:32px;text-align:center;color:#9CA3AF;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:32px;height:32px;margin:0 auto 8px;display:block;color:#E5E7EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            Aucune réservation récente
        </div>

        <div x-show="recentBookings.length > 0" class="db-recent-list">
          <template x-for="b in recentBookings" :key="b.id">
            <div class="db-recent-row">
              <div :style="avatarStyle(b.status)" x-text="(b.client_name||'?').charAt(0).toUpperCase()"></div>
              <div class="db-recent-info">
                <div class="db-recent-name" x-text="b.client_name ?? '-'"></div>
                <div class="db-recent-meta" x-text="'Ch. ' + (b.room_number ?? '-') + ' · ' + formatDate(b.check_in)"></div>
              </div>
              <div class="db-recent-right">
                <div class="db-recent-amount" x-text="formatPrice(b.total_amount)"></div>
                <span :style="statusStyle(b.status)" x-text="statusLabel(b.status)"></span>
              </div>
            </div>
          </template>
        </div>

      </div>

      <div class="saas-card db-section-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <div>
            <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0 0 2px 0;">Occupation par type</h3>
            <p style="font-size:11px;color:#9CA3AF;margin:0;">Distribution actuelle</p>
          </div>
          <a href="<?= $base_url ?>/saas/rooms" style="font-size:12px;color:#C9A84C;font-weight:500;text-decoration:none;">Gérer →</a>
        </div>

        <div style="position:relative;text-align:center;margin-bottom:14px;">
          <svg viewBox="0 0 120 120" style="width:84px;height:84px;transform:rotate(-90deg);display:block;margin:0 auto;"><circle cx="60" cy="60" r="46" fill="none" stroke="#F3F4F6" stroke-width="12"/><circle cx="60" cy="60" r="46" fill="none" stroke="#C9A84C" stroke-width="12" stroke-linecap="round" stroke-dasharray="289.03" :stroke-dashoffset="289.03 - (289.03 * occupancyPct / 100)" style="transition:stroke-dashoffset 1.2s ease;"/></svg>
          <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;"><div style="font-size:17px;font-weight:800;color:#111827;" x-text="occupancyPct + '%' "></div><div style="font-size:9.5px;color:#9CA3AF;">Occupation</div></div>
        </div>

        <!-- Aucun type de chambre configuré -->
        <div x-show="typeDistribution.length === 0" style="font-size:13px;color:#9CA3AF;text-align:center;padding:12px 0;">
          Aucun type de chambre configuré.
        </div>
        <div x-show="typeDistribution.length > 0" style="display:flex;flex-direction:column;gap:8px;">
          <template x-for="t in typeDistribution" :key="t.label">
            <div style="display:flex;align-items:center;gap:8px;">
              <div :style="'width:8px;height:8px;border-radius:2px;background:' + t.color + ';flex-shrink:0;'"></div>
              <div style="font-size:12px;color:#374151;flex:1;" x-text="t.label"></div>
              <div style="font-size:12px;font-weight:600;color:#111827;min-width:36px;text-align:right;" x-text="t.count + ' ch.'"></div>
              <div style="font-size:11px;color:#6B7280;min-width:30px;text-align:right;" x-text="t.pct + '%'"></div>
              <div style="width:60px;height:5px;background:rgba(0,0,0,0.06);border-radius:3px;overflow:hidden;flex-shrink:0;">
                <div :style="'height:100%;width:' + t.pct + '%;border-radius:3px;background:' + t.color + ';transition:width 0.8s ease;'"></div>
              </div>
            </div>
          </template>
          <div style="margin-top:8px;padding-top:8px;border-top:1px solid rgba(0,0,0,0.06);font-size:11px;color:#6B7280;">
            Total chambres : <strong style="color:#111827;" x-text="stats?.total_rooms ?? 0"></strong>
            &nbsp;·&nbsp; Occupées : <strong style="color:#1B4332;" x-text="occupiedRooms"></strong>
          </div>
        </div>
      </div>
    </div>

    <div class="saas-card db-section-card" x-show="canSeeFinance" style="max-width:520px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <h3 style="font-size:14px;font-weight:700;color:#111827;margin:0;">Résumé financier</h3>
        <a href="<?= $base_url ?>/saas/invoices" style="font-size:12px;color:#C9A84C;font-weight:500;text-decoration:none;">Facturation →</a>
      </div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div class="db-fin-row"><span class="db-fin-label">Revenus</span><span class="db-fin-value" x-text="formatPrice(stats?.revenue ?? 0)"></span></div>
        <div class="db-fin-row db-fin-green"><span class="db-fin-label">Paiements reçus</span><span class="db-fin-value" style="color:#16A34A;" x-text="formatPrice(stats?.payments_received ?? 0)"></span></div>
        <template x-if="(stats?.payments_cancelled ?? 0) > 0">
          <div style="display:flex;justify-content:space-between;align-items:center;padding:0 2px;"><span style="font-size:12px;color:#6B7280;">Annulé</span><span style="font-size:13px;font-weight:600;color:#9CA3AF;text-decoration:line-through;" x-text="formatPrice(stats?.payments_cancelled ?? 0)"></span></div>
        </template>
        <div class="db-fin-row db-fin-amber"><span class="db-fin-label">Solde en attente</span><span class="db-fin-value" style="color:#D97706;" x-text="formatPrice(stats?.payments_pending ?? 0)"></span></div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0 2px;"><span style="font-size:12px;color:#6B7280;">Dépenses</span><span style="font-size:13px;font-weight:600;color:#DC2626;" x-text="formatPrice(stats?.expenses ?? 0)"></span></div>
        <div style="border-top:1px solid rgba(0,0,0,0.06);padding-top:8px;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:13px;font-weight:700;color:#111827;">Bénéfice net</span>
          <span :style="(stats?.net_profit ?? 0) >=0 ? 'color:#16A34A;font-size:14px;font-weight:800;' : 'color:#DC2626;font-size:14px;font-weight:800;'" x-text="formatPrice(stats?.net_profit ?? 0)"></span>
        </div>
      </div>
    </div>
  </div>
</div>
