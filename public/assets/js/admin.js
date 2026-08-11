/* ============================================================
   Afristay — JS global Admin plateforme (superadmin)
   Chargé par src/templates/admin/layout.php (avant Alpine)
   Miroir de saasLayout() (saas.js) sans les notions propres à un
   établissement (pas de sélecteur d'établissement, pas de plan à vérifier —
   le superadmin a accès à tout), plus une garde de rôle.
   ============================================================ */

function adminLayout(baseUrl) {
  return {
    baseUrl: baseUrl || '',
    sidebarOpen: false,
    user: null,

    async init() {
      /* ── 1. Accès réservé à l'app installée (mode standalone), comme le SaaS ── */
      const pwa = window.AfristayPWA;
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

      /* ── 3. Affichage immédiat depuis le cache localStorage ── */
      const cached = localStorage.getItem('user');
      this.user = cached ? JSON.parse(cached) : null;

      /* ── 4. Vérification du rôle réel depuis le serveur (JWT signé, fiable) ── */
      try {
        const res = await fetch(this.baseUrl + '/api/auth/me', {
          headers: { 'Authorization': 'Bearer ' + token },
        });

        if (res.status === 401 || res.status === 403) {
          // Le témoin "empreinte activée sur cet appareil" (login.js::biometricFirstMode)
          // doit survivre — réglage de l'appareil, pas de la session.
          const biometricHint = localStorage.getItem('biometric_login_hint');
          localStorage.clear();
          if (biometricHint) localStorage.setItem('biometric_login_hint', biometricHint);
          window.location.href = this.baseUrl + '/login';
          return;
        }

        const data = await res.json();
        if (data.success && data.data) {
          this.user = data.data;
          localStorage.setItem('user', JSON.stringify(data.data));
        }
      } catch {
        /* Mode hors-ligne : on conserve le cache localStorage comme fallback. */
      }

      /* ── 5. Garde de rôle : l'espace admin est réservé au superadmin.
              Protection d'UX seulement — l'API reste protégée côté serveur
              (middleware role:superadmin) quoi qu'il arrive ici. ── */
      if (this.user?.role !== 'superadmin') {
        window.location.href = this.baseUrl + '/saas';
        return;
      }

      /* Nettoyage défensif : un superadmin n'est rattaché à aucun établissement
         particulier. Efface une éventuelle valeur restée en cache d'avant ce
         correctif (session déjà ouverte lors du déploiement de ce fichier). */
      localStorage.removeItem('establishment_id');
      localStorage.removeItem('establishments');
    },

    logout() {
      const biometricHint = localStorage.getItem('biometric_login_hint');
      localStorage.clear();
      if (biometricHint) localStorage.setItem('biometric_login_hint', biometricHint);
      window.location.href = this.baseUrl + '/login';
    },

    get userName()     { return this.user?.name ?? 'Admin'; },
    get userInitials() {
      const parts = this.userName.split(' ').filter(Boolean);
      return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '');
    },
  };
}
