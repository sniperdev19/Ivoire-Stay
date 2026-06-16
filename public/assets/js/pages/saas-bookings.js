/* ============================================================
   Ivoire Stay — Page SaaS : Réservations (src/templates/saas/bookings.php)
   ============================================================ */

function bookingsPage(baseUrl) {
  return {
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
      let url = baseUrl + '/api/bookings'
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
      const res = await fetch(baseUrl + '/api/rooms?establishment_id=' + this.estId(), { headers:this.apiHeaders() });
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
      const res = await fetch(baseUrl + '/api/bookings/' + booking.id, { headers:this.apiHeaders() });
      const data = await res.json();
      if (data.success) this.selectedBooking = data.data?.booking ?? data.data ?? booking;
    } catch(e) {}
    finally { this.detailLoading = false; }
  },

  async updateStatus(bookingId, newStatus) {
    this.statusUpdating = true;
    try {
      let endpoint = baseUrl + '/api/bookings/' + bookingId;
      let method = 'PUT';
      let body = JSON.stringify({ status:newStatus });
      if (newStatus === 'checked_in') { endpoint = baseUrl + '/api/bookings/' + bookingId + '/checkin'; method='POST'; body = null; }
      if (newStatus === 'checked_out') { endpoint = baseUrl + '/api/bookings/' + bookingId + '/checkout'; method='POST'; body = null; }
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
      const res = await fetch(baseUrl + '/api/bookings/' + id, { method:'DELETE', headers:this.apiHeaders() });
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
      const res = await fetch(baseUrl + '/api/bookings', {
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
  };
}
