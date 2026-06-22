<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>

<?php $pageJs = 'saas-rooms'; $pageCss = 'saas-rooms'; ?>
<div x-data="roomsPage('<?= $base_url ?>')"
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
        <div class="saas-card" style="text-align:center;padding:40px 20px;">
          <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:8px;" x-text="rooms.length === 0 ? 'Aucune chambre configurée' : 'Aucune chambre ne correspond à vos filtres'"></div>
          <div style="color:#6B7280;font-size:14px;" x-text="rooms.length === 0 ? 'Cliquez sur « Ajouter une chambre » pour commencer.' : 'Essayez de modifier ou d\'effacer les filtres.'"></div>
          <button x-show="rooms.length > 0" type="button" class="btn-saas-primary" style="margin-top:18px;" @click="filterStatus='all'; filterType='';">Effacer les filtres</button>
        </div>
      </template>

      <template x-if="filteredRooms.length > 0">
        <div>
          <div x-show="viewMode === 'grid'" class="room-grid">
            <template x-for="room in roomsByFloor.flatMap(([floor, rooms]) => rooms)" :key="room.id">
              <div class="room-card" :class="'status-' + room.status" @click="openEditRoom(room)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                  <div>
                    <div style="font-size:15px;font-weight:700;color:#111827;" x-text="room.number"></div>
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
                      <div style="font-weight:700;color:#111827;">Chambre <span x-text="room.number"></span></div>
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
              <div style="font-size:13px;color:#4B5563;">Passage : <strong x-text="type.passage_price ? formatPrice(type.passage_price) + ' / heure' : '—'"></strong></div>
            </div>
            <div style="font-size:13px;color:#6B7280;min-height:40px;" x-text="type.description || 'Aucune description'"></div>
            <button type="button" class="btn-saas-danger" style="margin-top:16px;" @click="deleteType(type.id)">Supprimer le type</button>
          </div>
        </template>
      </div>
    </template>
  </div>

  <!-- Modal chambre -->
  <div x-cloak x-show="showRoomModal" class="saas-modal-bg" @click.self="showRoomModal=false" @keydown.escape.window="showRoomModal=false">
    <form class="saas-modal" @click.stop @submit.prevent="saveRoom()">
      <div class="saas-modal-header">
        <h2 x-text="editingRoom ? 'Modifier une chambre' : 'Ajouter une chambre'"></h2>
        <button type="button" class="btn-saas-secondary" @click="showRoomModal=false">Fermer</button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
          <div>
            <label class="saas-label">Numéro de chambre *</label>
            <input type="text" class="saas-input" x-model="roomForm.number" required />
          </div>
          <div>
            <label class="saas-label">Étage</label>
            <input type="text" class="saas-input" x-model="roomForm.floor" />
          </div>
          <div>
            <label class="saas-label">Type de chambre *</label>
            <select class="saas-input" x-model="roomForm.room_type_id" required>
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
        <div x-show="roomError" x-cloak style="margin-top:18px;padding:14px;border-radius:14px;background:rgba(254,226,226,0.4);border:1px solid rgba(220,38,38,0.2);color:#991B1B;">
          <strong>Erreur :</strong> <span x-text="roomError"></span>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="showRoomModal=false">Annuler</button>
        <button type="submit" class="btn-saas-primary" :disabled="roomSaving">
          <span x-show="!roomSaving" x-text="editingRoom ? 'Enregistrer' : 'Ajouter'"></span>
          <span x-show="roomSaving">Sauvegarde...</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Modal type de chambre -->
  <div x-cloak x-show="showTypeModal" class="saas-modal-bg" @click.self="showTypeModal=false" @keydown.escape.window="showTypeModal=false">
    <form class="saas-modal" @click.stop @submit.prevent="saveType()">
      <div class="saas-modal-header">
        <h2 x-text="editingType ? 'Modifier le type de chambre' : 'Nouveau type de chambre'"></h2>
        <button type="button" class="btn-saas-secondary" @click="showTypeModal=false">Fermer</button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
          <div>
            <label class="saas-label">Nom du type *</label>
            <input type="text" class="saas-input" x-model="typeForm.name" required />
          </div>
          <div>
            <label class="saas-label">Capacité (pers.)</label>
            <input type="number" min="1" class="saas-input" x-model.number="typeForm.capacity" />
          </div>
        </div>
        <div style="margin-top:18px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;">
          <div>
            <label class="saas-label">Prix par nuit (base) *</label>
            <input type="number" min="0" class="saas-input" x-model.number="typeForm.base_price" required />
          </div>
          <div>
            <label class="saas-label">Prix week-end</label>
            <input type="number" min="0" class="saas-input" x-model.number="typeForm.weekend_price" />
          </div>
          <div>
            <label class="saas-label">Prix passage <span style="font-size:11px;font-weight:400;color:#6B7280;">(par heure)</span></label>
            <input type="number" min="0" class="saas-input" x-model.number="typeForm.passage_price" placeholder="ex : 5000" />
          </div>
        </div>
        <div style="margin-top:18px;">
          <label class="saas-label">Description (optionnel)</label>
          <textarea class="saas-input" rows="4" x-model="typeForm.description"></textarea>
        </div>
        <div x-show="typeError" x-cloak style="margin-top:18px;padding:14px;border-radius:14px;background:rgba(254,226,226,0.4);border:1px solid rgba(220,38,38,0.2);color:#991B1B;">
          <strong>Erreur :</strong> <span x-text="typeError"></span>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="showTypeModal=false">Annuler</button>
        <button type="submit" class="btn-saas-primary" :disabled="typeSaving">
          <span x-show="!typeSaving" x-text="editingType ? 'Enregistrer' : 'Créer le type'"></span>
          <span x-show="typeSaving">Sauvegarde...</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Toast -->
  <div class="saas-toast" x-show="toast" style="display:grid;">
    <div class="toast-box" :class="toast?.type === 'error' ? 'error' : ''">
      <div style="font-size:14px;font-weight:600;" x-text="toast?.msg"></div>
    </div>
  </div>
</div>
