<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>

<?php $pageJs = 'saas-bookings'; $pageCss = 'saas-bookings'; ?>
<div x-data="bookingsPage('<?= $base_url ?>')"
 x-init="init()"
 @keydown.escape.window="showCreate=false; showDetail=false">

  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h1 style="font-size:24px;font-weight:700;margin:0;color:#111827;">Réservations</h1>
      <p style="margin:8px 0 0;color:#6B7280;font-size:14px;"><span x-text="filteredBookings.length"></span> réservation(s) affichée(s) · <span x-text="total"></span> totale(s)</p>
    </div>
    <button type="button" class="btn-saas-primary" style="white-space:nowrap;" @click="showCreate=true">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Nouvelle réservation
    </button>
  </div>

  <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:22px;">
    <template x-for="status in ['all','confirmed','pending','checked_in','checked_out','cancelled']" :key="status">
      <button type="button"
        class="status-tab"
        :class="{ 'active': filterStatus === status }"
        @click="filterStatus = status; applyFilters();">
        <span x-text="status === 'all' ? 'Toutes' : statusConfig(status).label"></span>
        <span class="tab-count" x-text="countByStatus(status)"></span>
      </button>
    </template>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;margin-bottom:20px;align-items:end;">
    <label class="saas-label">
      Recherche
      <input type="text" class="saas-input" placeholder="Client, chambre, téléphone" x-model="filterSearch" @input="" />
    </label>
    <label class="saas-label">
      Date arrivée min
      <input type="date" class="saas-input" x-model="filterDateFrom" />
    </label>
    <label class="saas-label">
      Date arrivée max
      <input type="date" class="saas-input" x-model="filterDateTo" />
    </label>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <button type="button" class="btn-saas-secondary" @click="resetFilters()">Effacer</button>
      <button type="button" class="btn-saas-primary" @click="applyFilters()">Filtrer</button>
    </div>
  </div>

  <div x-show="loading" style="display:grid;gap:16px;">
    <div class="saas-card" style="padding:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;"><div style="width:180px;height:18px;background:rgba(0,0,0,0.05);border-radius:8px;"></div><div style="width:90px;height:18px;background:rgba(0,0,0,0.05);border-radius:8px;"></div></div>
      <div style="display:grid;gap:10px;">
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:80%;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:90%;"></div>
      </div>
    </div>
    <div class="saas-card" style="padding:20px;">
      <div style="display:grid;gap:10px;">
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:50%;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:70%;"></div>
        <div style="height:14px;background:rgba(0,0,0,0.05);border-radius:6px;width:60%;"></div>
      </div>
    </div>
  </div>

  <div x-show="!loading" style="display:grid;gap:20px;">
    <div x-show="error" style="padding:14px 16px;background:#FEF3C7;border:1px solid rgba(220,145,24,0.3);border-radius:12px;color:#92400E;">
      <span x-text="error"></span>
    </div>

    <div x-show="filteredBookings.length === 0" class="saas-card" style="padding:32px;text-align:center;">
      <p style="font-size:15px;font-weight:600;color:#111827;margin:0 0 8px;">Aucune réservation ne correspond à vos filtres.</p>
      <p style="color:#6B7280;margin:0 0 16px;">Ajustez les filtres ou cliquez sur Effacer pour retrouver toutes les réservations.</p>
      <button type="button" class="btn-saas-secondary" @click="resetFilters()">Effacer les filtres</button>
    </div>

    <div x-show="filteredBookings.length > 0" class="saas-card" style="padding:0;overflow:hidden;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(0,0,0,0.06);">
        <div>
          <h2 style="font-size:18px;font-weight:700;margin:0;color:#111827;">Liste des réservations</h2>
          <p style="margin:6px 0 0;color:#6B7280;font-size:13px;">Cliquez sur une ligne pour voir les détails.</p>
        </div>
        <span style="font-size:13px;color:#6B7280;"><span x-text="filteredBookings.length"></span> / <span x-text="total"></span></span>
      </div>

      <div style="overflow-x:auto;">
        <table class="saas-table" style="min-width:940px;">
          <thead>
            <tr>
              <th>Client</th>
              <th>Chambre</th>
              <th>Arrivée</th>
              <th>Départ</th>
              <th>Source</th>
              <th>Montant</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="booking in filteredBookings" :key="booking.id">
              <tr class="booking-row" @click="openDetail(booking)">
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-weight:600;color:#111827;" x-text="booking.client_name"></span>
                    <span style="font-size:13px;color:#6B7280;" x-text="booking.client_phone"></span>
                  </div>
                </td>
                <td>
                  <div style="display:flex;flex-direction:column;gap:4px;">
                    <span style="font-weight:600;color:#111827;" x-text="booking.room_name"></span>
                    <span style="font-size:13px;color:#6B7280;" x-text="typeLabel(booking.booking_type)"></span>
                  </div>
                </td>
                <td x-text="formatDate(booking.check_in)"></td>
                <td x-text="formatDate(booking.check_out)"></td>
                <td><span class="badge badge-info" x-text="sourceLabel(booking.source)"></span></td>
                <td style="font-weight:700;color:#111827;text-align:right;" x-text="formatPrice(booking.total_price)"></td>
                <td><span :class="statusConfig(booking.status).badge" x-text="statusConfig(booking.status).label"></span></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-top:1px solid rgba(0,0,0,0.06);">
        <span style="color:#6B7280;font-size:13px;">Page <span x-text="page"></span> sur <span x-text="totalPages"></span></span>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page-1)" :disabled="page<=1" :style="page<=1 ? 'opacity:0.4;cursor:not-allowed;' : ''">← Préc.</button>
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page+1)" :disabled="page>=totalPages" :style="page>=totalPages ? 'opacity:0.4;cursor:not-allowed;' : ''">Suiv. →</button>
        </div>
      </div>
    </div>
  </div>

  <div x-show="showDetail" class="saas-modal-bg" @click.self="showDetail=false">
    <div class="saas-modal" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <div>
          <p style="font-size:13px;color:#6B7280;margin:0;">Réservation #<span x-text="selectedBooking?.id"></span></p>
          <h2 style="font-size:18px;font-weight:700;margin:6px 0 0;color:#111827;" x-text="selectedBooking?.client_name"></h2>
        </div>
        <button type="button" style="background:none;border:none;color:#6B7280;cursor:pointer;font-size:18px;" @click="showDetail=false">×</button>
      </div>
      <div class="saas-modal-body">
        <div x-show="detailLoading" style="display:flex;justify-content:center;padding:40px 0;"><svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:28px;height:28px;stroke:#C9A84C;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="4" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="4" stroke-linecap="round"></path></svg></div>
        <div x-show="!detailLoading" style="display:grid;gap:20px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div>
              <p class="saas-label">Client</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="selectedBooking?.client_name"></p>
              <p style="margin:6px 0 0;color:#6B7280;" x-text="selectedBooking?.client_phone"></p>
              <p style="margin:4px 0 0;color:#6B7280;" x-text="selectedBooking?.client_email"></p>
            </div>
            <div>
              <p class="saas-label">Séjour</p>
              <p style="margin:0;color:#111827;"><strong x-text="selectedBooking?.room_name"></strong></p>
              <p style="margin:6px 0 0;color:#6B7280;" x-text="typeLabel(selectedBooking?.booking_type)"></p>
              <p style="margin:4px 0 0;color:#6B7280;">Nuit(s) : <span x-text="selectedBooking?.nights ?? '-'" /></p>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
            <div>
              <p class="saas-label">Arrivée</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="formatDate(selectedBooking?.check_in)"></p>
            </div>
            <div>
              <p class="saas-label">Départ</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="formatDate(selectedBooking?.check_out)"></p>
            </div>
          </div>
          <div>
            <p class="saas-label">Notes</p>
            <p style="margin:0;color:#6B7280;" x-text="selectedBooking?.notes || 'Aucune note' "></p>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:center;">
            <div>
              <p class="saas-label">Montant total</p>
              <p style="margin:0;font-weight:700;color:#111827;" x-text="formatPrice(selectedBooking?.total_price)"></p>
            </div>
            <div>
              <p class="saas-label">Statut</p>
              <span :class="statusConfig(selectedBooking?.status).badge" x-text="statusConfig(selectedBooking?.status).label"></span>
            </div>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <template x-for="action in nextActions(selectedBooking?.status || '')" :key="action.value">
              <button type="button" :class="'btn-saas-secondary action-btn-' + action.color" style="font-weight:600;" @click="updateStatus(selectedBooking.id, action.value)">
                <span x-text="action.label"></span>
              </button>
            </template>
          </div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-danger" @click="deleteBooking(selectedBooking.id)">Supprimer</button>
        <button type="button" class="btn-saas-secondary" @click="showDetail=false">Fermer</button>
      </div>
    </div>
  </div>

  <div x-show="showCreate" class="saas-modal-bg" @click.self="showCreate=false">
    <div class="saas-modal" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <div>
          <p style="font-size:13px;color:#6B7280;margin:0;">Nouvelle réservation</p>
          <h2 style="font-size:18px;font-weight:700;margin:6px 0 0;color:#111827;">Créer une réservation</h2>
        </div>
        <button type="button" style="background:none;border:none;color:#6B7280;cursor:pointer;font-size:18px;" @click="showCreate=false">×</button>
      </div>
      <form class="saas-modal-body" @submit.prevent="createBooking()">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Chambre</label>
            <select class="saas-input" x-model="form.room_id" required>
              <option value="">Sélectionner une chambre</option>
              <template x-for="room in rooms" :key="room.id">
                <option :value="room.id" x-text="room.name + ' — ' + (room.room_type?.name || '')"></option>
              </template>
            </select>
          </div>
          <div>
            <label class="saas-label">Type de séjour</label>
            <select class="saas-input" x-model="form.booking_type">
              <option value="nuit">Nuit</option>
              <option value="weekend">Week-end</option>
              <option value="passage">Passage</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Arrivée</label>
            <input type="date" class="saas-input" x-model="form.check_in" required />
          </div>
          <div>
            <label class="saas-label">Départ</label>
            <input type="date" class="saas-input" x-model="form.check_out" required />
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Nom du client</label>
            <input type="text" class="saas-input" x-model="form.client_name" required />
          </div>
          <div>
            <label class="saas-label">Téléphone</label>
            <input type="text" class="saas-input" x-model="form.client_phone" required />
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
          <div>
            <label class="saas-label">Email (optionnel)</label>
            <input type="email" class="saas-input" x-model="form.client_email" />
          </div>
          <div>
            <label class="saas-label">Source</label>
            <select class="saas-input" x-model="form.source">
              <option value="manual">Manuel</option>
              <option value="phone">Téléphone</option>
              <option value="walk_in">Sur place</option>
              <option value="online">En ligne</option>
            </select>
          </div>
        </div>
        <div style="margin-bottom:18px;">
          <label class="saas-label">Notes (optionnel)</label>
          <textarea class="saas-input" rows="4" x-model="form.notes" style="resize:vertical;"></textarea>
        </div>
        <div x-show="createError" style="margin-bottom:18px;padding:12px 14px;background:#FEF2F2;border:1px solid rgba(220,38,38,0.2);border-radius:12px;color:#B91C1C;" x-text="createError"></div>
        <div class="saas-modal-footer">
          <button type="button" class="btn-saas-secondary" @click="showCreate=false" :disabled="createLoading">Annuler</button>
          <button type="submit" class="btn-saas-primary" :disabled="createLoading">
            <span x-show="!createLoading">Créer la réservation</span>
            <span x-show="createLoading" style="display:inline-flex;align-items:center;gap:8px;">
              <svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:16px;height:16px;stroke:white;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="3" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="3" stroke-linecap="round"></path></svg>
              Création...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div x-show="toast" style="position:fixed;bottom:24px;right:24px;z-index:110;">
    <div :style="toast.type === 'success' ? 'background:#DCFCE7;color:#166534;' : 'background:#FEE2E2;color:#991B1B;'" style="padding:14px 18px;border-radius:14px;box-shadow:0 18px 50px rgba(15,23,42,0.15);min-width:260px;">
      <p style="margin:0;font-size:14px;font-weight:600;" x-text="toast.msg"></p>
    </div>
  </div>
</div>
