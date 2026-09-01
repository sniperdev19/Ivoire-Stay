/* ============================================================
   Afristay — Mes prospects (src/templates/agent/prospects.php)
   Carte de prospection personnelle de l'agent : établissements démarchés,
   pas encore inscrits sur la plateforme, géolocalisés via navigator.geolocation
   au moment de la saisie (ils n'ont pas de fiche établissement à géolocaliser).
   ============================================================ */

const PROSPECT_STATUS_META = {
  a_contacter: { label: 'À contacter', badge: 'ag-badge-a-contacter', color: '#8A8A78' },
  contacte:    { label: 'Contacté',    badge: 'ag-badge-contacte',    color: '#2563EB' },
  interesse:   { label: 'Intéressé',   badge: 'ag-badge-interesse',   color: '#B45309' },
  inscrit:     { label: 'Inscrit',     badge: 'ag-badge-inscrit',     color: '#2D6A4F' },
  perdu:       { label: 'Perdu',       badge: 'ag-badge-perdu',       color: '#A33333' },
};

function agentProspectsPage(baseUrl) {
  return {
    agent: {},
    prospects: [],
    loaded: false,

    view: 'list', // list | map
    filterStatus: 'all',

    showModal: false,
    editingId: null,
    form: { establishment_name: '', phone: '', notes: '' },
    geo: { lat: null, lng: null, state: 'idle' }, // idle | locating | ok | error
    saving: false,
    saveError: '',

    confirmDeleteId: null,

    _map: null,
    _markers: [],

    init() {
      const token = localStorage.getItem('agent_token');
      if (!token) {
        window.location.href = baseUrl + '/agent/login';
        return;
      }
      const cached = localStorage.getItem('agent');
      if (cached) { try { this.agent = JSON.parse(cached); } catch (_) {} }
      this.load();
    },

    get initials() {
      const words = (this.agent.nom || '').trim().split(/\s+/).filter(Boolean);
      if (!words.length) return 'AS';
      return words.slice(0, 2).map(w => w[0].toUpperCase()).join('');
    },

    statusMeta(status) {
      return PROSPECT_STATUS_META[status] || PROSPECT_STATUS_META.a_contacter;
    },

    get statusList() {
      return Object.entries(PROSPECT_STATUS_META).map(([value, meta]) => ({ value, label: meta.label }));
    },

    get filteredProspects() {
      if (this.filterStatus === 'all') return this.prospects;
      return this.prospects.filter(p => p.status === this.filterStatus);
    },

    get geolocatedCount() {
      return this.prospects.filter(p => p.latitude !== null && p.longitude !== null).length;
    },

    authHeaders() {
      return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('agent_token'),
      };
    },

    async load() {
      try {
        const [meRes, prospectsRes] = await Promise.all([
          fetch(baseUrl + '/api/agent/me', { headers: this.authHeaders() }),
          fetch(baseUrl + '/api/agent/prospects', { headers: this.authHeaders() }),
        ]);
        if (meRes.status === 401 || prospectsRes.status === 401) { this.logout(); return; }

        const meData = await meRes.json();
        if (meData.success) {
          this.agent = meData.data.agent;
          localStorage.setItem('agent', JSON.stringify(this.agent));
        }

        const prospectsData = await prospectsRes.json();
        if (prospectsData.success) this.prospects = prospectsData.data;
      } catch (_) { /* réseau indisponible — on garde les données déjà chargées */ }
      this.loaded = true;
    },

    setView(view) {
      this.view = view;
      if (view === 'map') this.$nextTick(() => this.buildMap());
    },

    formatDate(v) {
      if (!v) return '';
      const d = new Date(String(v).replace(' ', 'T'));
      if (isNaN(d)) return '';
      return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
    },

    // ── Ajout / édition ──────────────────────────────────────────────
    openAddModal() {
      this.editingId = null;
      this.form = { establishment_name: '', phone: '', notes: '' };
      this.geo  = { lat: null, lng: null, state: 'idle' };
      this.saveError = '';
      this.showModal = true;
      this.locate();
    },

    openEditModal(p) {
      this.editingId = p.id;
      this.form = {
        establishment_name: p.establishment_name,
        phone: p.phone,
        notes: p.notes || '',
      };
      this.geo = {
        lat: p.latitude !== null ? Number(p.latitude) : null,
        lng: p.longitude !== null ? Number(p.longitude) : null,
        state: (p.latitude !== null && p.longitude !== null) ? 'ok' : 'idle',
      };
      this.saveError = '';
      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
    },

    locate() {
      if (!navigator.geolocation) {
        this.geo.state = 'error';
        return;
      }
      this.geo.state = 'locating';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          this.geo.lat = pos.coords.latitude;
          this.geo.lng = pos.coords.longitude;
          this.geo.state = 'ok';
        },
        () => { this.geo.state = 'error'; },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    },

    async saveProspect() {
      this.saveError = '';
      if (!this.form.establishment_name.trim()) {
        this.saveError = "Le nom de l'établissement est requis.";
        return;
      }
      if (!this.form.phone.trim()) {
        this.saveError = 'Le numéro de téléphone est requis.';
        return;
      }

      this.saving = true;
      const payload = {
        establishment_name: this.form.establishment_name.trim(),
        phone: this.form.phone.trim(),
        notes: this.form.notes.trim(),
        latitude: this.geo.state === 'ok' ? this.geo.lat : null,
        longitude: this.geo.state === 'ok' ? this.geo.lng : null,
      };

      try {
        const url    = this.editingId ? `${baseUrl}/api/agent/prospects/${this.editingId}` : `${baseUrl}/api/agent/prospects`;
        const method = this.editingId ? 'PUT' : 'POST';
        const res  = await fetch(url, { method, headers: this.authHeaders(), body: JSON.stringify(payload) });
        const data = await res.json();

        if (data.success) {
          if (this.editingId) {
            const idx = this.prospects.findIndex(p => p.id === this.editingId);
            if (idx !== -1) this.prospects[idx] = data.data;
          } else {
            this.prospects.unshift(data.data);
          }
          this.showModal = false;
        } else {
          this.saveError = data.message || 'Erreur lors de l\'enregistrement.';
        }
      } catch (_) {
        this.saveError = 'Erreur réseau.';
      } finally {
        this.saving = false;
      }
    },

    // ── Statut / suppression ─────────────────────────────────────────
    async updateStatus(p, status) {
      if (status === p.status) return;
      const previous = p.status;
      p.status = status; // optimiste
      try {
        const res  = await fetch(`${baseUrl}/api/agent/prospects/${p.id}`, {
          method: 'PUT', headers: this.authHeaders(), body: JSON.stringify({ status }),
        });
        const data = await res.json();
        if (!data.success) p.status = previous;
      } catch (_) {
        p.status = previous;
      }
    },

    askDelete(id) {
      this.confirmDeleteId = id;
    },

    async confirmDelete() {
      const id = this.confirmDeleteId;
      if (!id) return;
      try {
        const res  = await fetch(`${baseUrl}/api/agent/prospects/${id}`, { method: 'DELETE', headers: this.authHeaders() });
        const data = await res.json();
        if (data.success) this.prospects = this.prospects.filter(p => p.id !== id);
      } catch (_) { /* réseau indisponible */ }
      this.confirmDeleteId = null;
    },

    // ── Carte personnelle ─────────────────────────────────────────────
    buildMap() {
      const el = document.getElementById('agent-prospects-map');
      if (!el || typeof L === 'undefined') return;

      if (this._map) { this._map.remove(); this._map = null; }

      const ciBounds = L.latLngBounds([2.8, -9.7], [11.8, -1.6]);
      this._map = L.map(el, {
        minZoom: 6,
        maxBounds: ciBounds,
        maxBoundsViscosity: 1.0,
      }).setView([7.54, -5.55], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
      }).addTo(this._map);

      const withGeo = this.prospects.filter(p => p.latitude !== null && p.longitude !== null);
      const bounds = [];
      withGeo.forEach(p => {
        const meta = this.statusMeta(p.status);
        const tip = document.createElement('div');
        const strong = document.createElement('strong');
        strong.textContent = p.establishment_name;
        tip.append(strong, document.createElement('br'), document.createTextNode(`${p.phone} · ${meta.label}`));

        L.circleMarker([Number(p.latitude), Number(p.longitude)], {
          radius: 9, color: '#fff', weight: 2,
          fillColor: meta.color, fillOpacity: 0.9,
        })
          .addTo(this._map)
          .bindTooltip(tip, { direction: 'top' });
        bounds.push([Number(p.latitude), Number(p.longitude)]);
      });

      if (bounds.length > 1) this._map.fitBounds(bounds, { padding: [30, 30] });
      else if (bounds.length === 1) this._map.setView(bounds[0], 13);
    },

    logout() {
      fetch(baseUrl + '/api/agent/logout', { method: 'POST', headers: this.authHeaders() }).catch(() => {});
      localStorage.removeItem('agent_token');
      localStorage.removeItem('agent');
      window.location.href = baseUrl + '/agent/login';
    },
  };
}
