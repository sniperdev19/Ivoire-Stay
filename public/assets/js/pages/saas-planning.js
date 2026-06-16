/* ============================================================
   Ivoire Stay — Page SaaS : Planning (src/templates/saas/planning.php)
   ============================================================ */

function planningPage(baseUrl) {
  return {
  bookings: [],
  rooms: [],
  loading: true,
  error: null,
  viewDays: 14,
  startDate: null,
  selectedBooking: null,
  showDetail: false,

  fallbackRooms: [
    { id:1, name:'101', room_type:{ name:'Standard' } },
    { id:2, name:'102', room_type:{ name:'Standard' } },
    { id:3, name:'103', room_type:{ name:'Deluxe' } },
    { id:4, name:'201', room_type:{ name:'Suite Junior' } },
    { id:5, name:'301', room_type:{ name:'Présidentielle' } },
  ],
  fallbackBookings: [
    { id:1, room_id:1, client_name:'Kouamé Adou',
      check_in:'2026-06-13', check_out:'2026-06-16',
      status:'confirmed', total_amount:165000 },
    { id:2, room_id:3, client_name:'Fatou Diallo',
      check_in:'2026-06-14', check_out:'2026-06-15',
      status:'checked_in', total_amount:95000 },
    { id:3, room_id:4, client_name:'Marc Koffi',
      check_in:'2026-06-15', check_out:'2026-06-17',
      status:'pending', total_amount:290000 },
    { id:4, room_id:2, client_name:'Awa Traoré',
      check_in:'2026-06-16', check_out:'2026-06-18',
      status:'confirmed', total_amount:110000 },
    { id:5, room_id:5, client_name:'Yao Brou',
      check_in:'2026-06-12', check_out:'2026-06-14',
      status:'checked_out', total_amount:640000 },
  ],

  async init() {
    this.startDate = this.getMonday(new Date());
    await this.loadData();
  },

  getMonday(d) {
    const date = new Date(d);
    const day = date.getDay();
    const diff = date.getDate() - day + (day===0?-6:1);
    date.setDate(diff);
    date.setHours(0,0,0,0);
    return date;
  },

  apiHeaders() {
    return {
      'Content-Type':'application/json',
      'Authorization':'Bearer '
        + localStorage.getItem('token')
    };
  },
  estId() {
    return localStorage.getItem('establishment_id')||'1';
  },

  async loadData() {
    this.loading = true;
    this.error = null;
    try {
      const [roomsRes, planRes] = await Promise.all([
        fetch(baseUrl + '/api/rooms'
          + '?establishment_id=' + this.estId(),
          { headers: this.apiHeaders() }),
        fetch(baseUrl + '/api/planning'
          + '?establishment_id=' + this.estId(),
          { headers: this.apiHeaders() })
      ]);

      const roomsData = await roomsRes.json();
      const planData = await planRes.json();

      let r = roomsData.success
        ? (roomsData.data?.rooms
            ?? roomsData.data ?? [])
        : [];
      this.rooms = r.length ? r : this.fallbackRooms;

      let b = planData.success
        ? (planData.data?.bookings
            ?? planData.data ?? [])
        : [];
      this.bookings = (b.length ? b : this.fallbackBookings)
        .map(bk => ({
          ...bk,
          client_name: bk.client_name
            ?? bk.public_client?.name
            ?? bk.user?.name
            ?? 'Client',
          total_amount: bk.total_amount
            ?? bk.total_price ?? 0,
        }));

    } catch(e) {
      this.rooms = this.fallbackRooms;
      this.bookings = this.fallbackBookings;
      this.error = 'Données de démonstration affichées.';
    } finally {
      this.loading = false;
    }
  },

  get days() {
    const result = [];
    for (let i = 0; i < this.viewDays; i++) {
      const d = new Date(this.startDate);
      d.setDate(d.getDate() + i);
      result.push(d);
    }
    return result;
  },

  isToday(date) {
    const t = new Date();
    return date.toDateString() === t.toDateString();
  },

  formatDayLabel(date) {
    return date.toLocaleDateString('fr-FR',
      { weekday:'short', day:'numeric' });
  },

  formatMonthLabel(date) {
    return date.toLocaleDateString('fr-FR',
      { month:'long', year:'numeric' });
  },

  prevWeek() {
    const d = new Date(this.startDate);
    d.setDate(d.getDate() - 7);
    this.startDate = d;
  },
  nextWeek() {
    const d = new Date(this.startDate);
    d.setDate(d.getDate() + 7);
    this.startDate = d;
  },
  goToday() {
    this.startDate = this.getMonday(new Date());
  },

  getBookingForRoomDay(roomId, date) {
    return this.bookings.find(b => {
      if (b.room_id != roomId) return false;
      const ci = new Date(b.check_in);
      const co = new Date(b.check_out);
      ci.setHours(0,0,0,0);
      co.setHours(0,0,0,0);
      const d = new Date(date);
      d.setHours(0,0,0,0);
      return d >= ci && d < co;
    });
  },

  getBarStyle(booking) {
    const days = this.days;
    const ci = new Date(booking.check_in);
    const co = new Date(booking.check_out);
    ci.setHours(0,0,0,0);
    co.setHours(0,0,0,0);

    const startD = new Date(days[0]);
    const endD = new Date(days[days.length-1]);
    startD.setHours(0,0,0,0);
    endD.setHours(23,59,59,0);

    const visStart = ci < startD ? startD : ci;
    const visEnd = co > endD ? 
      new Date(endD.getTime()+1) : co;

    const totalMs = (this.viewDays) * 86400000;
    const left = Math.max(0,
      (visStart - startD) / totalMs * 100);
    const width = Math.max(1,
      (visEnd - visStart) / totalMs * 100);

    return `left:${left}%;width:${width}%;`;
  },

  bookingsForRoom(roomId) {
    const days = this.days;
    if (!days.length) return [];
    const startD = new Date(days[0]);
    const endD = new Date(days[days.length-1]);
    startD.setHours(0,0,0,0);
    endD.setHours(23,59,59,0);
    return this.bookings.filter(b => {
      if (b.room_id != roomId) return false;
      const ci = new Date(b.check_in);
      const co = new Date(b.check_out);
      return co > startD && ci <= endD;
    });
  },

  openDetail(booking) {
    this.selectedBooking = booking;
    this.showDetail = true;
  },

  formatPrice(p) {
    return new Intl.NumberFormat('fr-FR')
      .format(p??0) + ' FCFA';
  },
  formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('fr-FR',{
      day:'numeric', month:'short', year:'numeric'
    });
  },
  statusLabel(s) {
    return {
      confirmed:'Confirmée', pending:'En attente',
      checked_in:'Arrivée', checked_out:'Départ',
      cancelled:'Annulée'
    }[s] ?? s;
  },
  statusBadge(s) {
    return {
      confirmed:'badge badge-success',
      pending:'badge badge-warning',
      checked_in:'badge badge-info',
      checked_out:'badge badge-gold',
      cancelled:'badge badge-danger'
    }[s] ?? 'badge';
  }
  };
}
