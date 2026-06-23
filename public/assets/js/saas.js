/* ============================================================
   Ivoire Stay — JS global SaaS (B2B)
   Chargé par src/templates/saas/layout.php (avant Alpine)
   ============================================================ */

function saasLayout(baseUrl) {
  return {
    baseUrl: baseUrl || '',
    sidebarOpen: false,
    user: null,
    establishment: null,
    establishments: [],

    async init() {
      /* ── 1. Accès réservé à l'app installée (mode standalone) ── */
      const pwa = window.IvoireStayPWA;
      const standalone = pwa
        ? pwa.isStandalone()
        : (window.matchMedia('(display-mode: standalone)').matches ||
           window.matchMedia('(display-mode: fullscreen)').matches  ||
           window.navigator.standalone === true);

      if (!standalone) {
        window.location.href = this.baseUrl + '/login';
        return;
      }

      /* ── 2. Token JWT présent ── */
      const token = localStorage.getItem('token');
      if (!token) {
        window.location.href = this.baseUrl + '/login';
        return;
      }

      /* ── 3. Affichage immédiat depuis le cache localStorage
              (évite le flash blanc; sera corrigé par la réponse serveur) ── */
      const cached = localStorage.getItem('user');
      this.user = cached ? JSON.parse(cached) : null;

      const stored = localStorage.getItem('establishments');
      if (stored) {
        try { this.establishments = JSON.parse(stored); } catch { this.establishments = []; }
      }
      const estId = localStorage.getItem('establishment_id');
      if (estId && this.establishments.length) {
        this.establishment =
          this.establishments.find(e => e.id == estId) ?? this.establishments[0];
      }

      /* ── 4. Vérification du rôle réel depuis le serveur
              Empêche l'escalade de privilège par manipulation du localStorage.
              Le JWT est signé côté serveur — le rôle renvoyé est fiable. ── */
      try {
        const res = await fetch(this.baseUrl + '/api/auth/me', {
          headers: { 'Authorization': 'Bearer ' + token },
        });

        if (res.status === 401 || res.status === 403) {
          /* Token expiré ou invalide → déconnexion forcée */
          localStorage.clear();
          window.location.href = this.baseUrl + '/login';
          return;
        }

        const data = await res.json();
        if (data.success && data.data) {
          /* Écrase les données locales avec les données vérifiées du serveur */
          this.user = data.data;
          localStorage.setItem('user', JSON.stringify(data.data));
        }
      } catch {
        /* Mode hors-ligne : on conserve le cache localStorage comme fallback.
           Les vraies données protégées restent inaccessibles (API calls échouent). */
      }
    },

    logout() {
      localStorage.clear();
      window.location.href = this.baseUrl + '/login';
    },

    switchEstablishment(id) {
      localStorage.setItem('establishment_id', String(id));
      this.establishment = this.establishments.find(e => e.id == id);
      window.location.reload();
    },

    get userName()     { return this.user?.name  ?? 'Utilisateur'; },
    /* Défaut 'staff' (le rôle le plus restrictif) tant que le serveur n'a pas répondu */
    get userRole()     { return this.user?.role  ?? 'staff'; },
    get userInitials() {
      const parts = this.userName.split(' ').filter(Boolean);
      return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '');
    },

    /* Sections Finance et Paramètres : uniquement owner et superadmin.
       Le rôle vient du serveur (JWT vérifié), pas du localStorage manipulable. */
    get canSeeFinance()  { return ['owner', 'superadmin'].includes(this.userRole); },
    get canSeeSettings() { return ['owner', 'superadmin'].includes(this.userRole); },

    /* Matrice des fonctionnalités par plan (miroir de config/plans.php) */
    planMatrix: {
      starter:  { invoices: false, payments: false, expenses: false, reports: false, pdf: false, boost: false, multi_estab: false },
      pro:      { invoices: true,  payments: true,  expenses: true,  reports: true,  pdf: true,  boost: false, multi_estab: false },
      business: { invoices: true,  payments: true,  expenses: true,  reports: true,  pdf: true,  boost: true,  multi_estab: true  },
    },
    get currentPlan() { return this.establishment?.plan ?? 'starter'; },
    canFeature(name)  { return this.planMatrix[this.currentPlan]?.[name] ?? false; },
  };
}

/* ─── Helper partagé : vérification du plan depuis localStorage ─────────────
   Utilisé par les pages individuelles (composants Alpine séparés du layout).
   Retourne true si le plan actuel n'autorise pas la fonctionnalité. ──────── */
function planUpgradeRequired(feature) {
  try {
    const id    = localStorage.getItem('establishment_id');
    const list  = JSON.parse(localStorage.getItem('establishments') || '[]');
    const estab = list.find(e => String(e.id) === String(id)) ?? list[0] ?? {};
    const plan  = estab.plan ?? 'starter';
    const matrix = {
      starter:  { invoices: false, payments: false, expenses: false, reports: false, pdf: false },
      pro:      { invoices: true,  payments: true,  expenses: true,  reports: true,  pdf: true  },
      business: { invoices: true,  payments: true,  expenses: true,  reports: true,  pdf: true  },
    };
    return !(matrix[plan]?.[feature] ?? false);
  } catch { return true; }
}

/* ─── Statuts de réservation — source de vérité unique ─────────────────────
   Utilisé par bookings, clients, planning (et tout futur composant).        */
const BOOKING_STATUS = {
  confirmed:   { label: 'Confirmée',  badge: 'badge badge-success', color: '#16a34a' },
  pending:     { label: 'En attente', badge: 'badge badge-warning', color: '#D97706' },
  checked_in:  { label: 'Arrivée',    badge: 'badge badge-info',    color: '#2563EB' },
  checked_out: { label: 'Départ',     badge: 'badge badge-gold',    color: '#6B7280' },
  cancelled:   { label: 'Annulée',    badge: 'badge badge-danger',  color: '#DC2626' },
};

/* ─── Catégories de dépenses — source de vérité unique ─────────────────────
   Utilisé par expenses et reports.                                           */
const EXPENSE_CAT = {
  labels: { maintenance: 'Maintenance', salaries: 'Salaires', supplies: 'Fournitures', utilities: 'Énergie / Eau', marketing: 'Marketing', other: 'Autre' },
  colors: { maintenance: '#D97706',     salaries: '#2563EB',  supplies: '#059669',      utilities: '#7C3AED',       marketing: '#EC4899',   other: '#6B7280' },
};

/* ─── Mixin partagé par tous les composants Alpine de page SaaS ─────────────
   Usage : return { ...saasHelpers, /* état et méthodes spécifiques *\/ };   */
const saasHelpers = {
  /* Auth */
  apiHeaders() {
    return { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') };
  },
  estId() { return localStorage.getItem('establishment_id') || ''; },

  /* Formatage */
  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p ?? 0) + ' FCFA'; },
  formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
  },

  /* Statut de réservation */
  bookingStatus(s) { return BOOKING_STATUS[s] ?? { label: s, badge: 'badge', color: '#9CA3AF' }; },

  /* Catégories de dépenses */
  catLabel(c) { return EXPENSE_CAT.labels[c] ?? c; },
  catColor(c) { return EXPENSE_CAT.colors[c] ?? '#9CA3AF'; },

  /* Toast notification */
  toast: null,
  toastTimer: null,
  showToast(msg, type = 'success') {
    this.toast = { msg, type };
    clearTimeout(this.toastTimer);
    this.toastTimer = setTimeout(() => { this.toast = null; }, 3500);
  },
};

/* ─── Panneau de notifications ───────────────────────────────────────────────
   Composant Alpine autonome utilisé dans le header de saas/layout.php.       */
function notificationsPanel(baseUrl) {
  return {
    open:          false,
    notifications: [],
    unread:        0,
    loading:       false,
    _pollTimer:    null,

    async init() {
      await this.load();
      this._pollTimer = setInterval(() => this.load(), 30_000);
    },

    _headers() {
      return { 'Authorization': 'Bearer ' + localStorage.getItem('token') };
    },

    async load() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/notifications', { headers: this._headers() });
        const data = await res.json();
        if (data.success) {
          this.notifications = data.data?.notifications ?? [];
          this.unread        = data.data?.unread        ?? 0;
        }
      } catch { /* hors-ligne — conserve l'état précédent */ }
      finally { this.loading = false; }
    },

    async markRead(id) {
      const n = this.notifications.find(n => n.id == id);
      if (!n || n.read_at) return;
      n.read_at = new Date().toISOString();
      this.unread = Math.max(0, this.unread - 1);
      fetch(baseUrl + '/api/notifications/' + id + '/read', { method: 'PUT', headers: this._headers() });
    },

    async markAllRead() {
      const now = new Date().toISOString();
      this.notifications.forEach(n => { if (!n.read_at) n.read_at = now; });
      this.unread = 0;
      fetch(baseUrl + '/api/notifications/read-all', { method: 'POST', headers: this._headers() });
    },

    timeAgo(dateStr) {
      if (!dateStr) return '';
      const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
      if (diff < 60)    return 'À l\'instant';
      if (diff < 3600)  return 'Il y a ' + Math.floor(diff / 60) + ' min';
      if (diff < 86400) return 'Il y a ' + Math.floor(diff / 3600) + 'h';
      return 'Il y a ' + Math.floor(diff / 86400) + 'j';
    },

    typeConfig(type) {
      return {
        booking_new:      { icon: '🏨', color: '#2563EB', bg: '#EFF6FF' },
        payment_received: { icon: '💳', color: '#16a34a', bg: '#F0FDF4' },
        invoice_sent:     { icon: '📧', color: '#C9A84C', bg: '#FFFBEB' },
        invoice_paid:     { icon: '✅', color: '#16a34a', bg: '#F0FDF4' },
      }[type] ?? { icon: '🔔', color: '#6B7280', bg: '#F9FAFB' };
    },

    get badgeLabel() {
      return this.unread > 99 ? '99+' : String(this.unread);
    },
  };
}
