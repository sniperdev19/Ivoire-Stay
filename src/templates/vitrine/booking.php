<?php
// Template page réservation injectée dans le layout vitrine.
// Variables disponibles : $title, $room_id
?>

<script>
function bookingData() {
  return {
    room: null,
    loading: true,
    error: null,
    step: 1,
    form: {
      room_id: null,
      check_in: '',
      check_out: '',
      client_name: '',
      client_email: '',
      client_phone: ''
    },
    errors: {},
    booking: null,
    submitting: false,
    bookingError: null,
    base: '',

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
      this.base = this.$el.dataset.base;
      this.form.room_id = this.$el.dataset.roomId;
      const params = new URLSearchParams(window.location.search);
      this.form.check_in = params.get('check_in') ?? '';
      this.form.check_out = params.get('check_out') ?? '';
      await this.loadRoom();
    },

    async loadRoom() {
      this.loading = true;
      this.error = null;
      try {
        const res = await fetch(this.base + '/api/public/rooms/' + this.form.room_id);
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
      this.errors.check_out = "Date de départ requise";
    }
    if (this.form.check_in && this.form.check_out && this.nights <= 0) {
      this.errors.check_out = 'La date de départ doit être après l\'arrivée';
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
      const nameParts = (this.form.client_name ?? '').trim().split(' ');
      const firstName = nameParts[0] ?? '';
      const lastName = nameParts.slice(1).join(' ') || firstName;

      const payload = {
        room_id:      this.form.room_id,
        check_in:     this.form.check_in,
        check_out:    this.form.check_out,
        first_name:   firstName,
        last_name:    lastName,
        email:        this.form.client_email,
        phone:        this.form.client_phone,
        booking_type: 'nuit',
        guests_count: 1
      };

      const res = await fetch(this.base + '/api/public/booking', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
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
  }
}
}
</script>

<div
  x-data="bookingData()"
  x-init="init()"
  data-room-id="<?= htmlspecialchars((string) $room_id, ENT_QUOTES, 'UTF-8') ?>"
  data-base="<?= htmlspecialchars(rtrim(APP_URL, '/'), ENT_QUOTES, 'UTF-8') ?>"
  class="bk-page">

  <style>
    :root {
      --gold: #C9A84C;
      --forest: #1B4332;
      --cream: #FAF7F2;
      --mid: #2D6A4F;
    }

    @keyframes shimmer {
      0%   { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    @keyframes scaleIn {
      from { transform: scale(0.6); opacity: 0; }
      to   { transform: scale(1);   opacity: 1; }
    }
    @keyframes fadeUp {
      from { transform: translateY(16px); opacity: 0; }
      to   { transform: translateY(0);    opacity: 1; }
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .bk-page {
      min-height: 100vh;
      background: var(--cream);
      font-family: 'Inter', sans-serif;
      display: grid;
      grid-template-columns: 400px 1fr;
    }

    /* ══════════════════════════════════
       PANNEAU GAUCHE — sticky room panel
    ══════════════════════════════════ */
    .bk-panel {
      grid-column: 1;
      position: sticky;
      top: 0;
      height: 100vh;
      background: var(--forest);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .bk-panel-img {
      position: relative;
      flex: 1;
      overflow: hidden;
    }
    .bk-panel-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.8s ease;
    }
    .bk-panel-img-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        to bottom,
        rgba(8, 22, 15, 0.2) 0%,
        rgba(8, 22, 15, 0.85) 100%
      );
    }
    .bk-panel-img-badge {
      position: absolute;
      top: 24px;
      left: 24px;
      padding: 6px 14px;
      background: var(--gold);
      color: white;
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      border-radius: 4px;
    }
    .bk-panel-info {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 28px 28px 0;
    }
    .bk-panel-room-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.35em;
      color: rgba(201,168,76,0.7);
      margin-bottom: 8px;
    }
    .bk-panel-room-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: 40px;
      font-weight: 300;
      color: white;
      line-height: 1.0;
      margin-bottom: 6px;
    }
    .bk-panel-room-type {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: rgba(255,255,255,0.5);
      margin-bottom: 20px;
    }

    /* Récapitulatif dans le panneau */
    .bk-panel-summary {
      background: rgba(6,14,9,0.75);
      backdrop-filter: blur(16px);
      padding: 20px 28px;
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    .bk-summary-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 11px 0;
      border-bottom: 0.5px solid rgba(255,255,255,0.06);
    }
    .bk-summary-row:last-child { border-bottom: none; }
    .bk-summary-label {
      font-family: 'Inter', sans-serif;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.25em;
      color: rgba(255,255,255,0.38);
    }
    .bk-summary-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px;
      color: white;
      font-weight: 400;
    }
    .bk-summary-val.gold {
      color: var(--gold);
      font-size: 24px;
      font-weight: 700;
    }
    .bk-summary-total-label {
      font-family: 'Inter', sans-serif;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.25em;
      color: rgba(201,168,76,0.65);
    }
    .bk-summary-total-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 32px;
      font-weight: 700;
      color: var(--gold);
      line-height: 1;
    }

    /* Garanties */
    .bk-panel-guarantees {
      padding: 16px 28px 20px;
      background: rgba(6,14,9,0.75);
      display: flex;
      flex-direction: column;
      gap: 9px;
      border-top: 0.5px solid rgba(255,255,255,0.06);
    }
    .bk-guarantee {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: rgba(255,255,255,0.5);
    }
    .bk-guarantee-dot {
      width: 5px;
      height: 5px;
      border-radius: 50%;
      background: var(--gold);
      flex-shrink: 0;
    }

    /* Skeleton gauche */
    .bk-panel-skel {
      flex: 1;
      background: linear-gradient(90deg,
        rgba(27,67,50,0.5) 25%,
        rgba(27,67,50,0.8) 50%,
        rgba(27,67,50,0.5) 75%);
      background-size: 200% 100%;
      animation: shimmer 2s infinite;
    }

    /* ══════════════════════════════════
       PANNEAU DROIT — formulaire
    ══════════════════════════════════ */
    .bk-form-area {
      grid-column: 2;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      padding: 0;
    }

    /* STEPPER */
    .bk-stepper {
      position: sticky;
      top: 0;
      z-index: 40;
      background: rgba(250,247,242,0.97);
      backdrop-filter: blur(20px);
      border-bottom: 0.5px solid rgba(27,67,50,0.08);
      padding: 18px 64px;
      display: flex;
      align-items: center;
      gap: 0;
    }
    .bk-step {
      display: flex;
      align-items: center;
      gap: 12px;
      flex: 1;
    }
    .bk-step:last-child { flex: 0; }
    .bk-step-circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      font-weight: 700;
      flex-shrink: 0;
      transition: all 0.3s ease;
    }
    .bk-step-circle.inactive {
      background: transparent;
      border: 1px solid rgba(27,67,50,0.15);
      color: rgba(27,67,50,0.35);
    }
    .bk-step-circle.active {
      background: var(--gold);
      border: none;
      color: white;
      box-shadow: 0 4px 14px rgba(201,168,76,0.4);
    }
    .bk-step-circle.done {
      background: var(--forest);
      border: none;
      color: white;
    }
    .bk-step-circle.done svg {
      width: 16px;
      height: 16px;
      stroke: white;
      stroke-width: 2.5;
      fill: none;
    }
    .bk-step-label {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.2em;
      transition: color 0.3s;
    }
    .bk-step-label.active { color: var(--forest); font-weight: 600; }
    .bk-step-label.inactive { color: rgba(27,67,50,0.32); }
    .bk-step-connector {
      flex: 1;
      height: 1px;
      margin: 0 16px;
      transition: background 0.4s ease;
    }
    .bk-step-connector.done { background: var(--forest); }
    .bk-step-connector.pending { background: rgba(27,67,50,0.1); }

    /* CONTENU DES ÉTAPES */
    .bk-form-content {
      flex: 1;
      padding: 48px 64px 72px;
      max-width: 680px;
    }
    .bk-step-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 56px;
      font-weight: 300;
      color: var(--forest);
      line-height: 0.95;
      margin-bottom: 8px;
    }
    .bk-step-title em {
      color: var(--gold);
      font-style: italic;
    }
    .bk-step-subtitle {
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      color: rgba(27,67,50,0.48);
      margin-bottom: 40px;
    }
    .bk-step-rule {
      width: 40px;
      height: 1px;
      background: var(--gold);
      margin-bottom: 32px;
    }

    /* Champs de formulaire */
    .bk-field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
    .bk-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .bk-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.35em;
      color: var(--gold);
    }
    .bk-input {
      width: 100%;
      padding: 16px 18px;
      border: 1px solid rgba(27,67,50,0.1);
      border-radius: 14px;
      background: white;
      color: var(--forest);
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      appearance: none;
      -webkit-appearance: none;
    }
    .bk-input:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
    }
    .bk-input::placeholder { color: rgba(27,67,50,0.25); }
    .bk-input.error { border-color: #EF4444; }
    .bk-field-error {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: #EF4444;
      margin-top: 2px;
    }

    /* Bloc résumé nuits */
    .bk-nights-block {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      background: rgba(201,168,76,0.06);
      border: 0.5px solid rgba(201,168,76,0.2);
      border-radius: 16px;
      margin-bottom: 24px;
    }
    .bk-nights-left { display: flex; flex-direction: column; gap: 3px; }
    .bk-nights-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.3em;
      color: rgba(27,67,50,0.4);
    }
    .bk-nights-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 40px;
      color: var(--forest);
      font-weight: 400;
      line-height: 1;
    }
    .bk-nights-val span { font-size: 16px; color: rgba(27,67,50,0.5); font-family: 'Inter', sans-serif; }
    .bk-nights-total {
      text-align: right;
    }
    .bk-nights-total-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.3em;
      color: var(--gold);
      display: block;
      margin-bottom: 4px;
    }
    .bk-nights-total-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 36px;
      font-weight: 700;
      color: var(--forest);
      line-height: 1;
    }

    /* Bloc paiement Mobile Money */
    .bk-mm-block {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 16px 20px;
      background: rgba(27,67,50,0.04);
      border: 0.5px solid rgba(27,67,50,0.1);
      border-radius: 14px;
      margin-bottom: 24px;
    }
    .bk-mm-icon {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(201,168,76,0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: var(--gold);
    }
    .bk-mm-icon svg { width: 16px; height: 16px; }
    .bk-mm-title {
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: var(--forest);
      font-weight: 500;
      margin-bottom: 4px;
    }
    .bk-mm-methods {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: var(--gold);
      font-weight: 600;
    }

    /* Récap step 3 */
    .bk-recap-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 28px;
    }
    .bk-recap-item { display: flex; flex-direction: column; gap: 4px; }
    .bk-recap-label {
      font-family: 'Inter', sans-serif;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.3em;
      color: rgba(27,67,50,0.38);
    }
    .bk-recap-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 20px;
      color: var(--forest);
      font-weight: 400;
      line-height: 1.1;
    }
    .bk-recap-room {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px 0;
      border-bottom: 0.5px solid rgba(27,67,50,0.08);
      margin-bottom: 20px;
    }
    .bk-recap-room-img {
      width: 72px;
      height: 72px;
      border-radius: 12px;
      object-fit: cover;
      flex-shrink: 0;
    }
    .bk-recap-room-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: 24px;
      color: var(--forest);
      font-weight: 300;
      line-height: 1;
    }
    .bk-recap-total-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 20px;
      border-top: 0.5px solid rgba(27,67,50,0.08);
      margin-bottom: 28px;
    }
    .bk-recap-total-label {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px;
      color: var(--forest);
      font-weight: 300;
    }
    .bk-recap-total-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 40px;
      font-weight: 700;
      color: var(--gold);
      line-height: 1;
    }

    /* Erreur booking */
    .bk-error {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      background: rgba(239,68,68,0.06);
      border: 0.5px solid rgba(239,68,68,0.2);
      border-radius: 12px;
      color: #DC2626;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      margin-bottom: 20px;
    }
    .bk-error svg { width: 16px; height: 16px; flex-shrink: 0; }

    /* Boutons */
    .bk-btn-primary {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 17px 36px;
      background: linear-gradient(135deg, var(--gold), #A67C2E);
      color: white;
      border: none;
      border-radius: 999px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
      width: 100%;
    }
    .bk-btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(201,168,76,0.4);
    }
    .bk-btn-primary:disabled { opacity: 0.65; cursor: not-allowed; }
    .bk-btn-primary svg { width: 16px; height: 16px; }
    .bk-btn-primary .bk-spinner {
      width: 18px;
      height: 18px;
      border: 2px solid rgba(255,255,255,0.4);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      flex-shrink: 0;
    }
    .bk-btn-secondary {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 17px 28px;
      border: 1px solid rgba(27,67,50,0.18);
      color: var(--forest);
      background: transparent;
      border-radius: 999px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }
    .bk-btn-secondary:hover { border-color: var(--forest); background: rgba(27,67,50,0.04); }
    .bk-btn-secondary svg { width: 14px; height: 14px; }
    .bk-btn-row {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-top: 8px;
    }

    /* ══ SUCCÈS ══ */
    .bk-success {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 64px 64px;
      text-align: center;
      animation: fadeUp 0.5s ease;
    }
    .bk-success-icon {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: rgba(201,168,76,0.1);
      border: 1.5px solid var(--gold);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 28px;
      animation: scaleIn 0.5s cubic-bezier(0.34,1.56,0.64,1);
    }
    .bk-success-icon svg { width: 44px; height: 44px; stroke: var(--gold); stroke-width: 2; fill: none; }
    .bk-success-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 56px;
      font-weight: 300;
      color: var(--forest);
      line-height: 1.0;
      margin-bottom: 12px;
    }
    .bk-success-title em { color: var(--gold); font-style: italic; }
    .bk-success-sub {
      font-family: 'Inter', sans-serif;
      font-size: 15px;
      color: rgba(27,67,50,0.55);
      line-height: 1.8;
      max-width: 440px;
      margin: 0 auto 36px;
    }
    .bk-success-card {
      background: white;
      border-radius: 20px;
      border: 0.5px solid rgba(201,168,76,0.15);
      padding: 28px 32px;
      width: 100%;
      max-width: 440px;
      text-align: left;
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 28px;
      box-shadow: 0 8px 32px rgba(15,43,32,0.08);
    }
    .bk-success-ref {
      font-family: 'Inter', sans-serif;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.3em;
      color: var(--gold);
      margin-bottom: 4px;
    }
    .bk-success-ref-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px;
      color: var(--forest);
      font-weight: 400;
    }
    .bk-success-detail {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 10px;
      border-top: 0.5px solid rgba(27,67,50,0.07);
    }
    .bk-success-detail-label {
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      color: rgba(27,67,50,0.4);
      text-transform: uppercase;
      letter-spacing: 0.2em;
    }
    .bk-success-detail-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 18px;
      color: var(--forest);
    }
    .bk-success-detail-val.gold { color: var(--gold); font-weight: 700; font-size: 22px; }
    .bk-success-sms {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 14px 18px;
      background: rgba(201,168,76,0.06);
      border: 0.5px solid rgba(201,168,76,0.15);
      border-radius: 12px;
      max-width: 440px;
      text-align: left;
      margin-bottom: 28px;
    }
    .bk-success-sms-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--gold);
      flex-shrink: 0;
      margin-top: 5px;
    }
    .bk-success-sms p {
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(27,67,50,0.65);
      line-height: 1.7;
    }
    .bk-success-btns {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;
    }

    /* Skeleton */
    .shimmer-bg {
      background: linear-gradient(90deg,
        #f0ebe1 25%, #e5dcd0 50%, #f0ebe1 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
    }

    /* Fallback error state */
    .bk-panel-fallback {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px;
      text-align: center;
      gap: 14px;
    }
    .bk-panel-fallback svg { width: 40px; height: 40px; stroke: var(--gold); }
    .bk-panel-fallback-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 24px;
      color: white;
      font-weight: 400;
    }
    .bk-panel-fallback-text {
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      color: rgba(255,255,255,0.55);
      max-width: 260px;
    }

    /* ══ RESPONSIVE ══ */
    @media (max-width: 1100px) {
      .bk-page { grid-template-columns: 320px 1fr; }
      .bk-form-content { padding: 40px 40px 60px; }
      .bk-stepper { padding: 16px 40px; }
      .bk-step-title { font-size: 44px; }
    }
    @media (max-width: 900px) {
      .bk-page { grid-template-columns: 1fr; }
      .bk-panel { position: relative; height: 320px; }
      .bk-panel-summary { display: none; }
      .bk-panel-guarantees { display: none; }
      .bk-panel-info { padding: 20px 20px 0; }
      .bk-form-content { padding: 32px 24px 56px; }
      .bk-stepper { padding: 14px 24px; }
      .bk-step-label { display: none; }
      .bk-step-title { font-size: 36px; }
      .bk-field-row { grid-template-columns: 1fr; }
      .bk-recap-grid { grid-template-columns: 1fr; }
      .bk-success { padding: 48px 24px; }
    }
  </style>

  <div class="bk-panel" x-show="loading">
    <div class="bk-panel-skel"></div>
  </div>

  <div class="bk-panel" x-show="!loading && !error && step < 3 || !loading && !error && !booking">
    <div class="bk-panel-img">
      <img
        :src="room?.photos?.[0]?.url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800'"
        alt="Photo de la chambre"
        x-show="room">
      <div class="bk-panel-img-overlay"></div>
      <div class="bk-panel-img-badge" x-show="room" x-text="room?.type_name ?? room?.room_type?.name ?? 'Chambre'"></div>
      <div class="bk-panel-info" x-show="room">
        <div class="bk-panel-room-label">Votre séjour</div>
        <div class="bk-panel-room-name" x-text="room?.name ?? '—'"></div>
        <div class="bk-panel-room-type" x-text="room?.establishment_name ?? ''"></div>
      </div>
    </div>

    <div class="bk-panel-summary" x-show="room">
      <div class="bk-summary-row" x-show="form.check_in && form.check_out">
        <span class="bk-summary-label">Arrivée</span>
        <span class="bk-summary-val" x-text="formatDate(form.check_in)"></span>
      </div>
      <div class="bk-summary-row" x-show="form.check_in && form.check_out">
        <span class="bk-summary-label">Départ</span>
        <span class="bk-summary-val" x-text="formatDate(form.check_out)"></span>
      </div>
      <div class="bk-summary-row" x-show="nights > 0">
        <span class="bk-summary-label">Durée</span>
        <span class="bk-summary-val" x-text="nights + ' nuit' + (nights > 1 ? 's' : '')"></span>
      </div>
      <div class="bk-summary-row" x-show="room">
        <span class="bk-summary-label">Prix / nuit</span>
        <span class="bk-summary-val" x-text="formatPrice(room?.base_price)"></span>
      </div>
      <div class="bk-summary-row" x-show="nights > 0">
        <span class="bk-summary-total-label">Total séjour</span>
        <span class="bk-summary-total-val" x-text="formatPrice(totalPrice)"></span>
      </div>
    </div>

    <div class="bk-panel-guarantees" x-show="room">
      <div class="bk-guarantee">
        <div class="bk-guarantee-dot"></div>
        Annulation gratuite 24h avant
      </div>
      <div class="bk-guarantee">
        <div class="bk-guarantee-dot"></div>
        Confirmation immédiate
      </div>
      <div class="bk-guarantee">
        <div class="bk-guarantee-dot"></div>
        Paiement Mobile Money sécurisé
      </div>
    </div>
  </div>

  <div class="bk-panel" x-show="!loading && error">
    <div class="bk-panel-fallback">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <div class="bk-panel-fallback-title">Aperçu indisponible</div>
      <div class="bk-panel-fallback-text" x-text="error"></div>
    </div>
  </div>

  <div class="bk-form-area">
    <div class="bk-stepper" x-show="!booking">
      <div class="bk-step">
        <div class="bk-step-circle"
          :class="step === 1 ? 'active' : step > 1 ? 'done' : 'inactive'">
          <template x-if="step > 1">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </template>
          <template x-if="step <= 1">
            <span>1</span>
          </template>
        </div>
        <span class="bk-step-label" :class="step === 1 ? 'active' : 'inactive'">Dates</span>
      </div>
      <div class="bk-step-connector" :class="step > 1 ? 'done' : 'pending'"></div>

      <div class="bk-step">
        <div class="bk-step-circle"
          :class="step === 2 ? 'active' : step > 2 ? 'done' : 'inactive'">
          <template x-if="step > 2">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </template>
          <template x-if="step <= 2">
            <span>2</span>
          </template>
        </div>
        <span class="bk-step-label" :class="step === 2 ? 'active' : 'inactive'">Coordonnées</span>
      </div>
      <div class="bk-step-connector" :class="step > 2 ? 'done' : 'pending'"></div>

      <div class="bk-step">
        <div class="bk-step-circle"
          :class="step === 3 ? 'active' : 'inactive'">
          <span>3</span>
        </div>
        <span class="bk-step-label" :class="step === 3 ? 'active' : 'inactive'">Confirmation</span>
      </div>
    </div>

    <div class="bk-form-content" x-show="step === 1">
      <h2 class="bk-step-title">Choisissez<br>vos <em>dates.</em></h2>
      <p class="bk-step-subtitle">Sélectionnez vos dates d'arrivée et de départ</p>
      <div class="bk-step-rule"></div>

      <div class="bk-field-row">
        <div class="bk-field">
          <label class="bk-label">Date d'arrivée</label>
          <input
            type="date"
            class="bk-input"
            :class="errors.check_in ? 'error' : ''"
            x-model="form.check_in"
            :min="new Date().toISOString().split('T')[0]">
          <span class="bk-field-error" x-show="errors.check_in" x-text="errors.check_in"></span>
        </div>
        <div class="bk-field">
          <label class="bk-label">Date de départ</label>
          <input
            type="date"
            class="bk-input"
            :class="errors.check_out ? 'error' : ''"
            x-model="form.check_out"
            :min="form.check_in || new Date().toISOString().split('T')[0]">
          <span class="bk-field-error" x-show="errors.check_out" x-text="errors.check_out"></span>
        </div>
      </div>

      <div class="bk-nights-block" x-show="nights > 0">
        <div class="bk-nights-left">
          <span class="bk-nights-label">Durée du séjour</span>
          <div class="bk-nights-val">
            <span x-text="nights"></span>
            <span x-text="' nuit' + (nights > 1 ? 's' : '')"></span>
          </div>
        </div>
        <div class="bk-nights-total">
          <span class="bk-nights-total-label">Total estimé</span>
          <div class="bk-nights-total-val" x-text="formatPrice(totalPrice)"></div>
        </div>
      </div>

      <button class="bk-btn-primary" @click.prevent="nextStep()">
        Continuer
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </button>
    </div>

    <div class="bk-form-content" x-show="step === 2">
      <h2 class="bk-step-title">Vos<br><em>informations.</em></h2>
      <p class="bk-step-subtitle">Ces informations seront utilisées pour votre réservation</p>
      <div class="bk-step-rule"></div>

      <div class="bk-field">
        <label class="bk-label">Nom complet</label>
        <input
          type="text"
          class="bk-input"
          :class="errors.client_name ? 'error' : ''"
          x-model="form.client_name"
          placeholder="Jean Kouassi">
        <span class="bk-field-error" x-show="errors.client_name" x-text="errors.client_name"></span>
      </div>

      <div class="bk-field-row">
        <div class="bk-field">
          <label class="bk-label">Adresse email</label>
          <input
            type="email"
            class="bk-input"
            :class="errors.client_email ? 'error' : ''"
            x-model="form.client_email"
            placeholder="jean@exemple.ci">
          <span class="bk-field-error" x-show="errors.client_email" x-text="errors.client_email"></span>
        </div>
        <div class="bk-field">
          <label class="bk-label">Téléphone</label>
          <input
            type="tel"
            class="bk-input"
            :class="errors.client_phone ? 'error' : ''"
            x-model="form.client_phone"
            placeholder="+225 07 00 00 00 00">
          <span class="bk-field-error" x-show="errors.client_phone" x-text="errors.client_phone"></span>
        </div>
      </div>

      <div class="bk-mm-block">
        <div class="bk-mm-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <div>
          <div class="bk-mm-title">Paiement 100% sécurisé via Mobile Money</div>
          <div class="bk-mm-methods">Orange Money · MTN Money · Wave</div>
        </div>
      </div>

      <div class="bk-btn-row">
        <button class="bk-btn-secondary" @click.prevent="prevStep()">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
          </svg>
          Retour
        </button>
        <button class="bk-btn-primary" style="flex:1;" @click.prevent="nextStep()">
          Vérifier ma réservation
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="bk-form-content" x-show="step === 3 && !booking">
      <h2 class="bk-step-title">Votre<br><em>récapitulatif.</em></h2>
      <p class="bk-step-subtitle">Vérifiez les informations avant de confirmer</p>
      <div class="bk-step-rule"></div>

      <div class="bk-recap-room">
        <img
          class="bk-recap-room-img"
          :src="room?.photos?.[0]?.url ?? 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400'"
          alt="Chambre"
          @error="$event.target.src='https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400'">
        <div>
          <div class="bk-recap-room-name" x-text="room?.name ?? '—'"></div>
          <div style="font-family:'Inter',sans-serif;font-size:12px;color:rgba(27,67,50,0.4);margin-top:4px;"
            x-text="room?.establishment_name ?? ''">
          </div>
        </div>
      </div>

      <div class="bk-recap-grid">
        <div class="bk-recap-item">
          <span class="bk-recap-label">Arrivée</span>
          <span class="bk-recap-val" x-text="formatDate(form.check_in)"></span>
        </div>
        <div class="bk-recap-item">
          <span class="bk-recap-label">Départ</span>
          <span class="bk-recap-val" x-text="formatDate(form.check_out)"></span>
        </div>
        <div class="bk-recap-item">
          <span class="bk-recap-label">Durée</span>
          <span class="bk-recap-val" x-text="nights + ' nuit' + (nights > 1 ? 's' : '')"></span>
        </div>
        <div class="bk-recap-item">
          <span class="bk-recap-label">Client</span>
          <span class="bk-recap-val" x-text="form.client_name"></span>
        </div>
        <div class="bk-recap-item">
          <span class="bk-recap-label">Email</span>
          <span class="bk-recap-val" style="font-size:15px;" x-text="form.client_email"></span>
        </div>
        <div class="bk-recap-item">
          <span class="bk-recap-label">Téléphone</span>
          <span class="bk-recap-val" x-text="form.client_phone"></span>
        </div>
      </div>

      <div class="bk-recap-total-row">
        <span class="bk-recap-total-label">Total à payer</span>
        <span class="bk-recap-total-val" x-text="formatPrice(totalPrice)"></span>
      </div>

      <div class="bk-error" x-show="bookingError">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="bookingError"></span>
      </div>

      <div class="bk-btn-row">
        <button class="bk-btn-secondary" @click.prevent="step = 2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
          </svg>
          Modifier
        </button>
        <button
          class="bk-btn-primary"
          style="flex:1;"
          @click.prevent="submitBooking()"
          :disabled="submitting">
          <div class="bk-spinner" x-show="submitting"></div>
          <span x-show="!submitting">Confirmer et réserver</span>
          <span x-show="submitting">Traitement en cours…</span>
          <svg x-show="!submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="bk-success" x-show="step === 3 && booking">
      <div class="bk-success-icon">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
      </div>

      <h2 class="bk-success-title">Réservation<br><em>confirmée !</em></h2>
      <p class="bk-success-sub">
        Votre séjour a été enregistré avec succès.
        Un email de confirmation vous sera envoyé sous peu.
      </p>

      <div class="bk-success-card">
        <div>
          <div class="bk-success-ref">Numéro de réservation</div>
          <div class="bk-success-ref-val" x-text="'#' + (booking?.id ?? booking?.booking_id ?? '—')"></div>
        </div>
        <div class="bk-success-detail">
          <span class="bk-success-detail-label">Chambre</span>
          <span class="bk-success-detail-val" x-text="room?.name ?? '—'"></span>
        </div>
        <div class="bk-success-detail">
          <span class="bk-success-detail-label">Période</span>
          <span class="bk-success-detail-val" x-text="formatDate(form.check_in) + ' – ' + formatDate(form.check_out)"></span>
        </div>
        <div class="bk-success-detail">
          <span class="bk-success-detail-label">Client</span>
          <span class="bk-success-detail-val" x-text="form.client_name"></span>
        </div>
        <div class="bk-success-detail">
          <span class="bk-success-detail-label">Montant total</span>
          <span class="bk-success-detail-val gold" x-text="formatPrice(totalPrice)"></span>
        </div>
      </div>

      <div class="bk-success-sms">
        <div class="bk-success-sms-dot"></div>
        <p>
          Vous recevrez un SMS sur le <strong x-text="form.client_phone"></strong>
          pour finaliser le paiement via Mobile Money.
        </p>
      </div>

      <div class="bk-success-btns">
        <a :href="base + '/'" class="bk-btn-primary" style="text-decoration:none;width:auto;padding:15px 32px;">
          Retour à l'accueil
        </a>
        <a :href="base + '/search'" class="bk-btn-secondary" style="text-decoration:none;">
          Voir d'autres établissements
        </a>
      </div>
    </div>
  </div>
</div>
