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
    <div class="kpi-grid" style="margin-bottom:20px;">
      <div class="db-shimmer" style="height:100px;"></div>
      <div class="db-shimmer" style="height:100px;"></div>
      <div class="db-shimmer" style="height:100px;"></div>
      <div class="db-shimmer" style="height:100px;"></div>
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
    <div class="kpi-grid">

      <div class="kpi-card kpi-gold">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
          <div style="width:42px;height:42px;border-radius:10px;background:rgba(201,168,76,0.12);display:grid;place-items:center;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </div>
          <div style="background:#DCFCE7;color:#16A34A;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:700;">+12%</div>
        </div>
        <div style="font-size:22px;font-weight:800;color:#111827;line-height:1;margin-bottom:4px;" x-text="formatPrice(stats?.revenue ?? 0)"></div>
        <div style="font-size:12px;color:#6B7280;">Revenus ce mois</div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(0,0,0,0.05);font-size:12px;color:#9CA3AF;">
          Reçus : <span style="color:#16A34A;font-weight:600;" x-text="formatPrice(stats?.payments_received ?? 0)"></span>
        </div>
      </div>

      <div class="kpi-card kpi-blue">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;">
          <div style="width:42px;height:42px;border-radius:10px;background:rgba(37,99,235,0.1);display:grid;place-items:center;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;color:#2563EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V9a2 2 0 012-2h2a2 2 0 012 2v10"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19h14"/></svg>
          </div>
          <div style="font-size:22px;font-weight:800;color:#111827;" x-text="occupancyPct + '%' "></div>
        </div>
        <div style="height:6px;background:rgba(0,0,0,0.06);border-radius:6px;overflow:hidden;margin-bottom:8px;">
          <div :style="'width:' + occupancyPct + '%;height:100%;background:linear-gradient(90deg,#2563EB,#60A5FA);transition:width 1s ease;' "></div>
        </div>
        <div style="font-size:12px;color:#6B7280;">Taux d'occupation</div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(0,0,0,0.05);font-size:12px;color:#9CA3AF;">
          <span style="color:#2563EB;font-weight:600;" x-text="stats?.total_rooms ?? 0"></span> chambres au total
        </div>
      </div>

      <div class="kpi-card kpi-green">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
          <div style="width:42px;height:42px;border-radius:10px;background:rgba(22,163,74,0.1);display:grid;place-items:center;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;color:#16A34A;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5a2 2 0 012-2h2a2 2 0 012 2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
          </div>
          <div style="background:#DCFCE7;color:#16A34A;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:600;">Actives</div>
        </div>
        <div style="font-size:28px;font-weight:800;color:#111827;line-height:1;margin-bottom:4px;" x-text="stats?.active_bookings ?? 0"></div>
        <div style="font-size:12px;color:#6B7280;">Réservations actives</div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(0,0,0,0.05);font-size:12px;color:#9CA3AF;">
          En attente : <span style="color:#D97706;font-weight:600;" x-text="(planning||[]).filter(p=>p.status==='pending').length"></span>
        </div>
      </div>

      <div class="kpi-card kpi-purple">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
          <div style="width:42px;height:42px;border-radius:10px;background:rgba(124,58,237,0.1);display:grid;place-items:center;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;color:#7C3AED;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h1m4-4h1m-1 4h1"/></svg>
          </div>
          <div style="font-size:22px;font-weight:800;color:#111827;"><span x-text="stats?.available_rooms ?? 0"></span><span style="font-size:14px;color:#9CA3AF;font-weight:400;"> / <span x-text="stats?.total_rooms ?? 0"></span></span></div>
        </div>
        <div style="font-size:12px;color:#6B7280;margin-bottom:10px;">Chambres disponibles</div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(0,0,0,0.05);font-size:12px;color:#9CA3AF;">
          Bénéfice net : <span :style="(stats?.net_profit ?? 0) >= 0 ? 'color:#16A34A':'color:#DC2626'" style="font-weight:600;" x-text="formatPrice(stats?.net_profit ?? 0)"></span>
        </div>
      </div>

    </div>

    <div class="main-grid">
      <div style="padding:0;overflow:hidden;background:#ffffff;border:1px solid rgba(0,0,0,0.06);border-radius:24px;box-shadow:0 14px 40px rgba(15,23,42,0.05);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;">
          <div>
            <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 2px 0;">Réservations récentes</h3>
            <p style="font-size:12px;color:#9CA3AF;margin:0;">Dernières activités enregistrées</p>
          </div>
          <a href="<?= $base_url ?>/saas/bookings" class="btn-saas-secondary" style="font-size:13px;padding:7px 14px;">Voir tout →</a>
        </div>

        <div x-show="(planning||[]).length === 0" style="padding:32px;text-align:center;color:#9CA3AF;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:32px;height:32px;margin:0 auto 8px;display:block;color:#E5E7EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            Aucune réservation récente
        </div>

        <div x-show="(planning||[]).length > 0" style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;">
            <thead style="text-align:left;color:rgba(0,0,0,0.65);font-size:13px;">
              <tr>
                <th style="padding:10px 20px;border-bottom:1px solid rgba(0,0,0,0.05);">Client</th>
                <th style="padding:10px 16px;border-bottom:1px solid rgba(0,0,0,0.05);">Chambre</th>
                <th style="padding:10px 16px;border-bottom:1px solid rgba(0,0,0,0.05);">Check-in</th>
                <th style="padding:10px 20px;text-align:right;border-bottom:1px solid rgba(0,0,0,0.05);">Montant</th>
                <th style="padding:10px 16px;border-bottom:1px solid rgba(0,0,0,0.05);">Statut</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="b in planning" :key="b.id">
                <tr style="border-top:1px solid rgba(0,0,0,0.06);">
                  <td style="padding:13px 20px;"><div style="display:flex;align-items:center;gap:10px;"><div style="width:30px;height:30px;border-radius:8px;background:rgba(201,168,76,0.12);display:grid;place-items:center;font-size:12px;font-weight:700;color:#C9A84C;" x-text="(b.client_name||'?').charAt(0).toUpperCase()"></div><span style="font-size:14px;font-weight:500;color:#111827;" x-text="b.client_name ?? '-'"></span></div></td>
                  <td style="padding:13px 16px;font-size:13px;color:#4B5563;" x-text="b.room_name ?? '-'"></td>
                  <td style="padding:13px 16px;font-size:13px;color:#4B5563;" x-text="formatDate(b.checkin ?? b.check_in)"></td>
                  <td style="padding:13px 20px;font-size:13px;font-weight:600;color:#111827;text-align:right;" x-text="formatPrice(b.amount ?? b.total_price)"></td>
                  <td style="padding:13px 16px;"><span :style="statusStyle(b.status)" x-text="statusLabel(b.status)"></span></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

      </div>

      <div class="saas-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
          <div>
            <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 2px 0;">Occupation par type</h3>
            <p style="font-size:12px;color:#9CA3AF;margin:0;">Distribution actuelle</p>
          </div>
          <a href="<?= $base_url ?>/saas/rooms" style="font-size:12px;color:#C9A84C;font-weight:500;text-decoration:none;">Gérer →</a>
        </div>

        <div style="position:relative;text-align:center;margin-bottom:24px;">
          <svg viewBox="0 0 120 120" style="width:110px;height:110px;transform:rotate(-90deg);display:block;margin:0 auto;"><circle cx="60" cy="60" r="46" fill="none" stroke="#F3F4F6" stroke-width="12"/><circle cx="60" cy="60" r="46" fill="none" stroke="#C9A84C" stroke-width="12" stroke-linecap="round" stroke-dasharray="289.03" :stroke-dashoffset="289.03 - (289.03 * occupancyPct / 100)" style="transition:stroke-dashoffset 1.2s ease;"/></svg>
          <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;"><div style="font-size:22px;font-weight:800;color:#111827;" x-text="occupancyPct + '%' "></div><div style="font-size:11px;color:#9CA3AF;">Occupation</div></div>
        </div>

        <!-- Aucun type de chambre configuré -->
        <div x-show="typeDistribution.length === 0" style="font-size:13px;color:#9CA3AF;text-align:center;padding:12px 0;">
          Aucun type de chambre configuré.
        </div>
        <div x-show="typeDistribution.length > 0" style="display:flex;flex-direction:column;gap:12px;">
          <template x-for="t in typeDistribution" :key="t.label">
            <div style="display:flex;align-items:center;gap:10px;">
              <div :style="'width:8px;height:8px;border-radius:2px;background:' + t.color + ';flex-shrink:0;'"></div>
              <div style="font-size:13px;color:#374151;flex:1;" x-text="t.label"></div>
              <div style="font-size:13px;font-weight:600;color:#111827;min-width:40px;text-align:right;" x-text="t.count + ' ch.'"></div>
              <div style="font-size:12px;color:#6B7280;min-width:32px;text-align:right;" x-text="t.pct + '%'"></div>
              <div style="width:72px;height:5px;background:rgba(0,0,0,0.06);border-radius:3px;overflow:hidden;flex-shrink:0;">
                <div :style="'height:100%;width:' + t.pct + '%;border-radius:3px;background:' + t.color + ';transition:width 0.8s ease;'"></div>
              </div>
            </div>
          </template>
          <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(0,0,0,0.06);font-size:12px;color:#6B7280;">
            Total chambres : <strong style="color:#111827;" x-text="stats?.total_rooms ?? 0"></strong>
            &nbsp;·&nbsp; Occupées : <strong style="color:#1B4332;" x-text="occupiedRooms"></strong>
          </div>
        </div>
      </div>
    </div>

    <div class="bottom-grid">
      <div class="saas-card">
        <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 16px 0;">Actions rapides</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <?php
            $actions = [
              ['label'=>'Nouvelle réservation','icon'=>'M12 4v16m8-8H4','href'=>'/saas/bookings','color'=>'#C9A84C','bg'=>'rgba(201,168,76,0.08)'],
              ['label'=>'Ajouter chambre','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5','href'=>'/saas/rooms','color'=>'#2563EB','bg'=>'rgba(37,99,235,0.08)'],
              ['label'=>'Voir le planning','icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','href'=>'/saas/planning','color'=>'#16A34A','bg'=>'rgba(22,163,74,0.08)'],
              ['label'=>'Rapports','icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','href'=>'/saas/reports','color'=>'#7C3AED','bg'=>'rgba(124,58,237,0.08)'],
            ];
          ?>
          <?php foreach($actions as $a): ?>
            <a href="<?= $base_url . $a['href'] ?>" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:16px 10px;background:#F9FAFB;border:1px solid rgba(0,0,0,0.06);border-radius:12px;text-decoration:none;transition:all 0.2s;text-align:center;" onmouseover="this.style.background='rgba(201,168,76,0.06)';this.style.borderColor='rgba(201,168,76,0.25)'" onmouseout="this.style.background='#F9FAFB';this.style.borderColor='rgba(0,0,0,0.06)'">
              <div style="width:38px;height:38px;border-radius:10px;background:<?= $a['bg'] ?>;display:grid;place-items:center;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;color:<?= $a['color'] ?>;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $a['icon'] ?>"/></svg>
              </div>
              <span style="font-size:12px;font-weight:500;color:#374151;"><?= htmlspecialchars($a['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="saas-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Résumé financier</h3>
          <a href="<?= $base_url ?>/saas/invoices" style="font-size:12px;color:#C9A84C;font-weight:500;text-decoration:none;">Facturation →</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:13px;color:#6B7280;">Revenus</span><span style="font-size:14px;font-weight:600;color:#111827;" x-text="formatPrice(stats?.revenue ?? 0)"></span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:13px;color:#6B7280;">Paiements reçus</span><span style="font-size:14px;font-weight:600;color:#16A34A;" x-text="formatPrice(stats?.payments_received ?? 0)"></span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:13px;color:#6B7280;">En attente</span><span style="font-size:14px;font-weight:600;color:#D97706;" x-text="formatPrice(stats?.payments_pending ?? 0)"></span></div>
          <div style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:13px;color:#6B7280;">Dépenses</span><span style="font-size:14px;font-weight:600;color:#DC2626;" x-text="formatPrice(stats?.expenses ?? 0)"></span></div>
          <div style="border-top:1px solid rgba(0,0,0,0.06);padding-top:12px;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:14px;font-weight:700;color:#111827;">Bénéfice net</span>
            <span :style="(stats?.net_profit ?? 0) >=0 ? 'color:#16A34A;font-size:16px;font-weight:800;' : 'color:#DC2626;font-size:16px;font-weight:800;'" x-text="formatPrice(stats?.net_profit ?? 0)"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
