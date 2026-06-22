/* ============================================================
   Ivoire Stay — Page SaaS : Réservations (src/templates/saas/bookings.php)
   ============================================================ */

function bookingsPage(baseUrl) {
  return {
  ...saasHelpers,

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
    room_id: '', check_in: '', check_out: '',
    first_name: '', last_name: '', client_email: '', client_phone: '',
    booking_type: 'nuit', hours: 3, guests_count: 2, source: 'manual', notes: '',
  },

  async init() {
    await Promise.all([this.loadBookings(), this.loadRooms()]);
  },

  openCreate() {
    this.resetForm();
    this.showCreate = true;
  },

  async loadBookings() {
    this.loading = true;
    this.error = null;
    try {
      let url = baseUrl + '/api/bookings'
        + '?establishment_id=' + this.estId()
        + '&page=' + this.page
        + '&per_page=' + this.perPage;
      if (this.filterStatus !== 'all') url += '&status=' + this.filterStatus;
      if (this.filterSearch)  url += '&search=' + encodeURIComponent(this.filterSearch);
      if (this.filterDateFrom) url += '&from=' + this.filterDateFrom;
      if (this.filterDateTo)   url += '&to='   + this.filterDateTo;

      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();

      if (data.success) {
        const raw = data.data?.bookings ?? data.data?.data ?? data.data ?? [];
        this.bookings = raw.map(b => ({
          ...b,
          client_name:  b.client_name  ?? b.public_client?.name  ?? b.user?.name  ?? 'Client #' + b.id,
          client_phone: b.client_phone ?? b.public_client?.phone ?? b.user?.phone ?? '',
          client_email: b.client_email ?? b.public_client?.email ?? b.user?.email ?? '',
          room_number:  b.room_number  ?? b.room?.number ?? '?',
          room_type:    b.room_type    ?? b.room?.type_name ?? '',
          total_price:  b.total_price  ?? b.total_amount ?? 0,
          hours:        b.hours ?? 0,
          nights: b.booking_type === 'passage' ? 0
            : (b.nights ?? (b.check_in && b.check_out
              ? Math.round((new Date(b.check_out) - new Date(b.check_in)) / 86400000)
              : 1)),
        }));
        this.total = data.data?.total ?? this.bookings.length;
      } else {
        this.bookings = [];
        this.total = 0;
        this.error = data.message ?? 'Impossible de charger les réservations.';
      }
    } catch(e) {
      this.bookings = [];
      this.total = 0;
      this.error = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
      this.loading = false;
    }
  },

  async loadRooms() {
    try {
      const res  = await fetch(baseUrl + '/api/rooms?establishment_id=' + this.estId(), { headers: this.apiHeaders() });
      const data = await res.json();
      this.rooms = data.success ? (data.data?.rooms ?? data.data ?? []) : [];
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
      const res  = await fetch(baseUrl + '/api/bookings/' + booking.id, { headers: this.apiHeaders() });
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
      let body = JSON.stringify({ status: newStatus });
      if (newStatus === 'checked_in')  { endpoint = baseUrl + '/api/bookings/' + bookingId + '/checkin';  method = 'POST'; body = null; }
      if (newStatus === 'checked_out') { endpoint = baseUrl + '/api/bookings/' + bookingId + '/checkout'; method = 'POST'; body = null; }
      const res  = await fetch(endpoint, { method, headers: this.apiHeaders(), body });
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
      const res  = await fetch(baseUrl + '/api/bookings/' + id, { method: 'DELETE', headers: this.apiHeaders() });
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
      const isPassage = this.form.booking_type === 'passage';
      const payload = {
        room_id:          this.form.room_id,
        check_in:         this.form.check_in,
        check_out:        isPassage ? this.form.check_in : this.form.check_out,
        booking_type:     this.form.booking_type,
        hours:            isPassage ? Number(this.form.hours) : undefined,
        guests_count:     Number(this.form.guests_count) || 1,
        source:           this.form.source,
        notes:            this.form.notes || null,
        establishment_id: this.estId(),
        client: {
          first_name: this.form.first_name.trim(),
          last_name:  this.form.last_name.trim(),
          email:      this.form.client_email  || null,
          phone:      this.form.client_phone  || null,
        },
      };
      const res  = await fetch(baseUrl + '/api/bookings', {
        method: 'POST', headers: this.apiHeaders(), body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (data.success) {
        await this.loadBookings();
        this.showCreate = false;
        this.resetForm();
        this.showToast('Réservation créée avec succès.', 'success');
      } else {
        this.createError = data.message ?? 'Erreur lors de la création.';
      }
    } catch(e) {
      this.createError = 'Erreur réseau. Veuillez réessayer.';
    } finally { this.createLoading = false; }
  },

  resetForm() {
    this.form = {
      room_id: '', check_in: '', check_out: '',
      first_name: '', last_name: '', client_email: '', client_phone: '',
      booking_type: 'nuit', hours: 3, guests_count: 2, source: 'manual', notes: '',
    };
    this.createError = null;
  },

  get totalPages() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
  async goToPage(p) {
    if (p < 1 || p > this.totalPages) return;
    this.page = p;
    await this.loadBookings();
  },

  get filteredBookings() {
    const q = this.filterSearch?.toLowerCase();
    return this.bookings.filter(b => {
      if (this.filterStatus !== 'all' && b.status !== this.filterStatus) return false;
      if (q && !`${b.client_name} ${b.room_number} ${b.room_type} ${b.client_phone}`.toLowerCase().includes(q)) return false;
      if (this.filterDateFrom && b.check_in < this.filterDateFrom) return false;
      if (this.filterDateTo   && b.check_in > this.filterDateTo)   return false;
      return true;
    });
  },

  countByStatus(s) {
    if (s === 'all') return this.bookings.length;
    return this.bookings.filter(b => b.status === s).length;
  },

  statusConfig(s) { return BOOKING_STATUS[s] ?? { label: s, badge: 'badge' }; },
  sourceLabel(s)  { return { manual:'Manuel / Sur place', online:'En ligne', phone:'Téléphone' }[s] ?? s ?? '—'; },
  typeLabel(t)    { return { nuit:'Nuit', weekend:'Week-end', passage:'Passage' }[t] ?? t ?? '—'; },
  typeBadgeStyle(t) {
    return {
      nuit:    'background:rgba(37,99,235,0.08);color:#1D4ED8;',
      weekend: 'background:rgba(124,58,237,0.08);color:#6D28D9;',
      passage: 'background:rgba(184,134,11,0.1);color:#92400E;',
    }[t] ?? 'background:rgba(0,0,0,0.05);color:#6B7280;';
  },

  nextActions(status) {
    const map = {
      pending:     [{ label:'Confirmer', value:'confirmed',   color:'success' }, { label:'Annuler', value:'cancelled', color:'danger' }],
      confirmed:   [{ label:'Check-in',  value:'checked_in',  color:'info'    }, { label:'Annuler', value:'cancelled', color:'danger' }],
      checked_in:  [{ label:'Check-out', value:'checked_out', color:'gold'    }],
      checked_out: [], cancelled: [],
    };
    return map[status] ?? [];
  },
  };
}
