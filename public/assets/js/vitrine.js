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
    scrolled: false,
    mobileOpen: false,
    init() {
      const isHome = window.location.pathname === '/'
        || window.location.pathname.endsWith('/public/')
        || window.location.pathname.endsWith('/public');

      if (!isHome) {
        this.scrolled = true;
      }

      window.addEventListener('scroll', () => {
        if (isHome) {
          this.scrolled = window.scrollY > 80;
        } else {
          this.scrolled = true;
        }
      });
    },
  };
}
