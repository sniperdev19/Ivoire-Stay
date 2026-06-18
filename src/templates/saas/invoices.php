<?php
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!-- Template Factures SaaS -->
<!-- Variables disponibles : $title, $page, $base_url -->

<div x-data="{
  invoices: [], loading: true, error: null,
  page: 1, perPage: 15, total: 0,
  search: '', statusFilter: '',
  showModal: false, editing: null,
  submitting: false, formError: null,
  rooms: [],

  form: { booking_id:'', room_id:'', client_name:'', client_email:'', amount_ht:'', tax_rate:18, notes:'', status:'draft' },

  /* fallbackInvoices: [
    { id:1, invoice_number:'INV-2026-001', client_name:'Kouamé Adou', client_email:'k@test.ci', amount_ht:84746, tax_rate:18, amount_ttc:100000, status:'paid', created_at:'2026-06-10', booking_id:1 },
    { id:2, invoice_number:'INV-2026-002', client_name:'Fatou Diallo', client_email:'f@test.ci', amount_ht:42373, tax_rate:18, amount_ttc:50000, status:'sent', created_at:'2026-06-12', booking_id:2 },
    { id:3, invoice_number:'INV-2026-003', client_name:'Marc Koffi', client_email:'m@test.ci', amount_ht:127119, tax_rate:18, amount_ttc:150000, status:'draft', created_at:'2026-06-14', booking_id:3 }
  ], */

  apiHeaders() { const token = localStorage.getItem('token') ?? ''; return { 'Content-Type':'application/json', 'Authorization':'Bearer ' + token }; },
  estId() { let id = localStorage.getItem('establishment_id'); if (id && id !== 'null' && id !== 'undefined') return id; try { const list = JSON.parse(localStorage.getItem('establishments') || '[]'); if (Array.isArray(list) && list.length>0) { id = list[0].id ?? list[0].establishment_id; if (id) { localStorage.setItem('establishment_id', String(id)); return String(id); } } } catch(e) {} try { const user = JSON.parse(localStorage.getItem('user') || '{}'); if (user.establishment_id) { localStorage.setItem('establishment_id', String(user.establishment_id)); return String(user.establishment_id); } } catch(e) {} return '1'; },

  async init() { await this.loadInvoices(); },

  async loadInvoices() {
    this.loading = true; this.error = null;
    try {
      const res = await fetch('<?= $base_url ?>/api/invoices?establishment_id='+this.estId()+'&page='+this.page+'&per_page='+this.perPage+(this.search? '&search='+encodeURIComponent(this.search):'')+(this.statusFilter? '&status='+this.statusFilter:''), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.invoices = data.data?.invoices ?? data.data ?? [];
        this.total = data.data?.total ?? this.invoices.length;
        // Ne PAS activer le fallback si l'API répond avec succès
      } else {
        console.warn('API error:', data.message);
        this.invoices = [];
        this.total = 0;
      }
    } catch(e) {
      this.invoices = [];
      this.total = 0;
      console.error('Network error:', e);
      this.error = 'Impossible de charger les factures. Veuillez réessayer.';
    }
    finally { this.loading = false; }
  },

  openCreate() { this.editing = null; this.form = { booking_id:'', room_id:'', client_name:'', client_email:'', amount_ht:'', tax_rate:18, notes:'', status:'draft' }; this.formError = null; this.showModal = true; },
  openEdit(inv) { this.editing = inv; this.form = { booking_id:inv.booking_id??'', room_id:inv.room_id??'', client_name:inv.client_name??'', client_email:inv.client_email??'', amount_ht:inv.amount_ht??'', tax_rate:inv.tax_rate??18, notes:inv.notes??'', status:inv.status??'draft' }; this.formError=null; this.showModal=true; },

  get amountTtc() { const ht = parseFloat(this.form.amount_ht)||0; const tax = parseFloat(this.form.tax_rate)||0; return Math.round(ht*(1+tax/100)); },

  async saveInvoice() {
    if (!this.form.client_name?.trim()) return this.formError='Nom client requis.';
    if (!this.form.amount_ht || this.form.amount_ht <= 0) return this.formError='Montant HT requis.';
    this.submitting = true; this.formError = null;
    try {
      const url = this.editing ? '<?= $base_url ?>/api/invoices/'+this.editing.id : '<?= $base_url ?>/api/invoices';
      const res = await fetch(url, { method: this.editing ? 'PUT':'POST', headers: this.apiHeaders(), body: JSON.stringify({ ...this.form, establishment_id: this.estId(), amount_ttc: this.amountTtc }) });
      const data = await res.json();
      if (data.success) { this.showModal = false; await this.loadInvoices(); } else { this.formError = data.message ?? 'Erreur.'; }
    } catch(e) { this.formError = 'Erreur réseau.'; }
    finally { this.submitting = false; }
  },

  downloadPdf(id) { window.open('<?= $base_url ?>/api/invoices/'+id+'/pdf'+'?token='+localStorage.getItem('token'), '_blank'); },

  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p??0)+' FCFA'; },
  formatDate(d) { if(!d) return '-'; return new Date(d).toLocaleDateString('fr-FR',{ day:'numeric', month:'short', year:'numeric' }); },
  statusCfg(s) { return { draft:{label:'Brouillon', badge:'badge badge-info'}, sent:{label:'Envoyée', badge:'badge badge-warning'}, paid:{label:'Payée', badge:'badge badge-success'} }[s] ?? {label:s, badge:'badge'}; }
}"
 x-init="init()" @keydown.enter.window="loadInvoices()" @keydown.escape.window="showModal=false">

  <!-- En-tête -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div>
      <h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Facturation</h1>
      <p style="margin:6px 0 0;color:#9CA3AF;">Gérer vos factures</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <button class="btn-saas-secondary" @click="openCreate()">+ Nouvelle facture</button>
    </div>
  </div>

  <!-- KPI -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;">
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Total factures</div><div style="font-size:20px;font-weight:800;" x-text="invoices.length"></div></div>
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Montant total TTC</div><div style="font-size:18px;font-weight:800;color:#1B4332;" x-text="formatPrice(invoices.reduce((s,i)=> s + (i.amount_ttc||0),0))"></div></div>
    <div class="kpi-card saas-card"><div style="font-size:12px;color:#9CA3AF;">Payées</div><div style="font-size:18px;font-weight:800;color:#16a34a;" x-text="invoices.filter(i=>i.status==='paid').length"></div></div>
  </div>

  <!-- Filtres -->
  <div class="saas-card" style="padding:12px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <input class="saas-input" placeholder="Rechercher client ou n° facture" x-model="search" style="flex:1;min-width:220px;" />
    <select class="saas-input" x-model="statusFilter" style="width:160px;"><option value="">Tous</option><option value="draft">Brouillon</option><option value="sent">Envoyée</option><option value="paid">Payée</option></select>
    <button class="btn-saas-primary" @click="page=1; loadInvoices()">Filtrer</button>
  </div>

  <!-- Table -->
  <div class="saas-card" style="padding:0;overflow:hidden;">
    <template x-if="loading">
      <div style="padding:12px;">
        <template x-for="i in [1,2,3,4,5]" :key="i">
          <div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);">
            <div class="cl-shimmer" style="width:140px;height:14px;border-radius:8px;"></div>
            <div class="cl-shimmer" style="height:14px;width:220px;border-radius:8px;flex:1;"></div>
            <div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div>
            <div class="cl-shimmer" style="width:80px;height:14px;border-radius:8px;"></div>
            <div class="cl-shimmer" style="width:40px;height:14px;border-radius:8px;"></div>
          </div>
        </template>
      </div>
    </template>

    <template x-if="!loading">
      <div style="overflow-x:auto;">
        <table class="saas-table" style="width:100%;">
          <thead>
            <tr>
              <th>N° Facture</th>
              <th>Client</th>
              <th>Montant HT</th>
              <th>TVA</th>
              <th>Montant TTC</th>
              <th>Statut</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-if="invoices.length === 0">
              <tr><td colspan="8" style="text-align:center;padding:36px;color:#9CA3AF;">
                <div style="display:inline-block;text-align:center;"><svg xmlns="http://www.w3.org/2000/svg" style="width:36px;height:36px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6M9 17h6"/></svg><div style="margin-top:8px;font-weight:600;color:#111827;">Aucune facture</div></div>
              </td></tr>
            </template>

            <template x-for="inv in invoices" :key="inv.id">
              <tr>
                <td><div style="font-family:Cormorant,serif;font-size:15px;color:#C9A84C;font-weight:700;" x-text="inv.invoice_number"></div></td>
                <td><div style="font-weight:600;color:#111827;" x-text="inv.client_name"></div><div style="font-size:12px;color:#9CA3AF;" x-text="inv.client_email"></div></td>
                <td x-text="formatPrice(inv.amount_ht)"></td>
                <td x-text="(inv.tax_rate??0) + '%' "></td>
                <td style="font-weight:800;color:#1B4332;" x-text="formatPrice(inv.amount_ttc)"></td>
                <td><span :class="statusCfg(inv.status).badge" x-text="statusCfg(inv.status).label"></span></td>
                <td x-text="formatDate(inv.created_at)"></td>
                <td style="white-space:nowrap;">
                  <button class="btn-saas-secondary" @click.stop="downloadPdf(inv.id)" title="PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                  </button>
                  <button class="btn-saas-secondary" @click.stop="openEdit(inv)">Modifier</button>
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
    <div class="saas-modal" style="max-width:560px;" @click.stop>
      <div class="saas-modal-header">
        <h2 x-text="editing ? 'Modifier la facture' : 'Nouvelle facture'"></h2>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:12px;">
          <div><label class="saas-label">Nom client</label><input class="saas-input" x-model="form.client_name" /></div>
          <div><label class="saas-label">Email client</label><input class="saas-input" type="email" x-model="form.client_email" /></div>
          <div style="display:grid;grid-template-columns:1fr 120px;gap:12px;"><div><label class="saas-label">Montant HT</label><input class="saas-input" type="number" x-model.number="form.amount_ht" /></div><div><label class="saas-label">Taux TVA</label><input class="saas-input" type="number" x-model.number="form.tax_rate" /></div></div>
          <div style="background:rgba(201,168,76,0.06);padding:10px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:13px;color:#6B7280;">Montant TTC</div>
            <div style="font-weight:800;color:#1B4332;" x-text="formatPrice(amountTtc)"></div>
          </div>
          <div><label class="saas-label">Statut</label><select class="saas-input" x-model="form.status"><option value="draft">Brouillon</option><option value="sent">Envoyée</option><option value="paid">Payée</option></select></div>
          <div><label class="saas-label">Notes</label><textarea class="saas-input" rows="3" x-model="form.notes"></textarea></div>
          <template x-if="formError"><div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;" x-text="formError"></div></template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="saveInvoice()" :disabled="submitting"> <span x-show="!submitting">Enregistrer</span><div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div></button>
      </div>
    </div>
  </div>

</div>
<?php ?>
<div class="invoices">
    <h2>Factures</h2>
    <div id="invoicesList">Liste des factures...</div>
</div>
