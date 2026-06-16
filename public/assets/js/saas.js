/* ============================================================
   Ivoire Stay — JS global SaaS (B2B)
   Chargé par src/templates/saas/layout.php (avant Alpine)
   ============================================================ */

/**
 * Composant Alpine du shell SaaS (sidebar + topbar).
 * Gère l'authentification côté client, le sélecteur d'établissement
 * multi-tenant et la déconnexion.
 *
 * @param {string} baseUrl  Préfixe d'URL de l'app (APP_URL), injecté par PHP.
 * Utilisé via x-data="saasLayout('<?= $base_url ?>')" dans le layout.
 */
function saasLayout(baseUrl) {
  return {
    baseUrl: baseUrl || '',
    sidebarOpen: false,
    user: JSON.parse(localStorage.getItem('user') ?? 'null'),
    establishment: null,
    establishments: [],

    init() {
      // Accès au SaaS uniquement depuis l'app installée (mode standalone).
      const standalone = window.matchMedia('(display-mode: standalone)').matches
        || window.matchMedia('(display-mode: fullscreen)').matches
        || window.navigator.standalone === true;
      if (!standalone) {
        window.location.href = this.baseUrl + '/login';
        return;
      }

      const token = localStorage.getItem('token');
      if (!token) {
        window.location.href = this.baseUrl + '/login';
        return;
      }

      const stored = localStorage.getItem('establishments');
      if (stored) {
        try { this.establishments = JSON.parse(stored); } catch (e) { this.establishments = []; }
      }

      const estId = localStorage.getItem('establishment_id');
      if (estId && this.establishments.length) {
        this.establishment = this.establishments.find(e => e.id == estId) ?? this.establishments[0];
      }
    },

    logout() {
      localStorage.clear();
      window.location.href = this.baseUrl + '/login';
    },

    switchEstablishment(id) {
      localStorage.setItem('establishment_id', id);
      this.establishment = this.establishments.find(e => e.id == id);
      window.location.reload();
    },

    get userName() {
      return this.user?.name ?? 'Utilisateur';
    },
    get userRole() {
      return this.user?.role ?? 'owner';
    },
    get userInitials() {
      const parts = this.userName.split(' ').filter(Boolean);
      return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '');
    },
    get canSeeFinance() {
      return ['owner', 'superadmin'].includes(this.userRole);
    },
    get canSeeSettings() {
      return ['owner', 'superadmin'].includes(this.userRole);
    },
  };
}
