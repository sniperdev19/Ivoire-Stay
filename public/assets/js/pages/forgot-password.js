/* ============================================================
   Afristay — Page mot de passe oublié (saas/forgot-password.php)
   ============================================================ */

function forgotPasswordPage(baseUrl) {
  return {
    email:   '',
    loading: false,
    error:   null,
    sent:    false,
    message: '',

    async submit() {
      this.error = null;
      if (!this.email.trim()) {
        this.error = 'Veuillez saisir votre adresse email.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/auth/forgot-password', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ email: this.email }),
        });
        const data = await res.json();
        if (data.success) {
          this.sent    = true;
          this.message = data.message;
        } else {
          this.error = data.message ?? 'Une erreur est survenue.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
