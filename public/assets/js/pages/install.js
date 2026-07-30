/* ============================================================
   Afristay — Page d'installation PWA (saas/install.php)
   Dépend de pwa.js (chargé avant) qui expose window.AfristayPWA.
   Onboarding post-inscription : installation obligatoire avant /login.
   ============================================================ */

function installPage(baseUrl) {
  return {
    isApp:           false,
    installable:     false,
    installFeedback: null,

    init() {
      const pwa = window.AfristayPWA;

      this.isApp       = pwa.isStandalone();
      this.installable = pwa.canInstall();

      document.addEventListener('pwa:installable', () => {
        this.installable = true;
      });

      document.addEventListener('pwa:installed', () => {
        this.isApp = true;
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

    goToLogin() {
      window.location.href = baseUrl + '/login';
    },
  };
}
