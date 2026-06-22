/* ============================================================
   Ivoire Stay — Page réservation (vitrine/booking.php)
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
    room: null,
    loading: true,
    error: null,
    step: 1,
    form: {
      room_id:    roomId,
      check_in:   '',
      check_out:  '',
      first_name: '',
      last_name:  '',
      email:      '',
      phone:      '',
      notes:      '',
      hours:      3,
    },
    errors: {},
    payMode:   'online',
    payMethod: 'orange',
    booking: null,
    submitting: false,
    bookingError: null,
    paymentVerifying: false,

    get isPassage() {
      return !!(this.form.check_in && this.form.check_out && this.form.check_in === this.form.check_out);
    },

    get nights() {
      if (!this.form.check_in || !this.form.check_out || this.isPassage) return 0;
      const d1 = new Date(this.form.check_in);
      const d2 = new Date(this.form.check_out);
      return Math.max(0, Math.round((d2 - d1) / 86400000));
    },

    get totalPrice() {
      if (this.isPassage) {
        return (this.room?.passage_price ?? 0) * (this.form.hours || 0);
      }
      return (this.room?.base_price ?? 0) * this.nights;
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
    },

    async loadRoom() {
      this.loading = true;
      this.error = null;
      try {
        const res = await fetch(this.apiBase + '/api/public/rooms/' + this.form.room_id);
        const data = await res.json();
        if (data.success) {
          this.room = data.data?.room ?? data.data ?? null;
        } else {
          this.error = 'Chambre introuvable.';
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

    nextStep() {
      if (this.step === 1) {
        if (!this.validateStep1()) return;
        this.step = 2;
      } else if (this.step === 2) {
        if (!this.validateStep2()) return;
        this.step = 3;
      }
    },

    prevStep() {
      this.errors = {};
      if (this.step > 1) this.step -= 1;
    },

    formatPrice(p) {
      return p == null ? '' : new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
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

    async verifyOnlinePayment(ref) {
      this.paymentVerifying = true;
      try {
        const res  = await fetch(this.apiBase + '/api/public/booking-payment/verify/' + encodeURIComponent(ref));
        const data = await res.json();
        if (data.success && data.data?.status === 'paid') {
          this.booking = { reference: ref, booking_status: 'confirmed', ...data.data };
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

