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
      room_id: roomId,
      check_in: '',
      check_out: '',
      client_name: '',
      client_email: '',
      client_phone: '',
    },
    errors: {},
    booking: null,
    submitting: false,
    bookingError: null,

    get nights() {
      if (!this.form.check_in || !this.form.check_out) return 0;
      const d1 = new Date(this.form.check_in);
      const d2 = new Date(this.form.check_out);
      return Math.max(0, Math.round((d2 - d1) / 86400000));
    },

    get totalPrice() {
      return (this.room?.base_price ?? 0) * this.nights;
    },

    async init() {
      const params = new URLSearchParams(window.location.search);
      this.form.check_in = params.get('check_in') ?? '';
      this.form.check_out = params.get('check_out') ?? '';
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
      if (!this.form.check_in) {
        this.errors.check_in = "Date d'arrivée requise";
      }
      if (!this.form.check_out) {
        this.errors.check_out = 'Date de départ requise';
      }
      if (this.form.check_in && this.form.check_out && this.nights <= 0) {
        this.errors.check_out = "La date de départ doit être après l'arrivée";
      }
      return Object.keys(this.errors).length === 0;
    },

    validateStep2() {
      this.errors = {};
      if (!this.form.client_name) {
        this.errors.client_name = 'Nom complet requis';
      }
      if (!this.form.client_email) {
        this.errors.client_email = 'Email requis';
      } else if (!/^\S+@\S+\.\S+$/.test(this.form.client_email)) {
        this.errors.client_email = 'Email invalide';
      }
      if (!this.form.client_phone) {
        this.errors.client_phone = 'Téléphone requis';
      }
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
      this.submitting = true;
      try {
        const res = await fetch(this.apiBase + '/api/public/booking', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.booking = data.data ?? data;
        } else {
          this.bookingError = data.message || 'Impossible de finaliser la réservation.';
        }
      } catch (e) {
        this.bookingError = 'Erreur lors de la réservation.';
      } finally {
        this.submitting = false;
      }
    },
  };
}
