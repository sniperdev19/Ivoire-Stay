/* ============================================================
   Afristay — Connexion agent commercial
   Dépend de pwa.js (chargé avant) qui expose window.AfristayPWA.
   Même gate PWA que le reste (login.js) : accès app installée uniquement.
   ============================================================ */

function agentLoginPage(baseUrl) {
  return {
    isApp:          false,
    installable:    false,
    installFeedback: null,

    form:     { numero: '', password: '' },
    loading:  false,
    error:    null,
    showPass: false,

    init() {
      const pwa = window.AfristayPWA;

      this.isApp      = pwa.isStandalone();
      this.installable = pwa.canInstall();

      document.addEventListener('pwa:installable', () => {
        this.installable = true;
      });

      document.addEventListener('pwa:installed', () => {
        this.isApp      = true;
        this.installable = false;
      });

      window.matchMedia('(display-mode: standalone)').addEventListener?.('change', e => {
        if (e.matches) this.isApp = true;
      });
    },

    async install() {
      const pwa = window.AfristayPWA;

      if (this.installable) {
        const accepted = await pwa.install();
        this.installable = pwa.canInstall();
        if (accepted) {
          setTimeout(() => window.location.reload(), 700);
        }
      } else if (pwa.isIOS()) {
        this.installFeedback = 'ios';
      } else {
        this.installFeedback = 'unavailable';
        setTimeout(() => { this.installFeedback = null; }, 4000);
      }
    },

    async submit() {
      this.error = null;
      if (!this.form.numero.trim() || !this.form.password) {
        this.error = 'Veuillez remplir tous les champs.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/agent/login', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('agent_token', data.data.token);
          localStorage.setItem('agent', JSON.stringify(data.data.agent));
          window.location.href = baseUrl + '/agent/dashboard';
        } else {
          this.error = data.message ?? 'Numéro ou mot de passe incorrect.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
