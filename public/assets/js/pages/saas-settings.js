/* ============================================================
   Afristay — Page SaaS : Paramètres (src/templates/saas/settings.php)
   ============================================================ */

function settingsPage(baseUrl) {
  return {
  ...saasHelpers,

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
    online_payment_control: 'Contrôle du paiement en ligne',
  },
  activeTab: 'general',
  saving: false,
  saveError: null,
  saveSuccess: false,
  isNewEstab: false,
  addingEstab: false,
  get creatingEstab() { return this.isNewEstab || this.addingEstab; },

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

  /* Le contrôle du paiement en ligne est réservé aux plans Premium (config/plans.php: online_payment_control) */
  get onlinePaymentLocked() { return planUpgradeRequired('online_payment_control'); },

  async toggleOnlinePayment() {
    if (this.onlinePaymentLocked || this.creatingEstab || this.savingPayment) return;
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
          : 'Paiement en ligne désactivé — vos clients paieront sur place.', 'success');
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
    const id = this.estId();

    // Retour depuis GeniusPay après paiement
    const urlParams = new URLSearchParams(window.location.search);

    // Lien direct vers un onglet (ex : "Upgrader →" dans la sidebar)
    const tabParam = urlParams.get('tab');
    if (['general', 'team', 'subscription', 'account'].includes(tabParam)) {
      this.activeTab = tabParam;
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
        this.showToast("Passez au plan Premium+ pour gérer plusieurs établissements.", 'error');
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

  // ── Membres d'équipe (rôle receptionist) ──────────────────────────────
  teamMembers: [],
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
        this.teamMembers = data.data ?? [];
      } else {
        this.teamMembers = [];
        this.teamError = data.message ?? 'Impossible de charger les membres.';
      }
    } catch(e) {
      this.teamMembers = [];
      this.teamError = 'Erreur réseau.';
    } finally {
      this.teamLoading = false;
    }
  },

  openAddMember() {
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
    if (!this.teamForm.name?.trim()) { this.teamFormError = 'Le nom est requis.'; return; }
    if (!this.teamEditing) {
      if (!this.teamForm.email?.trim()) { this.teamFormError = "L'email est requis."; return; }
      if (!this.teamForm.password || this.teamForm.password.length < 8) {
        this.teamFormError = 'Mot de passe requis (min. 8 caractères).'; return;
      }
    } else if (this.teamForm.password && this.teamForm.password.length < 8) {
      this.teamFormError = 'Mot de passe trop court (min. 8 caractères).'; return;
    }

    this.teamSubmitting = true; this.teamFormError = null;
    try {
      const url    = this.teamEditing ? baseUrl + '/api/team/' + this.teamEditing.id : baseUrl + '/api/team';
      const method = this.teamEditing ? 'PUT' : 'POST';
      const payload = this.teamEditing
        ? { name: this.teamForm.name, phone: this.teamForm.phone || null, ...(this.teamForm.password ? { password: this.teamForm.password } : {}) }
        : { name: this.teamForm.name, email: this.teamForm.email, phone: this.teamForm.phone || null, password: this.teamForm.password, establishment_id: this.teamForm.establishment_id || this.estId() };

      const res  = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(payload) });
      const data = await res.json();
      if (data.success) {
        this.showTeamModal = false;
        await this.loadTeam();
        this.showToast(this.teamEditing ? 'Membre mis à jour.' : 'Membre créé.', 'success');
      } else {
        this.teamFormError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.teamFormError = 'Erreur réseau.';
    } finally {
      this.teamSubmitting = false;
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

  async saveGeneral() {
    this.saving = true; this.saveError = null; this.saveSuccess = false;
    try {
      let url, method;
      if (this.creatingEstab) {
        if (!this.form.name?.trim()) { this.saveError = 'Le nom est requis.'; this.saving = false; return; }
        if (!this.form.city?.trim()) { this.saveError = 'La ville est requise.'; this.saving = false; return; }
        url    = baseUrl + '/api/establishments';
        method = 'POST';
      } else {
        url    = baseUrl + '/api/establishments/' + this.estId();
        method = 'PUT';
      }

      const res  = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(this.form) });
      const data = await res.json();

      if (data.success) {
        this.saveSuccess = true;
        setTimeout(() => this.saveSuccess = false, 3000);

        if (this.creatingEstab) {
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
        }
      } else {
        this.saveError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.saveError = 'Erreur réseau.';
    } finally {
      this.saving = false;
    }
  },

  planLabel(p) { return { starter: 'Starter', pro: 'Pro', business: 'Business' }[p] ?? p; },
  planColor(p) { return { starter: 'badge-info', pro: 'badge-gold', business: 'badge-success' }[p] ?? 'badge'; },
  };
}
