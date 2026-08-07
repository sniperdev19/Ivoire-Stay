/* ============================================================
   Afristay — Page réservation (vitrine/booking.php)
   Tunnel de réservation en 3 étapes (dates → infos → confirmation).
   ============================================================ */

/**
 * Composant Alpine du tunnel de réservation.
 * @param {string} apiBase  Préfixe d'URL de l'app (APP_URL).
 * @param {number|string} roomId  Identifiant de la chambre.
 * Utilisé via x-data="bookingPage('<?= ... ?>', <?= ... ?>)".
 */
function bookingPage(apiBase, roomId) {
  return {
    apiBase: (apiBase || '').replace(/\/$/, ''),

    /* Priorité à l'établissement d'origine (dispo dès que `room` est chargé) ;
       repli sur l'historique du navigateur, puis sur la recherche générique. */
    goBackToProperty() {
      if (this.room?.establishment_slug) {
        window.location.href = this.apiBase + '/property/' + this.room.establishment_slug;
      } else if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = this.apiBase + '/search';
      }
    },

    photoUrl(path) {
      if (!path) return null;
      if (path.startsWith('http')) return path;
      const clean = path.replace(/^\/+/, '');
      return this.apiBase + '/' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
    },

    /* Galerie chambre : room.photos_list est une chaîne 'a.jpg|b.jpg|c.jpg' (max 3), repli sur cover_photo */
    photoIdx: 0,
    roomPhotos() {
      if (this.room?.photos_list) return this.room.photos_list.split('|').filter(Boolean);
      return this.room?.cover_photo ? [this.room.cover_photo] : [];
    },
    touchStartX: 0,
    swipeRoomPhoto(e) {
      const len = this.roomPhotos().length;
      if (!len) return;
      const dx = e.changedTouches[0].clientX - this.touchStartX;
      if (Math.abs(dx) < 40) return;
      this.photoIdx = dx < 0 ? (this.photoIdx + 1) % len : (this.photoIdx - 1 + len) % len;
    },

    room: null,
    loading: true,
    error: null,
    step: 1,
    form: {
      room_id:    roomId,
      check_in:   '',
      check_out:  '',
      first_name:    '',
      last_name:     '',
      email:         '',
      phone:         '',
      id_doc_type:   '',
      id_doc_number: '',
      notes:         '',
      hours:         3,
    },
    errors: {},
    payMode:   'online',
    payMethod: 'orange',
    booking: null,
    submitting: false,
    bookingError: null,
    paymentVerifying: false,
    guestPushSupported: 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window,
    guestPushEnabled: false,

    /* Planning de la chambre : { 'YYYY-MM-DD': 'available' | <statut réservation> } */
    availability: {},
    availabilityLoading: false,
    calMonth: null,

    get isPassage() {
      return !!(this.form.check_in && this.form.check_out && this.form.check_in === this.form.check_out);
    },

    get nights() {
      if (!this.form.check_in || !this.form.check_out || this.isPassage) return 0;
      const d1 = new Date(this.form.check_in);
      const d2 = new Date(this.form.check_out);
      return Math.max(0, Math.round((d2 - d1) / 86400000));
    },

    /* Nombre de forfaits week-end (bloc vendredi+samedi+dimanche complet) inclus
       dans le séjour choisi — sert à prévenir le client que le tarif week-end
       s'applique avant qu'il ne s'étonne du total (bk-weekend-notice). Le tarif
       week-end est un FORFAIT pour les 3 nuits ensemble, pas un prix par nuit :
       un week-end incomplet (ex. arrivée un samedi) reste au tarif de base. */
    get weekendPackageCount() {
      return this._weekendBreakdown().packages;
    },

    get totalPrice() {
      if (this.isPassage) {
        return (this.room?.passage_price ?? 0) * (this.form.hours || 0);
      }
      return this._weekendBreakdown().total;
    },

    _weekendBreakdown() {
      if (!this.form.check_in || !this.nights || !this.room) return { total: 0, packages: 0 };
      const weekendPrice = parseFloat(this.room.weekend_price ?? 0);
      const basePrice    = parseFloat(this.room.base_price    ?? 0);
      if (!weekendPrice || weekendPrice === basePrice) {
        return { total: basePrice * this.nights, packages: 0 };
      }
      const start = new Date(this.form.check_in + 'T00:00:00');
      const dows  = [];
      for (let i = 0; i < this.nights; i++) {
        const day = new Date(start);
        day.setDate(start.getDate() + i);
        dows.push(day.getDay());
      }
      let total = 0, packages = 0;
      for (let i = 0; i < dows.length; i++) {
        if (dows[i] === 5 && dows[i + 1] === 6 && dows[i + 2] === 0) {
          total += weekendPrice;
          packages++;
          i += 2; // bloc vendredi-samedi-dimanche déjà compté
        } else {
          total += basePrice;
        }
      }
      return { total, packages };
    },

    async init() {
      const params = new URLSearchParams(window.location.search);
      this.form.check_in  = params.get('check_in')  ?? '';
      this.form.check_out = params.get('check_out') ?? '';

      // Retour depuis GeniusPay
      const paymentStatus = params.get('payment');
      const payRef        = params.get('ref');
      if (paymentStatus === 'success' && payRef) {
        await this.loadRoom();
        await this.verifyOnlinePayment(payRef);
        return;
      }
      if (paymentStatus === 'error') {
        await this.loadRoom();
        this.bookingError = 'Le paiement a échoué ou a été annulé. Vous pouvez réessayer.';
        this.step = 3;
        return;
      }

      await this.loadRoom();
      if (!this.error) {
        this.calMonth = this.startOfMonth(new Date());
        this.$watch('form.check_in',  () => this.validateDatesLive());
        this.$watch('form.check_out', () => this.validateDatesLive());
        await this.loadAvailability();
      }
    },

    /* ── Planning / disponibilités ── */

    startOfMonth(d) {
      return new Date(d.getFullYear(), d.getMonth(), 1);
    },

    todayStr() {
      const d = new Date();
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    },

    async loadAvailability() {
      this.availabilityLoading = true;
      try {
        const from = this.todayStr();
        const toDate = new Date();
        toDate.setDate(toDate.getDate() + 400);
        const to = toDate.getFullYear() + '-' + String(toDate.getMonth() + 1).padStart(2, '0') + '-' + String(toDate.getDate()).padStart(2, '0');
        const res  = await fetch(`${this.apiBase}/api/public/availability/${this.form.room_id}?from=${from}&to=${to}`);
        const data = await res.json();
        if (data.success && data.data) this.availability = data.data;
      } catch (e) {
        // silencieux : le calendrier reste vide, la vérification finale au submit reste active
      } finally {
        this.availabilityLoading = false;
      }
    },

    isBooked(dateStr) {
      const status = this.availability[dateStr];
      return !!status && status !== 'available';
    },

    /* [start, end) pour un séjour classique ; date unique pour un passage (start === end) */
    rangeHasBooked(start, end) {
      if (!start || !end) return false;
      if (start === end) return this.isBooked(start);
      const s = new Date(start + 'T00:00:00');
      const e = new Date(end + 'T00:00:00');
      for (let d = new Date(s); d < e; d.setDate(d.getDate() + 1)) {
        if (this.isBooked(d.toISOString().split('T')[0])) return true;
      }
      return false;
    },

    validateDatesLive() {
      const conflictMsg = 'Ces dates chevauchent une réservation déjà existante pour cette chambre.';
      if (this.errors.check_in === conflictMsg) this.errors.check_in = '';
      if (!this.form.check_in || !this.form.check_out) return;
      if (this.rangeHasBooked(this.form.check_in, this.form.check_out)) {
        this.errors.check_in = conflictMsg;
      }
    },

    calMonthLabel() {
      return this.calMonth ? this.calMonth.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) : '';
    },

    isCurrentCalMonth() {
      const t = this.startOfMonth(new Date());
      return this.calMonth && this.calMonth.getFullYear() === t.getFullYear() && this.calMonth.getMonth() === t.getMonth();
    },

    calPrevMonth() {
      if (this.isCurrentCalMonth()) return;
      this.calMonth = new Date(this.calMonth.getFullYear(), this.calMonth.getMonth() - 1, 1);
    },

    calNextMonth() {
      this.calMonth = new Date(this.calMonth.getFullYear(), this.calMonth.getMonth() + 1, 1);
    },

    /* Grille de 7 colonnes (lundi → dimanche), null = case vide de calage */
    calDays() {
      if (!this.calMonth) return [];
      const year  = this.calMonth.getFullYear();
      const month = this.calMonth.getMonth();
      const startOffset  = (new Date(year, month, 1).getDay() + 6) % 7;
      const daysInMonth  = new Date(year, month + 1, 0).getDate();
      const today = this.todayStr();
      const cells = [];
      for (let i = 0; i < startOffset; i++) cells.push(null);
      for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        cells.push({
          date:    dateStr,
          day,
          booked:  this.isBooked(dateStr),
          past:    dateStr < today,
          inRange: !!(this.form.check_in && this.form.check_out && dateStr > this.form.check_in && dateStr < this.form.check_out),
        });
      }
      return cells;
    },

    calSelectDate(dateStr) {
      if (this.isBooked(dateStr) || dateStr < this.todayStr()) return;

      // Pas de sélection en cours, ou sélection déjà complète → on recommence
      if (!this.form.check_in || (this.form.check_in && this.form.check_out)) {
        this.form.check_in  = dateStr;
        this.form.check_out = '';
        return;
      }

      // Une arrivée est déjà choisie, on complète le départ
      if (dateStr < this.form.check_in) {
        this.form.check_in = dateStr;
        return;
      }
      if (dateStr === this.form.check_in) {
        this.form.check_out = dateStr; // forfait passage (même jour)
        return;
      }
      if (this.rangeHasBooked(this.form.check_in, dateStr)) {
        this.errors.check_out = 'Une réservation existante se trouve dans cette période.';
        return;
      }
      this.form.check_out = dateStr;
    },

    async loadRoom() {
      this.loading = true;
      this.error = null;
      try {
        const res = await fetch(this.apiBase + '/api/public/rooms/' + this.form.room_id);
        const data = await res.json();
        if (data.success) {
          this.room = data.data?.room ?? data.data ?? null;
          this.photoIdx = 0;
          // Paiement en ligne indisponible (verrou v1 global, plan Starter, ou désactivé par l'hôte) → paiement sur place uniquement
          if (this.room?.online_payment_enabled === false) this.payMode = 'onsite';
        } else {
          this.error = data.message || 'Chambre introuvable.';
        }
      } catch (e) {
        this.error = 'Erreur de chargement.';
      } finally {
        this.loading = false;
      }
    },

    validateStep1() {
      this.errors = {};
      if (!this.form.check_in)  this.errors.check_in  = "Date d'arrivée requise";
      if (!this.form.check_out) this.errors.check_out = 'Date de départ requise';
      if (this.isPassage) {
        const h = Number(this.form.hours);
        if (!h || h < 1 || h > 23) this.errors.hours = 'Veuillez choisir un nombre d\'heures valide (1 – 23)';
      } else if (this.form.check_in && this.form.check_out && this.nights <= 0) {
        this.errors.check_out = "La date de départ doit être après la date d'arrivée";
      }
      if (!this.errors.check_in && !this.errors.check_out && this.form.check_in && this.form.check_out
          && this.rangeHasBooked(this.form.check_in, this.form.check_out)) {
        this.errors.check_in = 'Ces dates chevauchent une réservation déjà existante pour cette chambre.';
      }
      return Object.keys(this.errors).length === 0;
    },

    validateStep2() {
      this.errors = {};
      if (!this.form.first_name.trim()) this.errors.first_name = 'Prénom requis';
      if (!this.form.last_name.trim())  this.errors.last_name  = 'Nom requis';
      if (!this.form.email)             this.errors.email       = 'Email requis';
      else if (!/^\S+@\S+\.\S+$/.test(this.form.email)) this.errors.email = 'Email invalide';
      if (!this.form.phone)             this.errors.phone       = 'Téléphone requis';
      return Object.keys(this.errors).length === 0;
    },

    async nextStep() {
      if (this.step === 1) {
        if (!this.validateStep1()) return;
        if (!this.isPassage) {
          const avail = await this.checkAvailability();
          if (!avail) return;
        }
        this.step = 2;
      } else if (this.step === 2) {
        if (!this.validateStep2()) return;
        this.step = 3;
      }
    },

    /* Revérification serveur au moment du submit (l'API renvoie { 'YYYY-MM-DD': 'available' | statut }) :
       filet de sécurité en cas de réservation concurrente survenue après le chargement du planning. */
    async checkAvailability() {
      try {
        const to = this.isPassage
          ? new Date(new Date(this.form.check_in + 'T00:00:00').getTime() + 86400000).toISOString().split('T')[0]
          : this.form.check_out;
        const url  = `${this.apiBase}/api/public/availability/${this.form.room_id}?from=${this.form.check_in}&to=${to}`;
        const res  = await fetch(url);
        const data = await res.json();
        if (!data.success || !data.data) return true;
        const calendar = data.data;
        const start = new Date(this.form.check_in + 'T00:00:00');
        const end   = new Date(to + 'T00:00:00');
        for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
          const status = calendar[d.toISOString().split('T')[0]];
          if (status && status !== 'available') {
            this.errors.check_in = 'La chambre n\'est plus disponible pour ces dates. Merci de choisir d\'autres dates.';
            return false;
          }
        }
        return true;
      } catch (e) {
        return true;
      }
    },

    prevStep() {
      this.errors = {};
      if (this.step > 1) this.step -= 1;
    },

    formatPrice(p) {
      return p == null ? '' : new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
    },

    /* Téléchargement direct (pas de fetch+blob nécessaire : l'auth passe par le
       guest_token en query string, pas par un header Authorization). */
    bookingPdfUrl() {
      const id    = this.booking?.booking_id ?? this.booking?.id;
      const token = this.booking?.guest_token;
      if (!id || !token) return null;
      return `${this.apiBase}/api/public/booking/${id}/pdf?token=${encodeURIComponent(token)}`;
    },

    formatDate(value) {
      if (!value) return '';
      const date = new Date(value);
      return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    },

    async submitBooking() {
      if (this.submitting) return;
      this.bookingError = null;
      this.submitting   = true;
      try {
        // 1. Créer la réservation
        const payload = {
          ...this.form,
          booking_type: this.isPassage ? 'passage' : 'nuit',
          hours:        this.isPassage ? Number(this.form.hours) : undefined,
          first_name:   this.form.first_name,
          last_name:    this.form.last_name,
          email:        this.form.email,
          phone:        this.form.phone,
          pay_mode:     this.payMode,
        };
        const res  = await fetch(this.apiBase + '/api/public/booking', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) {
          this.bookingError = data.message || 'Impossible de créer la réservation.';
          return;
        }

        const bookingId = data.data?.booking_id ?? data.data?.id;

        // 2a. Paiement sur place → afficher la confirmation directement
        if (this.payMode === 'onsite') {
          this.booking = data.data ?? data;
          window.AfristayMyBookings?.add(this.booking);
          return;
        }

        // 2b. Paiement en ligne → initier GeniusPay
        const payRes  = await fetch(this.apiBase + '/api/public/booking-payment/initiate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ booking_id: bookingId, pay_method: this.payMethod }),
        });
        const payData = await payRes.json();
        if (payData.success && payData.data?.payment_url) {
          window.location.href = payData.data.payment_url;
        } else {
          this.bookingError = payData.message || 'Impossible d\'initier le paiement. Réessayez ou payez sur place.';
        }
      } catch (e) {
        this.bookingError = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
      } finally {
        this.submitting = false;
      }
    },

    /**
     * Abonnement push d'un voyageur sans compte : indépendant de pwa.js/AfristayPWA
     * (réservé à l'espace hôtelier authentifié) — utilise directement le
     * service worker déjà partagé (public/sw.js) et le jeton d'appareil de
     * SA réservation (booking.guest_token), jamais un user_id.
     */
    async enableGuestPushReminder() {
      if (!this.guestPushSupported || !this.booking?.booking_id || !this.booking?.guest_token) return;
      try {
        if (Notification.permission === 'denied') return;
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        const reg = await navigator.serviceWorker.register(this.apiBase + '/sw.js', { scope: this.apiBase + '/' });
        await navigator.serviceWorker.ready;

        let sub = await reg.pushManager.getSubscription();
        if (!sub) {
          const keyRes = await fetch(this.apiBase + '/api/public/push/public-key');
          const keyData = await keyRes.json();
          const publicKey = keyData?.data?.public_key;
          if (!publicKey) return;

          const padding  = '='.repeat((4 - publicKey.length % 4) % 4);
          const base64   = (publicKey + padding).replace(/-/g, '+').replace(/_/g, '/');
          const rawData  = window.atob(base64);
          const keyArray = Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));

          sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyArray });
        }

        const res = await fetch(this.apiBase + '/api/public/push/subscribe', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            booking_id: this.booking.booking_id,
            guest_token: this.booking.guest_token,
            ...sub.toJSON(),
          }),
        });
        const data = await res.json();
        this.guestPushEnabled = !!data.success;
      } catch (e) {
        // best-effort — pas de rappel possible, le voyageur garde l'email
      }
    },

    async verifyOnlinePayment(ref) {
      this.paymentVerifying = true;
      try {
        const res  = await fetch(this.apiBase + '/api/public/booking-payment/verify/' + encodeURIComponent(ref));
        const data = await res.json();
        if (data.success && data.data?.status === 'paid') {
          this.booking = { reference: ref, booking_status: 'confirmed', ...data.data };
          // Reconstituer le formulaire (page rechargée après retour de GeniusPay) pour l'écran de confirmation
          this.form.check_in  = data.data.check_in  ?? this.form.check_in;
          this.form.check_out = data.data.check_out ?? this.form.check_out;
          this.form.hours     = data.data.hours      ?? this.form.hours;
          window.AfristayMyBookings?.add(this.booking);
        } else {
          this.bookingError = 'Paiement en cours de vérification. Si vous avez été débité, contactez l\'hôtel.';
          this.step = 3;
        }
      } catch (e) {
        this.bookingError = 'Impossible de vérifier le paiement. Contactez l\'hôtel avec votre référence.';
        this.step = 3;
      } finally {
        this.paymentVerifying = false;
      }
    },
  };
}

