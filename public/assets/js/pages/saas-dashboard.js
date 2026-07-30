/* ============================================================
   Afristay — Page SaaS : Tableau de bord (saas/dashboard.php)
   ============================================================ */

function dashboardPage(baseUrl) {
  return {
    ...saasHelpers,

    stats: null,
    planning: [],
    loading: true,
    error: null,

    apiBase: baseUrl || '',
    apiUrl(path) { return this.apiBase + path; },

    /* formatDate local : affichage court sans année */
    formatDate(d) {
      if (!d) return '-';
      return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    },

    /* statusLabel : statuts réservation (recent_bookings) + clés checkin/checkout (planning API) */
    statusLabel(s) {
      return {
        confirmed: 'Confirmée', pending: 'En attente', cancelled: 'Annulée',
        checked_in: 'Arrivée', checked_out: 'Départ',
        checkin: 'Arrivée', checkout: 'Départ',
      }[s] ?? s;
    },

    async init() {
      const token = localStorage.getItem('token');
      let id = localStorage.getItem('establishment_id');

      if (!id) {
        try {
          const raw  = localStorage.getItem('establishments');
          const list = raw ? JSON.parse(raw) : [];
          if (Array.isArray(list) && list.length > 0) {
            id = list[0].id ?? list[0].establishment_id ?? null;
            if (id) localStorage.setItem('establishment_id', String(id));
          }
        } catch(e) {}
      }

      if (!id) {
        this.error   = 'Aucun établissement configuré. Rendez-vous dans Paramètres pour créer le vôtre.';
        this.loading = false;
        return;
      }

      const headers = { Authorization: 'Bearer ' + token };
      try {
        const [statsRes, planRes] = await Promise.all([
          fetch(this.apiUrl('/api/dashboard/stats?establishment_id='    + id), { headers }),
          fetch(this.apiUrl('/api/dashboard/planning?establishment_id=' + id), { headers }),
        ]);
        const sData = await statsRes.json();
        const pData = await planRes.json();

        this.stats    = sData.success ? (sData.data ?? null) : null;
        this.planning = pData.success ? (pData.data?.bookings ?? []) : [];

        if (!sData.success) this.error = sData.message ?? 'Impossible de charger les statistiques.';
      } catch(e) {
        this.error = 'Erreur réseau. Vérifiez votre connexion.';
      } finally {
        this.loading = false;
      }
    },

    statusStyle(s) {
      const base = 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:9999px;font-size:11px;font-weight:700;';
      return ({
        confirmed:  base + 'background:rgba(16,185,129,0.12);color:#047857;',
        pending:    base + 'background:rgba(251,191,36,0.12);color:#92400E;',
        cancelled:  base + 'background:rgba(239,68,68,0.12);color:#B91C1C;',
        checked_in: base + 'background:rgba(56,189,248,0.12);color:#0369A1;',
        checked_out:base + 'background:rgba(249,115,22,0.12);color:#B45309;',
        checkin:    base + 'background:rgba(56,189,248,0.12);color:#0369A1;',
        checkout:   base + 'background:rgba(249,115,22,0.12);color:#B45309;',
      }[s]) ?? base + 'background:rgba(148,163,184,0.12);color:#334155;';
    },

    get recentBookings() {
      return this.stats?.recent_bookings ?? [];
    },

    get occupancyPct() {
      return Math.min(100, Math.round(this.stats?.occupancy_rate ?? 0));
    },

    get typeDistribution() {
      const palette = ['#C9A84C','#2563EB','#16A34A','#7C3AED','#DC2626','#0891B2','#D97706'];
      return (this.stats?.type_distribution ?? []).map((t, i) => ({
        ...t,
        color: palette[i % palette.length],
      }));
    },

    get occupiedRooms() {
      return this.stats?.occupied_rooms ?? 0;
    },
  };
}
