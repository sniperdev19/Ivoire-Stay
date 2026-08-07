/* ============================================================
   Afristay — Page SaaS : Chambres & Tarifs (src/templates/saas/rooms.php)
   ============================================================ */

function roomsPage(baseUrl) {
  return {
  ...saasHelpers,

  activeTab: 'rooms',
  viewMode: 'grid',
  showViewMenu: false,

  rooms: [],
  roomTypes: [],
  loadingRooms: true,
  loadingTypes: false,
  filterStatus: 'all',
  filterType: '',

  showRoomModal: false,
  roomStep: 1,
  editingRoom: null,
  roomForm: { number: '', floor: '', room_type_id: '', status: 'available', notes: '' },
  roomSaving: false,
  roomError: null,
  roomFormStatusMenu: false,

  // ── Étapes du formulaire "Nouvelle chambre" ──────────────────────────
  get canProceedRoomStep1() {
    if (!(this.roomForm.number || '').trim()) return false;
    if (this.roomTypeMode === 'existing') return !!this.roomForm.room_type_id;
    return !!(this.newTypeForm.name || '').trim();
  },
  get canProceedRoomStep2() {
    if (this.roomTypeMode === 'existing') return true;
    return this.newTypeForm.base_price !== '' && this.newTypeForm.base_price !== null && Number(this.newTypeForm.base_price) >= 0;
  },

  // Création à la volée d'un nouveau type de chambre depuis le formulaire "Chambre"
  roomTypeMode: 'existing', // 'existing' | 'new'
  newTypeForm: { name: '', capacity: 2, bed_type: '', beds_count: 1, base_price: '', weekend_price: '', passage_price: '', amenities: [] },

  resetNewTypeForm() {
    this.roomTypeMode = 'existing';
    this.newTypeForm = { name: '', capacity: 2, bed_type: '', beds_count: 1, base_price: '', weekend_price: '', passage_price: '', amenities: [] };
  },

  toggleNewTypeAmenity(name) {
    const i = this.newTypeForm.amenities.indexOf(name);
    if (i === -1) this.newTypeForm.amenities.push(name);
    else this.newTypeForm.amenities.splice(i, 1);
  },

  /* icon rendu directement en SVG dans le template (rooms.php), plus en x-html */
  roomStatusOptions: [
    { value: 'available',   label: 'Disponible',  color: '#16A34A' },
    { value: 'occupied',    label: 'Occupée',     color: '#2563EB' },
    { value: 'cleaning',    label: 'Ménage',      color: '#D97706' },
    { value: 'maintenance', label: 'Maintenance', color: '#DC2626' },
    { value: 'blocked',     label: 'Bloquée',     color: '#6B7280' },
  ],

  get selectedRoomType() {
    return this.roomTypes.find(t => t.id == this.roomForm.room_type_id) || null;
  },

  formatNumber(p) { return new Intl.NumberFormat('fr-FR').format(p ?? 0); },

  roomPhotos: [],
  roomPhotoUploading: false,
  roomPhotoDeleting: null,
  roomPhotoError: null,
  pendingRoomPhotos: [],

  get roomPreviewImageUrl() {
    if (this.pendingRoomPhotos.length) return this.pendingRoomPhotos[0].previewUrl;
    const cover = this.roomPhotos.find(p => p.is_cover) || this.roomPhotos[0];
    return cover ? this.photoUrl(cover.file_path) : null;
  },

  photoUrl(path) {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    const clean = path.replace(/^\/+/, '');
    return baseUrl + '/' + (clean.startsWith('assets/') ? clean : 'assets/' + clean);
  },

  async loadRoomPhotos(roomId) {
    try {
      const res  = await fetch(this.apiUrl('/api/rooms/' + roomId), { headers: this.apiHeaders() });
      const data = await res.json();
      this.roomPhotos = data.success ? (data.data?.photos ?? []) : [];
    } catch (e) {
      this.roomPhotos = [];
    }
  },

  async addRoomPhoto(evt) {
    const file = evt.target.files?.[0];
    evt.target.value = '';
    if (!file) return;
    this.roomPhotoError = null;

    // Création : la chambre n'a pas encore d'id, on met la photo en attente localement.
    if (!this.editingRoom) {
      const totalCount = this.roomPhotos.length + this.pendingRoomPhotos.length;
      if (totalCount >= 3) return;
      this.pendingRoomPhotos.push({ file, previewUrl: URL.createObjectURL(file) });
      return;
    }

    this.roomPhotoUploading = true;
    try {
      const body = new FormData();
      body.append('photo', file);
      const res  = await fetch(this.apiUrl('/api/rooms/' + this.editingRoom.id + '/photos'), {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        body,
      });
      const data = await res.json();
      if (data.success) {
        await this.loadRoomPhotos(this.editingRoom.id);
        await this.loadRooms();
        this.showToast('Photo ajoutée.', 'success');
      } else {
        this.roomPhotoError = data.message ?? 'Erreur lors de l\'envoi de la photo.';
      }
    } catch (e) {
      this.roomPhotoError = 'Erreur réseau.';
    } finally {
      this.roomPhotoUploading = false;
    }
  },

  removePendingPhoto(index) {
    const [removed] = this.pendingRoomPhotos.splice(index, 1);
    if (removed) URL.revokeObjectURL(removed.previewUrl);
  },

  async uploadPendingRoomPhotos(roomId) {
    for (const pending of this.pendingRoomPhotos) {
      try {
        const body = new FormData();
        body.append('photo', pending.file);
        await fetch(this.apiUrl('/api/rooms/' + roomId + '/photos'), {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
          body,
        });
      } catch (e) {
        // On continue avec les photos suivantes même si l'une d'elles échoue.
      }
      URL.revokeObjectURL(pending.previewUrl);
    }
    this.pendingRoomPhotos = [];
  },

  async removeRoomPhoto(id) {
    if (!confirm('Supprimer cette photo ?')) return;
    this.roomPhotoDeleting = id;
    try {
      const res  = await fetch(this.apiUrl('/api/room-photos/' + id), {
        method: 'DELETE',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
      });
      const data = await res.json();
      if (data.success) {
        this.roomPhotos = this.roomPhotos.filter(p => p.id !== id);
        await this.loadRooms();
        this.showToast('Photo supprimée.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur de suppression.', 'error');
      }
    } catch (e) {
      this.showToast('Erreur réseau.', 'error');
    } finally {
      this.roomPhotoDeleting = null;
    }
  },

  showTypeModal: false,
  typeTab: 1,
  editingType: null,
  typeForm: { name: '', base_price: '', weekend_price: '', passage_price: '', capacity: 2, description: '', bed_type: '', beds_count: 1, amenities: [] },
  typeSaving: false,
  typeError: null,

  typeBedOptions: [
    { value: 'Lit Simple',              label: 'Lit Simple' },
    { value: 'Lit Double Standard',      label: 'Lit Double Sta…' },
    { value: 'Lit Queen Size',           label: 'Lit Queen Size' },
    { value: 'Lit King Size Impérial',   label: 'Lit King Size Im…' },
  ],
  /* Liste élargie sur la base des équipements les plus proposés par les
     hébergements en Côte d'Ivoire (climatisation/ventilateur, moustiquaire,
     groupe électrogène pour les délestages, petit-déjeuner, parking…). */
  typeAmenityOptions: [
    'Climatisation', 'Ventilateur', 'Smart TV', 'DSTV / Canal+', 'Réfrigérateur', 'Wi-Fi',
    'Bain / Douche', 'Eau chaude', 'Coffre-fort', 'Mini bar', 'Balcon',
    'Moustiquaire', 'Groupe électrogène', 'Petit-déjeuner inclus', 'Parking privé',
    'Sèche-cheveux', 'Bureau de travail', 'Prises USB',
  ],
  toggleAmenity(name) {
    const i = this.typeForm.amenities.indexOf(name);
    if (i === -1) this.typeForm.amenities.push(name);
    else this.typeForm.amenities.splice(i, 1);
  },

  // ── Onglets du formulaire "Type de chambre" ──────────────────────────
  get canProceedTypeTab1() {
    return !!(this.typeForm.name || '').trim();
  },
  get canProceedTypeTab2() {
    return this.typeForm.base_price !== '' && this.typeForm.base_price !== null && Number(this.typeForm.base_price) >= 0;
  },

  statusMenuRoom: null,

  async init() {
    await this.loadRoomTypes();
    await this.loadRooms();
  },

  apiBase: baseUrl + '',
  apiUrl(path) { return this.apiBase + path; },

  async loadRooms() {
    this.loadingRooms = true;
    try {
      const res  = await fetch(this.apiUrl('/api/rooms?establishment_id=' + this.estId()), { headers: this.apiHeaders() });
      const data = await res.json();
      this.rooms = data.success
        ? (Array.isArray(data.data) ? data.data : data.data?.rooms ?? data.data ?? [])
        : [];

      if (this.roomTypes.length) {
        this.rooms = this.rooms.map(r => {
          if (!r.room_type) {
            const t = this.roomTypes.find(t => t.id == r.room_type_id);
            if (t) return { ...r, room_type: t };
          }
          return r;
        });
      }
    } catch(e) {
      this.rooms = [];
    } finally {
      this.loadingRooms = false;
    }
  },

  async loadRoomTypes() {
    this.loadingTypes = true;
    try {
      const res  = await fetch(this.apiUrl('/api/room-types?establishment_id=' + this.estId()), { headers: this.apiHeaders() });
      const data = await res.json();
      this.roomTypes = data.success
        ? (Array.isArray(data.data) ? data.data : data.data?.room_types ?? data.data ?? [])
        : [];
    } catch(e) {
      this.roomTypes = [];
    } finally {
      this.loadingTypes = false;
    }
  },

  get filteredRooms() {
    return this.rooms.filter(r => {
      const okStatus = this.filterStatus === 'all' || r.status === this.filterStatus;
      const okType   = !this.filterType || r.room_type_id == this.filterType;
      return okStatus && okType;
    });
  },

  get roomsByFloor() {
    const floors = {};
    this.filteredRooms.forEach(r => {
      const f = r.floor || '?';
      if (!floors[f]) floors[f] = [];
      floors[f].push(r);
    });
    return Object.entries(floors).sort((a, b) => a[0].localeCompare(b[0]));
  },

  countStatus(s) { return this.rooms.filter(r => r.status === s).length; },

  statusCfg(s) {
    return {
      available:   { label: 'Disponible',  color: '#2563EB', bg: 'rgba(37,99,235,0.1)',   badge: 'badge badge-info',    dot: '#2563EB' },
      occupied:    { label: 'Occupée',     color: '#16A34A', bg: 'rgba(22,163,74,0.1)',   badge: 'badge badge-success', dot: '#16A34A' },
      maintenance: { label: 'Maintenance', color: '#D97706', bg: 'rgba(217,119,6,0.1)',   badge: 'badge badge-warning', dot: '#D97706' },
      cleaning:    { label: 'Ménage',      color: '#7C3AED', bg: 'rgba(124,58,237,0.1)',  badge: 'badge',               dot: '#7C3AED', badgeStyle: 'background:rgba(124,58,237,0.1);color:#7C3AED;' },
      blocked:     { label: 'Bloquée',     color: '#6B7280', bg: 'rgba(107,114,128,0.1)', badge: 'badge',               dot: '#6B7280', badgeStyle: 'background:rgba(0,0,0,0.06);color:#6B7280;' },
    }[s] ?? { label: s || 'Inconnu', color: '#9CA3AF', bg: 'rgba(0,0,0,0.05)', badge: 'badge', dot: '#9CA3AF' };
  },

  async quickStatus(roomId, newStatus) {
    this.statusMenuRoom = null;
    const room = this.rooms.find(r => r.id === roomId);
    if (!room) return;
    const previous = room.status;
    room.status = newStatus;
    try {
      const res  = await fetch(this.apiUrl('/api/rooms/' + roomId + '/status'), {
        method: 'POST', headers: this.apiHeaders(), body: JSON.stringify({ status: newStatus })
      });
      const data = await res.json();
      if (!data.success) {
        room.status = previous;
        this.showToast('Erreur : statut non mis à jour.', 'error');
      } else {
        this.showToast('Statut mis à jour.', 'success');
      }
    } catch(e) {
      room.status = previous;
      this.showToast('Erreur réseau.', 'error');
    }
  },

  openCreateRoom() {
    this.editingRoom = null;
    this.roomForm = { number: '', floor: '', room_type_id: '', status: 'available', notes: '' };
    this.roomError = null;
    this.roomStep = 1;
    this.roomFormStatusMenu = false;
    this.resetNewTypeForm();
    this.roomPhotos = [];
    this.roomPhotoError = null;
    this.pendingRoomPhotos.forEach(p => URL.revokeObjectURL(p.previewUrl));
    this.pendingRoomPhotos = [];
    this.showRoomModal = true;
  },

  openEditRoom(room) {
    this.editingRoom = room;
    this.roomForm = {
      number:       room.number       || '',
      floor:        room.floor        || '',
      room_type_id: room.room_type_id || '',
      status:       room.status       || 'available',
      notes:        room.notes        || '',
    };
    this.roomError = null;
    this.roomStep = 1;
    this.roomFormStatusMenu = false;
    this.resetNewTypeForm();
    this.roomPhotos = [];
    this.roomPhotoError = null;
    this.pendingRoomPhotos.forEach(p => URL.revokeObjectURL(p.previewUrl));
    this.pendingRoomPhotos = [];
    this.showRoomModal = true;
    this.loadRoomPhotos(room.id);
  },

  async saveRoom() {
    if (this.roomSaving) return;
    this.roomError = null;

    const number = (this.roomForm.number || '').trim();
    if (!number) { this.roomError = 'Le numéro de chambre est obligatoire.'; return; }

    if (this.roomTypeMode === 'new') {
      const typeName  = (this.newTypeForm.name || '').trim();
      const basePrice = Number(this.newTypeForm.base_price);
      if (!typeName)                    { this.roomError = 'Le nom du nouveau type est obligatoire.'; return; }
      if (!basePrice || basePrice <= 0) { this.roomError = 'Le tarif de base du nouveau type doit être supérieur à 0.'; return; }
    } else if (!this.roomForm.room_type_id) {
      this.roomError = 'Le type de chambre est obligatoire.';
      return;
    }

    this.roomSaving = true;
    try {
      let roomTypeId = this.roomForm.room_type_id;

      // Le type est créé en premier s'il s'agit d'un nouveau type saisi à la volée.
      if (this.roomTypeMode === 'new') {
        const typePayload = {
          establishment_id: this.estId(),
          name:          this.newTypeForm.name.trim(),
          capacity:      Number(this.newTypeForm.capacity) || 1,
          base_price:    Number(this.newTypeForm.base_price),
          weekend_price: this.newTypeForm.weekend_price === '' ? null : Number(this.newTypeForm.weekend_price),
          passage_price: this.newTypeForm.passage_price === '' ? null : Number(this.newTypeForm.passage_price),
          bed_type:      this.newTypeForm.bed_type || null,
          beds_count:    Number(this.newTypeForm.beds_count) || 1,
          amenities:     this.newTypeForm.amenities || [],
        };
        const typeRes  = await fetch(this.apiUrl('/api/room-types'), { method: 'POST', headers: this.apiHeaders(), body: JSON.stringify(typePayload) });
        const typeData = await typeRes.json();
        if (!typeRes.ok || !typeData.success) {
          this.roomError = typeData.message || 'Erreur lors de la création du type (HTTP ' + typeRes.status + ').';
          this.roomSaving = false;
          return;
        }
        roomTypeId = typeData.data?.id;
        await this.loadRoomTypes();
      }

      const payload = {
        establishment_id: this.estId(),
        room_type_id:     roomTypeId,
        number,
        floor:            this.roomForm.floor === '' ? null : Number(this.roomForm.floor),
        status:           this.roomForm.status || 'available',
        notes:            (this.roomForm.notes || '').trim() || null,
      };

      const url    = this.editingRoom
        ? this.apiUrl('/api/rooms/' + this.editingRoom.id)
        : this.apiUrl('/api/rooms');
      const method = this.editingRoom ? 'PUT' : 'POST';
      const res    = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(payload) });
      const data   = await res.json();

      if (res.ok && data.success) {
        if (!this.editingRoom && this.pendingRoomPhotos.length && data.data?.id) {
          await this.uploadPendingRoomPhotos(data.data.id);
        }
        await this.loadRooms();
        this.showRoomModal = false;
        this.showToast(this.editingRoom ? 'Chambre modifiée.' : 'Chambre créée.', 'success');
      } else if (data.upgrade_required) {
        this.roomError = data.message || 'Limite de chambres atteinte. Passez à un plan supérieur.';
      } else {
        this.roomError = data.message || 'Erreur lors de la sauvegarde (HTTP ' + res.status + ').';
      }
    } catch (e) {
      console.error('[saveRoom]', e);
      this.roomError = 'Erreur réseau : ' + (e.message || e);
    } finally {
      this.roomSaving = false;
    }
  },

  async deleteRoom(id) {
    if (!confirm('Supprimer cette chambre définitivement ?')) return;
    try {
      const res  = await fetch(this.apiUrl('/api/rooms/' + id), { method: 'DELETE', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.rooms = this.rooms.filter(r => r.id !== id);
        this.showRoomModal = false;
        this.showToast('Chambre supprimée.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur de suppression.', 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    }
  },

  openEditType(type) {
    this.editingType = type;
    this.typeForm = {
      name:          type.name          || '',
      base_price:    type.base_price    ?? '',
      weekend_price: type.weekend_price ?? '',
      passage_price: type.passage_price ?? '',
      capacity:      type.capacity      ?? 2,
      description:   type.description   || '',
      bed_type:      type.bed_type      || '',
      beds_count:    type.beds_count    ?? 1,
      amenities:     Array.isArray(type.amenities) ? [...type.amenities] : [],
    };
    this.typeError     = null;
    this.typeTab       = 1;
    this.showTypeModal = true;
  },

  async saveType() {
    if (this.typeSaving) return;
    this.typeError = null;

    const name      = (this.typeForm.name || '').trim();
    const basePrice = Number(this.typeForm.base_price);
    if (!name)                          { this.typeError = 'Le nom du type est obligatoire.'; return; }
    if (!basePrice || basePrice <= 0)   { this.typeError = 'Le prix par nuit doit être supérieur à 0.'; return; }

    const payload = {
      establishment_id: this.estId(),
      name,
      capacity:      Number(this.typeForm.capacity) || 1,
      base_price:    basePrice,
      weekend_price: this.typeForm.weekend_price === '' ? null : Number(this.typeForm.weekend_price),
      passage_price: this.typeForm.passage_price === '' ? null : Number(this.typeForm.passage_price),
      description:   (this.typeForm.description || '').trim() || null,
      bed_type:      this.typeForm.bed_type || null,
      beds_count:    Number(this.typeForm.beds_count) || 1,
      amenities:     this.typeForm.amenities || [],
    };

    this.typeSaving = true;
    try {
      const url    = this.editingType
        ? this.apiUrl('/api/room-types/' + this.editingType.id)
        : this.apiUrl('/api/room-types');
      const method = this.editingType ? 'PUT' : 'POST';
      const res    = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(payload) });
      const data   = await res.json();

      if (res.ok && data.success) {
        await this.loadRoomTypes();
        this.showTypeModal = false;
        this.showToast(this.editingType ? 'Type modifié.' : 'Type créé.', 'success');
      } else {
        this.typeError = data.message || 'Erreur lors de la sauvegarde (HTTP ' + res.status + ').';
      }
    } catch (e) {
      console.error('[saveType]', e);
      this.typeError = 'Erreur réseau : ' + (e.message || e);
    } finally {
      this.typeSaving = false;
    }
  },

  async deleteType(id) {
    if (!confirm('Supprimer ce type ? Les chambres associées garderont leur tarif actuel.')) return;
    try {
      const res  = await fetch(this.apiUrl('/api/room-types/' + id), { method: 'DELETE', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.roomTypes = this.roomTypes.filter(t => t.id !== id);
        this.showToast('Type supprimé.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur de suppression.', 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    }
  },
  };
}
