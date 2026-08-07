<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Rapports & Analyses — template injecté via $content -->

<?php $pageJs = 'saas-reports'; $pageCss = 'saas-reports'; ?>
<div x-data="reportsPage('<?= $base_url ?>')"
 x-init="init()" @keydown.escape.window="loading=false">

  <!-- Blur-gate wrapper -->
  <div style="position:relative;min-height:520px;">

    <!-- Contenu (flouté si plan insuffisant) -->
    <div :style="upgradeRequired ? 'filter:blur(6px);pointer-events:none;user-select:none;' : ''">

      <!-- En-tête -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap;">
        <div>
          <h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Rapports &amp; Analyses</h1>
          <p style="margin:6px 0 0;color:#9CA3AF;">Vue synthétique financière et opérationnelle</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <div style="display:flex;background:white;border-radius:10px;padding:3px;">
            <button @click="changePeriod('month')" :style="{ background: period==='month'?'#1B4332':'transparent', color: period==='month'?'white':'#6B7280' }" class="btn-saas-secondary" style="padding:6px 10px;border-radius:8px;">Mois</button>
            <button @click="changePeriod('year')" :style="{ background: period==='year'?'#1B4332':'transparent', color: period==='year'?'white':'#6B7280' }" class="btn-saas-secondary" style="padding:6px 10px;border-radius:8px;">Année</button>
          </div>
          <button class="btn-saas-secondary" @click="exportPdf()" :disabled="pdfLoading">
            <span x-show="!pdfLoading">Exporter PDF</span>
            <span x-show="pdfLoading">Génération…</span>
          </button>
        </div>
      </div>

      <!-- Sélecteur de portée — uniquement si l'utilisateur a plusieurs établissements (plan Business) -->
      <div x-show="hasMultipleEstablishments" style="display:flex;background:white;border-radius:10px;padding:3px;margin-bottom:14px;width:fit-content;">
        <button @click="setViewMode('single')" :style="{ background: viewMode==='single'?'#1B4332':'transparent', color: viewMode==='single'?'white':'#6B7280' }" class="btn-saas-secondary" style="padding:6px 12px;border-radius:8px;">Cet établissement</button>
        <button @click="setViewMode('all')" :style="{ background: viewMode==='all'?'#1B4332':'transparent', color: viewMode==='all'?'white':'#6B7280' }" class="btn-saas-secondary" style="padding:6px 12px;border-radius:8px;">Tous les établissements</button>
        <button @click="setViewMode('compare')" :style="{ background: viewMode==='compare'?'#1B4332':'transparent', color: viewMode==='compare'?'white':'#6B7280' }" class="btn-saas-secondary" style="padding:6px 12px;border-radius:8px;">Comparatif</button>
      </div>

      <!-- Erreur -->
      <div x-show="error" style="margin-bottom:12px;padding:12px;border-radius:10px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);color:#DC2626;" x-text="error"></div>

      <!-- ══ VUE COMPARATIVE ══ -->
      <template x-if="viewMode === 'compare'">
        <div class="saas-card">
          <h3 class="report-section-title"><span class="report-section-dot"></span>Comparatif entre établissements</h3>
          <div style="overflow-x:auto;">
            <table class="saas-table" style="width:100%;">
              <thead><tr><th>Établissement</th><th>Revenus</th><th>Dépenses</th><th>Bénéfice net</th><th>Occupation</th></tr></thead>
              <tbody>
                <template x-if="compareRows.length === 0">
                  <tr><td colspan="5" style="text-align:center;color:#9CA3AF;padding:24px;">Aucune donnée pour cette période.</td></tr>
                </template>
                <template x-for="row in compareRows" :key="row.establishment_id">
                  <tr>
                    <td style="font-weight:600;" x-text="row.name"></td>
                    <td x-text="formatPrice(row.revenue)"></td>
                    <td x-text="formatPrice(row.expenses)"></td>
                    <td :style="{ color: row.net_profit >= 0 ? '#16a34a' : '#DC2626', 'font-weight': '700' }" x-text="formatPrice(row.net_profit)"></td>
                    <td x-text="row.occupancy_rate + ' %'"></td>
                  </tr>
                </template>
              </tbody>
              <tfoot x-show="compareRows.length > 0">
                <tr style="font-weight:800;border-top:2px solid rgba(0,0,0,0.08);">
                  <td>TOTAL</td>
                  <td x-text="formatPrice(compareTotals.revenue)"></td>
                  <td x-text="formatPrice(compareTotals.expenses)"></td>
                  <td :style="{ color: compareTotals.net >= 0 ? '#16a34a' : '#DC2626' }" x-text="formatPrice(compareTotals.net)"></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </template>

      <!-- ══ VUE SIMPLE / COMBINÉE ══ -->
      <template x-if="viewMode !== 'compare'">
      <div>
      <!-- ROW 1: 5 KPI -->
      <div class="saas-kpi-grid">
        <div class="saas-card report-kpi">
          <div class="report-kpi-icon" style="background:rgba(201,168,76,0.12);color:#C9A84C;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-8"/></svg>
          </div>
          <div class="report-kpi-body">
            <p class="report-kpi-label" x-text="'CA ' + periodLabel"></p>
            <p class="report-kpi-value" x-text="formatPrice(revenue)"></p>
          </div>
        </div>

        <div class="saas-card report-kpi">
          <div class="report-kpi-icon" style="background:rgba(220,38,38,0.1);color:#DC2626;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 18l-9.5-9.5-5 5L1 6M17 18h6v-6"/></svg>
          </div>
          <div class="report-kpi-body">
            <p class="report-kpi-label">Dépenses</p>
            <p class="report-kpi-value" x-text="formatPrice(expTotal)"></p>
          </div>
        </div>

        <div class="saas-card report-kpi">
          <div class="report-kpi-icon" :style="{ background: netProfit>=0?'rgba(22,163,74,0.1)':'rgba(220,38,38,0.1)', color: netProfit>=0?'#16a34a':'#DC2626' }">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          </div>
          <div class="report-kpi-body">
            <p class="report-kpi-label">Bénéfice net</p>
            <p class="report-kpi-value" :style="{ color: netProfit>=0?'#16a34a':'#DC2626' }" x-text="formatPrice(netProfit)"></p>
          </div>
        </div>

        <div class="saas-card report-kpi">
          <div class="report-kpi-icon" style="background:rgba(22,163,74,0.1);color:#16a34a;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
          </div>
          <div class="report-kpi-body">
            <p class="report-kpi-label">Encaissé</p>
            <p class="report-kpi-value" x-text="formatPrice(paidInv)"></p>
          </div>
        </div>

        <div class="saas-card">
          <div class="report-kpi" style="margin-bottom:12px;">
            <div class="report-kpi-icon" style="background:rgba(37,99,235,0.1);color:#2563EB;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="19" y1="5" x2="5" y2="19" stroke-width="2" stroke-linecap="round"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
            </div>
            <div class="report-kpi-body">
              <p class="report-kpi-label">Taux d'occupation</p>
              <p class="report-kpi-value" x-text="occupancy + '%'"></p>
            </div>
          </div>
          <div style="height:8px;background:#F3F4F6;border-radius:6px;overflow:hidden;"><div :style="'height:100%;width:'+occupancy+'%;background:#2563EB;'"></div></div>
        </div>
      </div>

      <!-- ROW 2: left distribution, right summary -->
      <div style="display:grid;grid-template-columns:60% 40%;gap:12px;margin-bottom:12px;">
        <div class="saas-card">
          <h3 class="report-section-title"><span class="report-section-dot"></span>Dépenses par catégorie</h3>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <template x-for="item in expByCategory" :key="item.cat">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="min-width:110px;display:flex;align-items:center;gap:8px;"><div style="width:10px;height:10px;border-radius:4px;" :style="{ background: catColor(item.cat) }"></div><div style="font-size:13px;color:#111827;" x-text="catLabel(item.cat)"></div></div>
                <div style="flex:1;background:#F3F4F6;border-radius:6px;height:8px;overflow:hidden;margin-right:8px;"><div :style="'height:100%;width:'+item.pct+'%;background:'+catColor(item.cat)+';'"></div></div>
                <div style="font-weight:700;color:#111827;min-width:110px;text-align:right;" x-text="formatPrice(item.amt)"></div>
                <div style="width:48px;text-align:right;color:#9CA3AF;font-size:11px;" x-text="item.pct+'%' "></div>
              </div>
            </template>
          </div>
          <div style="border-top:1px solid rgba(0,0,0,0.06);margin-top:12px;padding-top:12px;display:flex;justify-content:space-between;align-items:center;"><div style="color:#9CA3AF;">Total dépenses</div><div style="font-weight:800;color:#1B4332;" x-text="formatPrice(expTotal)"></div></div>
        </div>

        <div class="saas-card">
          <h3 class="report-section-title"><span class="report-section-dot"></span>Bilan <span x-text="periodLabel"></span></h3>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div><div style="font-size:12px;color:#9CA3AF;">Revenus bruts</div><div style="font-weight:800;color:#16a34a;" x-text="formatPrice(revenue)"></div></div>
            <div><div style="font-size:12px;color:#9CA3AF;">Factures payées</div><div style="font-weight:700;color:#16a34a;" x-text="formatPrice(paidInv)"></div></div>
            <div><div style="font-size:12px;color:#9CA3AF;">En attente encaissement</div><div style="font-weight:700;color:#D97706;" x-text="formatPrice(pendingPay)"></div></div>
            <div style="border-top:1px solid rgba(0,0,0,0.06);padding-top:10px;"><div style="font-size:12px;color:#9CA3AF;">Total dépenses</div><div style="font-weight:800;color:#DC2626;" x-text="formatPrice(expTotal)"></div>
              <div style="margin-top:8px;">
                <template x-for="(c,idx) in expByCategory.slice(0,3)" :key="c.cat"><div style="display:flex;justify-content:space-between;font-size:13px;margin-top:6px;"><div><span style="font-weight:700;color:#111827;" x-text="catLabel(c.cat)"></span></div><div style="font-weight:700;color:#111827;" x-text="formatPrice(c.amt)"></div></div></template>
              </div>
            </div>

            <div style="border-top:1px solid rgba(0,0,0,0.06);padding-top:12px;">
              <div class="report-net-card" :style="{ background: netProfit>=0?'rgba(22,163,74,0.08)':'rgba(220,38,38,0.06)' }">
                <div>
                  <p class="report-net-label">Résultat net</p>
                  <p class="report-net-value" :style="{ color: netProfit>=0?'#16a34a':'#DC2626' }" x-text="formatPrice(netProfit)"></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" style="width:26px;height:26px;flex-shrink:0;" :style="{ color: netProfit>=0?'#16a34a':'#DC2626' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path x-show="netProfit>=0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                  <path x-show="netProfit<0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
              </div>
            </div>

            <a href="<?= $base_url ?>/saas/invoices" class="btn-saas-secondary" style="justify-self:end;">Voir les factures →</a>
          </div>
        </div>
      </div>

      <!-- ROW 3: Paiements récents -->
      <div class="saas-card">
        <h3 class="report-section-title"><span class="report-section-dot"></span>Paiements reçus</h3>
        <div style="overflow-x:auto;">
          <table class="saas-table" style="width:100%;">
            <thead><tr><th>Référence</th><th>Client</th><th>Montant</th><th>Méthode</th><th>Date</th></tr></thead>
            <tbody>
              <template x-for="p in payments" :key="p.id">
                <tr>
                  <td x-text="p.reference || '—'"></td>
                  <td x-text="p.client_name"></td>
                  <td style="font-weight:800;" x-text="formatPrice(p.amount)"></td>
                  <td x-text="p.method"></td>
                  <td x-text="new Date(p.paid_at).toLocaleDateString('fr-FR')"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
      </div>
      </template>
      <!-- /vue simple / combinée -->

    </div><!-- /contenu flouté -->

    <!-- Overlay upgrade (par-dessus le flou) -->
    <div x-show="!loading && upgradeRequired"
         style="position:absolute;inset:0;z-index:10;background:rgba(248,250,252,0.55);">
      <div style="display:flex;align-items:center;justify-content:center;min-height:100%;padding:24px;">
        <div style="background:white;border-radius:20px;padding:36px 40px;text-align:center;max-width:380px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,0.18);border:1px solid rgba(0,0,0,0.06);">
          <div style="width:64px;height:64px;border-radius:18px;background:#0F2B20;display:grid;place-items:center;margin:0 auto 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:30px;height:30px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div style="display:inline-block;background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;margin-bottom:14px;letter-spacing:0.5px;">PLAN PRO REQUIS</div>
          <h2 style="margin:0 0 10px;font-size:20px;font-weight:800;color:#111827;">Rapports &amp; Analyses</h2>
          <p style="color:#6B7280;font-size:13px;margin:0 auto 24px;line-height:1.6;">Visualisez vos revenus, dépenses et taux d'occupation. Exportez vos rapports financiers en PDF. Disponible à partir du plan <strong style="color:#1B4332;">Pro</strong>.</p>
          <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= $base_url ?>/saas/settings" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#1B4332;color:white;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Passer au Pro →</a>
            <a href="<?= $base_url ?>/saas" style="display:inline-flex;align-items:center;padding:10px 20px;background:white;color:#374151;border:1px solid #E5E7EB;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Tableau de bord</a>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /blur-gate wrapper -->

</div>
