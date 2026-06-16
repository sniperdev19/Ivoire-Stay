/* ============================================================
   Ivoire Stay — Page SaaS : Chambres & Tarifs (src/templates/saas/rooms.php)
   ============================================================ */

function roomsPage(baseUrl) {
  return {
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
  roomForm: { name:'', floor:'', room_type_id:'', status:'available', notes:'' },
  roomSaving: false,
  roomError: null,

  showTypeModal: false,
  editingType: null,
  typeForm: { name:'', base_price:'', weekend_price:'', passage_price:'', capacity:2, description:'' },
  typeSaving: false,
  typeError: null,

  statusMenuRoom: null,
  toast: null,
  toastTimer: null,

  fallbackTypes: [
    { id:1, name:'Standard',             base_price:55000, weekend_price:65000, passage_price:25000, capacity:2, description:'Chambre confortable et économique.' },
    { id:2, name:'Deluxe',               base_price:95000, weekend_price:115000, passage_price:40000, capacity:2, description:'Plus d’espace et plus de services.' },
    { id:3, name:'Suite Junior',         base_price:145000, weekend_price:175000, passage_price:65000, capacity:3, description:'Idéal pour les familles et longs séjours.' },
    { id:4, name:'Suite Présidentielle', base_price:320000, weekend_price:380000, passage_price:150000, capacity:4, description:'Luxe maximal avec espace salon.' }
  ],
  fallbackRooms: [
    { id:1, name:'101', floor:'1', status:'occupied', room_type_id:1, room_type:{ name:'Standard', base_price:55000 }, notes:'' },
    { id:2, name:'102', floor:'1', status:'available', room_type_id:1, room_type:{ name:'Standard', base_price:55000 }, notes:'' },
    { id:3, name:'103', floor:'1', status:'occupied', room_type_id:2, room_type:{ name:'Deluxe', base_price:95000 }, notes:'' },
    { id:4, name:'104', floor:'1', status:'maintenance', room_type_id:2, room_type:{ name:'Deluxe', base_price:95000 }, notes:'Réparation robinet' },
    { id:5, name:'201', floor:'2', status:'available', room_type_id:3, room_type:{ name:'Suite Junior', base_price:145000 }, notes:'' },
    { id:6, name:'202', floor:'2', status:'occupied', room_type_id:3, room_type:{ name:'Suite Junior', base_price:145000 }, notes:'' },
    { id:7, name:'203', floor:'2', status:'occupied', room_type_id:2, room_type:{ name:'Deluxe', base_price:95000 }, notes:'' },
    { id:8, name:'204', floor:'2', status:'available', room_type_id:1, room_type:{ name:'Standard', base_price:55000 }, notes:'' },
    { id:9, name:'301', floor:'3', status:'occupied', room_type_id:4, room_type:{ name:'Suite Présidentielle', base_price:320000 }, notes:'' },
    { id:10, name:'302', floor:'3', status:'available', room_type_id:2, room_type:{ name:'Deluxe', base_price:95000 }, notes:'' },
    { id:11, name:'303', floor:'3', status:'occupied', room_type_id:1, room_type:{ name:'Standard', base_price:55000 }, notes:'' },
    { id:12, name:'304', floor:'3', status:'cleaning', room_type_id:3, room_type:{ name:'Suite Junior', base_price:145000 }, notes:'Ménage en cours' }
  ],

  async init() {
    await this.loadRoomTypes();   /* types d'abord */
    await this.loadRooms();       /* puis chambres (peut enrichir) */
  },

  apiHeaders() {
    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + localStorage.getItem('token')
    };
  },
  apiBase: baseUrl + '',
  apiUrl(path) { return this.apiBase + path; },
  estId() { return localStorage.getItem('establishment_id') || '1'; },

  async loadRooms() {
    this.loadingRooms = true;
    try {
      const res = await fetch(this.apiUrl('/api/rooms?establishment_id=' + this.estId()), { headers: this.apiHeaders() });
      const data = await res.json();
      this.rooms = data.success ? (Array.isArray(data.data) ? data.data : data.data?.rooms ?? data.data ?? []) : this.fallbackRooms;
      if (!this.rooms.length) this.rooms = this.fallbackRooms;
    } catch(e) {
      this.rooms = this.fallbackRooms;
    } finally {
      /* Enrichir avec room_type si absent */
      try {
        if (Array.isArray(this.rooms) && this.roomTypes && this.roomTypes.length) {
          this.rooms = this.rooms.map(r => {
            if (!r.room_type && this.roomTypes.length) {
              const t = this.roomTypes.find(t => t.id == r.room_type_id);
              if (t) r = { ...r, room_type: t };
            }
            return r;
          });
        }
      } catch (e) {
        // ignore enrichment errors
      }
      this.loadingRooms = false;
    }
  },

  async loadRoomTypes() {
    this.loadingTypes = true;
    try {
      const res = await fetch(this.apiUrl('/api/room-types?establishment_id=' + this.estId()), { headers: this.apiHeaders() });
      const data = await res.json();
      this.roomTypes = data.success ? (Array.isArray(data.data) ? data.data : data.data?.room_types ?? data.data ?? []) : this.fallbackTypes;
      if (!this.roomTypes.length) this.roomTypes = this.fallbackTypes;
    } catch(e) {
      this.roomTypes = this.fallbackTypes;
    } finally {
      this.loadingTypes = false;
    }
  },

  get filteredRooms() {
    return this.rooms.filter(r => {
      const okStatus = this.filterStatus === 'all' || r.status === this.filterStatus;
      const okType = !this.filterType || r.room_type_id == this.filterType;
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
    return Object.entries(floors).sort((a,b) => a[0].localeCompare(b[0]));
  },

  countStatus(s) {
    return this.rooms.filter(r => r.status === s).length;
  },

  statusCfg(s) {
    return {
      available:   { label:'Disponible',  color:'#2563EB', bg:'rgba(37,99,235,0.1)', badge:'badge badge-info',    dot:'#2563EB' },
      occupied:    { label:'Occupée',     color:'#16A34A', bg:'rgba(22,163,74,0.1)', badge:'badge badge-success', dot:'#16A34A' },
      maintenance: { label:'Maintenance', color:'#D97706', bg:'rgba(217,119,6,0.1)', badge:'badge badge-warning', dot:'#D97706' },
      cleaning:    { label:'Ménage',      color:'#7C3AED', bg:'rgba(124,58,237,0.1)', badge:'badge',               dot:'#7C3AED', badgeStyle:'background:rgba(124,58,237,0.1);color:#7C3AED;' },
      blocked:     { label:'Bloquée',     color:'#6B7280', bg:'rgba(107,114,128,0.1)', badge:'badge',               dot:'#6B7280', badgeStyle:'background:rgba(0,0,0,0.06);color:#6B7280;' }
    }[s] ?? { label:s || 'Inconnu', color:'#9CA3AF', bg:'rgba(0,0,0,0.05)', badge:'badge', dot:'#9CA3AF' };
  },

  async quickStatus(roomId, newStatus) {
    this.statusMenuRoom = null;
    const room = this.rooms.find(r => r.id === roomId);
    if (!room) return;
    const previous = room.status;
    room.status = newStatus;
    try {
      const res = await fetch(this.apiUrl('/api/rooms/' + roomId + '/status'), {
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
    this.roomForm = { name:'', floor:'', room_type_id:'', status:'available', notes:'' };
    this.roomError = null;
    this.showRoomModal = true;
  },

  openEditRoom(room) {
    this.editingRoom = room;
    this.roomForm = {
      name: room.name || '',
      floor: room.floor || '',
      room_type_id: room.room_type_id || '',
      status: room.status || 'available',
      notes: room.notes || ''
    };
    this.roomError = null;
    this.showRoomModal = true;
  },

  async saveRoom() {
    this.roomError = null;
    if (!this.roomForm.name || !this.roomForm.room_type_id) {
      this.roomError = 'Le numéro de chambre et le type sont obligatoires.';
      return;
    }
    this.roomSaving = true;
    try {
      const url = this.editingRoom ? this.apiUrl('/api/rooms/' + this.editingRoom.id) : this.apiUrl('/api/rooms');
      const method = this.editingRoom ? 'PUT' : 'POST';
      const res = await fetch(url, {
        method,
        headers: this.apiHeaders(),
        body: JSON.stringify({ ...this.roomForm, establishment_id: this.estId() })
      });
      const data = await res.json();
      if (data.success) {
        const type = this.roomTypes.find(t => t.id == this.roomForm.room_type_id);
        if (this.editingRoom) {
          const index = this.rooms.findIndex(r => r.id === this.editingRoom.id);
          if (index !== -1) {
            this.rooms[index] = { ...this.rooms[index], ...this.roomForm, room_type: type || this.rooms[index].room_type };
          }
        } else {
          this.rooms.push({ ...data.data, room_type: type });
        }
        this.showRoomModal = false;
        this.showToast(this.editingRoom ? 'Chambre modifiée.' : 'Chambre créée.', 'success');
      } else {
        this.roomError = data.message ?? 'Erreur lors de la sauvegarde.';
      }
    } catch(e) {
      this.roomError = 'Erreur réseau.';
    } finally {
      this.roomSaving = false;
    }
  },

  async deleteRoom(id) {
    if (!confirm('Supprimer cette chambre définitivement ?')) return;
    try {
      const res = await fetch(this.apiUrl('/api/rooms/' + id), { method:'DELETE', headers: this.apiHeaders() });
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

  openCreateType() {
    this.editingType = null;
    this.typeForm = { name:'', base_price:'', weekend_price:'', passage_price:'', capacity:2, description:'' };
    this.typeError = null;
    this.showTypeModal = true;
  },

  openEditType(type) {
    this.editingType = type;
    this.typeForm = {
      name: type.name || '',
      base_price: type.base_price ?? '',
      weekend_price: type.weekend_price ?? '',
      passage_price: type.passage_price ?? '',
      capacity: type.capacity ?? 2,
      description: type.description || ''
    };
    this.typeError = null;
    this.showTypeModal = true;
  },

  async saveType() {
    this.typeError = null;
    if (!this.typeForm.name || !this.typeForm.base_price) {
      this.typeError = 'Nom et prix de base obligatoires.';
      return;
    }
    this.typeSaving = true;
    try {
      const url = this.editingType ? this.apiUrl('/api/room-types/' + this.editingType.id) : this.apiUrl('/api/room-types');
      const method = this.editingType ? 'PUT' : 'POST';
      const res = await fetch(url, {
        method,
        headers: this.apiHeaders(),
        body: JSON.stringify({ ...this.typeForm, establishment_id: this.estId() })
      });
      const data = await res.json();
      if (data.success) {
        await this.loadRoomTypes();
        this.showTypeModal = false;
        this.showToast(this.editingType ? 'Type modifié.' : 'Type créé.', 'success');
      } else {
        this.typeError = data.message ?? 'Erreur lors de la sauvegarde.';
      }
    } catch(e) {
      this.typeError = 'Erreur réseau.';
    } finally {
      this.typeSaving = false;
    }
  },

  async deleteType(id) {
    if (!confirm('Supprimer ce type ? Les chambres associées garderont leur tarif actuel.')) return;
    try {
      const res = await fetch(this.apiUrl('/api/room-types/' + id), { method:'DELETE', headers: this.apiHeaders() });
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

  formatPrice(value) {
    return new Intl.NumberFormat('fr-FR').format(value ?? 0) + ' FCFA';
  },

  showToast(message, type = 'success') {
    this.toast = { message, type };
    clearTimeout(this.toastTimer);
    this.toastTimer = setTimeout(() => { this.toast = null; }, 3500);
  }
  };
}
