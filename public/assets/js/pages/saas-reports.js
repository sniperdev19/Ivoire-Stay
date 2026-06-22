/* ============================================================
   Ivoire Stay — Page SaaS : Rapports (src/templates/saas/reports.php)
   ============================================================ */

function reportsPage(baseUrl) {
  return {
  ...saasHelpers,

  stats: null, invoices: [], expenses: [], payments: [],
  loading: true, error: null, upgradeRequired: false, period: 'month',

  async init() {
    if (planUpgradeRequired('reports')) { this.upgradeRequired = true; this.loading = false; return; }
    await this.loadData();
  },

  async loadData() {
    this.loading = true; this.error = null;
    try {
      const [s, inv, exp, pay] = await Promise.all([
        fetch(baseUrl + '/api/dashboard/stats?establishment_id=' + this.estId() + '&period=' + this.period, { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/invoices?establishment_id='  + this.estId() + '&per_page=100', { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/expenses?establishment_id='  + this.estId() + '&per_page=100', { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/payments?establishment_id='  + this.estId() + '&per_page=100', { headers: this.apiHeaders() }).then(r => r.json()),
      ]);
      if (inv.upgrade_required || exp.upgrade_required || pay.upgrade_required) {
        this.upgradeRequired = true; return;
      }
      this.stats    = s.success   ? (s.data ?? null) : null;
      this.invoices = inv.success ? (inv.data?.invoices ?? inv.data ?? []) : [];
      this.expenses = exp.success ? (exp.data?.expenses ?? exp.data ?? []) : [];
      this.payments = pay.success ? (pay.data?.payments ?? pay.data ?? []) : [];
    } catch(e) {
      this.error = 'Impossible de charger les rapports.';
    } finally {
      this.loading = false;
    }
  },

  async changePeriod(p) { this.period = p; await this.loadData(); },

  get revenue()    { return this.stats?.revenue ?? 0; },
  get expTotal()   { return this.expenses.reduce((s, e) => s + (e.amount || 0), 0); },
  get netProfit()  { return this.revenue - this.expTotal; },
  get paidInv()    { return this.invoices.filter(i => i.status === 'paid').reduce((s, i) => s + (i.amount_ttc || 0), 0); },
  get pendingPay() { return this.payments.filter(p => p.status === 'pending').reduce((s, p) => s + (p.amount || 0), 0); },
  get occupancy()  { return this.stats?.occupancy_rate ?? 0; },

  get expByCategory() {
    const map = {};
    this.expenses.forEach(e => { map[e.category] = (map[e.category] || 0) + (e.amount || 0); });
    const total = this.expTotal || 1;
    return Object.entries(map)
      .map(([cat, amt]) => ({ cat, amt, pct: Math.round(amt / total * 100) }))
      .sort((a, b) => b.amt - a.amt);
  },
  };
}
