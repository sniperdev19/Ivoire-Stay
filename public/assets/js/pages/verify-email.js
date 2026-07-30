/* ============================================================
   Afristay — Page confirmation d'email (saas/verify-email.php)
   Auto-vérifie le jeton au chargement, sans formulaire.
   ============================================================ */

function verifyEmailPage(baseUrl) {
  return {
    token:   '',
    loading: true,
    done:    false,
    error:   null,

    async init() {
      this.token = new URLSearchParams(window.location.search).get('token') ?? '';

      if (!this.token) {
        this.loading = false;
        this.error = 'Lien invalide : le jeton de vérification est manquant.';
        return;
      }

      try {
        const res = await fetch(baseUrl + '/api/auth/verify-email', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ token: this.token }),
        });
        const data = await res.json();
        if (data.success) {
          this.done = true;
          /* Le cache local peut contenir l'ancien état (email_verified_at
             encore null) si l'utilisateur est déjà connecté sur cet appareil. */
          const cached = localStorage.getItem('user');
          if (cached) {
            try {
              const user = JSON.parse(cached);
              user.email_verified_at = new Date().toISOString();
              localStorage.setItem('user', JSON.stringify(user));
            } catch (_) { /* cache corrompu, ignoré */ }
          }
        } else {
          this.error = data.message ?? 'Lien invalide ou expiré.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
