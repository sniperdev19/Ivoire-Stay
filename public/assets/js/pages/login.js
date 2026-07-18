/* ============================================================
   Afristay — Page connexion SaaS
   Dépend de pwa.js (chargé avant) qui expose window.AfristayPWA.
   ============================================================ */

function loginPage(baseUrl) {
  return {
    /* ── PWA gate ── */
    isApp:          false,
    installable:    false,
    installFeedback: null,

    /* ── Formulaire ── */
    form:     { email: '', password: '' },
    loading:  false,
    error:    null,
    showPass: false,

    init() {
      const pwa = window.AfristayPWA;

      /* Détection initiale (standalone = dans l'app installée) */
      this.isApp      = pwa.isStandalone();
      this.installable = pwa.canInstall();

      /* Mise à jour réactive quand le prompt devient disponible */
      document.addEventListener('pwa:installable', () => {
        this.installable = true;
      });

      /* Mise à jour quand l'app vient d'être installée */
      document.addEventListener('pwa:installed', () => {
        this.isApp      = true;
        this.installable = false;
      });

      /* Détecte le passage en mode standalone (ex : rechargement depuis l'app) */
      window.matchMedia('(display-mode: standalone)').addEventListener?.('change', e => {
        if (e.matches) this.isApp = true;
      });
    },

    async install() {
      const pwa = window.AfristayPWA;

      if (this.installable) {
        /* Prompt natif disponible (Chrome / Edge sur Android & PC) */
        const accepted = await pwa.install();
        this.installable = pwa.canInstall();
        if (accepted) {
          /* Petit délai puis on recharge pour détecter le mode standalone */
          setTimeout(() => window.location.reload(), 700);
        }
      } else if (pwa.isIOS()) {
        /* iOS Safari : pas de prompt natif, afficher les instructions */
        this.installFeedback = 'ios';
      } else {
        /* Navigateur non compatible ou déjà installé */
        this.installFeedback = 'unavailable';
        setTimeout(() => { this.installFeedback = null; }, 4000);
      }
    },

    async submit() {
      this.error = null;
      if (!this.form.email.trim() || !this.form.password) {
        this.error = 'Veuillez remplir tous les champs.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/auth/login', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('token', data.data.token);
          localStorage.setItem('user', JSON.stringify(data.data.user));
          const isSuperadmin = data.data.user?.role === 'superadmin';

          /* Le superadmin (propriétaire de la plateforme) n'est rattaché à AUCUN
             établissement particulier — il pilote /admin, pas un hôtel donné.
             Establishment::forUser() lui renvoie TOUS les établissements de la
             plateforme (utile pour /admin/establishments), mais on ne doit surtout
             pas en sélectionner un au hasard comme « établissement actif ». */
          if (isSuperadmin) {
            localStorage.removeItem('establishment_id');
            localStorage.removeItem('establishments');
          } else {
            const estabs = data.data.establishments ?? [];
            localStorage.setItem('establishments', JSON.stringify(estabs));
            if (Array.isArray(estabs) && estabs.length > 0) {
              const estId = estabs[0].id ?? estabs[0].establishment_id;
              if (estId) localStorage.setItem('establishment_id', String(estId));
            } else {
              try {
                const payload = JSON.parse(atob(data.data.token.split('.')[1]));
                if (payload.establishment_id)
                  localStorage.setItem('establishment_id', String(payload.establishment_id));
              } catch (_) {}
            }
          }

          /* Redirige vers l'URL d'origine si la page a été ouverte via ?redirect= ;
             sinon un superadmin (propriétaire de la plateforme) atterrit sur son
             propre espace, pas sur l'outil de gestion hôtelière. */
          const redirect = new URLSearchParams(window.location.search).get('redirect');
          window.location.href = redirect
            ? decodeURIComponent(redirect)
            : baseUrl + (isSuperadmin ? '/admin' : '/saas');
        } else {
          this.error = data.message ?? 'Email ou mot de passe incorrect.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
