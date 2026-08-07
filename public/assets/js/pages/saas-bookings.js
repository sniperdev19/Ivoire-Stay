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
  createStep: 1,
  createLoading: false,
  createError: null,
  rooms: [],
  clients: [],
  clientSearch: '',
  _autoCheckout: null,

  // ── Calendrier de sélection des dates (même principe que la vitrine
  //    publique : src/templates/vitrine/booking.php / booking.js) ────────
  calMonth: null,
  calAvailability: {},
  calAvailabilityLoading: false,
  calSelecting: false,

  form: {
    room_id: '', check_in: '', check_out: '',
    public_client_id: '', first_name: '', last_name: '', client_email: '', client_phone: '',
    id_doc_type: '', id_doc_number: '',
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
    this.calMonth = this._calStartOfMonth(new Date());
    this.$watch('form.check_in',     () => this.autoSuggestCheckout());
    this.$watch('form.booking_type', () => this.autoSuggestCheckout());
    this.$watch('form.room_id',      () => this.loadCalAvailability());
  },

  openCreate() {
    this.resetForm();
    this.showCreate = true;
  },

  // ── Calendrier de sélection des dates ────────────────────────────────
  _calTodayStr() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  },
  _calStartOfMonth(d) { return new Date(d.getFullYear(), d.getMonth(), 1); },

  async loadCalAvailability() {
    this.calAvailability = {};
    if (!this.form.room_id) return;
    this.calAvailabilityLoading = true;
    try {
      const from   = this._calTodayStr();
      const toDate = new Date(); toDate.setDate(toDate.getDate() + 400);
      const to     = toDate.getFullYear() + '-' + String(toDate.getMonth() + 1).padStart(2, '0') + '-' + String(toDate.getDate()).padStart(2, '0');
      const res    = await fetch(baseUrl + `/api/public/availability/${this.form.room_id}?from=${from}&to=${to}`);
      const data   = await res.json();
      if (data.success && data.data) this.calAvailability = data.data;
    } catch(e) { /* silencieux — le calendrier reste vide, la création vérifie côté serveur */ }
    finally { this.calAvailabilityLoading = false; }
  },

  isCalBooked(dateStr) {
    const status = this.calAvailability[dateStr];
    return !!status && status !== 'available';
  },

  calRangeHasBooked(start, end) {
    if (!start || !end || start === end) return this.isCalBooked(start);
    const s = new Date(start + 'T00:00:00');
    const e = new Date(end + 'T00:00:00');
    for (let d = new Date(s); d < e; d.setDate(d.getDate() + 1)) {
      if (this.isCalBooked(d.toISOString().slice(0, 10))) return true;
    }
    return false;
  },

  calMonthLabel() {
    return this.calMonth ? this.calMonth.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) : '';
  },

  isCurrentCalMonth() {
    const t = this._calStartOfMonth(new Date());
    return this.calMonth && this.calMonth.getFullYear() === t.getFullYear() && this.calMonth.getMonth() === t.getMonth();
  },

  calPrevMonth() {
    if (this.isCurrentCalMonth()) return;
    this.calMonth = new Date(this.calMonth.getFullYear(), this.calMonth.getMonth() - 1, 1);
  },
  calNextMonth() {
    this.calMonth = new Date(this.calMonth.getFullYear(), this.calMonth.getMonth() + 1, 1);
  },

  // Grille 7 colonnes (Lun→Dim), null = case vide de calage en début de mois
  calDays() {
    if (!this.calMonth) return [];
    const year  = this.calMonth.getFullYear();
    const month = this.calMonth.getMonth();
    const startOffset = (new Date(year, month, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = this._calTodayStr();
    const cells = [];
    for (let i = 0; i < startOffset; i++) cells.push(null);
    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      cells.push({
        date:    dateStr,
        day,
        booked:  this.isCalBooked(dateStr),
        past:    dateStr < today,
        inRange: !!(this.form.check_in && this.form.check_out && dateStr > this.form.check_in && dateStr < this.form.check_out),
      });
    }
    return cells;
  },

  // Sélection en 2 clics (arrivée puis départ) — "passage" = un seul jour.
  // this.calSelecting distingue une sélection en cours d'une déjà bouclée,
  // indépendamment du départ pré-rempli automatiquement par autoSuggestCheckout().
  calSelectDate(dateStr) {
    if (this.isCalBooked(dateStr) || dateStr < this._calTodayStr()) return;

    if (this.form.booking_type === 'passage') {
      this.form.check_in  = dateStr;
      this.form.check_out = dateStr;
      return;
    }

    if (!this.form.check_in || !this.calSelecting) {
      this.form.check_in  = dateStr;
      this.form.check_out = '';
      this.calSelecting   = true;
      return;
    }
    if (dateStr <= this.form.check_in) {
      this.form.check_in  = dateStr;
      this.form.check_out = '';
      return;
    }
    if (this.calRangeHasBooked(this.form.check_in, dateStr)) {
      this.createError = 'Une réservation existante se trouve dans cette période.';
      return;
    }
    this.form.check_out = dateStr;
    this.calSelecting   = false;
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
    this.form.id_doc_type   = c.id_doc_type   || '';
    this.form.id_doc_number = c.id_doc_number || '';
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
    const isPassage = this.form.booking_type === 'passage';
    if (!this.form.check_in || (!isPassage && !this.form.check_out)) {
      this.createError = 'Veuillez sélectionner les dates du séjour sur le calendrier.';
      return;
    }
    this.createLoading = true;
    try {
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
          first_name:    this.form.first_name.trim(),
          last_name:     this.form.last_name.trim(),
          email:         this.form.client_email  || null,
          phone:         this.form.client_phone  || null,
          id_doc_type:   this.form.id_doc_type   || null,
          id_doc_number: this.form.id_doc_number || null,
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
    id_doc_type: '', id_doc_number: '',
      booking_type: 'nuit', hours: 3, guests_count: 2, source: 'manual', notes: '',
      record_payment: false, payment_type: 'full', payment_method: 'cash', payment_amount: '',
    };
    this.clientSearch = '';
    this._autoCheckout = null;
    this.createError = null;
    this.createStep = 1;
    this.calMonth = this._calStartOfMonth(new Date());
    this.calAvailability = {};
    this.calSelecting = false;
  },

  // ── Étapes du formulaire de création ─────────────────────────────────
  get canProceedStep1() {
    if (!this.form.room_id || !this.form.check_in) return false;
    if (this.form.booking_type !== 'passage' && !this.form.check_out) return false;
    return true;
  },
  get canProceedStep2() {
    if (this.form.public_client_id) return true;
    return !!(this.form.first_name.trim() && this.form.last_name.trim() && this.form.client_phone.trim());
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
  initials(name) {
    const parts = (name || '?').split(' ').filter(Boolean);
    return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '');
  },
  avatarBg(id) {
    const colors = [
      'linear-gradient(135deg,#C9A84C,#A67C2E)',
      'linear-gradient(135deg,#2563EB,#1D4ED8)',
      'linear-gradient(135deg,#16a34a,#15803D)',
      'linear-gradient(135deg,#7C3AED,#6D28D9)',
      'linear-gradient(135deg,#DC2626,#B91C1C)',
    ];
    return colors[(id ?? 0) % colors.length];
  },
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
