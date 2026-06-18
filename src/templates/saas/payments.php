<?php
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!-- Template Paiements SaaS -->

<div x-data="{
  payments: [], loading: true, error: null,
  showModal: false, editing: null, submitting: false, formError: null,
  methodFilter:'', statusFilter:'',

  form: { booking_id:'', amount:'', method:'mobile_money', type:'full', status:'pending', reference:'', notes:'' },

  /* fallbackPayments: [
    { id:1, booking_id:1, client_name:'Kouamé Adou', amount:100000, method:'mobile_money', type:'full', status:'confirmed', reference:'MM-001', created_at:'2026-06-10' },
    { id:2, booking_id:2, client_name:'Fatou Diallo', amount:25000, method:'cash', type:'deposit', status:'confirmed', reference:'CASH-002', created_at:'2026-06-12' },
    { id:3, booking_id:3, client_name:'Marc Koffi', amount:150000, method:'mobile_money', type:'full', status:'pending', reference:'MM-003', created_at:'2026-06-14' }
  ], */

  apiHeaders() { const token = localStorage.getItem('token') ?? ''; return { 'Content-Type':'application/json', 'Authorization':'Bearer ' + token }; },
  estId() {
    let id = localStorage.getItem('establishment_id');
    if (id && id !== 'null' && id !== 'undefined') return id;
    try { const list = JSON.parse(localStorage.getItem('establishments') || '[]'); if (Array.isArray(list) && list.length>0) { id = list[0].id ?? list[0].establishment_id; if (id) { localStorage.setItem('establishment_id', String(id)); return String(id); } } } catch(e) {}
    try { const user = JSON.parse(localStorage.getItem('user') || '{}'); if (user.establishment_id) { localStorage.setItem('establishment_id', String(user.establishment_id)); return String(user.establishment_id); } } catch(e) {}
    return '1';
  },

  async init() { await this.loadPayments(); },

  async loadPayments() {
    this.loading = true; this.error = null;
    try {
      const res = await fetch('<?= $base_url ?>/api/payments?establishment_id='+this.estId()+(this.methodFilter? '&method='+this.methodFilter:'')+(this.statusFilter? '&status='+this.statusFilter:''), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.payments = data.data?.payments ?? data.data ?? [];
        // Ne PAS activer le fallback si l'API répond avec succès
      } else {
        console.warn('API error:', data.message);
        this.payments = [];
      }
    } catch(e) {
      this.payments = [];
      console.error('Network error:', e);
      this.error = 'Impossible de charger les paiements. Veuillez réessayer.';
    }
    finally { this.loading = false; }
  },

  openCreate() { this.editing=null; this.form={ booking_id:'', amount:'', method:'mobile_money', type:'full', status:'pending', reference:'', notes:'' }; this.formError=null; this.showModal=true; },

  async savePayment() {
    if (!this.form.amount || this.form.amount <= 0) return this.formError='Montant requis.';
    this.submitting = true; this.formError=null;
    try {
      const url = this.editing ? '<?= $base_url ?>/api/payments/'+this.editing.id : '<?= $base_url ?>/api/payments';
      const res = await fetch(url, { method: this.editing? 'PUT':'POST', headers: this.apiHeaders(), body: JSON.stringify({ ...this.form, establishment_id: this.estId() }) });
      const data = await res.json();
      if (data.success) { this.showModal=false; await this.loadPayments(); } else { this.formError = data.message ?? 'Erreur.'; }
    } catch(e) { this.formError='Erreur réseau.'; }
    finally { this.submitting=false; }
  },

  methodLabel(m) { return { mobile_money:'Mobile Money', cash:'Espèces', card:'Carte', bank_transfer:'Virement' }[m] ?? m; },
  methodIcon(m) { return { mobile_money:'#16a34a', cash:'#D97706', card:'#2563EB', bank_transfer:'#7C3AED' }[m] ?? '#9CA3AF'; },
  typeLabel(t) { return { full:'Paiement complet', deposit:'Acompte', partial:'Partiel' }[t] ?? t; },
  statusCfg(s) { return { confirmed:{label:'Confirmé', badge:'badge badge-success'}, pending:{label:'En attente', badge:'badge badge-warning'}, failed:{label:'Échoué', badge:'badge badge-danger'} }[s] ?? {label:s,badge:'badge'}; },
  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p??0)+' FCFA'; },
  formatDate(d) { if(!d) return '-'; return new Date(d).toLocaleDateString('fr-FR',{ day:'numeric', month:'short', year:'numeric' }); }
}"
 x-init="init()" @keydown.escape.window="showModal=false">

  <!-- Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div><h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Paiements</h1><p style="margin:6px 0 0;color:#9CA3AF;">Enregistrer et gérer les paiements</p></div>
    <div><button class="btn-saas-secondary" @click="openCreate()">+ Enregistrer un paiement</button></div>
  </div>

  <!-- KPI row -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:12px;">
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Total paiements</div><div style="font-size:18px;font-weight:800;" x-text="payments.length"></div></div>
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Confirmés</div><div style="font-size:18px;font-weight:800;color:#16a34a;" x-text="payments.filter(p=>p.status==='confirmed').length"></div></div>
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">En attente</div><div style="font-size:18px;font-weight:800;color:#D97706;" x-text="payments.filter(p=>p.status==='pending').length"></div></div>
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Volume confirmé</div><div style="font-size:16px;font-weight:800;color:#1B4332;" x-text="formatPrice(payments.filter(p=>p.status==='confirmed').reduce((s,p)=>s+(p.amount||0),0))"></div></div>
  </div>

  <!-- Filters -->
  <div class="saas-card" style="padding:10px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <select class="saas-input" x-model="methodFilter"><option value="">Méthode (Tous)</option><option value="mobile_money">Mobile Money</option><option value="cash">Espèces</option><option value="card">Carte</option><option value="bank_transfer">Virement</option></select>
    <select class="saas-input" x-model="statusFilter"><option value="">Statut (Tous)</option><option value="confirmed">Confirmé</option><option value="pending">En attente</option><option value="failed">Échoué</option></select>
    <button class="btn-saas-primary" @click="loadPayments()">Filtrer</button>
  </div>

  <!-- Table -->
  <div class="saas-card" style="padding:0;overflow:hidden;">
    <template x-if="loading">
      <div style="padding:12px;"><template x-for="i in [1,2,3,4,5]" :key="i"><div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);"><div class="cl-shimmer" style="width:100px;height:14px;border-radius:8px;"></div><div class="cl-shimmer" style="width:220px;height:14px;border-radius:8px;flex:1;"></div><div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div><div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div></div></template></div>
    </template>

    <template x-if="!loading">
      <div style="overflow-x:auto;">
        <table class="saas-table" style="width:100%;">
          <thead><tr><th>Référence</th><th>Client</th><th>Montant</th><th>Méthode</th><th>Type</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
            <template x-for="p in payments" :key="p.id">
              <tr>
                <td><div style="display:inline-block;padding:6px 8px;border-radius:8px;background:rgba(201,168,76,0.08);font-weight:700;color:#C9A84C;" x-text="p.reference"></div></td>
                <td x-text="p.client_name"></td>
                <td style="font-weight:800;" x-text="formatPrice(p.amount)"></td>
                <td><div style="display:inline-flex;align-items:center;gap:8px;"><div style="width:10px;height:10px;border-radius:4px;background:${'#'};" :style="'background:'+methodIcon(p.method)"></div><span x-text="methodLabel(p.method)"></span></div></td>
                <td x-text="typeLabel(p.type)" style="color:#9CA3AF;"></td>
                <td><span :class="statusCfg(p.status).badge" x-text="statusCfg(p.status).label"></span></td>
                <td x-text="formatDate(p.created_at)"></td>
                <td>
                  <button class="btn-saas-secondary" @click.stop="openCreate(); editing = p; form = { booking_id:p.booking_id, amount:p.amount, method:p.method, type:p.type, status:p.status, reference:p.reference, notes:p.notes }">Modifier</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </template>
  </div>

  <!-- Modal -->
  <div x-show="showModal" class="saas-modal-bg" @keydown.escape.window="showModal=false" @click.self="showModal=false">
    <div class="saas-modal" style="max-width:540px;" @click.stop>
      <div class="saas-modal-header"><h2 x-text="editing? 'Modifier paiement':'Nouveau paiement'"></h2></div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:12px;">
          <div><label class="saas-label">Réservation ID</label><input class="saas-input" type="number" x-model.number="form.booking_id" /></div>
          <div><label class="saas-label">Montant</label><input class="saas-input" type="number" x-model.number="form.amount" /></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;"><div><label class="saas-label">Méthode</label><select class="saas-input" x-model="form.method"><option value="mobile_money">Mobile Money</option><option value="cash">Espèces</option><option value="card">Carte</option><option value="bank_transfer">Virement</option></select></div><div><label class="saas-label">Type</label><select class="saas-input" x-model="form.type"><option value="full">Paiement complet</option><option value="deposit">Acompte</option><option value="partial">Partiel</option></select></div></div>
          <div><label class="saas-label">Statut</label><select class="saas-input" x-model="form.status"><option value="pending">En attente</option><option value="confirmed">Confirmé</option><option value="failed">Échoué</option></select></div>
          <div><label class="saas-label">Référence</label><input class="saas-input" x-model="form.reference" /></div>
          <div><label class="saas-label">Notes</label><textarea class="saas-input" rows="3" x-model="form.notes"></textarea></div>
          <template x-if="formError"><div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;" x-text="formError"></div></template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="savePayment()" :disabled="submitting"> <span x-show="!submitting">Enregistrer</span><div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div></button>
      </div>
    </div>
  </div>

</div>
<?php ?>
<div class="payments">
    <h2>Paiements</h2>
    <div id="paymentsList">Historique des paiements...</div>
</div>
