/* ============================================================
   Afristay — Page SaaS : Réservations (src/templates/saas/bookings.php)
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
  clients: [],
  clientSearch: '',
  _autoCheckout: null,

  form: {
    room_id: '', check_in: '', check_out: '',
    public_client_id: '', first_name: '', last_name: '', client_email: '', client_phone: '',
    booking_type: 'nuit', hours: 3, guests_count: 2, source: 'manual', notes: '',
    record_payment: false, payment_type: 'full', payment_method: 'cash', payment_amount: '',
  },

  // ── Encaissement sur une réservation existante (modal Détail) ────────────
  payForm: { amount: '', method: 'cash', type: 'full', notes: '' },
  paySubmitting: false,
  payError: null,

  async init() {
    await Promise.all([this.loadBookings(), this.loadRooms(), this.loadClientsList()]);
    this.prefillFromQuery();
    this.$watch('form.check_in',     () => this.autoSuggestCheckout());
    this.$watch('form.booking_type', () => this.autoSuggestCheckout());
  },

  openCreate() {
    this.resetForm();
    this.showCreate = true;
  },

  async loadClientsList() {
    try {
      const res  = await fetch(baseUrl + '/api/clients?establishment_id=' + this.estId(), { headers: this.apiHeaders() });
      const data = await res.json();
      const raw  = data.success ? (data.data?.clients ?? data.data ?? []) : [];
      this.clients = raw.map(c => ({
        ...c,
        name: (c.name ?? [c.first_name, c.last_name].filter(Boolean).join(' ')) || 'Client',
      }));
    } catch(e) {
      this.clients = [];
    }
  },

  // Suggestions de clients existants pendant la saisie (évite de retaper les coordonnées d'un habitué)
  get clientSuggestions() {
    const q = (this.clientSearch || '').trim().toLowerCase();
    if (q.length < 2) return [];
    return this.clients.filter(c =>
      (c.name || '').toLowerCase().includes(q) || (c.phone || '').includes(q)
    ).slice(0, 6);
  },

  selectExistingClient(c) {
    this.form.public_client_id = c.id;
    const parts = (c.name || '').trim().split(/\s+/);
    this.form.first_name   = c.first_name || parts[0] || '';
    this.form.last_name    = c.last_name  || parts.slice(1).join(' ') || '';
    this.form.client_phone = c.phone || '';
    this.form.client_email = c.email || '';
    this.clientSearch = '';
  },

  // Pré-remplit automatiquement la date de départ (nuit = +1, week-end = +3), reste éditable
  autoSuggestCheckout() {
    if (!this.form.check_in || this.form.booking_type === 'passage') return;
    const wasAuto = !this.form.check_out || this.form.check_out === this._autoCheckout;
    if (!wasAuto) return;
    const d = new Date(this.form.check_in + 'T00:00:00');
    d.setDate(d.getDate() + (this.form.booking_type === 'weekend' ? 3 : 1));
    this._autoCheckout = d.toISOString().slice(0, 10);
    this.form.check_out = this._autoCheckout;
  },

  // Pré-remplissage depuis la fiche client (bouton "Réserver" de /saas/clients)
  prefillFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const clientId = params.get('client_id');
    if (!clientId) return;

    this.resetForm();
    this.form.public_client_id = clientId;
    this.form.first_name   = params.get('first_name') || '';
    this.form.last_name    = params.get('last_name')  || '';
    this.form.client_phone = params.get('phone') || '';
    this.form.client_email = params.get('email') || '';
    this.showCreate = true;

    // Nettoie l'URL pour éviter de rouvrir la modale à un rafraîchissement
    window.history.replaceState({}, '', window.location.pathname);
  },

  // Pré-remplit la modale de création à partir d'une réservation existante (même client/chambre)
  duplicateBooking(b) {
    this.resetForm();
    this.form.room_id      = b.room_id ?? '';
    this.form.booking_type = b.booking_type ?? 'nuit';
    this.form.hours        = b.hours || 3;
    this.form.guests_count = b.guests_count || 2;
    this.form.source       = b.source || 'manual';

    if (b.public_client_id) {
      this.form.public_client_id = b.public_client_id;
      const parts = (b.client_name || '').trim().split(/\s+/);
      this.form.first_name   = parts[0] || '';
      this.form.last_name    = parts.slice(1).join(' ') || '';
      this.form.client_phone = b.client_phone || '';
      this.form.client_email = b.client_email || '';
    }

    this.showDetail = false;
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
    this.payError = null;
    try {
      const res  = await fetch(baseUrl + '/api/bookings/' + booking.id, { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) this.selectedBooking = data.data?.booking ?? data.data ?? booking;
    } catch(e) {}
    finally {
      this.detailLoading = false;
      this.resetPayForm();
    }
  },

  resetPayForm() {
    const remaining = this.bookingPayRemaining;
    this.payForm = { amount: remaining ? remaining.remaining : '', method: 'cash', type: 'full', notes: '' };
    this.payError = null;
  },

  get bookingPayRemaining() {
    const b = this.selectedBooking;
    if (!b || !b.invoice_id) return null;
    const ttc  = parseFloat(b.amount_ttc ?? b.total_amount) || 0;
    const paid = parseFloat(b.paid_amount) || 0;
    return { ttc, paid, remaining: Math.max(0, ttc - paid) };
  },

  async submitBookingPayment() {
    const b = this.selectedBooking;
    const amount = Number(this.payForm.amount) || 0;
    if (!b?.invoice_id) { this.payError = 'Aucune facture associée à cette réservation.'; return; }
    if (amount <= 0) { this.payError = 'Montant invalide.'; return; }

    this.paySubmitting = true; this.payError = null;
    try {
      const res  = await fetch(baseUrl + '/api/payments', {
        method: 'POST', headers: this.apiHeaders(),
        body: JSON.stringify({
          booking_id: b.id, invoice_id: b.invoice_id,
          amount, method: this.payForm.method, type: this.payForm.type,
          notes: this.payForm.notes || null,
        }),
      });
      const data = await res.json();
      if (data.success) {
        await this.openDetail(b);
        await this.loadBookings();
        this.showToast('Paiement enregistré avec succès.', 'success');
      } else {
        this.payError = data.message ?? 'Erreur lors de l\'enregistrement du paiement.';
      }
    } catch(e) {
      this.payError = 'Erreur réseau.';
    } finally { this.paySubmitting = false; }
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
      };

      if (this.form.public_client_id) {
        payload.public_client_id = this.form.public_client_id;
      } else {
        payload.client = {
          first_name: this.form.first_name.trim(),
          last_name:  this.form.last_name.trim(),
          email:      this.form.client_email  || null,
          phone:      this.form.client_phone  || null,
        };
      }

      if (this.form.record_payment) {
        payload.payment = {
          type:   this.form.payment_type,
          amount: this.form.payment_type === 'partial' ? Number(this.form.payment_amount) || 0 : null,
          method: this.form.payment_method,
        };
      }

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
      public_client_id: '', first_name: '', last_name: '', client_email: '', client_phone: '',
      booking_type: 'nuit', hours: 3, guests_count: 2, source: 'manual', notes: '',
      record_payment: false, payment_type: 'full', payment_method: 'cash', payment_amount: '',
    };
    this.clientSearch = '';
    this._autoCheckout = null;
    this.createError = null;
  },

  get selectedRoomForBooking() {
    return this.rooms.find(r => r.id == this.form.room_id) || null;
  },

  photoUrl(path) {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    const clean = path.replace(/^\/+/, '');
    return baseUrl + '/' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
  },

  get selectedRoomPhotoUrl() {
    return this.photoUrl(this.selectedRoomForBooking?.cover_photo);
  },

  get bookingClientName() {
    const name = [this.form.first_name, this.form.last_name].filter(Boolean).join(' ').trim();
    return name || null;
  },

  get bookingDurationLabel() {
    if (this.form.booking_type === 'passage') {
      const h = Number(this.form.hours) || 0;
      return h + ' heure' + (h > 1 ? 's' : '');
    }
    const nights = this.bookingNights;
    return nights + ' nuit' + (nights > 1 ? 's' : '');
  },

  get bookingNights() {
    if (!this.form.check_in || !this.form.check_out) return 1;
    const n = Math.round((new Date(this.form.check_out) - new Date(this.form.check_in)) / 86_400_000);
    return n > 0 ? n : 1;
  },

  get bookingRate() {
    const room = this.selectedRoomForBooking;
    if (!room) return 0;
    if (this.form.booking_type === 'passage') return Number(room.passage_price) || 0;
    if (this.form.booking_type === 'weekend')  return Number(room.weekend_price) || Number(room.base_price) || 0;
    return Number(room.base_price) || 0;
  },

  get bookingEstimatedTotal() {
    const room = this.selectedRoomForBooking;
    if (!room) return 0;
    if (this.form.booking_type === 'passage') return this.bookingRate * (Number(this.form.hours) || 0);
    if (this.form.booking_type === 'weekend') return Number(room.weekend_price) || (this.bookingRate * this.bookingNights);

    // Séjour "Nuit" : le tarif week-end est un FORFAIT pour un bloc complet
    // vendredi+samedi+dimanche (pas un prix par nuit) — un week-end incomplet
    // reste au tarif de base. Cohérent avec le calcul serveur (Booking::calculateAmount)
    // et l'estimation affichée côté client sur la page de réservation publique (booking.js).
    const basePrice    = Number(room.base_price) || 0;
    const weekendPrice = Number(room.weekend_price) || 0;
    if (!weekendPrice || weekendPrice === basePrice || !this.form.check_in) return basePrice * this.bookingNights;

    const start = new Date(this.form.check_in + 'T00:00:00');
    const dows  = [];
    for (let i = 0; i < this.bookingNights; i++) {
      const day = new Date(start);
      day.setDate(start.getDate() + i);
      dows.push(day.getDay());
    }
    let total = 0;
    for (let i = 0; i < dows.length; i++) {
      if (dows[i] === 5 && dows[i + 1] === 6 && dows[i + 2] === 0) {
        total += weekendPrice;
        i += 2; // bloc vendredi-samedi-dimanche déjà compté
      } else {
        total += basePrice;
      }
    }
    return total;
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
  typeColor(t) {
    return { nuit: '#1D4ED8', weekend: '#6D28D9', passage: '#92400E' }[t] ?? '#6B7280';
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
