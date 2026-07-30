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
    _map: null,
    _hotelMarker: null,
    _clientMarker: null,
    _routeLine: null,
    routeLoading: false,
    routeError: null,
    routeInfo: null, // { distanceKm, durationMin } une fois l'itinéraire tracé

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
      this._map = map;
      this._hotelMarker = L.marker([lat, lng]).addTo(map);
    },

    /* Géolocalise le visiteur puis trace l'itinéraire routier (OSRM, service
       public gratuit sans clé) jusqu'à l'établissement. Déclenché uniquement
       au clic — jamais de demande de permission géoloc au chargement de la
       page. */
    showRoute() {
      if (!this._map) return;
      if (!navigator.geolocation) {
        this.routeError = "La géolocalisation n'est pas supportée par votre navigateur.";
        return;
      }
      this.routeLoading = true;
      this.routeError   = null;

      navigator.geolocation.getCurrentPosition(
        (pos) => this.drawRoute(pos.coords.latitude, pos.coords.longitude),
        (err) => {
          this.routeLoading = false;
          this.routeError = err.code === err.PERMISSION_DENIED
            ? "Vous avez refusé l'accès à votre position. Autorisez la géolocalisation puis réessayez."
            : 'Impossible de récupérer votre position. Réessayez.';
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    },

    async drawRoute(clientLat, clientLng) {
      const hotelLat = Number(this.property?.latitude);
      const hotelLng = Number(this.property?.longitude);
      try {
        const url = `https://router.project-osrm.org/route/v1/driving/${clientLng},${clientLat};${hotelLng},${hotelLat}?overview=full&geometries=geojson`;
        const res  = await fetch(url);
        const data = await res.json();
        if (data.code !== 'Ok' || !data.routes?.length) {
          throw new Error('no route');
        }
        const route  = data.routes[0];
        const coords = route.geometry.coordinates.map(([lng, lat]) => [lat, lng]);

        if (this._routeLine)   this._map.removeLayer(this._routeLine);
        if (this._clientMarker) this._map.removeLayer(this._clientMarker);

        this._clientMarker = L.marker([clientLat, clientLng]).addTo(this._map);
        this._routeLine = L.polyline(coords, { color: '#C9A84C', weight: 4 }).addTo(this._map);
        this._map.fitBounds(this._routeLine.getBounds(), { padding: [24, 24] });

        this.routeInfo = {
          distanceKm:  (route.distance / 1000).toFixed(1),
          durationMin: Math.round(route.duration / 60),
        };
      } catch (e) {
        this.routeError = "Itinéraire indisponible pour le moment. Réessayez.";
      } finally {
        this.routeLoading = false;
      }
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
