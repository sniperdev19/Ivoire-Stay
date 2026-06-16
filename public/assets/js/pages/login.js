/* ============================================================
   Ivoire Stay — Page autonome : Connexion (saas/login.php)
   Authentification → stockage JWT + établissements en localStorage.
   ============================================================ */

/**
 * Composant Alpine du formulaire de connexion.
 * @param {string} baseUrl  Préfixe d'URL de l'app (APP_URL).
 * Utilisé via x-data="loginForm('<?= $base_url ?>')".
 */
function loginForm(baseUrl) {
  return {
    form: { email: '', password: '' },
    loading: false,
    error: null,
    showPass: false,

    async submit() {
      this.error = null;
      if (!this.form.email.trim() || !this.form.password) {
        this.error = 'Veuillez remplir tous les champs.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('token', data.data.token);
          localStorage.setItem('user', JSON.stringify(data.data.user));
          localStorage.setItem('establishments', JSON.stringify(data.data.establishments ?? []));
          const e = data.data.establishments ?? [];
          if (e.length) localStorage.setItem('establishment_id', e[0].id);
          window.location.href = baseUrl + '/saas';
        } else {
          this.error = data.message ?? 'Email ou mot de passe incorrect.';
        }
      } catch (e) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
