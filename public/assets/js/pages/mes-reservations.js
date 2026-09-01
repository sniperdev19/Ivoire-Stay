/* ============================================================
   Afristay — Page "Mes réservations" (vitrine/mes-reservations.php)
   Pour le voyageur qui ne consulte pas ses emails : retrouve et télécharge
   ses réservations soit via l'historique local de cet appareil
   (AfristayMyBookings, alimenté par booking.js à la confirmation), soit via
   une recherche email + téléphone (POST /api/public/booking/find).
   ============================================================ */
function myBookingsPage(apiBase) {
  return {
    apiBase: (apiBase || '').replace(/\/$/, ''),

    bookings: [],
    loading: true,
    showSearchForm: false,
    searchForm: { email: '', phone: '' },
    searching: false,
    searchError: null,

    cancelConfirmId: null,
    cancelling: false,
    cancelError: null,

    async init() {
      await this.loadLocalHistory();
      this.showSearchForm = this.bookings.length === 0;
      this.loading = false;
    },

    /* Recharge le statut à jour de chaque réservation connue de cet appareil
       (le token seul est stocké en local, jamais les détails — ils peuvent
       avoir changé côté hôtel entre deux visites). Une réservation dont le
       jeton est rejeté (ex. base réinitialisée) est silencieusement retirée. */
    async loadLocalHistory() {
      const saved = window.AfristayMyBookings ? window.AfristayMyBookings.list() : [];
      if (!saved.length) return;

      const results = await Promise.all(saved.map(async (b) => {
        try {
          const res  = await fetch(`${this.apiBase}/api/public/booking/${b.id}?token=${encodeURIComponent(b.guest_token)}`);
          const data = await res.json();
          return data.success ? { ...data.data, guest_token: b.guest_token } : null;
        } catch (e) {
          return null;
        }
      }));

      this.bookings = results
        .filter(Boolean)
        .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
    },

    async search() {
      this.searchError = null;
      if (!this.searchForm.email && !this.searchForm.phone) {
        this.searchError = 'Indiquez un email ou un numéro de téléphone.';
        return;
      }
      this.searching = true;
      try {
        const res  = await fetch(this.apiBase + '/api/public/booking/find', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.searchForm),
        });
        const data = await res.json();
        if (!data.success) {
          this.searchError = data.message || 'Erreur lors de la recherche.';
          return;
        }
        const found = data.data || [];
        if (!found.length) {
          this.searchError = 'Aucune réservation trouvée avec ces informations.';
          return;
        }
        found.forEach((b) => window.AfristayMyBookings?.add(b));

        const merged = [...this.bookings];
        found.forEach((b) => {
          const idx = merged.findIndex((x) => x.id === b.id);
          if (idx >= 0) merged[idx] = b; else merged.push(b);
        });
        this.bookings = merged.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
        this.showSearchForm = false;
      } catch (e) {
        this.searchError = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
      } finally {
        this.searching = false;
      }
    },

    pdfUrl(b) {
      return `${this.apiBase}/api/public/booking/${b.id}/pdf?token=${encodeURIComponent(b.guest_token)}`;
    },

    canCancel(b) {
      return ['pending', 'confirmed'].includes(b.status);
    },

    askCancel(id) {
      this.cancelConfirmId = id;
      this.cancelError = null;
    },

    /* Le voyageur annule lui-même sa réservation (statut pending/confirmed
       uniquement — au-delà, seule l'équipe de l'établissement peut agir).
       Preuve d'identité : le guest_token déjà connu de cet appareil. */
    async confirmCancel(b) {
      this.cancelling = true;
      this.cancelError = null;
      try {
        const res = await fetch(`${this.apiBase}/api/public/booking/${b.id}/cancel`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token: b.guest_token }),
        });
        const data = await res.json();
        if (!data.success) {
          this.cancelError = data.message || "Impossible d'annuler cette réservation.";
          return;
        }
        const idx = this.bookings.findIndex((x) => x.id === b.id);
        if (idx >= 0) this.bookings[idx] = { ...this.bookings[idx], ...data.data, guest_token: b.guest_token };
        this.cancelConfirmId = null;
      } catch (e) {
        this.cancelError = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
      } finally {
        this.cancelling = false;
      }
    },

    statusLabel(status) {
      return ({
        pending:     'En attente de confirmation',
        confirmed:   'Confirmée',
        checked_in:  'En cours de séjour',
        checked_out: 'Séjour terminé',
        cancelled:   'Annulée',
      })[status] || status;
    },

    formatPrice(p) {
      return p == null ? '' : new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
    },

    formatDate(value) {
      if (!value) return '—';
      return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    },
  };
}
