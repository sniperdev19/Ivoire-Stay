/* ============================================================
   Ivoire Stay — Page SaaS : Tableau de bord (saas/dashboard.php)
   Stats + planning du jour, avec données de repli (démo).
   ============================================================ */

/**
 * Composant Alpine du tableau de bord.
 * @param {string} baseUrl  Préfixe d'URL de l'app (APP_URL).
 * Utilisé via x-data="dashboardPage('<?= $base_url ?>')".
 */
function dashboardPage(baseUrl) {
  return {
    stats: null,
    planning: [],
    loading: true,
    error: null,

    apiBase: baseUrl || '',
    apiUrl(path) { return this.apiBase + path; },

    fallbackStats: {
      revenue: 1245000,
      occupancy_rate: 68,
      active_bookings: 7,
      available_rooms: 8,
      total_rooms: 24,
      expenses: 320000,
      payments_received: 980000,
      payments_pending: 265000,
      net_profit: 925000,
      month: 'Juin 2026',
    },

    fallbackPlanning: [
      { id: 1, client_name: 'Kouamé Adou', room_name: 'Suite Présidentielle', checkin: '2026-06-14', amount: 320000, status: 'confirmed' },
      { id: 2, client_name: 'Fatou Diallo', room_name: 'Chambre Deluxe', checkin: '2026-06-14', amount: 95000, status: 'pending' },
      { id: 3, client_name: 'Marc Koffi', room_name: 'Studio Standard', checkin: '2026-06-13', amount: 55000, status: 'checkout' },
      { id: 4, client_name: 'Awa Traoré', room_name: 'Chambre Deluxe', checkin: '2026-06-15', amount: 190000, status: 'confirmed' },
      { id: 5, client_name: 'Yao Brou', room_name: 'Suite Junior', checkin: '2026-06-14', amount: 145000, status: 'checkin' },
    ],

    async init() {
      const token = localStorage.getItem('token');
      let id = localStorage.getItem('establishment_id');

      if (!id) {
        try {
          const raw = localStorage.getItem('establishments');
          const list = raw ? JSON.parse(raw) : [];
          if (Array.isArray(list) && list.length > 0) {
            id = list[0].id ?? list[0].establishment_id ?? null;
            if (id) localStorage.setItem('establishment_id', id);
          }
        } catch (e) {}
      }

      if (!id) {
        this.stats = this.fallbackStats;
        this.planning = this.fallbackPlanning;
        this.loading = false;
        return;
      }

      const headers = { Authorization: 'Bearer ' + token };
      try {
        const [statsRes, planRes] = await Promise.all([
          fetch(this.apiUrl('/api/dashboard/stats?establishment_id=' + id), { headers }),
          fetch(this.apiUrl('/api/dashboard/planning?establishment_id=' + id), { headers }),
        ]);
        const sData = await statsRes.json();
        const pData = await planRes.json();

        this.stats = sData.success ? (sData.data ?? this.fallbackStats) : this.fallbackStats;
        this.planning = pData.success ? (pData.data?.bookings ?? this.fallbackPlanning) : this.fallbackPlanning;
      } catch (e) {
        this.stats = this.fallbackStats;
        this.planning = this.fallbackPlanning;
      } finally {
        this.loading = false;
      }
    },

    formatPrice(p) {
      return new Intl.NumberFormat('fr-FR').format(p ?? 0) + ' FCFA';
    },
    formatDate(d) {
      if (!d) return '-';
      return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    },
    statusLabel(s) {
      return { confirmed: 'Confirmée', pending: 'En attente', cancelled: 'Annulée', checkin: 'Arrivée', checkout: 'Départ' }[s] ?? s;
    },
    statusStyle(s) {
      return {
        confirmed: 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:9999px;background:rgba(16,185,129,0.12);color:#047857;font-size:11px;font-weight:700;',
        pending: 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:9999px;background:rgba(251,191,36,0.12);color:#92400E;font-size:11px;font-weight:700;',
        cancelled: 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:9999px;background:rgba(239,68,68,0.12);color:#B91C1C;font-size:11px;font-weight:700;',
        checkin: 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:9999px;background:rgba(56,189,248,0.12);color:#0369A1;font-size:11px;font-weight:700;',
        checkout: 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:9999px;background:rgba(249,115,22,0.12);color:#B45309;font-size:11px;font-weight:700;',
      }[s] ?? 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:9999px;background:rgba(148,163,184,0.12);color:#334155;font-size:11px;font-weight:700;';
    },
    get occupancyPct() {
      return Math.min(100, Math.round(this.stats?.occupancy_rate ?? 0));
    },
  };
}
