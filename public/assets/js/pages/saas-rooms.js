/* ============================================================
   Ivoire Stay — Page SaaS : Chambres & Tarifs (src/templates/saas/rooms.php)
   ============================================================ */

function roomsPage(baseUrl) {
  return {
  ...saasHelpers,

  activeTab: 'rooms',
  viewMode: 'grid',

  rooms: [],
  roomTypes: [],
  loadingRooms: true,
  loadingTypes: false,
  filterStatus: 'all',
  filterType: '',

  showRoomModal: false,
  editingRoom: null,
  roomForm: { number: '', floor: '', room_type_id: '', status: 'available', notes: '' },
  roomSaving: false,
  roomError: null,

  showTypeModal: false,
  editingType: null,
  typeForm: { name: '', base_price: '', weekend_price: '', passage_price: '', capacity: 2, description: '' },
  typeSaving: false,
  typeError: null,

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
    this.showRoomModal = true;
  },

  async saveRoom() {
    if (this.roomSaving) return;
    this.roomError = null;

    const number = (this.roomForm.number || '').trim();
    if (!number)                      { this.roomError = 'Le numéro de chambre est obligatoire.'; return; }
    if (!this.roomForm.room_type_id)  { this.roomError = 'Le type de chambre est obligatoire.'; return; }

    const payload = {
      establishment_id: this.estId(),
      room_type_id:     this.roomForm.room_type_id,
      number,
      floor:            this.roomForm.floor === '' ? null : Number(this.roomForm.floor),
      status:           this.roomForm.status || 'available',
      notes:            (this.roomForm.notes || '').trim() || null,
    };

    this.roomSaving = true;
    try {
      const url    = this.editingRoom
        ? this.apiUrl('/api/rooms/' + this.editingRoom.id)
        : this.apiUrl('/api/rooms');
      const method = this.editingRoom ? 'PUT' : 'POST';
      const res    = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(payload) });
      const data   = await res.json();

      if (res.ok && data.success) {
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

  emptyTypeForm() {
    return { name: '', base_price: '', weekend_price: '', passage_price: '', capacity: 2, description: '' };
  },

  openCreateType() {
    this.editingType   = null;
    this.typeForm      = this.emptyTypeForm();
    this.typeError     = null;
    this.showTypeModal = true;
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
    };
    this.typeError     = null;
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
