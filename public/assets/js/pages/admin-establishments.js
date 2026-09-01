/* ============================================================
   Afristay — Admin plateforme : Établissements (src/templates/admin/establishments.php)
   ============================================================ */

function adminEstablishmentsPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    establishments: [],
    search: '',
    savingId: null,
    toast: null,

    analyticsLoading: true,
    analytics: null,
    _map: null,

    async init() {
      await Promise.all([this.loadEstablishments(), this.loadAnalytics()]);
    },

    async loadEstablishments() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/establishments', { headers: this.apiHeaders() });
        const data = await res.json();
        this.establishments = data.success ? (data.data ?? []) : [];
      } catch(e) {
        this.establishments = [];
      } finally {
        this.loading = false;
      }
    },

    /* Occupation, CA et classement du mois en cours, agrégés côté serveur
       (AdminController::establishmentsAnalytics) — voir /api/admin/establishments-analytics. */
    async loadAnalytics() {
      this.analyticsLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/establishments-analytics', { headers: this.apiHeaders() });
        const data = await res.json();
        this.analytics = data.success ? data.data : null;
      } catch(e) {
        this.analytics = null;
      } finally {
        this.analyticsLoading = false;
        this.$nextTick(() => this.buildMap());
      }
    },

    get citiesWithGeo()    { return (this.analytics?.cities ?? []).filter(c => c.latitude !== null && c.longitude !== null); },
    get citiesWithoutGeo() { return (this.analytics?.cities ?? []).filter(c => c.latitude === null || c.longitude === null); },

    // Rampe séquentielle bleue validée (dataviz skill, references/palette.md) : une
    // seule teinte, clair→foncé, pour une magnitude continue (taux d'occupation en %).
    // Pas de dégradé vert→rouge type "feu tricolore" : cette encodage est réservé aux
    // statuts (bon/mauvais), pas à un pourcentage qui n'a rien d'intrinsèquement négatif.
    get occupancyLegendSteps() { return ['#cde2fb', '#86b6ef', '#3987e5', '#1c5cab', '#0d366b']; },
    occupancyColor(pct) {
      const steps = this.occupancyLegendSteps;
      const idx   = Math.min(steps.length - 1, Math.floor((pct ?? 0) / (100 / steps.length)));
      return steps[Math.max(0, idx)];
    },

    buildMap() {
      const el = document.getElementById('admin-occupancy-map');
      if (!el || typeof L === 'undefined') return;

      if (this._map) { this._map.remove(); this._map = null; }

      // Bornes larges autour de la C.-I. (pas de contour officiel exact — pas de fichier
      // GeoJSON dans le projet et impossible d'en télécharger un ici, CSP + pas d'accès
      // réseau externe) : on bloque plutôt le panoramique/zoom arrière pour qu'on ne
      // puisse jamais faire glisser la carte jusqu'aux pays voisins ou au reste du monde.
      const ciBounds = L.latLngBounds([2.8, -9.7], [11.8, -1.6]);

      // Toujours initialisée (vue par défaut Côte d'Ivoire), même sans établissement
      // géolocalisé — seuls les marqueurs sont conditionnels, jamais la carte elle-même.
      this._map = L.map(el, {
        minZoom: 6,
        maxBounds: ciBounds,
        maxBoundsViscosity: 1.0,
      }).setView([7.54, -5.55], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
      }).addTo(this._map);

      const cities = this.citiesWithGeo;
      const bounds = [];
      cities.forEach(c => {
        // c.city vient d'un champ libre saisi par le propriétaire (fiche établissement) —
        // jamais interpolé tel quel dans le HTML du tooltip Leaflet (bindTooltip rend du HTML).
        const tip = document.createElement('div');
        const strong = document.createElement('strong');
        strong.textContent = c.city;
        tip.append(strong, document.createElement('br'), document.createTextNode(`${c.occupancy}% d'occupation · ${c.establishments} étab.`));

        L.circleMarker([c.latitude, c.longitude], {
          radius: 9 + Math.min(12, c.establishments * 2),
          color: '#fff', weight: 2,
          fillColor: this.occupancyColor(c.occupancy), fillOpacity: 0.9,
        })
          .addTo(this._map)
          .bindTooltip(tip, { direction: 'top' });
        bounds.push([c.latitude, c.longitude]);
      });

      if (bounds.length > 1) this._map.fitBounds(bounds, { padding: [30, 30] });
      else this._map.setView(bounds[0], 12);
    },

    monthLabel(ym) {
      if (!ym) return '';
      const [y, m] = ym.split('-');
      const mois = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
      return mois[parseInt(m, 10) - 1] + ' ' + y;
    },

    get filteredEstablishments() {
      const q = (this.search || '').trim().toLowerCase();
      if (!q) return this.establishments;
      return this.establishments.filter(e =>
        `${e.name} ${e.city ?? ''} ${e.owner_name ?? ''}`.toLowerCase().includes(q)
      );
    },

    async toggleActive(estab) {
      const next  = estab.is_active ? 0 : 1;
      const label = next ? 'réactiver' : 'désactiver';
      if (!confirm(`Confirmer : ${label} « ${estab.name} » ?`)) return;

      this.savingId = estab.id;
      try {
        const res  = await fetch(baseUrl + '/api/establishments/' + estab.id, {
          method: 'PUT', headers: this.apiHeaders(), body: JSON.stringify({ is_active: next }),
        });
        const data = await res.json();
        if (data.success) {
          estab.is_active = next;
          this.showToast(next ? 'Établissement réactivé.' : 'Établissement désactivé.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch(e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.savingId = null;
      }
    },

    /** Bannit/débannit manuellement un établissement (AdminController::banEstablishment) —
        distinct du toggle Actif/Désactivé (is_active, modifiable par le owner lui-même) :
        masque de la vitrine et bloque les nouvelles réservations (PublicController,
        BookingController::store), réservé au superadmin. */
    async toggleBan(estab) {
      const banning = !estab.banned_at;
      const label   = banning ? 'bannir' : 'débannir';
      if (!confirm(`Confirmer : ${label} « ${estab.name} » ?`)) return;

      this.savingId = estab.id;
      try {
        const action = banning ? 'ban' : 'unban';
        const res  = await fetch(baseUrl + '/api/admin/establishments/' + estab.id + '/' + action, {
          method: 'POST', headers: this.apiHeaders(),
        });
        const data = await res.json();
        if (data.success) {
          estab.banned_at = banning ? new Date().toISOString() : null;
          this.showToast(banning ? 'Établissement banni.' : 'Établissement débanni.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.savingId = null;
      }
    },

    async changePlan(estab, newPlan) {
      const previous = estab.plan;
      if (newPlan === previous) return;

      this.savingId = estab.id;
      try {
        const res  = await fetch(baseUrl + '/api/establishments/' + estab.id, {
          method: 'PUT', headers: this.apiHeaders(), body: JSON.stringify({ plan: newPlan }),
        });
        const data = await res.json();
        if (data.success) {
          estab.plan = newPlan;
          this.showToast('Plan mis à jour.', 'success');
        } else {
          estab.plan = previous;
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch(e) {
        estab.plan = previous;
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.savingId = null;
      }
    },

    planLabel(p) { return { starter: 'Starter', pro: 'Pro', business: 'Business' }[p] ?? p; },
  };
}
