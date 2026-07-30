/* ============================================================
   Afristay — JS global VITRINE (B2C)
   Chargé par src/templates/vitrine/layout.php (avant Alpine)
   ============================================================ */

/**
 * Composant Alpine de la barre de navigation publique.
 * Gère l'état "scrolled" (navbar flottante) et le menu mobile.
 * Utilisé via x-data="vitrineNav()" dans le layout.
 */
function vitrineNav() {
  return {
    scrolled: true,
    mobileOpen: false,
    init() {},
  };
}

/**
 * Formulaire d'inscription newsletter du footer (toutes les pages vitrine,
 * cf. src/templates/vitrine/layout.php). Voir PublicController::
 * newsletterSubscribe() — pas de compte requis, juste un email.
 */
function newsletterFooterForm(baseUrl) {
  return {
    email:  '',
    sending: false,
    message: '',
    msgOk:   false,

    async subscribe() {
      const email = this.email.trim();
      if (!email) return;

      this.sending = true;
      this.message = '';
      try {
        const res  = await fetch(baseUrl + '/api/public/newsletter/subscribe', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ email }),
        });
        const data = await res.json();
        this.msgOk   = !!data.success;
        this.message = data.message || (this.msgOk ? 'Inscription réussie.' : 'Une erreur est survenue.');
        if (this.msgOk) this.email = '';
      } catch (e) {
        this.msgOk   = false;
        this.message = 'Erreur réseau — réessayez plus tard.';
      } finally {
        this.sending = false;
      }
    },
  };
}

/**
 * Popup d'annonce plateforme, affiché au chargement de la vitrine
 * (src/templates/vitrine/layout.php). Charge l'annonce active
 * (Announcement::activeOne()) et l'affiche une fois — la fermeture est
 * mémorisée par ID (localStorage) pour ne plus jamais réafficher CETTE
 * annonce précise, tout en réapparaissant automatiquement si une NOUVELLE
 * annonce est publiée plus tard. Remplace l'ancien bandeau fixe en haut de
 * page (jugé mal adapté) — en overlay, ça ne modifie donc plus jamais la
 * mise en page ni la position de la nav flottante.
 */
function vitrineAnnouncementPopup(baseUrl) {
  return {
    announcement: null,
    open: false,

    async init() {
      try {
        const res  = await fetch(baseUrl + '/api/public/announcements');
        const data = await res.json();
        if (data.success && data.data) {
          this.announcement = data.data;
          const dismissedId = localStorage.getItem('afristay_announcement_dismissed');
          if (String(dismissedId) !== String(this.announcement.id)) {
            this.open = true;
          }
        }
      } catch (e) { /* pas de popup si l'API échoue — pas bloquant */ }
    },

    dismiss() {
      if (!this.announcement) return;
      localStorage.setItem('afristay_announcement_dismissed', String(this.announcement.id));
      this.open = false;
    },
  };
}
