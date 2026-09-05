/* ============================================================
   Afristay — Page SaaS : Paramètres (src/templates/saas/settings.php)
   ============================================================ */

/* Désactivé le 2026-08-11 à la demande explicite de l'utilisateur — même
   flag que public/assets/js/pages/login.js, à modifier en même temps que
   lui pour réactiver la fonctionnalité (code backend intact). */
const BIOMETRIC_LOGIN_ENABLED = true;

function settingsPage(baseUrl, onlinePaymentsEnabled, commissionPct, estabSharePct) {
  return {
  ...saasHelpers,

  // Verrou v1 global (ONLINE_PAYMENTS_ENABLED, config.php) : paiement en ligne
  // en cours de développement, indépendant du plan de l'établissement.
  onlinePaymentsEnabled: !!onlinePaymentsEnabled,
  // Taux de commission plateforme (%) du plan Starter (PlanPricingService::commissionPct), pour l'affichage.
  commissionPct: commissionPct ?? 0,
  // Part de la commission (%) qui réduit réellement le montant touché par l'établissement
  // (le reste est déjà inclus dans le prix vu par le client — PlanPricingService::establishmentSharePct).
  estabSharePct: estabSharePct ?? 0,

  establishment: null,
  loading: true,
  subscription: null,
  plans: {},
  featureLabels: {
    invoices:    'Facturation',
    payments:    'Gestion des paiements',
    expenses:    'Suivi des dépenses',
    reports:     'Rapports & Analyses',
    pdf:         'Export PDF',
    boost:       'Boost vitrine',
    multi_estab: 'Multi-établissements',
    online_payment: 'Paiement en ligne',
    online_payment_control: 'Paiement en ligne désactivable (sans commission)',
  },
  activeTab: 'general',
  saving: false,
  saveError: null,
  saveSuccess: false,
  isNewEstab: false,
  addingEstab: false,
  get creatingEstab() { return this.isNewEstab || this.addingEstab; },

  /* Navigation "carrousel" entre les cartes de l'onglet Général — une carte
     visible à la fois, boutons Précédent/Suivant. La liste dépend du mode
     (Photos n'existe qu'en édition, l'étape finale "Créer" n'existe qu'en
     création — cf. cartes correspondantes dans settings.php). */
  generalCardIndex: 0,
  get generalSections() {
    const base = ['identity', 'location', 'contact', 'payment', 'visibility', 'presentation', 'hours'];
    return this.creatingEstab ? [...base, 'create'] : [...base, 'photos'];
  },
  generalSectionLabels: {
    identity: 'Identité', location: 'Localisation', contact: 'Contact',
    payment: 'Paiement en ligne', visibility: 'Visibilité', presentation: 'Présentation',
    hours: 'Horaires', photos: 'Photos', create: 'Créer l\'établissement',
  },
  generalPrev() { if (this.generalCardIndex > 0) this.generalCardIndex--; },
  generalNext() { if (this.generalCardIndex < this.generalSections.length - 1) this.generalCardIndex++; },

  /* Onglets Général/Membres/Abonnement réservés owner/superadmin — un
     receptionist n'a que "Compte". Recalculé depuis le cache localStorage
     (pas depuis le scope Alpine parent saasLayout, séparé de ce composant)
     pour rester fiable même en arrivant directement sur cette page. */
  get canSeeSettings() {
    let user = null;
    try { user = JSON.parse(localStorage.getItem('user') || 'null'); } catch (_) {}
    return ['owner', 'superadmin'].includes(user?.role ?? 'staff');
  },

  form: { name: '', type: 'hotel', address: '', city: '', phone: '', email: '', description: '', website: '', latitude: null, longitude: null, online_payment_enabled: true, is_boosted: false, check_in_time: '14:00', check_out_time: '12:00' },

  photos: [],
  photoUploading: false,
  photoDeleting: null,
  photoError: null,

  photoUrl(path) {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    const clean = path.replace(/^\/+/, '');
    return baseUrl + '/' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
  },

  async addPhoto(evt) {
    const file = evt.target.files?.[0];
    evt.target.value = '';
    if (!file) return;
    this.photoError = null;
    this.photoUploading = true;
    try {
      const body = new FormData();
      body.append('photo', file);
      const res  = await fetch(baseUrl + '/api/establishments/' + this.estId() + '/photo', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        body,
      });
      const data = await res.json();
      if (data.success) {
        const estRes = await fetch(baseUrl + '/api/establishments/' + this.estId(), { headers: this.apiHeaders() }).then(r => r.json());
        if (estRes.success) this.photos = (estRes.data?.establishment ?? estRes.data)?.photos ?? [];
        this.showToast('Photo ajoutée.', 'success');
      } else {
        this.photoError = data.message ?? 'Erreur lors de l\'envoi de la photo.';
      }
    } catch (e) {
      this.photoError = 'Erreur réseau.';
    } finally {
      this.photoUploading = false;
    }
  },

  async removePhoto(id) {
    if (!confirm('Supprimer cette photo ?')) return;
    this.photoDeleting = id;
    try {
      const res  = await fetch(baseUrl + '/api/establishment-photos/' + id, {
        method: 'DELETE',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
      });
      const data = await res.json();
      if (data.success) {
        this.photos = this.photos.filter(p => p.id !== id);
        this.showToast('Photo supprimée.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur de suppression.', 'error');
      }
    } catch (e) {
      this.showToast('Erreur réseau.', 'error');
    } finally {
      this.photoDeleting = null;
    }
  },

  savingPayment: false,

  /* Sur le plan Starter, le paiement en ligne est forcé actif (non désactivable) en échange
     de la commission plateforme — seuls Pro/Business ont le contrôle du toggle (config/plans.php: online_payment_control). */
  get onlinePaymentForced() { return planUpgradeRequired('online_payment_control'); },
  /* Fonctionnalité en développement pour tout le monde, indépendamment du plan (v1) */
  get onlinePaymentComingSoon() { return !this.onlinePaymentsEnabled; },

  async toggleOnlinePayment() {
    if (this.onlinePaymentComingSoon || this.onlinePaymentForced || this.creatingEstab || this.savingPayment) return;
    const previous = this.form.online_payment_enabled;
    this.form.online_payment_enabled = !previous;
    this.savingPayment = true;
    try {
      const res  = await fetch(baseUrl + '/api/establishments/' + this.estId(), {
        method: 'PUT',
        headers: this.apiHeaders(),
        body: JSON.stringify({ online_payment_enabled: this.form.online_payment_enabled }),
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(this.form.online_payment_enabled
          ? 'Paiement en ligne réactivé.'
          : 'Paiement en ligne désactivé. Vos clients paieront sur place.', 'success');
      } else {
        this.form.online_payment_enabled = previous;
        this.showToast(data.message ?? 'Erreur lors de la mise à jour.', 'error');
      }
    } catch (e) {
      this.form.online_payment_enabled = previous;
      this.showToast('Erreur réseau.', 'error');
    } finally {
      this.savingPayment = false;
    }
  },

  savingBoost: false,

  /* Le boost (mise en avant dans la recherche publique) est réservé au plan Business */
  get boostLocked() { return planUpgradeRequired('boost'); },

  async toggleBoost() {
    if (this.boostLocked || this.creatingEstab || this.savingBoost) return;
    const previous = this.form.is_boosted;
    this.form.is_boosted = !previous;
    this.savingBoost = true;
    try {
      const res  = await fetch(baseUrl + '/api/establishments/' + this.estId(), {
        method: 'PUT',
        headers: this.apiHeaders(),
        body: JSON.stringify({ is_boosted: this.form.is_boosted }),
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(this.form.is_boosted
          ? 'Établissement mis en avant dans les résultats de recherche.'
          : 'Mise en avant désactivée.', 'success');
      } else {
        this.form.is_boosted = previous;
        this.showToast(data.message ?? 'Erreur lors de la mise à jour.', 'error');
      }
    } catch (e) {
      this.form.is_boosted = previous;
      this.showToast('Erreur réseau.', 'error');
    } finally {
      this.savingBoost = false;
    }
  },

  locating: false,
  locateError: null,
  locateSuccess: false,

  /* Capture la position GPS du navigateur et l'enregistre en DB (silencieusement si l'établissement existe déjà) */
  locateMe() {
    if (!navigator.geolocation) {
      this.locateError = "La géolocalisation n'est pas supportée par votre navigateur.";
      return;
    }
    this.locating      = true;
    this.locateError   = null;
    this.locateSuccess = false;

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        this.form.latitude  = pos.coords.latitude;
        this.form.longitude = pos.coords.longitude;
        this.locating       = false;
        this.locateSuccess  = true;

        // Établissement déjà créé : on enregistre la position tout de suite, sans attendre "Enregistrer"
        if (!this.creatingEstab && this.estId()) {
          try {
            await fetch(baseUrl + '/api/establishments/' + this.estId(), {
              method: 'PUT',
              headers: this.apiHeaders(),
              body: JSON.stringify({ latitude: this.form.latitude, longitude: this.form.longitude }),
            });
          } catch (e) {
            /* hors-ligne — la position reste dans le formulaire et partira au prochain enregistrement */
          }
        }
      },
      (err) => {
        this.locating = false;
        this.locateError = err.code === err.PERMISSION_DENIED
          ? "Vous avez refusé l'accès à votre position. Autorisez la géolocalisation dans les réglages de votre navigateur, puis réessayez."
          : 'Impossible de récupérer votre position. Réessayez.';
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  },

  paymentSuccess: false,
  paymentError: null,

  async verifyPayment(ref) {
    try {
      const res  = await fetch(baseUrl + '/api/subscriptions/verify/' + encodeURIComponent(ref), {
        headers: this.apiHeaders(),
      });
      const data = await res.json();
      if (data.success && data.data?.status === 'active') {
        // Rafraîchir l'établissement en DB → mettre à jour localStorage → recharger
        const estRes = await fetch(baseUrl + '/api/establishments/' + this.estId(), {
          headers: this.apiHeaders(),
        }).then(r => r.json());
        if (estRes.success) {
          const estab  = estRes.data?.establishment ?? estRes.data;
          const estabs = JSON.parse(localStorage.getItem('establishments') || '[]');
          const idx    = estabs.findIndex(e => String(e.id) === String(estab.id));
          if (idx !== -1) estabs[idx] = { ...estabs[idx], plan: estab.plan, plan_expires_at: estab.plan_expires_at };
          else estabs.push(estab);
          localStorage.setItem('establishments', JSON.stringify(estabs));
        }
        // Recharger pour que le sidebar prenne le nouveau plan
        window.location.href = window.location.pathname + '?activated=1';
      } else {
        this.paymentError = data.data?.gp_status === 'pending'
          ? 'Paiement en cours de traitement…'
          : 'Paiement non confirmé. Contactez le support si vous avez été débité.';
        this.activeTab = 'subscription';
      }
    } catch (e) {
      this.paymentError = 'Impossible de vérifier le paiement.';
    }
  },

  /* formatDate local : 'Aucune' pour null, mois en toutes lettres */
  formatDate(d) {
    if (!d) return 'Aucune';
    return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  },

  async init() {
    this.loading = true;
    this.initPush();       // indépendant de l'établissement, ne bloque pas le reste
    this.loadNotifPrefs(); // idem
    this.loadSessions();   // idem
    this.loadBiometricCredentials(); // idem
    this.profileForm = { name: this.currentUser.name || '', phone: this.currentUser.phone || '' };
    const id = this.estId();

    // Retour depuis GeniusPay après paiement
    const urlParams = new URLSearchParams(window.location.search);

    // Lien direct vers un onglet (ex : "Upgrader →" dans la sidebar)
    const tabParam = urlParams.get('tab');
    if (['general', 'team', 'subscription', 'account', 'notifications'].includes(tabParam)) {
      this.activeTab = tabParam;
    }
    if (!this.canSeeSettings) {
      this.activeTab = 'account';
    }

    const payRef = urlParams.get('ref');
    if (urlParams.get('sub') === 'ok' && payRef) {
      await this.verifyPayment(payRef);
      window.history.replaceState(null, '', window.location.pathname);
    }
    // Rechargement post-activation : afficher bannière succès
    if (urlParams.get('activated') === '1') {
      this.paymentSuccess = true;
      this.activeTab      = 'subscription';
      window.history.replaceState(null, '', window.location.pathname);
    }

    if (!id) {
      this.isNewEstab = true;
      try {
        const plansRes = await fetch(baseUrl + '/api/subscriptions/plans', { headers: this.apiHeaders() }).then(r => r.json());
        if (plansRes.success) this.plans = plansRes.data?.plans ?? plansRes.data ?? [];
      } catch(e) {}
      this.loading = false;
      return;
    }

    try {
      const [estRes, subRes, plansRes, historyRes] = await Promise.all([
        fetch(baseUrl + '/api/establishments/' + id,                             { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/subscriptions/status?establishment_id=' + id,      { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/subscriptions/plans',                              { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/subscriptions/history',                           { headers: this.apiHeaders() }).then(r => r.json()),
      ]);

      if (estRes.success) {
        this.establishment = estRes.data?.establishment ?? estRes.data;
        this.form = {
          name:        this.establishment?.name        ?? '',
          type:        this.establishment?.type        ?? 'hotel',
          address:     this.establishment?.address     ?? '',
          city:        this.establishment?.city        ?? '',
          phone:       this.establishment?.phone       ?? '',
          email:       this.establishment?.email       ?? '',
          description: this.establishment?.description ?? '',
          website:     this.establishment?.website     ?? '',
          latitude:    this.establishment?.latitude  != null ? Number(this.establishment.latitude)  : null,
          longitude:   this.establishment?.longitude != null ? Number(this.establishment.longitude) : null,
          online_payment_enabled: this.establishment?.online_payment_enabled != null
            ? !!Number(this.establishment.online_payment_enabled) : true,
          is_boosted: !!Number(this.establishment?.is_boosted ?? 0),
          // La colonne SQL renvoie "HH:MM:SS" ; <input type="time"> attend "HH:MM".
          check_in_time:  (this.establishment?.check_in_time  ?? '14:00:00').slice(0, 5),
          check_out_time: (this.establishment?.check_out_time ?? '12:00:00').slice(0, 5),
        };
        this.photos = this.establishment?.photos ?? [];
      }
      if (subRes.success)   this.subscription = subRes.data?.subscription ?? subRes.data;
      if (plansRes.success) this.plans = plansRes.data?.plans ?? plansRes.data ?? [];
      if (historyRes.success) this.subHistory = historyRes.data ?? [];
      this.loadTeam(); // onglet indépendant, ne bloque pas le rendu du reste de la page
    } catch(e) {
      /* erreur réseau — formulaire reste vide */
    } finally {
      this.loading = false;
      this.loadingSubHistory = false;
    }

    // Demande d'ajout d'un établissement supplémentaire (lien "+ Ajouter un établissement")
    if (urlParams.get('add_estab') === '1') {
      window.history.replaceState(null, '', window.location.pathname);
      if (planUpgradeRequired('multi_estab')) {
        this.activeTab = 'subscription';
        this.showToast("Passez au plan Business pour gérer plusieurs établissements.", 'error');
      } else {
        this.startAddEstablishment();
      }
    }
  },

  /* Bascule le formulaire "Général" en mode création d'un établissement supplémentaire,
     sans toucher à l'établissement actif tant que la création n'est pas confirmée. */
  startAddEstablishment() {
    this.addingEstab = true;
    this.activeTab   = 'general';
    this.generalCardIndex = 0;
    this.form = { name: '', type: 'hotel', address: '', city: '', phone: '', email: '', description: '', website: '', latitude: null, longitude: null, online_payment_enabled: true, is_boosted: false, check_in_time: '14:00', check_out_time: '12:00' };
    this.photos = [];
  },

  cancelAddEstablishment() {
    window.location.href = baseUrl + '/saas/settings';
  },

  // ── Notifications push (hors application) ─────────────────────────────
  pushEnabled: false,
  pushUnsupported: false,
  pushLoading: false,
  pushError: null,

  async initPush() {
    this.pushUnsupported = !(window.AfristayPWA && window.AfristayPWA.pushSupported());
    if (this.pushUnsupported) return;
    try {
      // Resynchronise vers le serveur si besoin : un abonnement navigateur "orphelin"
      // (POST d'origine échoué) ne doit pas afficher le bouton comme activé.
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

  // ── Préférences de notification (types désactivables) ─────────────────
  notifTypeLabels: {
    booking_new:            'Nouvelle réservation',
    booking_cancelled:      'Réservation annulée',
    payment_received:       'Paiement reçu',
    invoice_sent:           'Facture envoyée par email',
    invoice_paid:           'Facture soldée',
    team_member_added:      "Membre d'équipe ajouté",
    subscription_expiring:  'Abonnement bientôt expiré',
    arrival_reminder:       'Arrivées prévues demain',
    departure_reminder:     'Départs prévus demain',
    new_establishment:      'Nouvel établissement inscrit (superadmin)',
    payout_requested:       'Demande de retrait (superadmin)',
    subscription_activated: 'Abonnement activé (superadmin)',
    establishment_frozen:   'Établissement gelé (limite du plan dépassée)',
    establishment_unfrozen: 'Établissement réactivé',
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

  // ── Profil (onglet Compte) ─────────────────────────────────────────────
  get currentUser() {
    try { return JSON.parse(localStorage.getItem('user') || '{}'); } catch (e) { return {}; }
  },
  get userAvatarUrl() {
    const path = this.currentUser.avatar_path;
    return path ? this.photoUrl(path) : null;
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

  // ── Appareils connectés (sessions actives + historique) ────────────────
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

  // ── Connexion par empreinte digitale (WebAuthn/passkey) — facultative ──
  // Ne PAS confondre avec le device_token du gate "app installée" (pwa.js
  // ensureWebauthnCredential) : ici chaque empreinte est liée au compte
  // (webauthn_login_credentials.user_id), c'est un raccourci de connexion,
  // jamais un remplacement du mot de passe.
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
      if (data.success) {
        this.biometricCredentials = data.data ?? [];
        // Auto-réparation du témoin localStorage (login.js::biometricFirstMode) sur
        // l'état réel du serveur — au cas où il aurait divergé (ex : effacé par un
        // localStorage.clear() de déconnexion ailleurs dans l'app). Simplement ouvrir
        // Paramètres suffit à corriger un témoin désynchronisé, sans manipulation manuelle.
        if (this.biometricCredentials.length > 0) {
          localStorage.setItem('biometric_login_hint', '1');
        } else {
          localStorage.removeItem('biometric_login_hint');
        }
      }
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
        // loadBiometricCredentials() pose le témoin localStorage pour /login
        // (loginPage()::biometricFirstMode) — voir son commentaire.
        await this.loadBiometricCredentials();
      } else {
        this.biometricError = verifyData.message || "Échec de l'activation.";
      }
    } catch (e) {
      // Annulation utilisateur (navigator.credentials.create rejeté) ou appareil sans authentificateur.
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
        // loadBiometricCredentials() retire le témoin localStorage si c'était la
        // dernière empreinte du compte — voir son commentaire.
        await this.loadBiometricCredentials();
      }
    } catch (e) {
      this.showToast('Erreur réseau.', 'error');
    } finally {
      this.biometricActionLoading = false;
    }
  },

  // ── Mon QR code (agents commerciaux, fonctionnalité temporaire) ───────
  showQrModal: false,
  qrCodeToken: '',
  qrCodeLoading: false,
  qrAgentLinked: false,

  async openMyQrCode() {
    this.showQrModal   = true;
    this.qrCodeLoading = true;
    this.qrCodeToken   = '';
    this.qrAgentLinked = false;
    document.getElementById('qr-code-canvas').innerHTML = '';
    try {
      const res  = await fetch(baseUrl + '/api/establishment/qr?establishment_id=' + this.estId(), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success && data.data.agent_linked) {
        // Premier scan gagne, jamais de réassignation (AgentController::scanQr) —
        // le QR n'a plus aucune utilité une fois rattaché, on ne l'affiche plus
        // comme actif pour ne pas laisser croire qu'un nouveau scan ferait quelque chose.
        this.qrAgentLinked = true;
      } else if (data.success && data.data.qr_token) {
        this.qrCodeToken = data.data.qr_token;
        const qr = qrcode(0, 'M');
        qr.addData(this.qrCodeToken);
        qr.make();
        document.getElementById('qr-code-canvas').innerHTML = qr.createImgTag(6, 8);
      }
    } catch (e) {
      // silencieux : le modal affiche simplement un QR vide
    } finally {
      this.qrCodeLoading = false;
    }
  },

  async copyQrCode() {
    if (!this.qrCodeToken) return;
    try {
      await navigator.clipboard.writeText(this.qrCodeToken);
      this.showToast('Code copié.', 'success');
    } catch (e) {
      this.showToast('Impossible de copier le code.', 'error');
    }
  },

  // ── Membres d'équipe (rôle receptionist) ──────────────────────────────
  teamMembers: [],
  pendingInvitations: [],
  teamLoading: true,
  teamError: null,
  showTeamModal: false,
  teamEditing: null,
  teamSubmitting: false,
  teamFormError: null,
  teamForm: { name: '', email: '', phone: '', password: '', establishment_id: '' },

  /* Établissements du propriétaire connecté (plan Business multi-établissements) —
     déjà en cache localStorage (posé par saasLayout() au chargement de la page). */
  get establishments() {
    try { return JSON.parse(localStorage.getItem('establishments') || '[]'); } catch(e) { return []; }
  },

  async loadTeam() {
    this.teamLoading = true; this.teamError = null;
    try {
      const res  = await fetch(baseUrl + '/api/team?establishment_id=' + this.estId(), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.teamMembers       = data.data?.members ?? [];
        this.pendingInvitations = data.data?.invitations ?? [];
      } else {
        this.teamMembers = [];
        this.pendingInvitations = [];
        this.teamError = data.message ?? 'Impossible de charger les membres.';
      }
    } catch(e) {
      this.teamMembers = [];
      this.pendingInvitations = [];
      this.teamError = 'Erreur réseau.';
    } finally {
      this.teamLoading = false;
    }
  },

  openInviteMember() {
    this.teamEditing   = null;
    this.teamForm      = { name: '', email: '', phone: '', password: '', establishment_id: this.estId() };
    this.teamFormError = null;
    this.showTeamModal = true;
  },

  openEditMember(m) {
    this.teamEditing   = m;
    this.teamForm      = { name: m.name, email: m.email, phone: m.phone ?? '', password: '', establishment_id: m.establishment_id ?? this.estId() };
    this.teamFormError = null;
    this.showTeamModal = true;
  },

  async saveMember() {
    if (this.teamEditing) {
      if (!this.teamForm.name?.trim()) { this.teamFormError = 'Le nom est requis.'; return; }
      if (this.teamForm.password && this.teamForm.password.length < 8) {
        this.teamFormError = 'Mot de passe trop court (min. 8 caractères).'; return;
      }
    } else if (!this.teamForm.email?.trim()) {
      this.teamFormError = "L'email est requis."; return;
    }

    this.teamSubmitting = true; this.teamFormError = null;
    try {
      const url     = this.teamEditing ? baseUrl + '/api/team/' + this.teamEditing.id : baseUrl + '/api/team/invite';
      const payload = this.teamEditing
        ? { name: this.teamForm.name, phone: this.teamForm.phone || null, ...(this.teamForm.password ? { password: this.teamForm.password } : {}) }
        : { email: this.teamForm.email, establishment_id: this.teamForm.establishment_id || this.estId() };

      const res  = await fetch(url, {
        method: this.teamEditing ? 'PUT' : 'POST',
        headers: this.apiHeaders(),
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (data.success) {
        this.showTeamModal = false;
        await this.loadTeam();
        this.showToast(this.teamEditing ? 'Membre mis à jour.' : 'Invitation envoyée.', 'success');
      } else {
        this.teamFormError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.teamFormError = 'Erreur réseau.';
    } finally {
      this.teamSubmitting = false;
    }
  },

  async resendInvitation(inv) {
    try {
      const res  = await fetch(baseUrl + '/api/team/invite', {
        method: 'POST',
        headers: this.apiHeaders(),
        body: JSON.stringify({ email: inv.email, establishment_id: inv.establishment_id }),
      });
      const data = await res.json();
      if (data.success) {
        await this.loadTeam();
        this.showToast('Invitation renvoyée.', 'success');
      } else {
        this.showToast(data.message ?? "Erreur lors de l'envoi.", 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    }
  },

  async cancelInvitation(inv) {
    if (!confirm(`Annuler l'invitation envoyée à ${inv.email} ?`)) return;
    try {
      const res  = await fetch(baseUrl + '/api/team/invite/' + inv.id, { method: 'DELETE', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.pendingInvitations = this.pendingInvitations.filter(x => x.id !== inv.id);
        this.showToast('Invitation annulée.', 'success');
      } else {
        this.showToast(data.message ?? "Erreur lors de l'annulation.", 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    }
  },

  async deleteMember(m) {
    if (!confirm(`Supprimer l'accès de ${m.name} ?`)) return;
    try {
      const res  = await fetch(baseUrl + '/api/team/' + m.id, { method: 'DELETE', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.teamMembers = this.teamMembers.filter(x => x.id !== m.id);
        this.showToast('Membre supprimé.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur de suppression.', 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    }
  },

  subHistory: [],
  loadingSubHistory: true,
  planActionLoading: false,
  planActionError: null,

  async loadSubHistory() {
    try {
      const res  = await fetch(baseUrl + '/api/subscriptions/history', { headers: this.apiHeaders() });
      const data = await res.json();
      this.subHistory = data.success ? (data.data ?? []) : [];
    } catch (e) {
      this.subHistory = [];
    } finally {
      this.loadingSubHistory = false;
    }
  },

  subHistoryStatusCfg(s) {
    return {
      pending:   { label: 'En attente', badge: 'badge badge-warning' },
      active:    { label: 'Actif',      badge: 'badge badge-success' },
      failed:    { label: 'Échoué',     badge: 'badge badge-danger' },
      cancelled: { label: 'Annulé',     badge: 'badge' },
      expired:   { label: 'Expiré',     badge: 'badge' },
    }[s] ?? { label: s ?? '—', badge: 'badge' };
  },

  async cancelSubscription() {
    if (this.planActionLoading) return;
    if (!confirm("Annuler votre abonnement ? Vous repasserez immédiatement au plan Starter et perdrez l'accès aux fonctionnalités payantes.")) return;
    this.planActionLoading = true; this.planActionError = null;
    try {
      const res  = await fetch(baseUrl + '/api/subscriptions/cancel', { method: 'POST', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.showToast('Abonnement annulé.', 'success');
        setTimeout(() => window.location.reload(), 1000);
      } else {
        this.planActionError = data.message ?? 'Erreur lors de l\'annulation.';
      }
    } catch (e) {
      this.planActionError = 'Erreur réseau.';
    } finally {
      this.planActionLoading = false;
    }
  },

  async downgradeSubscription(targetPlan) {
    if (this.planActionLoading) return;
    const label = this.plans[targetPlan]?.name ?? this.planLabel(targetPlan);
    if (!confirm(`Rétrograder vers le plan ${label} ? Ce changement est immédiat.`)) return;
    this.planActionLoading = true; this.planActionError = null;
    try {
      const res  = await fetch(baseUrl + '/api/subscriptions/downgrade', {
        method: 'POST', headers: this.apiHeaders(), body: JSON.stringify({ plan: targetPlan }),
      });
      const data = await res.json();
      if (data.success) {
        this.showToast('Abonnement rétrogradé.', 'success');
        setTimeout(() => window.location.reload(), 1000);
      } else {
        this.planActionError = data.message ?? 'Erreur lors de la rétrogradation.';
      }
    } catch (e) {
      this.planActionError = 'Erreur réseau.';
    } finally {
      this.planActionLoading = false;
    }
  },

  // Uniquement la création (POST) : en édition, chaque carte du formulaire
  // s'enregistre indépendamment (voir saveIdentity/saveContact/savePresentation/
  // saveHours ci-dessous + Localisation/Paiement/Visibilité/Photos qui
  // s'enregistrent déjà instantanément à l'action).
  async saveGeneral() {
    if (!this.creatingEstab) return;
    this.saving = true; this.saveError = null; this.saveSuccess = false;
    try {
      if (!this.form.name?.trim()) { this.saveError = 'Le nom est requis.'; this.saving = false; return; }
      if (!this.form.city?.trim()) { this.saveError = 'La ville est requise.'; this.saving = false; return; }

      const res  = await fetch(baseUrl + '/api/establishments', {
        method: 'POST', headers: this.apiHeaders(), body: JSON.stringify(this.form),
      });
      const data = await res.json();

      if (data.success) {
        this.saveSuccess = true;
        setTimeout(() => this.saveSuccess = false, 3000);

        const estab = data.data?.establishment ?? data.data;
        if (estab?.id) {
          if (this.addingEstab) {
            // Un établissement supplémentaire : recharger la liste complète pour ne pas
            // écraser les établissements existants dans le localStorage.
            try {
              const listRes = await fetch(baseUrl + '/api/establishments', { headers: this.apiHeaders() }).then(r => r.json());
              if (listRes.success) localStorage.setItem('establishments', JSON.stringify(listRes.data ?? []));
            } catch (e) {}
          } else {
            localStorage.setItem('establishments', JSON.stringify([estab]));
          }
          localStorage.setItem('establishment_id', String(estab.id));
        }
        this.isNewEstab    = false;
        this.addingEstab   = false;
        this.establishment = estab;
        setTimeout(() => window.location.href = baseUrl + '/saas/settings', 1200);
      } else {
        this.saveError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.saveError = 'Erreur réseau.';
    } finally {
      this.saving = false;
    }
  },

  /* Enregistrement indépendant par carte (mode édition uniquement) — chaque
     carte du formulaire Général envoie juste ses propres champs en PUT
     partiel (EstablishController::update n'écrase que les clés reçues). */
  async _saveEstablishmentCard(payload, successMsg, savingFlag) {
    if (this[savingFlag]) return;
    this[savingFlag] = true;
    try {
      const res  = await fetch(baseUrl + '/api/establishments/' + this.estId(), {
        method: 'PUT', headers: this.apiHeaders(), body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (data.success) this.showToast(successMsg, 'success');
      else this.showToast(data.message ?? "Erreur lors de l'enregistrement.", 'error');
    } catch (e) {
      this.showToast('Erreur réseau.', 'error');
    } finally {
      this[savingFlag] = false;
    }
  },

  savingIdentity: false,
  saveIdentity() {
    return this._saveEstablishmentCard(
      { name: this.form.name, type: this.form.type, city: this.form.city, address: this.form.address },
      'Identité enregistrée.', 'savingIdentity'
    );
  },

  savingContact: false,
  saveContact() {
    return this._saveEstablishmentCard(
      { phone: this.form.phone, email: this.form.email, website: this.form.website },
      'Coordonnées enregistrées.', 'savingContact'
    );
  },

  savingPresentation: false,
  savePresentation() {
    return this._saveEstablishmentCard(
      { description: this.form.description },
      'Description enregistrée.', 'savingPresentation'
    );
  },

  savingHours: false,
  saveHours() {
    return this._saveEstablishmentCard(
      { check_in_time: this.form.check_in_time, check_out_time: this.form.check_out_time },
      'Horaires enregistrés.', 'savingHours'
    );
  },

  planLabel(p) { return { starter: 'Starter', pro: 'Pro', business: 'Business' }[p] ?? p; },
  planColor(p) { return { starter: 'badge-info', pro: 'badge-gold', business: 'badge-success' }[p] ?? 'badge'; },

  // ── Changement de mot de passe (onglet Compte) ─────────────────────────
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

  // Zone de danger : la suppression de compte n'est pas libre-service (cascade
  // établissements/réservations/paiements trop risquée pour un self-service) —
  // le bouton ouvre juste un email pré-rempli vers le support.
  get supportMailtoUrl() {
    let email = '';
    try { email = JSON.parse(localStorage.getItem('user') || '{}').email ?? ''; } catch (_) {}
    const subject = 'Suppression de mon compte Afristay';
    const body    = `Bonjour,\n\nJe souhaite supprimer définitivement mon compte Afristay (${email}) et toutes les données associées.\n\nMerci de me confirmer la marche à suivre.`;
    return 'mailto:support@afristay.ci?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
  },
  };
}
