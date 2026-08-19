/* ============================================================
   Afristay — Page SaaS : Rapports (src/templates/saas/reports.php)
   ============================================================ */

function reportsPage(baseUrl) {
  return {
  ...saasHelpers,

  summary: null,
  compareRows: [],
  loading: true, error: null, upgradeRequired: false,
  period: 'month',
  viewMode: 'single', // 'single' | 'all' | 'compare' — 'all'/'compare' seulement si plusieurs établissements
  pdfLoading: false,

  get myEstablishments() {
    try { return JSON.parse(localStorage.getItem('establishments') || '[]'); } catch (e) { return []; }
  },
  get hasMultipleEstablishments() { return this.myEstablishments.length > 1; },

  async init() {
    if (planUpgradeRequired('reports')) { this.upgradeRequired = true; this.loading = false; return; }
    await this.loadExpenseCategoryColors(baseUrl);
    await this.loadData();
  },

  async setViewMode(mode) {
    if (this.viewMode === mode) return;
    this.viewMode = mode;
    await this.loadData();
  },

  async loadData() {
    this.loading = true; this.error = null;
    try {
      let url;
      if (this.viewMode === 'compare') {
        url = baseUrl + '/api/reports/compare?period=' + this.period;
      } else {
        url = baseUrl + '/api/reports/summary?period=' + this.period
          + (this.viewMode === 'all' ? '&scope=all' : '&establishment_id=' + this.estId());
      }
      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.upgrade_required) { this.upgradeRequired = true; return; }
      if (data.success) {
        if (this.viewMode === 'compare') {
          this.compareRows = data.data?.establishments ?? [];
          this.summary = null;
        } else {
          this.summary = data.data;
          this.compareRows = [];
        }
      } else {
        this.error = data.message ?? 'Impossible de charger les rapports.';
      }
    } catch(e) {
      this.error = 'Impossible de charger les rapports.';
    } finally {
      this.loading = false;
    }
  },

  async changePeriod(p) { this.period = p; await this.loadData(); },

  get periodLabel() { return this.period === 'year' ? 'cette année' : 'ce mois'; },

  async exportPdf() {
    if (this.pdfLoading) return;
    this.pdfLoading = true;
    this.error = null;
    try {
      let url = baseUrl + '/api/reports/pdf?period=' + this.period + '&mode=' + this.viewMode;
      if (this.viewMode === 'single') url += '&establishment_id=' + this.estId();

      const res = await fetch(url, { headers: this.apiHeaders() });
      if (!res.ok) { this.error = 'Erreur lors de la génération du PDF.'; return; }

      const blob   = await res.blob();
      const objUrl = URL.createObjectURL(blob);
      const a      = document.createElement('a');
      a.href       = objUrl;
      a.download   = (this.viewMode === 'compare' ? 'rapport-comparatif' : 'rapport') + '-' + this.period + '.pdf';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(objUrl);
    } catch (e) {
      this.error = 'Erreur réseau lors du téléchargement du PDF.';
    } finally {
      this.pdfLoading = false;
    }
  },

  get revenue()    { return this.summary?.revenue          ?? 0; },
  get expTotal()   { return this.summary?.expenses         ?? 0; },
  get netProfit()  { return this.summary?.net_profit       ?? 0; },
  get paidInv()    { return this.summary?.paid_invoices    ?? 0; },
  get pendingPay() { return this.summary?.pending_payments ?? 0; },
  get occupancy()  { return this.summary?.occupancy_rate   ?? 0; },
  get payments()   { return this.summary?.recent_payments  ?? []; },

  get expByCategory() {
    const rows  = this.summary?.expenses_by_category ?? [];
    const total = this.expTotal || 1;
    return rows
      .map(r => ({ cat: r.category, amt: Number(r.total) || 0, pct: Math.round((Number(r.total) || 0) / total * 100) }))
      .sort((a, b) => b.amt - a.amt);
  },

  get revenueByRoomType() {
    const rows  = this.summary?.revenue_by_room_type ?? [];
    const total = this.revenue || 1;
    const palette = ['#1B4332', '#2563EB', '#D97706', '#9333EA', '#0D9488', '#DC2626'];
    return rows
      .map((r, i) => ({
        type:  r.room_type || 'Sans nom',
        amt:   Number(r.total) || 0,
        count: Number(r.bookings_count) || 0,
        pct:   Math.round((Number(r.total) || 0) / total * 100),
        color: palette[i % palette.length],
      }))
      .sort((a, b) => b.amt - a.amt);
  },

  get topClients() {
    return (this.summary?.top_clients ?? []).map(c => ({
      name:  c.client_name || 'Client',
      email: c.client_email || '',
      count: Number(c.bookings_count) || 0,
      spent: Number(c.total_spent) || 0,
    }));
  },

  /* Comparaison vs période précédente (mois dernier / année dernière selon this.period) */
  get prevPeriodLabel() { return this.period === 'year' ? "vs l'année dernière" : 'vs le mois dernier'; },
  get revenueChangePct()    { return this.summary?.previous?.revenue_pct    ?? null; },
  get expensesChangePct()   { return this.summary?.previous?.expenses_pct   ?? null; },
  get netProfitChangePct()  { return this.summary?.previous?.net_profit_pct ?? null; },
  get occupancyChangePct()  { return this.summary?.previous?.occupancy_pct  ?? null; },

  get compareTotals() {
    return this.compareRows.reduce((acc, r) => ({
      revenue:  acc.revenue  + (Number(r.revenue)    || 0),
      expenses: acc.expenses + (Number(r.expenses)   || 0),
      net:      acc.net      + (Number(r.net_profit) || 0),
    }), { revenue: 0, expenses: 0, net: 0 });
  },
  };
}
