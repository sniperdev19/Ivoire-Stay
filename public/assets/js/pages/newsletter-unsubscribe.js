/* ============================================================
   Afristay — Vitrine : confirmation de désabonnement newsletter
   (src/templates/vitrine/newsletter-unsubscribe.php)
   ============================================================ */

function newsletterUnsubscribePage(baseUrl) {
  return {
    loading: true,
    success: false,
    message: '',

    async init() {
      const token = new URLSearchParams(window.location.search).get('token');
      if (!token) {
        this.loading = false;
        this.message = 'Lien de désabonnement incomplet.';
        return;
      }
      try {
        const res  = await fetch(baseUrl + '/api/public/newsletter/unsubscribe?token=' + encodeURIComponent(token));
        const data = await res.json();
        this.success = !!data.success;
        this.message = data.message || (this.success ? 'Vous avez été désabonné(e).' : 'Lien invalide.');
      } catch (e) {
        this.success = false;
        this.message = 'Erreur réseau — réessayez plus tard.';
      } finally {
        this.loading = false;
      }
    },
  };
}
