/* ============================================================
   Ivoire Stay — Page SaaS : Planning
   ============================================================ */

function planningPage(baseUrl) {
  return {
  ...saasHelpers,

  bookings: [],
  rooms: [],
  loading: true,
  error: null,
  currentMonth: null,
  selectedBooking: null,
  _dayMap: {},

  async init() {
    this.currentMonth = this._firstOfMonth();
    await this.loadData();
  },

  _firstOfMonth() {
    const t = new Date();
    return new Date(t.getFullYear(), t.getMonth(), 1);
  },

  _ymd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  },

  async loadData() {
    this.loading = true;
    this.error   = null;
    try {
      // Plage : 1er du mois précédent → dernier jour du mois suivant
      const m   = this.currentMonth ?? this._firstOfMonth();
      const from = this._ymd(new Date(m.getFullYear(), m.getMonth() - 1, 1));
      const to   = this._ymd(new Date(m.getFullYear(), m.getMonth() + 2, 0));

      const qs = `establishment_id=${this.estId()}&from=${from}&to=${to}`;
      const [roomsRes, planRes] = await Promise.all([
        fetch(baseUrl + '/api/rooms?'    + qs, { headers: this.apiHeaders() }),
        fetch(baseUrl + '/api/planning?' + qs, { headers: this.apiHeaders() }),
      ]);
      const roomsData = await roomsRes.json();
      const planData  = await planRes.json();

      this.rooms = roomsData.success
        ? (roomsData.data?.rooms ?? roomsData.data ?? [])
        : [];

      const raw = planData.success
        ? (planData.data?.bookings ?? planData.data ?? [])
        : [];

      this.bookings = raw.map(bk => ({
        ...bk,
        client_name:  bk.client_name ?? bk.public_client?.name ?? bk.user?.name ?? 'Client',
        total_amount: bk.total_amount ?? bk.total_price ?? 0,
      }));

      this._buildDayMap();

      if (!roomsData.success) this.error = roomsData.message ?? 'Impossible de charger les chambres.';
    } catch(e) {
      this.rooms    = [];
      this.bookings = [];
      this._dayMap  = {};
      this.error = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
      this.loading = false;
    }
  },

  /* Construit un index "YYYY-MM-DD" → [bookings]. Plain object pour compatibilité Alpine. */
  _buildDayMap() {
    const map = {};
    for (const b of this.bookings) {
      const ci = new Date(b.check_in.replace(' ', 'T'));
      const co = new Date(b.check_out.replace(' ', 'T'));
      ci.setHours(0, 0, 0, 0);
      co.setHours(0, 0, 0, 0);
      // Passage (check_in === check_out) : inclure ce jour
      const end = ci.getTime() === co.getTime() ? new Date(ci.getTime() + 86_400_000) : co;
      for (let d = new Date(ci); d < end; d.setDate(d.getDate() + 1)) {
        const key = this._ymd(d);
        if (!map[key]) map[key] = [];
        map[key].push(b);
      }
    }
    this._dayMap = map;
  },

  get monthLabel() {
    if (!this.currentMonth) return '';
    return this.currentMonth.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
  },

  get showDetail() { return this.selectedBooking !== null; },

  get calendarCells() {
    if (!this.currentMonth) return [];
    const year  = this.currentMonth.getFullYear();
    const month = this.currentMonth.getMonth();

    /* Premier lundi de la grille (peut être dans le mois précédent) */
    let d = new Date(year, month, 1);
    const dow = d.getDay();
    d.setDate(d.getDate() + (dow === 0 ? -6 : 1 - dow));

    const todayTs = new Date().setHours(0, 0, 0, 0);
    const cells   = [];

    for (let i = 0; i < 42; i++) {
      const ts = new Date(d).setHours(0, 0, 0, 0);
      cells.push({
        ymd:            this._ymd(d),
        day:            d.getDate(),
        isCurrentMonth: d.getMonth() === month,
        isToday:        ts === todayTs,
      });
      d.setDate(d.getDate() + 1);
    }
    return cells;
  },

  /* O(1) — lit l'index pré-construit. Accepte "YYYY-MM-DD". */
  getBookingsForDay(ymd) {
    return this._dayMap[ymd] ?? [];
  },

  roomName(roomId) {
    const r = this.rooms.find(r => r.id == roomId);
    return r ? (r.number ? 'Ch. ' + r.number : r.name ?? '') : '';
  },

  chipColor(status)  { return BOOKING_STATUS[status]?.color ?? '#9CA3AF'; },
  statusLabel(s)     { return BOOKING_STATUS[s]?.label      ?? s; },
  statusBadge(s)     { return BOOKING_STATUS[s]?.badge      ?? 'badge'; },

  async shiftMonth(delta) {
    const d = new Date(this.currentMonth);
    d.setMonth(d.getMonth() + delta);
    this.currentMonth = d;
    await this.loadData();
  },
  async prevMonth() { await this.shiftMonth(-1); },
  async nextMonth() { await this.shiftMonth(+1); },
  async goToday()   { this.currentMonth = this._firstOfMonth(); await this.loadData(); },

  openDetail(booking)  { this.selectedBooking = booking; },
  closeDetail()        { this.selectedBooking = null; },
  };
}
