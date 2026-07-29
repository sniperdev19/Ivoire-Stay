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
 * Store global partagé entre le bandeau d'annonce et la nav flottante
 * (composants Alpine frères, pas parent/enfant — un store est le moyen le
 * plus simple de les faire communiquer sans tout restructurer). La nav lit
 * $store.vitrineChrome.bannerHeight pour décaler son "top" fixe exactement
 * de la hauteur réelle du bandeau (mesurée, jamais devinée) quand il est visible.
 */
document.addEventListener('alpine:init', () => {
  Alpine.store('vitrineChrome', { bannerHeight: 0 });
});

/**
 * Bandeau d'annonce plateforme en haut de la vitrine (src/templates/vitrine/
 * layout.php). Charge l'annonce active (Announcement::activeOne()) et permet
 * de la fermer — la fermeture est mémorisée par ID (localStorage) pour ne
 * plus jamais réafficher CETTE annonce précise, tout en réapparaissant
 * automatiquement si une NOUVELLE annonce est publiée plus tard.
 */
function vitrineAnnouncementBanner(baseUrl) {
  return {
    announcement: null,
    dismissed:    false,

    async init() {
      try {
        const res  = await fetch(baseUrl + '/api/public/announcements');
        const data = await res.json();
        if (data.success && data.data) {
          this.announcement = data.data;
          const dismissedId = localStorage.getItem('afristay_announcement_dismissed');
          this.dismissed = String(dismissedId) === String(this.announcement.id);
        }
      } catch (e) { /* pas de bandeau si l'API échoue — pas bloquant */ }
      this.$nextTick(() => this.syncHeight());
    },

    syncHeight() {
      Alpine.store('vitrineChrome').bannerHeight = (this.announcement && !this.dismissed) ? this.$el.offsetHeight : 0;
    },

    dismiss() {
      if (!this.announcement) return;
      localStorage.setItem('afristay_announcement_dismissed', String(this.announcement.id));
      this.dismissed = true;
      this.$nextTick(() => this.syncHeight());
    },
  };
}
