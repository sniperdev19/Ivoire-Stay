/* ============================================================
   Afristay — Page réinitialisation mot de passe (saas/reset-password.php)
   ============================================================ */

function resetPasswordPage(baseUrl) {
  return {
    token:           '',
    password:        '',
    passwordConfirm: '',
    showPass:        false,
    loading:         false,
    error:           null,
    done:            false,

    init() {
      this.token = new URLSearchParams(window.location.search).get('token') ?? '';
    },

    async submit() {
      this.error = null;
      if (this.password.length < 8) {
        this.error = 'Mot de passe : 8 caractères minimum.';
        return;
      }
      if (!/[a-zA-Z]/.test(this.password) || !/\d/.test(this.password)) {
        this.error = 'Mot de passe : au moins une lettre et un chiffre.';
        return;
      }
      if (this.password !== this.passwordConfirm) {
        this.error = 'Les mots de passe ne correspondent pas.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/auth/reset-password', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ token: this.token, password: this.password }),
        });
        const data = await res.json();
        if (data.success) {
          this.done = true;
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
