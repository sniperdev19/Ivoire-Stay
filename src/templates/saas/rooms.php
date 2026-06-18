<?php
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<style>/* Onglets principaux */
.room-tab {
  padding: 9px 20px; border-radius: 10px;
  font-size: 14px; font-weight: 500;
  cursor: pointer; border: none;
  background: transparent; color: #6B7280;
  transition: all 0.2s;
}
.room-tab:hover { background: rgba(0,0,0,0.05); }
.room-tab.active {
  background: white; color: #111827;
  box-shadow: 0 1px 6px rgba(0,0,0,0.1);
}

/* Card chambre (vue grille) */
.room-card {
  border-radius: 14px; border: 2px solid transparent;
  background: white; padding: 14px;
  cursor: pointer; transition: all 0.2s;
  position: relative; overflow: visible;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.room-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.room-card.status-available   { border-color: rgba(37,99,235,0.25); }
.room-card.status-occupied    { border-color: rgba(22,163,74,0.25); }
.room-card.status-maintenance { border-color: rgba(217,119,6,0.25); }
.room-card.status-cleaning    { border-color: rgba(124,58,237,0.25); }
.room-card.status-blocked     { border-color: rgba(107,114,128,0.2); opacity: 0.7; }

/* Pastille statut */
.status-dot {
  width: 10px; height: 10px; border-radius: 50%;
  display: inline-block; flex-shrink: 0;
}

/* Menu rapide statut */
.status-menu {
  position: absolute; top: calc(100% + 6px); right: 0;
  background: white; border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.14);
  border: 1px solid rgba(0,0,0,0.07);
  min-width: 170px; z-index: 50; overflow: hidden;
}
.status-menu-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; font-size: 13px; font-weight: 500;
  cursor: pointer; transition: background 0.15s;
  border: none; background: transparent; width: 100%;
  text-align: left;
}
.status-menu-item:hover { background: #F9FAFB; }

/* Filtre tags */
.filter-pill {
  padding: 5px 12px; border-radius: 50px;
  font-size: 12px; font-weight: 500; cursor: pointer;
  border: 1px solid rgba(0,0,0,0.1);
  background: white; color: #6B7280; transition: all 0.2s;
}
.filter-pill.active {
  background: #C9A84C; color: white; border-color: #C9A84C;
}

/* Type card */
.type-card {
  background: white; border-radius: 14px;
  border: 1px solid rgba(0,0,0,0.06);
  box-shadow: 0 1px 6px rgba(0,0,0,0.04);
  padding: 20px; transition: all 0.2s;
}
.type-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); }

@keyframes spin { to { transform: rotate(360deg); } }

.room-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0,1fr));
  gap: 12px;
}
@media(max-width:1280px){ .room-grid { grid-template-columns: repeat(3, minmax(0,1fr)); } }
@media(max-width:900px){ .room-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
@media(max-width:580px){ .room-grid { grid-template-columns: 1fr; } }

.room-info { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; }
.room-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin:14px 0; }
@media(max-width:900px){ .room-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:640px){ .room-stats { grid-template-columns:1fr; } }

.room-table { width:100%; border-collapse:collapse; }
.room-table th, .room-table td { padding:14px 16px; border-bottom:1px solid rgba(0,0,0,0.06); }
.room-table th { text-align:left; color:#4B5563; font-size:13px; }
.room-table tbody tr:hover { background:rgba(201,168,76,0.04); }

.saas-toast {
  position:fixed; bottom:24px; right:24px; z-index:80;
}
.toast-box {
  min-width:260px; background:white; border:1px solid rgba(0,0,0,0.08);
  border-left:4px solid var(--saas-gold); border-radius:14px;
  box-shadow:0 16px 40px rgba(15,23,42,0.14);
  padding:16px 18px; color:#111827;
}
.toast-box.error { border-left-color:#DC2626; }

.saas-modal-bg { position:fixed; inset:0; background:rgba(15,23,42,0.35); display:grid; place-items:center; z-index:70; padding:20px; }
.saas-modal { width:100%; max-width:720px; background:white; border-radius:24px; overflow:hidden; box-shadow:0 24px 60px rgba(15,23,42,0.18); }
.saas-modal-header, .saas-modal-footer { padding:20px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.saas-modal-body { padding:0 24px 24px; }
.saas-modal-header h2 { margin:0; font-size:18px; font-weight:700; }
.saas-modal-body label { display:block; margin-bottom:8px; font-size:13px; color:#4B5563; }
.saas-modal-body .saas-input, .saas-modal-body textarea, .saas-modal-body select { width:100%; }
.saas-modal-footer { border-top:1px solid rgba(0,0,0,0.06); }

.status-pill { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:9999px; font-size:13px; font-weight:600; }
</style>

<div x-data="{
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

  pendingPhotos: [],
  photoUploading: false,
  newRoomId: null,

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
  /* fallbackRooms: [
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
  ], */

  async init() {
    await this.loadRoomTypes();   /* types d'abord */
    await this.loadRooms();       /* puis chambres (peut enrichir) */
  },

  apiHeaders() {
    const token = localStorage.getItem('token') ?? '';
    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    };
  },
  apiBase: '<?= rtrim($base_url, '/') ?>',
  apiUrl(path) { return this.apiBase + path; },
  estId() {
    let id = localStorage.getItem('establishment_id');
    if (id && id !== 'null' && id !== 'undefined') return id;
    try {
      const list = JSON.parse(localStorage.getItem('establishments') || '[]');
      if (Array.isArray(list) && list.length > 0) {
        id = list[0].id ?? list[0].establishment_id;
        if (id) { localStorage.setItem('establishment_id', String(id)); return String(id); }
      }
    } catch (e) {}
    try {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      if (user.establishment_id) { localStorage.setItem('establishment_id', String(user.establishment_id)); return String(user.establishment_id); }
    } catch (e) {}
    return '1';
  },

  async loadRooms() {
    this.loadingRooms = true;
    try {
      const res = await fetch(this.apiUrl('/api/rooms?establishment_id=' + this.estId()), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.rooms = Array.isArray(data.data) ? data.data : data.data?.rooms ?? data.data ?? [];
        // Ne PAS activer le fallback si l'API répond avec succès
      } else {
        console.warn('API error:', data.message);
        // Laisser le tableau vide — ne pas charger le fallback
        this.rooms = [];
      }
    } catch(e) {
      this.rooms = [];
      console.error('Network error:', e);
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
    this.pendingPhotos = [];
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
    this.pendingPhotos = [];
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
      // Construire explicitement le payload en mappant `name` -> `number`
      const payload = {
        establishment_id: this.estId(),
        room_type_id: this.roomForm.room_type_id,
        number: this.roomForm.name,
        floor: this.roomForm.floor,
        status: this.roomForm.status,
        notes: this.roomForm.notes
      };
      const res = await fetch(url, {
        method,
        headers: this.apiHeaders(),
        body: JSON.stringify(payload)
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
        const createdId = data.data?.id ?? data.data?.room?.id;
        if (createdId && this.pendingPhotos.length) {
          await this.uploadPendingPhotos(createdId);
        }
      } else {
        this.roomError = data.message ?? 'Erreur lors de la sauvegarde.';
      }
    } catch(e) {
      this.roomError = 'Erreur réseau.';
    } finally {
      this.roomSaving = false;
    }
  },

  async uploadPendingPhotos(roomId) {
    if (!this.pendingPhotos.length) return;
    this.photoUploading = true;
    for (const file of this.pendingPhotos) {
      try {
        const fd = new FormData();
        fd.append('photo', file);
        fd.append('is_cover', this.pendingPhotos.indexOf(file) === 0 ? '1' : '0');
        await fetch(this.apiUrl('/api/rooms/' + roomId + '/photos'), {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') ?? '') },
          body: fd
        });
      } catch(e) { console.error('Upload photo error:', e); }
    }
    this.pendingPhotos = [];
    this.photoUploading = false;
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
}"
 x-init="init()"
 @click="statusMenuRoom = null">

  <!-- En-tête et actions -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h1 style="font-size:26px;font-weight:700;margin:0;color:#111827;">Chambres & Tarifs</h1>
      <p style="margin:10px 0 0;color:#6B7280;font-size:14px;">Gestion centralisée de vos chambres et de vos types de tarifs.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <button type="button" class="btn-saas-secondary" @click="openCreateType()">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Ajouter un type
      </button>
      <button type="button" class="btn-saas-primary" @click="openCreateRoom()">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Ajouter une chambre
      </button>
    </div>
  </div>

  <!-- Onglets principaux -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
    <button type="button" class="room-tab" :class="{ 'active': activeTab === 'rooms' }" @click="activeTab = 'rooms'">Chambres</button>
    <button type="button" class="room-tab" :class="{ 'active': activeTab === 'types' }" @click="activeTab = 'types'">Types & Tarifs</button>
  </div>

  <!-- Statistiques rapides -->
  <div class="room-stats">
    <div class="kpi-card">
      <div style="font-size:13px;color:#6B7280;margin-bottom:8px;">Total</div>
      <div style="font-size:20px;font-weight:800;color:#111827;" x-text="rooms.length"></div>
    </div>
    <div class="kpi-card">
      <div style="font-size:13px;color:#6B7280;margin-bottom:8px;">Disponibles</div>
      <div style="font-size:20px;font-weight:800;color:#2563EB;" x-text="countStatus('available')"></div>
    </div>
    <div class="kpi-card">
      <div style="font-size:13px;color:#6B7280;margin-bottom:8px;">Occupées</div>
      <div style="font-size:20px;font-weight:800;color:#16A34A;" x-text="countStatus('occupied')"></div>
    </div>
    <div class="kpi-card">
      <div style="font-size:13px;color:#6B7280;margin-bottom:8px;">Maint. / Ménage</div>
      <div style="font-size:20px;font-weight:800;color:#D97706;" x-text="countStatus('maintenance') + countStatus('cleaning')"></div>
    </div>
  </div>

  <!-- Filters et vue -->
  <div class="saas-card" style="padding:10px;margin-bottom:16px;">
    <div class="room-info">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <span class="filter-pill" :class="{ 'active': filterStatus === 'all' }" @click="filterStatus='all'">Toutes</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'available' }" @click="filterStatus='available'">Disponibles</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'occupied' }" @click="filterStatus='occupied'">Occupées</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'maintenance' }" @click="filterStatus='maintenance'">Maintenance</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'cleaning' }" @click="filterStatus='cleaning'">Ménage</span>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select class="saas-input" style="min-width:220px;" x-model="filterType">
          <option value="">Tous les types</option>
          <template x-for="type in roomTypes" :key="type.id">
            <option :value="type.id" x-text="type.name"></option>
          </template>
        </select>
        <button type="button" class="btn-saas-secondary" @click="filterStatus='all'; filterType='';">Effacer les filtres</button>
        <div style="display:flex;gap:6px;">
          <button type="button" class="btn-saas-secondary" :class="viewMode==='grid' ? 'active' : ''" @click="viewMode='grid'" style="padding:9px 12px;">Grille</button>
          <button type="button" class="btn-saas-secondary" :class="viewMode==='list' ? 'active' : ''" @click="viewMode='list'" style="padding:9px 12px;">Liste</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Onglet Chambres -->
  <div x-show="activeTab === 'rooms'">
    <div x-show="loadingRooms" style="display:grid;gap:16px;">
      <div class="saas-card" style="height:120px;">Chargement des chambres…</div>
      <div class="saas-card" style="height:120px;">Chargement des chambres…</div>
    </div>

    <div x-show="!loadingRooms">
      <template x-if="filteredRooms.length === 0">
        <div class="saas-card" style="text-align:center;">
          <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:8px;">Aucune chambre</div>
          <div style="color:#6B7280;font-size:14px;">Aucune chambre ne correspond à vos filtres.</div>
          <button type="button" class="btn-saas-primary" style="margin-top:18px;" @click="filterStatus='all'; filterType='';">Effacer les filtres</button>
        </div>
      </template>

      <template x-if="filteredRooms.length > 0">
        <div>
          <div x-show="viewMode === 'grid'" class="room-grid">
            <template x-for="room in roomsByFloor.flatMap(([floor, rooms]) => rooms)" :key="room.id">
              <div class="room-card" :class="'status-' + room.status" @click="openEditRoom(room)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                  <div>
                    <div style="font-size:15px;font-weight:700;color:#111827;" x-text="room.name"></div>
                    <div style="font-size:12px;color:#6B7280;" x-text="room.room_type?.name || '—'"></div>
                  </div>
                  <div style="text-align:right;">
                    <div style="font-size:13px;color:#6B7280;">Étage <strong style="color:#111827;" x-text="room.floor || '-'"></strong></div>
                    <div style="font-size:13px;font-weight:700;color:#C9A84C;" x-text="formatPrice(room.room_type?.base_price)"></div>
                  </div>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <span class="status-dot" :style="{ background: statusCfg(room.status).dot }"></span>
                    <span style="font-size:13px;color:#4B5563;" x-text="statusCfg(room.status).label"></span>
                  </div>
                  <div style="display:flex;gap:6px;">
                    <button type="button" class="btn-saas-secondary" @click.stop="statusMenuRoom = room.id" style="padding:6px 10px;font-size:12px;">Statut</button>
                    <button type="button" class="btn-saas-primary" @click.stop="openEditRoom(room)" style="padding:6px 10px;font-size:12px;">Modifier</button>
                  </div>
                </div>
                <div x-show="statusMenuRoom === room.id" class="status-menu" @click.stop>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'available')">
                    <span class="status-dot" style="background:#2563EB;"></span>
                    Disponible
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'occupied')">
                    <span class="status-dot" style="background:#16A34A;"></span>
                    Occupée
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'cleaning')">
                    <span class="status-dot" style="background:#7C3AED;"></span>
                    Ménage
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'maintenance')">
                    <span class="status-dot" style="background:#D97706;"></span>
                    Maintenance
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'blocked')">
                    <span class="status-dot" style="background:#6B7280;"></span>
                    Bloquée
                  </button>
                </div>
              </div>
            </template>
          </div>

          <div x-show="viewMode === 'list'" style="overflow-x:auto;">
            <table class="room-table">
              <thead>
                <tr>
                  <th>Chambre</th>
                  <th>Type</th>
                  <th>Étage</th>
                  <th>Prix</th>
                  <th>Statut</th>
                  <th style="width:180px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="room in filteredRooms" :key="room.id">
                  <tr>
                    <td>
                      <div style="font-weight:700;color:#111827;">Chambre <span x-text="room.name"></span></div>
                      <div style="font-size:13px;color:#6B7280;" x-text="room.notes || 'Aucune note'"></div>
                    </td>
                    <td x-text="room.room_type?.name || '—'"></td>
                    <td x-text="room.floor || '-'" style="color:#4B5563;"></td>
                    <td style="font-weight:700;color:#111827;" x-text="formatPrice(room.room_type?.base_price)"></td>
                    <td><span :class="statusCfg(room.status).badge" :style="statusCfg(room.status).badgeStyle || ''" x-text="statusCfg(room.status).label"></span></td>
                    <td>
                      <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="btn-saas-secondary" @click="openEditRoom(room)">Modifier</button>
                        <button type="button" class="btn-saas-secondary" @click.stop="statusMenuRoom = room.id">Statut</button>
                        <button type="button" class="btn-saas-danger" @click="deleteRoom(room.id)">Supprimer</button>
                      </div>
                      <div x-show="statusMenuRoom === room.id" class="status-menu" @click.stop>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'available')">Disponible</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'occupied')">Occupée</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'cleaning')">Ménage</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'maintenance')">Maintenance</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'blocked')">Bloquée</button>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- Onglet Types & Tarifs -->
  <div x-show="activeTab === 'types'">
    <div class="room-info" style="margin-bottom:18px;">
      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
        <div style="font-size:15px;font-weight:700;color:#111827;">Types de chambre</div>
        <div style="font-size:13px;color:#6B7280;">Liste des tarifs configurés et nombre de chambres par type.</div>
      </div>
      <button type="button" class="btn-saas-primary" @click="openCreateType()">Nouveau type</button>
    </div>

    <template x-if="loadingTypes">
      <div class="saas-card">Chargement des types...</div>
    </template>

    <template x-if="!loadingTypes">
      <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;" class="room-grid">
        <template x-for="type in roomTypes" :key="type.id">
          <div class="type-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;">
              <div>
                <div style="font-size:16px;font-weight:700;color:#111827;" x-text="type.name"></div>
                <div style="font-size:13px;color:#6B7280;" x-text="(rooms.filter(r => r.room_type_id == type.id).length) + ' chambre(s)' "></div>
              </div>
              <button type="button" class="btn-saas-secondary" @click="openEditType(type)">Modifier</button>
            </div>
            <div style="display:grid;gap:10px;margin-bottom:14px;">
              <div style="font-size:13px;color:#4B5563;">Capacité : <strong x-text="type.capacity"></strong> pers.</div>
              <div style="font-size:13px;color:#4B5563;">Nuit (base) : <strong x-text="formatPrice(type.base_price)"></strong></div>
              <div style="font-size:13px;color:#4B5563;">Week-end : <strong x-text="formatPrice(type.weekend_price)"></strong></div>
              <div style="font-size:13px;color:#4B5563;">Passage : <strong x-text="formatPrice(type.passage_price)"></strong></div>
            </div>
            <div style="font-size:13px;color:#6B7280;min-height:40px;" x-text="type.description || 'Aucune description'"></div>
            <button type="button" class="btn-saas-danger" style="margin-top:16px;" @click="deleteType(type.id)">Supprimer le type</button>
          </div>
        </template>
      </div>
    </template>
  </div>

  <!-- Modal chambre -->
  <div x-show="showRoomModal" class="saas-modal-bg" @click.self="showRoomModal=false">
    <div class="saas-modal" @click.stop>
      <div class="saas-modal-header">
        <div>
          <h2 x-text="editingRoom ? 'Modifier une chambre' : 'Ajouter une chambre'"></h2>
        </div>
        <button type="button" class="btn-saas-secondary" @click="showRoomModal=false">Fermer</button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
          <div>
            <label class="saas-label">Numéro de chambre *</label>
            <input type="text" class="saas-input" x-model="roomForm.name" />
          </div>
          <div>
            <label class="saas-label">Étage</label>
            <input type="text" class="saas-input" x-model="roomForm.floor" />
          </div>
          <div>
            <label class="saas-label">Type de chambre *</label>
            <select class="saas-input" x-model="roomForm.room_type_id">
              <option value="">Sélectionner un type</option>
              <template x-for="type in roomTypes" :key="type.id">
                <option :value="type.id" x-text="type.name"></option>
              </template>
            </select>
          </div>
          <div>
            <label class="saas-label">Statut actuel</label>
            <select class="saas-input" x-model="roomForm.status">
              <option value="available">Disponible</option>
              <option value="occupied">Occupée</option>
              <option value="maintenance">Maintenance</option>
              <option value="cleaning">Ménage</option>
              <option value="blocked">Bloquée</option>
            </select>
          </div>
        </div>
        <div style="margin-top:18px;">
          <label class="saas-label">Notes (optionnel)</label>
          <textarea class="saas-input" rows="4" x-model="roomForm.notes"></textarea>
        </div>
        <div style="margin-top:18px;">
          <label class="saas-label">Photos de la chambre</label>
          <div style="border:2px dashed rgba(201,168,76,0.4);border-radius:12px;padding:20px;text-align:center;background:rgba(201,168,76,0.03);cursor:pointer;"
            @click="$refs.photoInput.click()"
            @dragover.prevent
            @drop.prevent="pendingPhotos = [...pendingPhotos, ...$event.dataTransfer.files]">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:28px;height:28px;color:#C9A84C;margin:0 auto 8px;display:block;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p style="font-size:13px;color:#9CA3AF;margin:0;">Cliquez ou glissez des photos ici</p>
            <p style="font-size:11px;color:#C9A84C;margin:4px 0 0;">La première photo sera la photo de couverture</p>
          </div>
          <input type="file" x-ref="photoInput" accept="image/*" multiple style="display:none;"
            @change="pendingPhotos = [...pendingPhotos, ...$event.target.files]" />
          <div x-show="pendingPhotos.length > 0" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
            <template x-for="(file, idx) in pendingPhotos" :key="idx">
              <div style="position:relative;width:72px;height:72px;border-radius:8px;overflow:hidden;border:1px solid rgba(0,0,0,0.1);">
                <img :src="URL.createObjectURL(file)" style="width:100%;height:100%;object-fit:cover;" />
                <div x-show="idx===0" style="position:absolute;bottom:0;left:0;right:0;background:rgba(201,168,76,0.85);font-size:9px;color:white;text-align:center;padding:2px;">Couverture</div>
                <button type="button"
                  @click.stop="pendingPhotos = pendingPhotos.filter((_,i)=>i!==idx)"
                  style="position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(220,38,38,0.85);border:none;color:white;cursor:pointer;font-size:12px;line-height:1;display:flex;align-items:center;justify-content:center;">×</button>
              </div>
            </template>
          </div>
        </div>
        <template x-if="roomError">
          <div style="margin-top:18px;padding:14px;border-radius:14px;background:rgba(254,226,226,0.4);border:1px solid rgba(220,38,38,0.2);color:#991B1B;">
            <strong>Erreur :</strong> <span x-text="roomError"></span>
          </div>
        </template>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="showRoomModal=false">Annuler</button>
        <button type="button" class="btn-saas-primary" @click="saveRoom()" :disabled="roomSaving" :style="roomSaving ? 'opacity:0.6;cursor:not-allowed;' : ''">
          <span x-show="!roomSaving" x-text="editingRoom ? 'Enregistrer' : 'Ajouter'"></span>
          <span x-show="roomSaving">Sauvegarde...</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Modal type de chambre -->
  <div x-show="showTypeModal" class="saas-modal-bg" @click.self="showTypeModal=false">
    <div class="saas-modal" @click.stop>
      <div class="saas-modal-header">
        <div>
          <h2 x-text="editingType ? 'Modifier le type de chambre' : 'Nouveau type de chambre'"></h2>
        </div>
        <button type="button" class="btn-saas-secondary" @click="showTypeModal=false">Fermer</button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
          <div>
            <label class="saas-label">Nom du type *</label>
            <input type="text" class="saas-input" x-model="typeForm.name" />
          </div>
          <div>
            <label class="saas-label">Capacité (pers.)</label>
            <input type="number" min="1" class="saas-input" x-model="typeForm.capacity" />
          </div>
        </div>
        <div style="margin-top:18px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;">
          <div>
            <label class="saas-label">Prix par nuit (base) *</label>
            <input type="number" class="saas-input" x-model="typeForm.base_price" />
          </div>
          <div>
            <label class="saas-label">Prix week-end</label>
            <input type="number" class="saas-input" x-model="typeForm.weekend_price" />
          </div>
          <div>
            <label class="saas-label">Prix passage</label>
            <input type="number" class="saas-input" x-model="typeForm.passage_price" />
          </div>
        </div>
        <div style="margin-top:18px;">
          <label class="saas-label">Description (optionnel)</label>
          <textarea class="saas-input" rows="4" x-model="typeForm.description"></textarea>
        </div>
        <template x-if="typeError">
          <div style="margin-top:18px;padding:14px;border-radius:14px;background:rgba(254,226,226,0.4);border:1px solid rgba(220,38,38,0.2);color:#991B1B;">
            <strong>Erreur :</strong> <span x-text="typeError"></span>
          </div>
        </template>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="showTypeModal=false">Annuler</button>
        <button type="button" class="btn-saas-primary" @click="saveType()" :disabled="typeSaving" :style="typeSaving ? 'opacity:0.6;cursor:not-allowed;' : ''">
          <span x-show="!typeSaving" x-text="editingType ? 'Enregistrer' : 'Créer le type'"></span>
          <span x-show="typeSaving">Sauvegarde...</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="saas-toast" x-show="toast" style="display:grid;">
    <div class="toast-box" :class="toast.type === 'error' ? 'error' : ''">
      <div style="font-size:14px;font-weight:600;" x-text="toast.message"></div>
    </div>
  </div>
</div>
