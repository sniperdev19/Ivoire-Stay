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
  _dayMap: new Map(),

  async init() {
    this.currentMonth = this._firstOfMonth();
    await this.loadData();
  },

  _firstOfMonth() {
    const t = new Date();
    return new Date(t.getFullYear(), t.getMonth(), 1);
  },

  async loadData() {
    this.loading = true;
    this.error   = null;
    try {
      const [roomsRes, planRes] = await Promise.all([
        fetch(baseUrl + '/api/rooms?establishment_id='    + this.estId(), { headers: this.apiHeaders() }),
        fetch(baseUrl + '/api/planning?establishment_id=' + this.estId(), { headers: this.apiHeaders() }),
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
      this._dayMap  = new Map();
      this.error = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
      this.loading = false;
    }
  },

  /* Construit un index jour→[bookings] en O(n×durée), appelé une seule fois après loadData. */
  _buildDayMap() {
    const map = new Map();
    for (const b of this.bookings) {
      const ciTs = new Date(b.check_in).setHours(0, 0, 0, 0);
      const coTs = new Date(b.check_out).setHours(0, 0, 0, 0);
      for (let ts = ciTs; ts < coTs; ts += 86_400_000) {
        if (!map.has(ts)) map.set(ts, []);
        map.get(ts).push(b);
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
        date:           new Date(ts),
        day:            d.getDate(),
        isCurrentMonth: d.getMonth() === month,
        isToday:        ts === todayTs,
      });
      d.setDate(d.getDate() + 1);
    }
    return cells;
  },

  /* O(1) — lit l'index pré-construit */
  getBookingsForDay(date) {
    return this._dayMap.get(new Date(date).setHours(0, 0, 0, 0)) ?? [];
  },

  roomName(roomId) { return this.rooms.find(r => r.id == roomId)?.name ?? ''; },

  chipColor(status)  { return BOOKING_STATUS[status]?.color ?? '#9CA3AF'; },
  statusLabel(s)     { return BOOKING_STATUS[s]?.label      ?? s; },
  statusBadge(s)     { return BOOKING_STATUS[s]?.badge      ?? 'badge'; },

  shiftMonth(delta) {
    const d = new Date(this.currentMonth);
    d.setMonth(d.getMonth() + delta);
    this.currentMonth = d;
  },
  prevMonth() { this.shiftMonth(-1); },
  nextMonth() { this.shiftMonth(+1); },
  goToday()   { this.currentMonth = this._firstOfMonth(); },

  openDetail(booking)  { this.selectedBooking = booking; },
  closeDetail()        { this.selectedBooking = null; },
  };
}
