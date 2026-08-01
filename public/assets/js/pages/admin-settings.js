/* ============================================================
   Afristay — Admin plateforme : Paramètres (src/templates/admin/settings.php)
   Trois sections : Mon profil (comptes /api/auth/*, mêmes endpoints que
   saas-settings.js), Réglages plateforme (/api/admin/settings) et Sauvegarde
   de la base de données (reprend l'ancien admin-backups.js).
   ============================================================ */

function adminSettingsPage(baseUrl) {
  return {
    ...saasHelpers,

    async init() {
      this.profileForm = { name: this.currentUser.name || '', phone: this.currentUser.phone || '' };
      await Promise.all([this.loadBackups(), this.loadPlatformSettings()]);
    },

    // ── Mon profil ──────────────────────────────────────────────────────────
    get currentUser() {
      try { return JSON.parse(localStorage.getItem('user') || '{}'); } catch (e) { return {}; }
    },
    get userAvatarUrl() {
      const path = this.currentUser.avatar_path;
      return path ? this.photoUrl(path) : null;
    },
    photoUrl(path) {
      if (!path) return null;
      if (path.startsWith('http')) return path;
      const clean = path.replace(/^\/+/, '');
      return baseUrl + '/' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
    },

    profileForm: { name: '', phone: '' },
    profileSaving: false,
    profileError: null,
    avatarUploading: false,
    avatarError: null,

    async saveProfile() {
      this.profileError = null;
      const name = this.profileForm.name.trim();
      if (!name) { this.profileError = 'Le nom est requis'; return; }

      this.profileSaving = true;
      try {
        const res  = await fetch(baseUrl + '/api/auth/profile', {
          method: 'PUT',
          headers: this.apiHeaders(),
          body: JSON.stringify({ name, phone: this.profileForm.phone.trim() }),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('user', JSON.stringify(data.data));
          this.showToast('Profil mis à jour.', 'success');
        } else {
          this.profileError = data.message || 'Erreur lors de la mise à jour.';
        }
      } catch (e) {
        this.profileError = 'Erreur réseau.';
      } finally {
        this.profileSaving = false;
      }
    },

    async uploadAvatar(evt) {
      const file = evt.target.files?.[0];
      evt.target.value = '';
      if (!file) return;

      this.avatarError = null;
      this.avatarUploading = true;
      try {
        const fd = new FormData();
        fd.append('avatar', file);
        const res  = await fetch(baseUrl + '/api/auth/avatar', {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
          body: fd,
        });
        const data = await res.json();
        if (data.success) {
          const user = this.currentUser;
          user.avatar_path = data.data.avatar_path;
          localStorage.setItem('user', JSON.stringify(user));
          this.showToast('Photo de profil mise à jour.', 'success');
        } else {
          this.avatarError = data.message || "Échec de l'envoi de la photo.";
        }
      } catch (e) {
        this.avatarError = 'Erreur réseau.';
      } finally {
        this.avatarUploading = false;
      }
    },

    async removeAvatar() {
      try {
        const res  = await fetch(baseUrl + '/api/auth/avatar', { method: 'DELETE', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          const user = this.currentUser;
          user.avatar_path = null;
          localStorage.setItem('user', JSON.stringify(user));
          this.showToast('Photo de profil supprimée.', 'success');
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      }
    },

    showPasswordModal: false,
    passwordForm: { current: '', new: '', confirm: '' },
    passwordError: null,
    passwordSaving: false,

    openPasswordModal() {
      this.passwordForm  = { current: '', new: '', confirm: '' };
      this.passwordError = null;
      this.showPasswordModal = true;
    },
    closePasswordModal() {
      if (this.passwordSaving) return;
      this.showPasswordModal = false;
    },
    async changePassword() {
      this.passwordError = null;
      if (!this.passwordForm.current || !this.passwordForm.new) {
        this.passwordError = 'Tous les champs sont requis.'; return;
      }
      if (this.passwordForm.new !== this.passwordForm.confirm) {
        this.passwordError = 'Les nouveaux mots de passe ne correspondent pas.'; return;
      }
      if (this.passwordForm.new.length < 8) {
        this.passwordError = 'Le nouveau mot de passe doit contenir au moins 8 caractères.'; return;
      }

      this.passwordSaving = true;
      try {
        const res  = await fetch(baseUrl + '/api/auth/change-password', {
          method: 'POST', headers: this.apiHeaders(),
          body: JSON.stringify({ current_password: this.passwordForm.current, new_password: this.passwordForm.new }),
        });
        const data = await res.json();
        if (data.success) {
          this.showPasswordModal = false;
          this.showToast('Mot de passe modifié.', 'success');
        } else {
          this.passwordError = data.message ?? 'Erreur lors du changement de mot de passe.';
        }
      } catch (e) {
        this.passwordError = 'Erreur réseau.';
      } finally {
        this.passwordSaving = false;
      }
    },

    // ── Réglages plateforme ─────────────────────────────────────────────────
    settingsLoading: true,
    settingsSaving: false,
    settingsError: null,
    settings: {},

    async loadPlatformSettings() {
      this.settingsLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/settings', { headers: this.apiHeaders() });
        const data = await res.json();
        this.settings = data.success ? data.data : {};
      } catch (e) {
        this.settings = {};
      } finally {
        this.settingsLoading = false;
      }
    },

    async savePlatformSettings() {
      this.settingsError = null;
      this.settingsSaving = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/settings', {
          method: 'PUT', headers: this.apiHeaders(), body: JSON.stringify(this.settings),
        });
        const data = await res.json();
        if (data.success) {
          await this.loadPlatformSettings();
          this.showToast('Réglages plateforme mis à jour.', 'success');
        } else {
          this.settingsError = data.message ?? 'Erreur lors de la mise à jour.';
        }
      } catch (e) {
        this.settingsError = 'Erreur réseau.';
      } finally {
        this.settingsSaving = false;
      }
    },

    // ── Sauvegarde de la base de données ────────────────────────────────────
    loadingBackups:  true,
    creatingBackup:  false,
    backups: [],

    async loadBackups() {
      this.loadingBackups = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/backups', { headers: this.apiHeaders() });
        const data = await res.json();
        this.backups = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.backups = [];
      } finally {
        this.loadingBackups = false;
      }
    },

    async createBackup() {
      this.creatingBackup = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/backups', { method: 'POST', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          await this.loadBackups();
          this.showToast('Sauvegarde créée.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur lors de la sauvegarde.', 'error');
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.creatingBackup = false;
      }
    },

    /* Téléchargement authentifié : le token est un Bearer en header, pas un
       cookie — un <a href> brut ne l'enverrait pas, il faut passer par
       fetch + blob (même pattern que le téléchargement PDF, saas-billing.js). */
    async downloadBackup(b) {
      try {
        const res = await fetch(baseUrl + '/api/admin/backups/' + b.filename + '/download', {
          headers: this.apiHeaders(),
        });
        if (!res.ok) { this.showToast('Erreur lors du téléchargement.', 'error'); return; }
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = b.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      } catch (e) {
        this.showToast('Erreur réseau lors du téléchargement.', 'error');
      }
    },

    formatDateTime(d) {
      if (!d) return '-';
      // created_at vient au format 'Y-m-d H:i:s' (MySQL-like) — Safari ne parse pas
      // cet espace comme séparateur date/heure, remplacer par 'T' pour rester portable.
      return new Date(d.replace(' ', 'T')).toLocaleString('fr-FR', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
      });
    },

    formatSize(bytes) {
      if (!bytes) return '0 o';
      if (bytes < 1024) return bytes + ' o';
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
      return (bytes / 1048576).toFixed(1) + ' Mo';
    },
  };
}
