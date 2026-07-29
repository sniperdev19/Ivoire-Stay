/* ============================================================
   Afristay — Profil agent commercial (src/templates/agent/profile.php)
   ============================================================ */

function agentProfilePage(baseUrl) {
  return {
    agent: {},
    form: { nom: '', numero: '', operateur_money: '' },
    saving:    false,
    saveError: null,
    saveOk:    false,

    pwForm: { current_password: '', new_password: '', new_password_confirm: '' },
    pwSaving: false,
    pwError:  null,
    pwOk:     false,

    init() {
      const token = localStorage.getItem('agent_token');
      if (!token) {
        window.location.href = baseUrl + '/agent/login';
        return;
      }
      const cached = localStorage.getItem('agent');
      if (cached) { try { this.agent = JSON.parse(cached); this.fillForm(); } catch (_) {} }
      this.load();
    },

    fillForm() {
      this.form.nom             = this.agent.nom             || '';
      this.form.numero          = this.agent.numero          || '';
      this.form.operateur_money = this.agent.operateur_money || '';
    },

    authHeaders() {
      return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('agent_token'),
      };
    },

    async load() {
      try {
        const res  = await fetch(baseUrl + '/api/agent/me', { headers: this.authHeaders() });
        const data = await res.json();
        if (res.status === 401) { this.logout(); return; }
        if (data.success) {
          this.agent = data.data.agent;
          localStorage.setItem('agent', JSON.stringify(this.agent));
          this.fillForm();
        }
      } catch (_) { /* réseau indisponible — on garde le cache déjà affiché */ }
    },

    async saveProfile() {
      this.saving = true;
      this.saveError = null;
      this.saveOk = false;
      try {
        const res  = await fetch(baseUrl + '/api/agent/profile', {
          method:  'PUT',
          headers: this.authHeaders(),
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.agent = data.data;
          localStorage.setItem('agent', JSON.stringify(this.agent));
          this.fillForm();
          this.saveOk = true;
          setTimeout(() => { this.saveOk = false; }, 2500);
        } else {
          this.saveError = data.message || 'Erreur lors de la mise à jour.';
        }
      } catch (_) {
        this.saveError = 'Erreur réseau.';
      } finally {
        this.saving = false;
      }
    },

    async changePassword() {
      this.pwError = null;
      this.pwOk    = false;

      if (!this.pwForm.current_password || !this.pwForm.new_password) {
        this.pwError = 'Tous les champs sont requis.';
        return;
      }
      if (this.pwForm.new_password !== this.pwForm.new_password_confirm) {
        this.pwError = 'Les mots de passe ne correspondent pas.';
        return;
      }

      this.pwSaving = true;
      try {
        const res  = await fetch(baseUrl + '/api/agent/change-password', {
          method:  'POST',
          headers: this.authHeaders(),
          body:    JSON.stringify({
            current_password: this.pwForm.current_password,
            new_password:     this.pwForm.new_password,
          }),
        });
        const data = await res.json();
        if (data.success) {
          this.pwOk = true;
          this.pwForm = { current_password: '', new_password: '', new_password_confirm: '' };
        } else {
          this.pwError = data.message || 'Erreur lors du changement de mot de passe.';
        }
      } catch (_) {
        this.pwError = 'Erreur réseau.';
      } finally {
        this.pwSaving = false;
      }
    },

    logout() {
      fetch(baseUrl + '/api/agent/logout', { method: 'POST', headers: this.authHeaders() }).catch(() => {});
      localStorage.removeItem('agent_token');
      localStorage.removeItem('agent');
      window.location.href = baseUrl + '/agent/login';
    },
  };
}
