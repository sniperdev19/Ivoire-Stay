/* ============================================================
   Ivoire Stay — Page établissement (vitrine/property.php)
   Chargement de l'établissement, galerie photos, réservation.
   ============================================================ */

/**
 * Composant Alpine de la page établissement.
 * @param {string} apiBase     Préfixe d'URL de l'app (APP_URL).
 * @param {number|string} propertyId  Identifiant de l'établissement.
 * Utilisé via x-data="propertyPage('<?= ... ?>', <?= ... ?>)".
 */
function propertyPage(apiBase, propertyId) {
  return {
    apiBase: (apiBase || '').replace(/\/$/, ''),
    propertyId: propertyId,
    property: null,
    rooms: [],
    availability: {},
    loading: true,
    error: null,
    activePhoto: 0,
    lightboxOpen: false,
    selectedRoom: null,
    checkIn: '',
    checkOut: '',

    async init() {
      if (!this.propertyId) {
        this.error = 'Établissement introuvable.';
        this.loading = false;
        return;
      }
      await this.loadProperty(this.propertyId);
    },

    async loadProperty(id) {
      this.loading = true;
      this.error = null;
      try {
        const res = await fetch(this.apiBase + '/api/public/property/' + id);
        const data = await res.json();
        if (data.success) {
          this.property = data.data.establishment ?? data.data ?? null;
          this.rooms = Array.isArray(data.data.rooms) ? data.data.rooms : [];
          this.activePhoto = 0;
          this.rooms.forEach(room => { if (room?.id) this.loadAvailability(room.id); });
        } else {
          this.error = 'Établissement introuvable.';
        }
      } catch (e) {
        this.error = 'Erreur de chargement.';
      } finally {
        this.loading = false;
      }
    },

    async loadAvailability(roomId) {
      if (!roomId) return;
      try {
        const res = await fetch(this.apiBase + '/api/public/availability/' + roomId);
        const data = await res.json();
        this.availability = { ...this.availability, [roomId]: data.data ?? {} };
      } catch (e) {
        // Pas de message d'erreur utilisateur pour les disponibilités
      }
    },

    get minPrice() {
      const prices = this.rooms.map(r => r.base_price).filter(p => p != null && p > 0);
      return prices.length ? Math.min(...prices) : null;
    },

    formatPrice(p) {
      return p == null ? '—' : new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
    },

    typeLabel(t) {
      return { hotel: 'Hôtel', residence: 'Résidence', villa: 'Villa', appartement: 'Appartement' }[t] ?? (t ?? '');
    },

    photoUrl(path) {
      if (!path) return null;
      if (path.startsWith('http')) return path;
      return this.apiBase + '/' + path.replace(/^\/+/, '');
    },

    bookRoom(room) {
      const params = new URLSearchParams();
      if (this.checkIn)  params.set('check_in',  this.checkIn);
      if (this.checkOut) params.set('check_out', this.checkOut);
      const qs = params.toString();
      window.location.href = this.apiBase + '/booking/' + room.id + (qs ? '?' + qs : '');
    },
  };
}
