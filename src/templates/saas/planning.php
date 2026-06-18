<?php
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<style>
  .planning-day-header {
    font-size:11px;font-weight:700;
    color:#9CA3AF;text-transform:uppercase;
    letter-spacing:0.06em;padding:8px 12px;
    background:#FAFAFA;border-radius:8px;
    margin-bottom:8px;
  }
  .planning-room-row {
    display:grid;
    grid-template-columns:120px 1fr;
    gap:8px;align-items:center;
    margin-bottom:6px;
  }
  .planning-room-label {
    font-size:13px;font-weight:600;color:#374151;
    white-space:nowrap;overflow:hidden;
    text-overflow:ellipsis;
  }
  .planning-bar-wrap {
    position:relative;height:36px;
    background:#F3F4F6;border-radius:8px;
    overflow:hidden;
  }
  .planning-bar {
    position:absolute;top:4px;height:28px;
    border-radius:6px;display:flex;
    align-items:center;padding:0 8px;
    font-size:11px;font-weight:600;
    color:white;white-space:nowrap;
    overflow:hidden;text-overflow:ellipsis;
    cursor:pointer;transition:opacity 0.2s;
    min-width:40px;
  }
  .planning-bar:hover { opacity:0.85; }
  .planning-bar.confirmed { background:#16a34a; }
  .planning-bar.pending   { background:#D97706; }
  .planning-bar.checked_in { background:#2563EB; }
  .planning-bar.checked_out { background:#6B7280; }
  .planning-bar.cancelled  { background:#DC2626; }

  .cal-day-col {
    flex:1;min-width:32px;text-align:center;
    font-size:11px;color:#9CA3AF;
    border-right:1px solid rgba(0,0,0,0.05);
    padding:4px 0;
  }
  .cal-day-col.today {
    background:rgba(201,168,76,0.08);
    color:#C9A84C;font-weight:700;
  }
  @keyframes spin { to { transform:rotate(360deg); } }
  @keyframes shimmer {
    0%{background-position:200% 0}
    100%{background-position:-200% 0}
  }
</style>

<div x-data="{
  bookings: [],
  rooms: [],
  loading: true,
  error: null,
  viewDays: 14,
  startDate: null,
  selectedBooking: null,
  showDetail: false,

  /* fallbackRooms: [
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
  ], */

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

  apiHeaders() { const token = localStorage.getItem('token') ?? ''; return { 'Content-Type':'application/json', 'Authorization':'Bearer ' + token }; },
  apiBase: '<?= rtrim($base_url, '/') ?>',
  apiUrl(path) { return this.apiBase + path; },
  estId() {
    let id = localStorage.getItem('establishment_id');
    if (id && id !== 'null' && id !== 'undefined') return id;
    try { const list = JSON.parse(localStorage.getItem('establishments') || '[]'); if (Array.isArray(list) && list.length > 0) { id = list[0].id ?? list[0].establishment_id; if (id) { localStorage.setItem('establishment_id', String(id)); return String(id); } } } catch(e) {}
    try { const user = JSON.parse(localStorage.getItem('user') || '{}'); if (user.establishment_id) { localStorage.setItem('establishment_id', String(user.establishment_id)); return String(user.establishment_id); } } catch(e) {}
    return '1';
  },

  async loadData() {
    this.loading = true;
    this.error = null;
    try {
      const [roomsRes, planRes] = await Promise.all([
        fetch(this.apiUrl('/api/rooms?establishment_id=' + this.estId()), { headers: this.apiHeaders() }),
        fetch(this.apiUrl('/api/planning?establishment_id=' + this.estId()), { headers: this.apiHeaders() })
      ]);

      const roomsData = await roomsRes.json();
      const planData = await planRes.json();

      if (roomsData.success) {
        this.rooms = roomsData.data?.rooms ?? roomsData.data ?? [];
        // Ne PAS activer le fallback si l'API répond avec succès
      } else {
        console.warn('API error:', roomsData.message);
        this.rooms = [];
      }

      if (planData.success) {
        const b = planData.data?.bookings ?? planData.data ?? [];
        this.bookings = b.map(bk => ({
          ...bk,
          client_name: bk.client_name
            ?? bk.public_client?.name
            ?? bk.user?.name
            ?? 'Client',
          total_amount: bk.total_amount
            ?? bk.total_price ?? 0,
        }));
        // Ne PAS activer le fallback si l'API répond avec succès
      } else {
        console.warn('API error:', planData.message);
        this.bookings = [];
      }

    } catch(e) {
      this.rooms = [];
      this.bookings = [];
      console.error('Network error:', e);
      this.error = 'Impossible de charger le planning. Veuillez réessayer.';
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
}"
 x-init="init()"
 @keydown.escape.window="showDetail=false">

  <!-- HEADER -->
  <div style="display:flex;align-items:center;
    justify-content:space-between;
    margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;
        color:#111827;margin:0 0 4px;">Planning</h1>
      <p style="font-size:13px;color:#9CA3AF;margin:0;"
        x-text="formatMonthLabel(startDate 
          ?? new Date())">
      </p>
    </div>
    <div style="display:flex;align-items:center;
      gap:8px;flex-wrap:wrap;">
      <div style="display:flex;gap:10px;flex-wrap:wrap;
        margin-right:8px;">
        <?php
          $statuses = [
            ['confirmed','#16a34a','Confirmée'],
            ['checked_in','#2563EB','Arrivée'],
            ['pending','#D97706','En attente'],
            ['cancelled','#DC2626','Annulée'],
          ];
          foreach($statuses as [$s,$c,$l]):
        ?>
        <div style="display:flex;align-items:center;
          gap:5px;">
          <div style="width:10px;height:10px;
            border-radius:3px;background:<?= $c ?>;
            flex-shrink:0;">
          </div>
          <span style="font-size:11px;color:#6B7280;">
            <?= $l ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;background:white;
        border:1px solid rgba(0,0,0,0.08);
        border-radius:10px;padding:3px;gap:2px;">
        <button @click="viewDays=7"
          :style="viewDays===7
            ?'background:#1B4332;color:white;'
            :'background:transparent;color:#6B7280;'"
          style="padding:5px 12px;border-radius:7px;
            border:none;font-size:12px;font-weight:500;
            cursor:pointer;transition:all 0.2s;
            font-family:Inter,sans-serif;">
          7 jours
        </button>
        <button @click="viewDays=14"
          :style="viewDays===14
            ?'background:#1B4332;color:white;'
            :'background:transparent;color:#6B7280;'"
          style="padding:5px 12px;border-radius:7px;
            border:none;font-size:12px;font-weight:500;
            cursor:pointer;transition:all 0.2s;
            font-family:Inter,sans-serif;">
          14 jours
        </button>
        <button @click="viewDays=30"
          :style="viewDays===30
            ?'background:#1B4332;color:white;'
            :'background:transparent;color:#6B7280;'"
          style="padding:5px 12px;border-radius:7px;
            border:none;font-size:12px;font-weight:500;
            cursor:pointer;transition:all 0.2s;
            font-family:Inter,sans-serif;">
          30 jours
        </button>
      </div>
      <button @click="prevWeek()" class="btn-saas-secondary"
        style="padding:8px 12px;">
        <svg xmlns="http://www.w3.org/2000/svg"
          style="width:15px;height:15px;" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2"
            d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <button @click="goToday()" class="btn-saas-secondary"
        style="padding:8px 14px;font-size:12px;">
        Aujourd'hui
      </button>
      <button @click="nextWeek()" class="btn-saas-secondary"
        style="padding:8px 12px;">
        <svg xmlns="http://www.w3.org/2000/svg"
          style="width:15px;height:15px;" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2"
            d="M9 5l7 7-7 7"/>
        </svg>
      </button>
      <a href="<?= $base_url ?>/saas/bookings"
        class="btn-saas-primary">
        <svg xmlns="http://www.w3.org/2000/svg"
          style="width:15px;height:15px;" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round"
            stroke-linejoin="round" stroke-width="2"
            d="M12 4v16m8-8H4"/>
        </svg>
        Réservation
      </a>
    </div>
  </div>

  <div x-show="error"
    style="padding:12px 16px;background:#FEF3C7;
      border:1px solid rgba(217,119,6,0.3);
      border-radius:10px;color:#92400E;
      font-size:13px;margin-bottom:16px;"
    x-text="error">
  </div>

  <div x-show="loading" class="saas-card"
    style="padding:40px;text-align:center;">
    <div style="width:32px;height:32px;border-radius:50%;
      border:3px solid rgba(201,168,76,0.2);
      border-top-color:#C9A84C;
      animation:spin 0.8s linear infinite;
      margin:0 auto 12px;">
    </div>
    <div style="font-size:14px;color:#9CA3AF;">
      Chargement du planning...
    </div>
  </div>

  <div x-show="!loading" class="saas-card"
    style="padding:0;overflow:hidden;">

    <div style="
      display:grid;
      border-bottom:2px solid rgba(0,0,0,0.08);
      position:sticky;top:0;z-index:10;
      background:white;
    "
    :style="'grid-template-columns:140px repeat('
      + viewDays + ',1fr);'">
      <div style="padding:12px;border-right:
        1px solid rgba(0,0,0,0.06);
        background:#FAFAFA;">
        <span style="font-size:11px;font-weight:600;
          color:#9CA3AF;text-transform:uppercase;
          letter-spacing:0.06em;">Chambre</span>
      </div>
      <template x-for="(day, idx) in days" :key="idx">
        <div :class="isToday(day)?'cal-day-col today':'cal-day-col'"
          style="padding:8px 4px;
            border-right:1px solid rgba(0,0,0,0.05);">
          <div style="font-size:10px;font-weight:600;
            text-transform:uppercase;"
            x-text="day.toLocaleDateString('fr-FR',
              {weekday:'short'})">
          </div>
          <div style="font-size:13px;font-weight:700;
            margin-top:2px;"
            x-text="day.getDate()">
          </div>
        </div>
      </template>
    </div>

    <div style="max-height:calc(100vh - 280px);
      overflow-y:auto;">
      <template x-for="room in rooms" :key="room.id">
        <div style="display:grid;
          border-bottom:1px solid rgba(0,0,0,0.05);"
          :style="'grid-template-columns:140px 1fr;'">

          <div style="padding:10px 12px;
            border-right:1px solid rgba(0,0,0,0.06);
            background:#FAFAFA;
            display:flex;flex-direction:column;
            justify-content:center;">
            <div style="font-size:13px;font-weight:700;
              color:#111827;" x-text="room.name">
            </div>
            <div style="font-size:11px;color:#9CA3AF;"
              x-text="room.room_type?.name??''">
            </div>
          </div>

          <div style="position:relative;height:48px;">
            <div style="display:flex;height:100%;
              position:absolute;inset:0;">
              <template x-for="(d,i) in days" :key="i">
                <div :class="isToday(d)
                  ?'flex-1 border-r border-dashed'
                  :'flex-1 border-r'"
                  :style="isToday(d)
                    ?'border-color:rgba(201,168,76,0.3);background:rgba(201,168,76,0.04);'
                    :'border-color:rgba(0,0,0,0.04);'">
                </div>
              </template>
            </div>

            <template x-for="booking in 
              bookingsForRoom(room.id)"
              :key="booking.id">
              <div
                :class="'planning-bar ' + booking.status"
                :style="getBarStyle(booking)"
                @click="openDetail(booking)"
                :title="booking.client_name">
                <span x-text="booking.client_name">
                </span>
              </div>
            </template>
          </div>
        </div>
      </template>

      <div x-show="!rooms.length"
        style="padding:48px;text-align:center;
          color:#9CA3AF;font-size:14px;">
        Aucune chambre configurée.
        <a href="<?= $base_url ?>/saas/rooms"
          style="color:#C9A84C;font-weight:600;">
          Ajouter des chambres →
        </a>
      </div>
    </div>
  </div>

  <div x-show="showDetail" 
    class="saas-modal-bg"
    @click.self="showDetail=false">
    <div class="saas-modal" style="max-width:480px;">
      <div class="saas-modal-header">
        <div>
          <p style="font-size:12px;color:#9CA3AF;margin:0;">
            Réservation #<span 
              x-text="selectedBooking?.id"></span>
          </p>
          <h2 style="font-size:17px;font-weight:700;
            color:#111827;margin:4px 0 0;"
            x-text="selectedBooking?.client_name">
          </h2>
        </div>
        <button @click="showDetail=false"
          style="background:none;border:none;
            cursor:pointer;padding:4px;color:#6B7280;">
          <svg xmlns="http://www.w3.org/2000/svg"
            style="width:20px;height:20px;" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round"
              stroke-linejoin="round" stroke-width="2"
              d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:16px;">
          <div style="display:flex;align-items:center;
            justify-content:space-between;">
            <span style="font-size:13px;color:#6B7280;">
              Statut
            </span>
            <span :class="statusBadge(
              selectedBooking?.status)"
              x-text="statusLabel(
              selectedBooking?.status)">
            </span>
          </div>

          <div style="display:flex;align-items:center;
            justify-content:space-between;">
            <span style="font-size:13px;color:#6B7280;">
              Chambre
            </span>
            <span style="font-size:13px;font-weight:600;
              color:#111827;"
              x-text="rooms.find(r => 
                r.id==selectedBooking?.room_id)
                ?.name ?? '#' + selectedBooking?.room_id">
            </span>
          </div>

          <div style="display:grid;
            grid-template-columns:1fr 1fr;gap:12px;">
            <div style="background:#FAFAFA;
              border-radius:10px;padding:12px;">
              <div style="font-size:11px;color:#9CA3AF;
                margin-bottom:4px;">Check-in</div>
              <div style="font-size:13px;font-weight:600;
                color:#111827;"
                x-text="formatDate(
                  selectedBooking?.check_in)">
              </div>
            </div>
            <div style="background:#FAFAFA;
              border-radius:10px;padding:12px;">
              <div style="font-size:11px;color:#9CA3AF;
                margin-bottom:4px;">Check-out</div>
              <div style="font-size:13px;font-weight:600;
                color:#111827;"
                x-text="formatDate(
                  selectedBooking?.check_out)">
              </div>
            </div>
          </div>

          <div style="display:flex;align-items:center;
            justify-content:space-between;
            padding:12px;background:rgba(201,168,76,0.06);
            border:1px solid rgba(201,168,76,0.15);
            border-radius:10px;">
            <span style="font-size:13px;color:#6B7280;">
              Montant total
            </span>
            <span style="font-size:16px;font-weight:800;
              color:#1B4332;"
              x-text="formatPrice(
                selectedBooking?.total_amount
                ??selectedBooking?.total_price)">
            </span>
          </div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <a :href="'<?= $base_url ?>/saas/bookings'"
          class="btn-saas-secondary">
          Voir les réservations →
        </a>
        <button @click="showDetail=false"
          class="btn-saas-primary">
          Fermer
        </button>
      </div>
    </div>
  </div>

</div>
