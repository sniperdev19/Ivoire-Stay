/* ============================================================
   Afristay — Admin plateforme : Paramètres (src/templates/admin/settings.php)
   Quatre sections : Mon profil (comptes /api/auth/*, mêmes endpoints que
   saas-settings.js — sessions actives + empreinte incluses), Réglages
   plateforme (/api/admin/settings), Notifications et Sauvegarde de la base
   de données (reprend l'ancien admin-backups.js).
   ============================================================ */

/* Même flag que public/assets/js/pages/saas-settings.js et login.js — à
   modifier en même temps qu'eux pour réactiver la fonctionnalité (code
   backend intact, désactivé le 2026-08-11 à la demande de l'utilisateur). */
const BIOMETRIC_LOGIN_ENABLED = false;

function adminSettingsPage(baseUrl) {
  return {
    ...saasHelpers,

    activeTab: 'profile',

    async init() {
      this.profileForm = { name: this.currentUser.name || '', phone: this.currentUser.phone || '' };
      this.initPush();       // indépendant du reste, ne bloque pas le chargement
      this.loadNotifPrefs(); // idem
      this.loadSessions();
      this.loadBiometricCredentials();
      await Promise.all([this.loadBackups(), this.loadPlatformSettings()]);
    },

    // ── Appareils connectés (sessions actives + historique) — même infra que saas-settings.js ──
    sessions: [],
    sessionsLoading: false,
    sessionsActionLoading: false,
    get activeSessionsCount() { return this.sessions.filter(s => s.is_active).length; },

    async loadSessions() {
      this.sessionsLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/auth/sessions', { headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) this.sessions = data.data ?? [];
      } catch (e) { /* liste vide si hors-ligne */ }
      finally { this.sessionsLoading = false; }
    },

    async revokeSession(id) {
      this.sessionsActionLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/auth/sessions/' + id + '/revoke', { method: 'POST', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          this.showToast('Appareil déconnecté.', 'success');
          await this.loadSessions();
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.sessionsActionLoading = false;
      }
    },

    async revokeOtherSessions() {
      this.sessionsActionLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/auth/sessions/revoke-others', { method: 'POST', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          this.showToast(data.message || 'Appareils déconnectés.', 'success');
          await this.loadSessions();
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.sessionsActionLoading = false;
      }
    },

    // ── Connexion par empreinte digitale (WebAuthn/passkey) — facultative, même infra que saas-settings.js ──
    biometricSupported: BIOMETRIC_LOGIN_ENABLED && !!(window.AfristayPWA && window.AfristayPWA.webauthnSupported()),
    biometricCredentials: [],
    biometricLoading: false,
    biometricEnrolling: false,
    biometricActionLoading: false,
    biometricError: null,

    async loadBiometricCredentials() {
      if (!this.biometricSupported) return;
      this.biometricLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/auth/webauthn/login-credential', { headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) this.biometricCredentials = data.data ?? [];
      } catch (e) { /* liste vide si hors-ligne */ }
      finally { this.biometricLoading = false; }
    },

    async enrollBiometric() {
      if (this.biometricEnrolling) return;
      this.biometricError = null;
      this.biometricEnrolling = true;
      try {
        const optRes  = await fetch(baseUrl + '/api/auth/webauthn/login-credential/register-options', {
          method: 'POST', headers: this.apiHeaders(),
        });
        const optData = await optRes.json();
        const { state, publicKey } = optData?.data ?? {};
        if (!optData.success || !state || !publicKey) {
          this.biometricError = optData.message || "Impossible de démarrer l'activation.";
          return;
        }

        const publicKeyOptions = {
          ...publicKey,
          challenge: window.AfristayPWA.b64urlToBuffer(publicKey.challenge),
          user: { ...publicKey.user, id: window.AfristayPWA.b64urlToBuffer(publicKey.user.id) },
          excludeCredentials: (publicKey.excludeCredentials || []).map(c => ({ ...c, id: window.AfristayPWA.b64urlToBuffer(c.id) })),
        };

        const credential = await navigator.credentials.create({ publicKey: publicKeyOptions });
        if (!credential) { this.biometricError = 'Cérémonie annulée.'; return; }

        const credentialJson = {
          id: credential.id,
          rawId: window.AfristayPWA.bufferToB64url(credential.rawId),
          type: credential.type,
          response: {
            clientDataJSON: window.AfristayPWA.bufferToB64url(credential.response.clientDataJSON),
            attestationObject: window.AfristayPWA.bufferToB64url(credential.response.attestationObject),
            transports: credential.response.getTransports ? credential.response.getTransports() : [],
          },
        };

        const verifyRes  = await fetch(baseUrl + '/api/auth/webauthn/login-credential/register-verify', {
          method: 'POST', headers: this.apiHeaders(),
          body: JSON.stringify({ state, credential: credentialJson }),
        });
        const verifyData = await verifyRes.json();
        if (verifyData.success) {
          this.showToast('Empreinte activée sur cet appareil.', 'success');
          await this.loadBiometricCredentials();
        } else {
          this.biometricError = verifyData.message || "Échec de l'activation.";
        }
      } catch (e) {
        this.biometricError = e?.name === 'NotAllowedError' ? null : "Impossible d'activer l'empreinte sur cet appareil.";
      } finally {
        this.biometricEnrolling = false;
      }
    },

    async revokeBiometric(id) {
      this.biometricActionLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/auth/webauthn/login-credential/' + id, { method: 'DELETE', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          this.showToast('Empreinte désactivée.', 'success');
          await this.loadBiometricCredentials();
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.biometricActionLoading = false;
      }
    },

    /* formatDate local : 'Aucune' pour null, mois en toutes lettres — même
       override que saas-settings.js (remplace saasHelpers.formatDate, plus
       court, pour les sessions/empreintes). */
    formatDate(d) {
      if (!d) return 'Aucune';
      return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
    },

    // ── Notifications push (hors application) — même infra que saas-settings.js ──
    pushEnabled: false,
    pushUnsupported: false,
    pushLoading: false,
    pushError: null,

    async initPush() {
      this.pushUnsupported = !(window.AfristayPWA && window.AfristayPWA.pushSupported());
      if (this.pushUnsupported) return;
      try {
        const sub = await window.AfristayPWA.syncPushSubscription(baseUrl, localStorage.getItem('token'));
        this.pushEnabled = !!sub;
      } catch (e) { /* laisse pushEnabled à false */ }
    },

    async togglePush() {
      if (this.pushUnsupported || this.pushLoading) return;
      this.pushLoading = true; this.pushError = null;
      const token = localStorage.getItem('token');
      try {
        if (this.pushEnabled) {
          await window.AfristayPWA.disablePush(baseUrl, token);
          this.pushEnabled = false;
          this.showToast('Notifications désactivées.', 'success');
        } else {
          const ok = await window.AfristayPWA.enablePush(baseUrl, token);
          if (ok) {
            this.pushEnabled = true;
            this.showToast('Notifications activées.', 'success');
          } else {
            this.pushError = "Autorisation refusée ou indisponible. Vérifiez les réglages de notifications de votre navigateur pour ce site.";
          }
        }
      } catch (e) {
        this.pushError = 'Erreur lors de la mise à jour.';
      } finally {
        this.pushLoading = false;
      }
    },

    // ── Préférences de notification (types désactivables, alertes superadmin uniquement) ──
    notifTypeLabels: {
      new_establishment:      'Nouvel établissement inscrit',
      payout_requested:       'Demande de retrait',
      subscription_activated: 'Abonnement activé',
    },
    notifMutedTypes: [],
    notifPrefsLoading: false,
    notifPrefsSaving: false,

    get notifTypeList() {
      return Object.entries(this.notifTypeLabels).map(([type, label]) => ({ type, label }));
    },

    async loadNotifPrefs() {
      this.notifPrefsLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/notifications/preferences', { headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) this.notifMutedTypes = data.data?.muted_types ?? [];
      } catch (e) { /* laisse la liste vide — tout reste activé par défaut */ }
      finally { this.notifPrefsLoading = false; }
    },

    isNotifMuted(type) { return this.notifMutedTypes.includes(type); },

    async toggleNotifType(type) {
      if (this.notifPrefsSaving) return;
      const next = this.isNotifMuted(type)
        ? this.notifMutedTypes.filter(t => t !== type)
        : [...this.notifMutedTypes, type];

      const previous = this.notifMutedTypes;
      this.notifMutedTypes = next; // optimiste
      this.notifPrefsSaving = true;
      try {
        const res  = await fetch(baseUrl + '/api/notifications/preferences', {
          method: 'PUT',
          headers: this.apiHeaders(),
          body: JSON.stringify({ muted_types: next }),
        });
        const data = await res.json();
        if (!data.success) this.notifMutedTypes = previous; // repli si échec serveur
      } catch (e) {
        this.notifMutedTypes = previous;
      } finally {
        this.notifPrefsSaving = false;
      }
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

    // ── Réglages plateforme (carrousel, même pattern que l'onglet Général du SaaS) ──
    settingsLoading: true,
    settingsSaving: false,
    settingsError: null,
    settings: {},
    settingsCardIndex: 0,
    settingsSections: ['agents', 'contact', 'prices', 'bonuses'],
    settingsSectionLabels: {
      agents:  'Agents commerciaux',
      contact: 'Coordonnées de contact',
      prices:  'Prix des abonnements',
      bonuses: 'Primes agents commerciaux',
    },

    // Prix annuel = mensuel × 12 avec 20% de remise — même taux que le badge
    // "–20%" affiché sur /tarifs (vitrine/pricing.php) : le champ annuel est
    // désactivé côté template, jamais saisi à la main, pour ne jamais dériver
    // de ce taux annoncé publiquement.
    syncYearlyPrice(plan) {
      const monthly = Number(this.settings['plan_price_' + plan + '_monthly']) || 0;
      this.settings['plan_price_' + plan + '_yearly'] = Math.round(monthly * 12 * 0.8);
    },

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
