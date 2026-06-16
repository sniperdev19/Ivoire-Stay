/* ============================================================
   Ivoire Stay — Page SaaS : Rapports (src/templates/saas/reports.php)
   ============================================================ */

function reportsPage(baseUrl) {
  return {
  stats: null, invoices: [], expenses: [], payments: [],
  loading: true, error: null, period: 'month',

  apiHeaders() { return { 'Content-Type':'application/json', 'Authorization':'Bearer '+localStorage.getItem('token') }; },
  estId() { return localStorage.getItem('establishment_id')||'1'; },

  async init() {
    this.loading = true; this.error = null;
    try {
      const [s,inv,exp,pay] = await Promise.all([
        fetch(baseUrl + '/api/dashboard/stats?establishment_id='+this.estId()+'&period='+this.period,{headers:this.apiHeaders()}).then(r=>r.json()),
        fetch(baseUrl + '/api/invoices?establishment_id='+this.estId()+'&per_page=100',{headers:this.apiHeaders()}).then(r=>r.json()),
        fetch(baseUrl + '/api/expenses?establishment_id='+this.estId()+'&per_page=100',{headers:this.apiHeaders()}).then(r=>r.json()),
        fetch(baseUrl + '/api/payments?establishment_id='+this.estId()+'&per_page=100',{headers:this.apiHeaders()}).then(r=>r.json()),
      ]);
      this.stats    = s.success ? (s.data ?? null) : null;
      this.invoices = inv.success ? (inv.data?.invoices ?? inv.data ?? []) : [];
      this.expenses = exp.success ? (exp.data?.expenses ?? exp.data ?? []) : [];
      this.payments = pay.success ? (pay.data?.payments ?? pay.data ?? []) : [];
    } catch(e) { this.error = 'Impossible de charger les rapports.'; }
    finally { this.loading = false; }
  },

  async changePeriod(p) { this.period = p; await this.init(); },

  get revenue(){ return this.stats?.revenue ?? 0; },
  get expTotal(){ return this.expenses.reduce((s,e)=>s+(e.amount||0),0); },
  get netProfit(){ return this.revenue - this.expTotal; },
  get paidInv(){ return this.invoices.filter(i=>i.status==='paid').reduce((s,i)=>s+(i.amount_ttc||0),0); },
  get pendingPay(){ return this.payments.filter(p=>p.status==='pending').reduce((s,p)=>s+(p.amount||0),0); },
  get occupancy(){ return this.stats?.occupancy_rate ?? 0; },

  get expByCategory(){
    const map = {}; this.expenses.forEach(e=>{ map[e.category] = (map[e.category]||0) + (e.amount||0); });
    const total = this.expTotal || 1;
    return Object.entries(map).map(([cat,amt])=>({ cat, amt, pct: Math.round(amt/total*100) })).sort((a,b)=>b.amt-a.amt);
  },

  catLabel(c){ return {maintenance:'Maintenance',salaries:'Salaires',supplies:'Fournitures',utilities:'Énergie/Eau',marketing:'Marketing',other:'Autre'}[c]??c; },
  catColor(c){ return {maintenance:'#D97706',salaries:'#2563EB',supplies:'#059669',utilities:'#7C3AED',marketing:'#EC4899',other:'#6B7280'}[c]??'#9CA3AF'; },

  formatPrice(p){ return new Intl.NumberFormat('fr-FR').format(p??0)+' FCFA'; }
  };
}
