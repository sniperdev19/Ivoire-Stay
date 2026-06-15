<?php ?>
<style>
  .status-tab {
    display:inline-flex;align-items:center;gap:6px;
    padding:8px 16px;border-radius:10px;
    font-size:13px;font-weight:500;
    cursor:pointer;border:1px solid transparent;
    transition:all 0.2s;white-space:nowrap;
    background:transparent;color:#6B7280;
  }
  .status-tab:hover { background:rgba(0,0,0,0.04); }
  .status-tab.active {
    background:white;color:#111827;
    border-color:rgba(0,0,0,0.1);
    box-shadow:0 1px 4px rgba(0,0,0,0.08);
  }
  .status-tab .tab-count {
    background:rgba(0,0,0,0.08);color:#6B7280;
    font-size:11px;font-weight:700;
    padding:1px 6px;border-radius:50px;
  }
  .status-tab.active .tab-count {
    background:#C9A84C;color:white;
  }
  .booking-row { cursor:pointer;transition:background 0.15s; }
  .booking-row:hover td { background:rgba(201,168,76,0.03) !important; }
  .action-btn-success { background:#DCFCE7;color:#16a34a;border:1px solid rgba(22,163,74,0.2); }
  .action-btn-info    { background:#EFF6FF;color:#2563EB;border:1px solid rgba(37,99,235,0.2); }
  .action-btn-gold    { background:rgba(201,168,76,0.1);color:#C9A84C;border:1px solid rgba(201,168,76,0.25); }
  .action-btn-danger  { background:#FEF2F2;color:#DC2626;border:1px solid rgba(220,38,38,0.2); }
  .table-empty { padding:32px;text-align:center;color:#9CA3AF; }
  .table-empty button { margin-top:14px; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .spinner { animation: spin 1s linear infinite; }
</style>

<div x-data="{
  bookings: [],
  loading: true,
  error: null,

  filterStatus: 'all',
  filterSearch: '',
  filterDateFrom: '',
  filterDateTo: '',

  page: 1,
  perPage: 10,
  total: 0,

  showDetail: false,
  selectedBooking: null,
  detailLoading: false,
  statusUpdating: false,

  showCreate: false,
  createLoading: false,
  createError: null,
  rooms: [],

  form: {
    room_id:'', check_in:'', check_out:'',
    client_name:'', client_email:'', client_phone:'',
    booking_type:'nuit', source:'manual', notes:''
  },

  fallbackBookings: [
    { id:1, client_name:'Kouamé Adou', client_phone:'+225 07 11 22 33', client_email:'kouame@email.ci', room_name:'Suite Présidentielle', room_id:9, check_in:'2026-06-14', check_out:'2026-06-16', nights:2, total_price:640000, status:'confirmed', source:'online', booking_type:'nuit', notes:'' },
    { id:2, client_name:'Fatou Diallo', client_phone:'+225 05 44 55 66', client_email:'fatou@email.ci', room_name:'Chambre Deluxe', room_id:3, check_in:'2026-06-14', check_out:'2026-06-15', nights:1, total_price:95000, status:'pending', source:'phone', booking_type:'nuit', notes:'Client VIP' },
    { id:3, client_name:'Marc Koffi', client_phone:'+225 01 77 88 99', client_email:'marc@email.ci', room_name:'Studio Standard', room_id:1, check_in:'2026-06-12', check_out:'2026-06-13', nights:1, total_price:55000, status:'checked_out', source:'manual', booking_type:'nuit', notes:'' },
    { id:4, client_name:'Awa Traoré', client_phone:'+225 07 22 33 44', client_email:'awa@email.ci', room_name:'Chambre Deluxe', room_id:3, check_in:'2026-06-15', check_out:'2026-06-17', nights:2, total_price:190000, status:'confirmed', source:'online', booking_type:'nuit', notes:'' },
    { id:5, client_name:'Yao Brou', client_phone:'+225 05 55 66 77', client_email:'yao@email.ci', room_name:'Suite Junior', room_id:5, check_in:'2026-06-14', check_out:'2026-06-15', nights:1, total_price:145000, status:'checked_in', source:'manual', booking_type:'nuit', notes:'' },
    { id:6, client_name:'Amina Coulibaly', client_phone:'+225 01 33 44 55', client_email:'amina@email.ci', room_name:'Studio Standard', room_id:2, check_in:'2026-06-10', check_out:'2026-06-11', nights:1, total_price:55000, status:'cancelled', source:'online', booking_type:'nuit', notes:'Annulé par client' },
    { id:7, client_name:'Jean Ouattara', client_phone:'+225 07 88 99 00', client_email:'jean@email.ci', room_name:'Suite Présidentielle', room_id:9, check_in:'2026-06-18', check_out:'2026-06-20', nights:2, total_price:640000, status:'pending', source:'phone', booking_type:'nuit', notes:'' }
  ],

  async init() {
    await Promise.all([this.loadBookings(), this.loadRooms()]);
  },

  apiHeaders() {
    const token = localStorage.getItem('token');
    return {
      'Content-Type':'application/json',
      'Authorization':'Bearer ' + (token ?? '')
    };
  },
  estId() { return localStorage.getItem('establishment_id') || '1'; },

  async loadBookings() {
    this.loading = true;
    this.error = null;
    try {
      let url = '<?= $base_url ?>/api/bookings'
        + '?establishment_id=' + this.estId()
        + '&page=' + this.page
        + '&per_page=' + this.perPage;
      if (this.filterStatus !== 'all') url += '&status=' + this.filterStatus;
      if (this.filterSearch) url += '&search=' + encodeURIComponent(this.filterSearch);
      if (this.filterDateFrom) url += '&from=' + this.filterDateFrom;
      if (this.filterDateTo)   url += '&to=' + this.filterDateTo;

      const res = await fetch(url, { headers:this.apiHeaders() });
      const data = await res.json();

      if (data.success) {
        const raw = data.data?.bookings 
          ?? data.data?.data 
          ?? data.data 
          ?? [];

        this.bookings = raw.map(b => ({
          ...b,
          client_name: b.client_name 
            ?? b.public_client?.name 
            ?? b.user?.name 
            ?? 'Client #' + b.id,
          client_phone: b.client_phone 
            ?? b.public_client?.phone 
            ?? b.user?.phone 
            ?? '',
          client_email: b.client_email 
            ?? b.public_client?.email 
            ?? b.user?.email 
            ?? '',
          room_name: b.room_name 
            ?? b.room?.name 
            ?? 'Chambre #' + b.room_id,
          total_price: b.total_price 
            ?? b.total_amount 
            ?? 0,
          nights: b.nights 
            ?? (b.check_in && b.check_out 
              ? Math.round((new Date(b.check_out) - new Date(b.check_in)) / 86400000)
              : 1),
        }));

        this.total = data.data?.total ?? this.bookings.length;
      } else {
        this.bookings = this.fallbackBookings;
        this.total = this.fallbackBookings.length;
      }
    } catch(e) {
      this.bookings = this.fallbackBookings;
      this.total = this.fallbackBookings.length;
      this.error = 'Impossible de charger les réservations. Données de secours affichées.';
    } finally {
      this.loading = false;
    }
  },

  async loadRooms() {
    try {
      const res = await fetch('<?= $base_url ?>/api/rooms?establishment_id=' + this.estId(), { headers:this.apiHeaders() });
      const data = await res.json();
      this.rooms = data.success ? (data.data?.rooms ?? data.data ?? []) : [];
      if (!this.rooms.length) {
        this.rooms = [
          { id:1, name:'101', room_type:{ name:'Standard', base_price:55000 } },
          { id:3, name:'103', room_type:{ name:'Deluxe', base_price:95000 } },
          { id:5, name:'201', room_type:{ name:'Suite Junior', base_price:145000 } },
          { id:9, name:'301', room_type:{ name:'Suite Présidentielle', base_price:320000 } }
        ];
      }
    } catch(e) {
      this.rooms = [];
    }
  },

  async applyFilters() {
    this.page = 1;
    await this.loadBookings();
  },

  async resetFilters() {
    this.filterStatus = 'all';
    this.filterSearch = '';
    this.filterDateFrom = '';
    this.filterDateTo = '';
    await this.applyFilters();
  },

  async openDetail(booking) {
    this.selectedBooking = { ...booking };
    this.showDetail = true;
    this.detailLoading = true;
    try {
      const res = await fetch('<?= $base_url ?>/api/bookings/' + booking.id, { headers:this.apiHeaders() });
      const data = await res.json();
      if (data.success) this.selectedBooking = data.data?.booking ?? data.data ?? booking;
    } catch(e) {}
    finally { this.detailLoading = false; }
  },

  async updateStatus(bookingId, newStatus) {
    this.statusUpdating = true;
    try {
      let endpoint = '<?= $base_url ?>/api/bookings/' + bookingId;
      let method = 'PUT';
      let body = JSON.stringify({ status:newStatus });
      if (newStatus === 'checked_in') { endpoint = '<?= $base_url ?>/api/bookings/' + bookingId + '/checkin'; method='POST'; body = null; }
      if (newStatus === 'checked_out') { endpoint = '<?= $base_url ?>/api/bookings/' + bookingId + '/checkout'; method='POST'; body = null; }
      const res = await fetch(endpoint, { method, headers:this.apiHeaders(), body });
      const data = await res.json();
      if (data.success) {
        const idx = this.bookings.findIndex(b => b.id === bookingId);
        if (idx !== -1) this.bookings[idx].status = newStatus;
        if (this.selectedBooking?.id === bookingId) this.selectedBooking.status = newStatus;
        this.showToast('Statut mis à jour avec succès.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur lors de la mise à jour.', 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    } finally { this.statusUpdating = false; }
  },

  async deleteBooking(id) {
    if (!confirm('Supprimer cette réservation ?')) return;
    try {
      const res = await fetch('<?= $base_url ?>/api/bookings/' + id, { method:'DELETE', headers:this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.bookings = this.bookings.filter(b => b.id !== id);
        this.showDetail = false;
        this.selectedBooking = null;
        this.showToast('Réservation supprimée.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur.', 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    }
  },

  async createBooking() {
    this.createError = null;
    this.createLoading = true;
    try {
      const res = await fetch('<?= $base_url ?>/api/bookings', {
        method:'POST', headers:this.apiHeaders(),
        body: JSON.stringify({ ...this.form, establishment_id:this.estId() })
      });
      const data = await res.json();
      if (data.success) {
        const created = data.data?.booking ?? data.data;
        if (created) this.bookings.unshift(created);
        this.showCreate = false;
        this.resetForm();
        this.showToast('Réservation créée avec succès.', 'success');
      } else {
        this.createError = data.message ?? 'Erreur lors de la création.';
        if (data.errors) this.createError += ' ' + JSON.stringify(data.errors);
      }
    } catch(e) {
      this.createError = 'Erreur réseau. Veuillez réessayer.';
    } finally { this.createLoading = false; }
  },

  resetForm() {
    this.form = { room_id:'', check_in:'', check_out:'', client_name:'', client_email:'', client_phone:'', booking_type:'nuit', source:'manual', notes:'' };
    this.createError = null;
  },

  get totalPages() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
  async goToPage(p) {
    if (p < 1 || p > this.totalPages) return;
    this.page = p;
    await this.loadBookings();
  },

  get filteredBookings() {
    let list = [...this.bookings];
    if (this.filterStatus !== 'all') list = list.filter(b => b.status === this.filterStatus);
    if (this.filterSearch) {
      const q = this.filterSearch.toLowerCase();
      list = list.filter(b => (b.client_name||'').toLowerCase().includes(q) || (b.room_name||'').toLowerCase().includes(q) || (b.client_phone||'').includes(q));
    }
    if (this.filterDateFrom) list = list.filter(b => b.check_in >= this.filterDateFrom);
    if (this.filterDateTo)   list = list.filter(b => b.check_in <= this.filterDateTo);
    return list;
  },

  countByStatus(s) {
    if (s === 'all') return this.bookings.length;
    return this.bookings.filter(b => b.status === s).length;
  },

  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p ?? 0) + ' FCFA'; },
  formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('fr-FR', { day:'numeric', month:'short', year:'numeric' });
  },
  statusConfig(s) {
    return {
      confirmed:{ label:'Confirmée', badge:'badge badge-success' },
      pending:{ label:'En attente', badge:'badge badge-warning' },
      checked_in:{ label:'Arrivée', badge:'badge badge-info' },
      checked_out:{ label:'Départ', badge:'badge badge-gold' },
      cancelled:{ label:'Annulée', badge:'badge badge-danger' }
    }[s] ?? { label:s, badge:'badge' };
  },
  sourceLabel(s) {
    return { manual:'Manuel', online:'En ligne', phone:'Téléphone', walk_in:'Sur place' }[s] ?? s;
  },
  typeLabel(t) {
    return { nuit:'Nuit', weekend:'Week-end', passage:'Passage' }[t] ?? t;
  },

  toast:null,
  toastTimer:null,
  showToast(msg,type='success') {
    this.toast = { msg,type };
    clearTimeout(this.toastTimer);
    this.toastTimer = setTimeout(() => { this.toast = null; }, 3500);
  },

  nextActions(status) {
    const map = {
      pending:[{ label:'Confirmer', value:'confirmed', color:'success' },{ label:'Annuler', value:'cancelled', color:'danger' }],
      confirmed:[{ label:'Check-in', value:'checked_in', color:'info' },{ label:'Annuler', value:'cancelled', color:'danger' }],
      checked_in:[{ label:'Check-out', value:'checked_out', color:'gold' }],
      checked_out:[], cancelled:[]
    };
    return map[status] ?? [];
  }
}"
 x-init="init()"
 @keydown.escape.window="showCreate=false; showDetail=false">

  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h1 style="font-size:24px;font-weight:700;margin:0;color:#111827;">Réservations</h1>
      <p style="margin:8px 0 0;color:#6B7280;font-size:14px;"><span x-text="filteredBookings.length"></span> réservation(s) affichée(s) · <span x-text="total"></span> totale(s)</p>
    </div>
    <button type="button" class="btn-saas-primary" style="white-space:nowrap;" @click="showCreate=true">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Nouvelle réservation
    </button>
  </div>

  <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:22px;">
    <template x-for="status in ['all','confirmed','pending','checked_in','checked_out','cancelled']" :key="status">
      <button type="button"
        class="status-tab"
        :class="{ 'active': filterStatus === status }"
        @click="filterStatus = status; applyFilters();">
        <span x-text="status === 'all' ? 'Toutes' : statusConfig(status).label"></span>
        <span class="tab-count" x-text="countByStatus(status)"></span>
      </button>
    </template>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;margin-bottom:20px;align-items:end;">
    <label class="saas-label">
      Recherche
      <input type="text" class="saas-input" placeholder="Client, chambre, téléphone" x-model="filterSearch" @input="" />
    </label>
    <label class="saas-label">
      Date arrivée min
      <input type="date" class="saas-input" x-model="filterDateFrom" />
    </label>
    <label class="saas-label">
      Date arrivée max
      <input type="date" class="saas-input" x-model="filterDateTo" />
    </label>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <button type="button" class="btn-saas-secondary" @click="resetFilters()">Effacer</button>
      <button type="button" class="btn-saas-primary" @click="applyFilters()">Filtrer</button>
    </div>
  </div>

  <div x-show="loading" style="display:grid;gap:16px;">
    <div class="saas-card" style="padding:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;"><div style="width:180px;height:18px;background:rgba(0,0,0,0.05);border-radius:8px;"></div><div style="width:90px;height:18px;background:rgba(0,0,0,0.05);border-radius:8px;"></div></div>
      <div style="display:grid;gap:10px;">
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:80%;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:90%;"></div>
      </div>
    </div>
    <div class="saas-card" style="padding:20px;">
      <div style="display:grid;gap:10px;">
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:50%;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:70%;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:60%;"></div>
      </div>
    </div>
  </div>

  <div x-show="!loading" style="display:grid;gap:20px;">
    <div x-show="error" style="padding:14px 16px;background:#FEF3C7;border:1px solid rgba(220,145,24,0.3);border-radius:12px;color:#92400E;">
      <span x-text="error"></span>
    </div>

    <div x-show="filteredBookings.length === 0" class="saas-card" style="padding:32px;text-align:center;">
      <p style="font-size:15px;font-weight:600;color:#111827;margin:0 0 8px;">Aucune réservation ne correspond à vos filtres.</p>
      <p style="color:#6B7280;margin:0 0 16px;">Ajustez les filtres ou cliquez sur Effacer pour retrouver toutes les réservations.</p>
      <button type="button" class="btn-saas-secondary" @click="resetFilters()">Effacer les filtres</button>
    </div>

    <div x-show="filteredBookings.length > 0" class="saas-card" style="padding:0;overflow:hidden;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(0,0,0,0.06);">
        <div>
          <h2 style="font-size:18px;font-weight:700;margin:0;color:#111827;">Liste des réservations</h2>
          <p style="margin:6px 0 0;color:#6B7280;font-size:13px;">Cliquez sur une ligne pour voir les détails.</p>
        </div>
        <span style="font-size:13px;color:#6B7280;"><span x-text="filteredBookings.length"></span> / <span x-text="total"></span></span>
      </div>

      <div style="overflow-x:auto;">
        <table class="saas-table" style="min-width:940px;">
          <thead>
            <tr>
              <th>Client</th>
              <th>Chambre</th>
              <th>Arrivée</th>
              <th>Départ</th>
              <th>Source</th>
              <th>Montant</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="booking in filteredBookings" :key="booking.id">
              <tr class="booking-row" @click="openDetail(booking)">
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-weight:600;color:#111827;" x-text="booking.client_name"></span>
                    <span style="font-size:13px;color:#6B7280;" x-text="booking.client_phone"></span>
                  </div>
                </td>
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-weight:600;color:#111827;" x-text="booking.room_name"></span>
                    <span style="font-size:13px;color:#6B7280;" x-text="typeLabel(booking.booking_type)"></span>
                  </div>
                </td>
                <td x-text="formatDate(booking.check_in)"></td>
                <td x-text="formatDate(booking.check_out)"></td>
                <td><span class="badge badge-info" x-text="sourceLabel(booking.source)"></span></td>
                <td style="font-weight:700;color:#111827;text-align:right;" x-text="formatPrice(booking.total_price)"></td>
                <td><span :class="statusConfig(booking.status).badge" x-text="statusConfig(booking.status).label"></span></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-top:1px solid rgba(0,0,0,0.06);">
        <span style="color:#6B7280;font-size:13px;">Page <span x-text="page"></span> sur <span x-text="totalPages"></span></span>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page-1)" :disabled="page<=1" :style="page<=1 ? 'opacity:0.4;cursor:not-allowed;' : ''">← Préc.</button>
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page+1)" :disabled="page>=totalPages" :style="page>=totalPages ? 'opacity:0.4;cursor:not-allowed;' : ''">Suiv. →</button>
        </div>
      </div>
    </div>
  </div>

  <div x-show="showDetail" class="saas-modal-bg" @click.self="showDetail=false">
    <div class="saas-modal" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <div>
          <p style="font-size:13px;color:#6B7280;margin:0;">Réservation #<span x-text="selectedBooking?.id"></span></p>
          <h2 style="font-size:18px;font-weight:700;margin:6px 0 0;color:#111827;" x-text="selectedBooking?.client_name"></h2>
        </div>
        <button type="button" style="background:none;border:none;color:#6B7280;cursor:pointer;font-size:18px;" @click="showDetail=false">×</button>
      </div>
      <div class="saas-modal-body">
        <div x-show="detailLoading" style="display:flex;justify-content:center;padding:40px 0;"><svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:28px;height:28px;stroke:#C9A84C;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="4" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="4" stroke-linecap="round"></path></svg></div>
        <div x-show="!detailLoading" style="display:grid;gap:20px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div>
              <p class="saas-label">Client</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="selectedBooking?.client_name"></p>
              <p style="margin:6px 0 0;color:#6B7280;" x-text="selectedBooking?.client_phone"></p>
              <p style="margin:4px 0 0;color:#6B7280;" x-text="selectedBooking?.client_email"></p>
            </div>
            <div>
              <p class="saas-label">Séjour</p>
              <p style="margin:0;color:#111827;"><strong x-text="selectedBooking?.room_name"></strong></p>
              <p style="margin:6px 0 0;color:#6B7280;" x-text="typeLabel(selectedBooking?.booking_type)"></p>
              <p style="margin:4px 0 0;color:#6B7280;">Nuit(s) : <span x-text="selectedBooking?.nights ?? '-'" /></p>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div>
              <p class="saas-label">Arrivée</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="formatDate(selectedBooking?.check_in)"></p>
            </div>
            <div>
              <p class="saas-label">Départ</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="formatDate(selectedBooking?.check_out)"></p>
            </div>
          </div>
          <div>
            <p class="saas-label">Notes</p>
            <p style="margin:0;color:#6B7280;" x-text="selectedBooking?.notes || 'Aucune note' "></p>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:center;">
            <div>
              <p class="saas-label">Montant total</p>
              <p style="margin:0;font-weight:700;color:#111827;" x-text="formatPrice(selectedBooking?.total_price)"></p>
            </div>
            <div>
              <p class="saas-label">Statut</p>
              <span :class="statusConfig(selectedBooking?.status).badge" x-text="statusConfig(selectedBooking?.status).label"></span>
            </div>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <template x-for="action in nextActions(selectedBooking?.status || '')" :key="action.value">
              <button type="button" :class="'btn-saas-secondary action-btn-' + action.color" style="font-weight:600;" @click="updateStatus(selectedBooking.id, action.value)">
                <span x-text="action.label"></span>
              </button>
            </template>
          </div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-danger" @click="deleteBooking(selectedBooking.id)">Supprimer</button>
        <button type="button" class="btn-saas-secondary" @click="showDetail=false">Fermer</button>
      </div>
    </div>
  </div>

  <div x-show="showCreate" class="saas-modal-bg" @click.self="showCreate=false">
    <div class="saas-modal" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <div>
          <p style="font-size:13px;color:#6B7280;margin:0;">Nouvelle réservation</p>
          <h2 style="font-size:18px;font-weight:700;margin:6px 0 0;color:#111827;">Créer une réservation</h2>
        </div>
        <button type="button" style="background:none;border:none;color:#6B7280;cursor:pointer;font-size:18px;" @click="showCreate=false">×</button>
      </div>
      <form class="saas-modal-body" @submit.prevent="createBooking()">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Chambre</label>
            <select class="saas-input" x-model="form.room_id" required>
              <option value="">Sélectionner une chambre</option>
              <template x-for="room in rooms" :key="room.id">
                <option :value="room.id" x-text="room.name + ' — ' + (room.room_type?.name || '')"></option>
              </template>
            </select>
          </div>
          <div>
            <label class="saas-label">Type de séjour</label>
            <select class="saas-input" x-model="form.booking_type">
              <option value="nuit">Nuit</option>
              <option value="weekend">Week-end</option>
              <option value="passage">Passage</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Arrivée</label>
            <input type="date" class="saas-input" x-model="form.check_in" required />
          </div>
          <div>
            <label class="saas-label">Départ</label>
            <input type="date" class="saas-input" x-model="form.check_out" required />
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Nom du client</label>
            <input type="text" class="saas-input" x-model="form.client_name" required />
          </div>
          <div>
            <label class="saas-label">Téléphone</label>
            <input type="text" class="saas-input" x-model="form.client_phone" required />
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Email (optionnel)</label>
            <input type="email" class="saas-input" x-model="form.client_email" />
          </div>
          <div>
            <label class="saas-label">Source</label>
            <select class="saas-input" x-model="form.source">
              <option value="manual">Manuel</option>
              <option value="phone">Téléphone</option>
              <option value="walk_in">Sur place</option>
              <option value="online">En ligne</option>
            </select>
          </div>
        </div>
        <div style="margin-bottom:18px;">
          <label class="saas-label">Notes (optionnel)</label>
          <textarea class="saas-input" rows="4" x-model="form.notes" style="resize:vertical;"></textarea>
        </div>
        <div x-show="createError" style="margin-bottom:18px;padding:12px 14px;background:#FEF2F2;border:1px solid rgba(220,38,38,0.2);border-radius:12px;color:#B91C1C;" x-text="createError"></div>
        <div class="saas-modal-footer">
          <button type="button" class="btn-saas-secondary" @click="showCreate=false" :disabled="createLoading">Annuler</button>
          <button type="submit" class="btn-saas-primary" :disabled="createLoading">
            <span x-show="!createLoading">Créer la réservation</span>
            <span x-show="createLoading" style="display:inline-flex;align-items:center;gap:8px;">
              <svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:16px;height:16px;stroke:white;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="3" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="3" stroke-linecap="round"></path></svg>
              Création...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div x-show="toast" style="position:fixed;bottom:24px;right:24px;z-index:110;">
    <div :style="toast.type === 'success' ? 'background:#DCFCE7;color:#166534;' : 'background:#FEE2E2;color:#991B1B;'" style="padding:14px 18px;border-radius:14px;box-shadow:0 18px 50px rgba(15,23,42,0.15);min-width:260px;">
      <p style="margin:0;font-size:14px;font-weight:600;" x-text="toast.msg"></p>
    </div>
  </div>
</div>
