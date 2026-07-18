/* ============================================================
   Afristay — Page établissement (vitrine/property.php)
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

    /* "14:00:00" (colonne SQL TIME) → "14h00" */
    formatHour(t) {
      if (!t) return '';
      const [h, m] = t.split(':');
      return `${parseInt(h, 10)}h${m}`;
    },

    typeLabel(t) {
      return { hotel: 'Hôtel', residence: 'Résidence', villa: 'Villa', appartement: 'Appartement' }[t] ?? (t ?? '');
    },

    /* Carte Leaflet (OSM) — attribution minimale, sans la barre d'outils de l'embed OSM classique */
    initMap() {
      if (typeof L === 'undefined') return;
      const el = document.getElementById('property-map');
      if (!el || el._leaflet_id) return;
      const lat = Number(this.property?.latitude);
      const lng = Number(this.property?.longitude);
      if (!lat || !lng) return;

      const map = L.map(el, { zoomControl: false }).setView([lat, lng], 15);
      L.control.zoom({ position: 'bottomright' }).addTo(map);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19,
      }).addTo(map);
      L.marker([lat, lng]).addTo(map);
    },

    photoUrl(path) {
      if (!path) return null;
      if (path.startsWith('http')) return path;
      const clean = path.replace(/^\/+/, '');
      return this.apiBase + '/' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
    },

    /* Galerie établissement : jusqu'à 3 photos (établissement_photos), repli sur cover_photo */
    get galleryPhotos() {
      const list = Array.isArray(this.property?.photos) ? this.property.photos.map(p => p.file_path) : [];
      if (list.length) return list;
      return this.property?.cover_photo ? [this.property.cover_photo] : [];
    },

    get heroPhoto() {
      const list = this.galleryPhotos;
      return list[this.activePhoto] ?? list[0] ?? null;
    },

    /* Galerie chambre : room.photos_list est une chaîne 'a.jpg|b.jpg|c.jpg' (max 3), repli sur cover_photo */
    roomPhotos(room) {
      if (room?.photos_list) return room.photos_list.split('|').filter(Boolean);
      return room?.cover_photo ? [room.cover_photo] : [];
    },

    /* Défilement tactile (swipe) — galerie héro de l'établissement */
    heroTouchStartX: 0,
    swipeHero(e) {
      const len = this.galleryPhotos.length;
      if (!len) return;
      const dx = e.changedTouches[0].clientX - this.heroTouchStartX;
      if (Math.abs(dx) < 40) return;
      this.activePhoto = dx < 0 ? (this.activePhoto + 1) % len : (this.activePhoto - 1 + len) % len;
    },

    bookRoom(room) {
      const params = new URLSearchParams();
      if (this.checkIn)  params.set('check_in',  this.checkIn);
      if (this.checkOut) params.set('check_out', this.checkOut);
      const qs = params.toString();
      window.location.href = this.apiBase + '/booking/' + (room.slug || room.id) + (qs ? '?' + qs : '');
    },
  };
}
