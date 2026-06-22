<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Rapports & Analyses — template injecté via $content -->

<?php $pageJs = 'saas-reports'; ?>
<div x-data="reportsPage('<?= $base_url ?>')"
 x-init="init()" @keydown.escape.window="loading=false">

  <!-- Blur-gate wrapper -->
  <div style="position:relative;min-height:520px;">

    <!-- Contenu (flouté si plan insuffisant) -->
    <div :style="upgradeRequired ? 'filter:blur(6px);pointer-events:none;user-select:none;' : ''">

      <!-- En-tête -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;">
        <div>
          <h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Rapports &amp; Analyses</h1>
          <p style="margin:6px 0 0;color:#9CA3AF;">Vue synthétique financière et opérationnelle</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <div style="display:flex;background:white;border-radius:10px;padding:3px;">
            <button @click="changePeriod('month')" :style="period==='month'?'background:#1B4332;color:white;':'background:transparent;color:#6B7280;'" class="btn-saas-secondary" style="padding:6px 10px;border-radius:8px;">Mois</button>
            <button @click="changePeriod('year')" :style="period==='year'?'background:#1B4332;color:white;':'background:transparent;color:#6B7280;'" class="btn-saas-secondary" style="padding:6px 10px;border-radius:8px;">Année</button>
          </div>
          <button class="btn-saas-secondary" @click="window.print()">Exporter PDF</button>
        </div>
      </div>

      <!-- Erreur -->
      <div x-show="error" style="margin-bottom:12px;padding:12px;border-radius:10px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);color:#DC2626;" x-text="error"></div>

      <!-- ROW 1: 5 KPI -->
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:12px;">
        <div class="saas-card"><div style="display:flex;align-items:center;gap:10px;"><svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-8"/></svg><div><div style="font-size:13px;color:#9CA3AF;">CA {{period}}</div><div style="font-size:18px;font-weight:800;color:#111827;" x-text="formatPrice(revenue)"></div></div></div></div>

        <div class="saas-card"><div style="display:flex;align-items:center;gap:10px;"><svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;color:#DC2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zM12 14v6"/></svg><div><div style="font-size:13px;color:#9CA3AF;">Dépenses</div><div style="font-size:18px;font-weight:800;color:#111827;" x-text="formatPrice(expTotal)"></div></div></div></div>

        <div class="saas-card"><div style="display:flex;align-items:center;gap:10px;"><svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;color:#6B7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg><div><div style="font-size:13px;color:#9CA3AF;">Bénéfice net</div><div :style="netProfit>=0? 'color:#16a34a;font-weight:800;':'color:#DC2626;font-weight:800;'" style="font-size:18px;" x-text="formatPrice(netProfit)"></div></div></div></div>

        <div class="saas-card"><div style="display:flex;align-items:center;gap:10px;"><svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg><div><div style="font-size:13px;color:#9CA3AF;">Encaissé</div><div style="font-size:18px;font-weight:800;color:#111827;" x-text="formatPrice(paidInv)"></div></div></div></div>

        <div class="saas-card"><div style="display:flex;flex-direction:column;gap:8px;"><div style="display:flex;align-items:center;gap:10px;"><svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;color:#2563EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/></svg><div><div style="font-size:13px;color:#9CA3AF;">Taux d'occupation</div><div style="font-size:18px;font-weight:800;color:#111827;" x-text="occupancy + '%' "></div></div></div><div style="height:8px;background:#F3F4F6;border-radius:6px;overflow:hidden;"><div :style="'height:100%;width:'+occupancy+'%;background:#2563EB;'"></div></div></div></div>
      </div>

      <!-- ROW 2: left distribution, right summary -->
      <div style="display:grid;grid-template-columns:60% 40%;gap:12px;margin-bottom:12px;">
        <div class="saas-card">
          <h3 style="margin:0 0 8px;font-size:16px;">Dépenses par catégorie</h3>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <template x-for="item in expByCategory" :key="item.cat">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="min-width:110px;display:flex;align-items:center;gap:8px;"><div style="width:10px;height:10px;border-radius:4px;" :style="'background:'+catColor(item.cat)"></div><div style="font-size:13px;color:#111827;" x-text="catLabel(item.cat)"></div></div>
                <div style="flex:1;background:#F3F4F6;border-radius:6px;height:8px;overflow:hidden;margin-right:8px;"><div :style="'height:100%;width:'+item.pct+'%;background:'+catColor(item.cat)+';'"></div></div>
                <div style="font-weight:700;color:#111827;min-width:110px;text-align:right;" x-text="formatPrice(item.amt)"></div>
                <div style="width:48px;text-align:right;color:#9CA3AF;font-size:11px;" x-text="item.pct+'%' "></div>
              </div>
            </template>
          </div>
          <div style="border-top:1px solid rgba(0,0,0,0.06);margin-top:12px;padding-top:12px;display:flex;justify-content:space-between;align-items:center;"><div style="color:#9CA3AF;">Total dépenses</div><div style="font-weight:800;color:#1B4332;" x-text="formatPrice(expTotal)"></div></div>
        </div>

        <div class="saas-card">
          <h3 style="margin:0 0 8px;font-size:16px;">Bilan "<span x-text="period"></span>"</h3>
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
              <div :style="netProfit>=0? 'background:rgba(16,185,129,0.06);padding:12px;border-radius:8px;':'background:rgba(220,38,38,0.06);padding:12px;border-radius:8px;'">
                <div style="font-size:13px;color:#6B7280;">Résultat net</div>
                <div style="font-size:20px;font-weight:800;" x-text="formatPrice(netProfit)"></div>
              </div>
            </div>

            <a href="<?= $base_url ?>/saas/invoices" class="btn-saas-secondary" style="justify-self:end;">Voir les factures →</a>
          </div>
        </div>
      </div>

      <!-- ROW 3: Paiements récents -->
      <div class="saas-card">
        <h3 style="margin:0 0 12px;font-size:16px;">Paiements reçus</h3>
        <div style="overflow-x:auto;">
          <table class="saas-table" style="width:100%;">
            <thead><tr><th>Référence</th><th>Client</th><th>Montant</th><th>Méthode</th><th>Date</th></tr></thead>
            <tbody>
              <template x-for="p in payments.filter(x=>x.status==='confirmed').slice(0,5)" :key="p.id">
                <tr>
                  <td x-text="p.reference"></td>
                  <td x-text="p.client_name"></td>
                  <td style="font-weight:800;" x-text="formatPrice(p.amount)"></td>
                  <td x-text="p.method"></td>
                  <td x-text="new Date(p.created_at).toLocaleDateString('fr-FR')"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

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
