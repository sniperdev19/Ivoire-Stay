/* ============================================================
   Afristay — Page acceptation invitation d'équipe (saas/team-invite.php)
   ============================================================ */

function teamInvitePage(baseUrl) {
  return {
    token:            '',
    email:            '',
    establishmentName: '',
    name:             '',
    password:         '',
    showPass:         false,
    loadingInfo:      true,
    infoError:        null,
    loading:          false,
    error:            null,
    done:             false,

    async init() {
      this.token = new URLSearchParams(window.location.search).get('token') ?? '';
      if (!this.token) {
        this.infoError = 'Lien invalide : le jeton est manquant.';
        this.loadingInfo = false;
        return;
      }
      try {
        const res  = await fetch(baseUrl + '/api/team/invite?token=' + encodeURIComponent(this.token));
        const data = await res.json();
        if (data.success) {
          this.email             = data.data.email;
          this.establishmentName = data.data.establishment_name;
        } else {
          this.infoError = data.message ?? 'Invitation invalide ou expirée.';
        }
      } catch (_) {
        this.infoError = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loadingInfo = false;
      }
    },

    async submit() {
      this.error = null;
      if (!this.name.trim()) {
        this.error = 'Le nom est requis.';
        return;
      }
      if (this.password.length < 8) {
        this.error = 'Mot de passe : 8 caractères minimum.';
        return;
      }
      if (!/[a-zA-Z]/.test(this.password) || !/\d/.test(this.password)) {
        this.error = 'Mot de passe : au moins une lettre et un chiffre.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/team/invite/accept', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ token: this.token, name: this.name, password: this.password }),
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
