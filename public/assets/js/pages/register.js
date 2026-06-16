/* ============================================================
   Ivoire Stay — Page autonome : Inscription (saas/register.php)
   Création de compte en 2 étapes (compte → établissement).
   ============================================================ */

/**
 * Composant Alpine du formulaire d'inscription.
 * @param {string} baseUrl  Préfixe d'URL de l'app (APP_URL).
 * Utilisé via x-data="registerForm('<?= $base_url ?>')".
 */
function registerForm(baseUrl) {
  return {
    form: {
      name: '',
      email: '',
      phone: '',
      password: '',
      password_confirm: '',
      establishment_name: '',
      establishment_type: 'hotel',
    },
    step: 1,
    loading: false,
    error: null,
    showPass: false,

    validateStep1() {
      this.error = null;
      if (!this.form.name.trim()) return (this.error = 'Nom complet requis.');
      if (!this.form.email.match(/^[^@]+@[^@]+\.[^@]+$/)) return (this.error = 'Email invalide.');
      if (!this.form.phone.trim()) return (this.error = 'Téléphone requis.');
      if (this.form.password.length < 8) return (this.error = 'Mot de passe : 8 caractères min.');
      if (this.form.password !== this.form.password_confirm) return (this.error = 'Mots de passe différents.');
      this.step = 2;
    },

    async submit() {
      this.error = null;
      if (!this.form.establishment_name.trim()) {
        this.error = "Nom de l'établissement requis.";
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/auth/register', {
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
          // Installation obligatoire : on envoie vers /login qui exige l'app installée.
          window.location.href = baseUrl + '/login?registered=1';
        } else {
          this.error = data.message ?? 'Erreur inscription.';
        }
      } catch (e) {
        this.error = 'Erreur réseau.';
      } finally {
        this.loading = false;
      }
    },
  };
}
