<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>

<?php $pageJs = 'saas-bookings'; $pageCss = 'saas-bookings'; ?>
<div x-data="bookingsPage('<?= $base_url ?>')"
 x-init="init()"
 @keydown.escape.window="showCreate=false; showDetail=false">

  <!-- En-tête -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h1 style="font-size:24px;font-weight:700;margin:0;color:#111827;">Réservations</h1>
      <p style="margin:8px 0 0;color:#6B7280;font-size:14px;">
        <span x-text="filteredBookings.length"></span> affichée(s) · <span x-text="total"></span> totale(s)
      </p>
    </div>
    <button type="button" class="btn-saas-primary" style="white-space:nowrap;" @click="openCreate()">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Nouvelle réservation
    </button>
  </div>

  <!-- Onglets statut -->
  <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <template x-for="s in ['all','pending','confirmed','checked_in','checked_out','cancelled']" :key="s">
      <button type="button" class="status-tab" :class="{ active: filterStatus === s }"
        @click="filterStatus = s; applyFilters()">
        <span x-text="s === 'all' ? 'Toutes' : statusConfig(s).label"></span>
        <span class="tab-count" x-text="countByStatus(s)"></span>
      </button>
    </template>
  </div>

  <!-- Filtres -->
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;margin-bottom:20px;align-items:end;">
    <label class="saas-label">
      Recherche
      <input type="text" class="saas-input" placeholder="Client, chambre, téléphone…" x-model="filterSearch" @keydown.enter="applyFilters()" />
    </label>
    <label class="saas-label">
      Arrivée à partir du
      <input type="date" class="saas-input" x-model="filterDateFrom" />
    </label>
    <label class="saas-label">
      Arrivée jusqu'au
      <input type="date" class="saas-input" x-model="filterDateTo" />
    </label>
    <div style="display:flex;gap:10px;">
      <button type="button" class="btn-saas-secondary" @click="resetFilters()">Effacer</button>
      <button type="button" class="btn-saas-primary" @click="applyFilters()">Filtrer</button>
    </div>
  </div>

  <!-- Skeleton -->
  <div x-show="loading" style="display:grid;gap:12px;">
    <template x-for="i in [1,2,3,4,5]" :key="i">
      <div style="height:64px;background:white;border-radius:12px;border:1px solid rgba(0,0,0,0.06);animation:pulse 1.5s ease-in-out infinite;"></div>
    </template>
  </div>

  <div x-show="!loading" style="display:grid;gap:20px;">

    <div x-show="error" style="padding:14px 16px;background:#FEF3C7;border:1px solid rgba(220,145,24,0.3);border-radius:12px;color:#92400E;" x-text="error"></div>

    <!-- Vide -->
    <div x-show="!error && filteredBookings.length === 0" class="saas-card" style="padding:48px;text-align:center;">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:48px;height:48px;color:#D1D5DB;margin:0 auto 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <p style="font-size:15px;font-weight:600;color:#111827;margin:0 0 8px;">Aucune réservation trouvée</p>
      <p style="color:#6B7280;margin:0 0 20px;font-size:14px;">Ajustez les filtres ou créez une nouvelle réservation.</p>
      <button type="button" class="btn-saas-secondary" @click="resetFilters()">Effacer les filtres</button>
    </div>

    <!-- Table -->
    <div x-show="filteredBookings.length > 0" class="saas-card" style="padding:0;overflow:hidden;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid rgba(0,0,0,0.06);">
        <div>
          <h2 style="font-size:16px;font-weight:700;margin:0;color:#111827;">Liste des réservations</h2>
          <p style="margin:4px 0 0;color:#6B7280;font-size:12px;">Cliquez sur une ligne pour voir le détail</p>
        </div>
        <span style="font-size:13px;color:#6B7280;"><span x-text="filteredBookings.length"></span> / <span x-text="total"></span></span>
      </div>

      <div style="overflow-x:auto;">
        <table class="saas-table" style="min-width:900px;">
          <thead>
            <tr>
              <th>Client</th>
              <th>Chambre</th>
              <th>Séjour</th>
              <th>Type</th>
              <th>Source</th>
              <th style="text-align:right;">Montant</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="b in filteredBookings" :key="b.id">
              <tr class="booking-row" @click="openDetail(b)" style="cursor:pointer;">
                <td>
                  <div style="font-weight:600;color:#111827;" x-text="b.client_name"></div>
                  <div style="font-size:12px;color:#6B7280;margin-top:2px;" x-text="b.client_phone"></div>
                </td>
                <td>
                  <div style="font-weight:600;color:#111827;" x-text="'Ch. ' + b.room_number"></div>
                  <div style="font-size:12px;color:#6B7280;margin-top:2px;" x-text="b.room_type"></div>
                </td>
                <td>
                  <template x-if="b.booking_type === 'passage'">
                    <div>
                      <div style="font-weight:500;color:#111827;" x-text="formatDate(b.check_in)"></div>
                      <div style="font-size:12px;color:#B8860B;margin-top:2px;" x-text="b.hours + ' heure' + (b.hours > 1 ? 's' : '')"></div>
                    </div>
                  </template>
                  <template x-if="b.booking_type !== 'passage'">
                    <div>
                      <div style="font-size:12px;color:#6B7280;">
                        <span x-text="formatDate(b.check_in)"></span>
                        <span style="color:#D1D5DB;margin:0 4px;">→</span>
                        <span x-text="formatDate(b.check_out)"></span>
                      </div>
                      <div style="font-weight:500;color:#111827;margin-top:2px;" x-text="b.nights + ' nuit' + (b.nights > 1 ? 's' : '')"></div>
                    </div>
                  </template>
                </td>
                <td><span class="badge" :style="typeBadgeStyle(b.booking_type)" x-text="typeLabel(b.booking_type)"></span></td>
                <td><span class="badge badge-info" x-text="sourceLabel(b.source)"></span></td>
                <td style="font-weight:700;color:#111827;text-align:right;" x-text="formatPrice(b.total_price)"></td>
                <td><span :class="statusConfig(b.status).badge" x-text="statusConfig(b.status).label"></span></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-top:1px solid rgba(0,0,0,0.06);">
        <span style="color:#6B7280;font-size:13px;">Page <span x-text="page"></span> sur <span x-text="totalPages"></span></span>
        <div style="display:flex;gap:10px;">
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page-1)" :disabled="page<=1" :style="page<=1?'opacity:0.4;cursor:not-allowed;':''">← Préc.</button>
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page+1)" :disabled="page>=totalPages" :style="page>=totalPages?'opacity:0.4;cursor:not-allowed;':''">Suiv. →</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ MODAL DÉTAIL ═══ -->
  <div x-cloak x-show="showDetail" class="saas-modal-bg" @click.self="showDetail=false" @keydown.escape.window="showDetail=false">
    <div class="saas-modal" style="max-width:640px;" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <div>
          <p style="font-size:12px;color:#6B7280;margin:0;">Réservation #<span x-text="selectedBooking?.id"></span></p>
          <h2 style="font-size:18px;font-weight:700;margin:6px 0 0;color:#111827;" x-text="selectedBooking?.client_name"></h2>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
          <span x-show="selectedBooking" :class="statusConfig(selectedBooking?.status).badge" x-text="statusConfig(selectedBooking?.status).label"></span>
          <button type="button" style="background:none;border:none;color:#6B7280;cursor:pointer;font-size:22px;line-height:1;" @click="showDetail=false">×</button>
        </div>
      </div>

      <div class="saas-modal-body">
        <div x-show="detailLoading" style="display:flex;justify-content:center;padding:40px 0;">
          <svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:28px;height:28px;stroke:#C9A84C;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="4" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="4" stroke-linecap="round"></path></svg>
        </div>

        <div x-show="!detailLoading" style="display:grid;gap:22px;">

          <!-- Client -->
          <div style="background:#F9FAFB;border-radius:12px;padding:16px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
              <p class="saas-label" style="margin-bottom:4px;">Client</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="selectedBooking?.client_name"></p>
              <p style="margin:4px 0 0;font-size:13px;color:#6B7280;" x-text="selectedBooking?.client_phone"></p>
              <p x-show="selectedBooking?.client_email" style="margin:3px 0 0;font-size:13px;color:#6B7280;" x-text="selectedBooking?.client_email"></p>
            </div>
            <div>
              <p class="saas-label" style="margin-bottom:4px;">Chambre</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="'Chambre ' + selectedBooking?.room_number + (selectedBooking?.floor ? ' (étage ' + selectedBooking.floor + ')' : '')"></p>
              <p style="margin:4px 0 0;font-size:13px;color:#6B7280;" x-text="selectedBooking?.room_type"></p>
              <p style="margin:3px 0 0;font-size:13px;color:#6B7280;" x-text="(selectedBooking?.guests_count ?? 1) + ' voyageur' + ((selectedBooking?.guests_count ?? 1) > 1 ? 's' : '')"></p>
            </div>
          </div>

          <!-- Dates / durée -->
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
            <div>
              <p class="saas-label" style="margin-bottom:4px;">Arrivée</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="formatDate(selectedBooking?.check_in)"></p>
            </div>
            <div x-show="selectedBooking?.booking_type !== 'passage'">
              <p class="saas-label" style="margin-bottom:4px;">Départ</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="formatDate(selectedBooking?.check_out)"></p>
            </div>
            <div>
              <p class="saas-label" style="margin-bottom:4px;" x-text="selectedBooking?.booking_type === 'passage' ? 'Durée' : 'Nuits'"></p>
              <p style="margin:0;font-weight:600;color:#111827;"
                x-text="selectedBooking?.booking_type === 'passage'
                  ? (selectedBooking?.hours + ' heure' + (selectedBooking?.hours > 1 ? 's' : ''))
                  : (selectedBooking?.nights + ' nuit' + (selectedBooking?.nights > 1 ? 's' : ''))">
              </p>
            </div>
            <div>
              <p class="saas-label" style="margin-bottom:4px;">Type de séjour</p>
              <span class="badge" :style="typeBadgeStyle(selectedBooking?.booking_type)" x-text="typeLabel(selectedBooking?.booking_type)"></span>
            </div>
          </div>

          <!-- Montant + facture -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;background:#F9FAFB;border-radius:12px;padding:16px;">
            <div>
              <p class="saas-label" style="margin-bottom:4px;">Montant total</p>
              <p style="margin:0;font-size:20px;font-weight:700;color:#111827;" x-text="formatPrice(selectedBooking?.total_amount ?? selectedBooking?.total_price)"></p>
            </div>
            <div x-show="selectedBooking?.invoice_number">
              <p class="saas-label" style="margin-bottom:4px;">Facture</p>
              <p style="margin:0;font-weight:600;color:#111827;" x-text="selectedBooking?.invoice_number"></p>
              <span class="badge badge-info" style="margin-top:4px;" x-text="selectedBooking?.invoice_status ?? 'émise'"></span>
            </div>
          </div>

          <!-- Notes -->
          <div x-show="selectedBooking?.notes">
            <p class="saas-label" style="margin-bottom:4px;">Notes</p>
            <p style="margin:0;font-size:14px;color:#374151;background:#F9FAFB;border-radius:10px;padding:12px 14px;" x-text="selectedBooking?.notes"></p>
          </div>

          <!-- Source + date création -->
          <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:12px;color:#9CA3AF;">
            <span>Source : <strong x-text="sourceLabel(selectedBooking?.source)"></strong></span>
            <span x-show="selectedBooking?.created_at">· Créée le <span x-text="formatDate(selectedBooking?.created_at)"></span></span>
          </div>

          <!-- Actions de statut -->
          <div x-show="nextActions(selectedBooking?.status ?? '').length > 0">
            <p class="saas-label" style="margin-bottom:10px;">Changer le statut</p>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
              <template x-for="action in nextActions(selectedBooking?.status ?? '')" :key="action.value">
                <button type="button" :class="'btn-saas-secondary action-btn-' + action.color" style="font-weight:600;"
                  @click="updateStatus(selectedBooking.id, action.value)" :disabled="statusUpdating">
                  <span x-text="action.label"></span>
                </button>
              </template>
            </div>
          </div>

        </div>
      </div>

      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-danger" @click="deleteBooking(selectedBooking.id)">Supprimer</button>
        <button type="button" class="btn-saas-secondary" @click="showDetail=false">Fermer</button>
      </div>
    </div>
  </div>

  <!-- ═══ MODAL CRÉATION ═══ -->
  <div x-cloak x-show="showCreate" class="saas-modal-bg" @click.self="showCreate=false" @keydown.escape.window="showCreate=false">
    <div class="saas-modal" style="max-width:600px;" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <div>
          <p style="font-size:12px;color:#6B7280;margin:0;">Nouvelle réservation</p>
          <h2 style="font-size:18px;font-weight:700;margin:6px 0 0;color:#111827;">Créer une réservation</h2>
        </div>
        <button type="button" style="background:none;border:none;color:#6B7280;cursor:pointer;font-size:22px;line-height:1;" @click="showCreate=false">×</button>
      </div>

      <form class="saas-modal-body" @submit.prevent="createBooking()" @click.stop>
        <!-- Chambre + type séjour -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div>
            <label class="saas-label">Chambre <span style="color:#DC2626;">*</span></label>
            <select class="saas-input" x-model="form.room_id" required>
              <option value="">Sélectionner une chambre</option>
              <template x-for="room in rooms" :key="room.id">
                <option :value="room.id" x-text="'Ch. ' + room.number + ' — ' + (room.room_type?.name || room.type_name || '')"></option>
              </template>
            </select>
          </div>
          <div>
            <label class="saas-label">Type de séjour</label>
            <select class="saas-input" x-model="form.booking_type">
              <option value="nuit">Nuit</option>
              <option value="weekend">Week-end</option>
              <option value="passage">Passage (horaire)</option>
            </select>
          </div>
        </div>

        <!-- Dates -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div>
            <label class="saas-label">Date d'arrivée <span style="color:#DC2626;">*</span></label>
            <input type="date" class="saas-input" x-model="form.check_in" required />
          </div>
          <div x-show="form.booking_type !== 'passage'">
            <label class="saas-label">Date de départ <span style="color:#DC2626;">*</span></label>
            <input type="date" class="saas-input" x-model="form.check_out" :required="form.booking_type !== 'passage'" />
          </div>
          <!-- Heures si passage -->
          <div x-show="form.booking_type === 'passage'">
            <label class="saas-label">Durée <span style="font-size:11px;color:#6B7280;">(heures)</span> <span style="color:#DC2626;">*</span></label>
            <select class="saas-input" x-model.number="form.hours" :required="form.booking_type === 'passage'">
              <option value="1">1 heure</option>
              <option value="2">2 heures</option>
              <option value="3" selected>3 heures</option>
              <option value="4">4 heures</option>
              <option value="6">6 heures</option>
              <option value="8">8 heures</option>
              <option value="12">12 heures</option>
            </select>
          </div>
        </div>

        <!-- Client -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div>
            <label class="saas-label">Prénom <span style="color:#DC2626;">*</span></label>
            <input type="text" class="saas-input" x-model="form.first_name" required placeholder="Kouamé" />
          </div>
          <div>
            <label class="saas-label">Nom <span style="color:#DC2626;">*</span></label>
            <input type="text" class="saas-input" x-model="form.last_name" required placeholder="Kobenan" />
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div>
            <label class="saas-label">Téléphone <span style="color:#DC2626;">*</span></label>
            <input type="tel" class="saas-input" x-model="form.client_phone" required placeholder="+225 07 00 00 00" />
          </div>
          <div>
            <label class="saas-label">Email <span style="font-size:11px;color:#9CA3AF;">(optionnel)</span></label>
            <input type="email" class="saas-input" x-model="form.client_email" placeholder="email@exemple.com" />
          </div>
        </div>

        <!-- Voyageurs + source -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div>
            <label class="saas-label">Nombre de voyageurs</label>
            <select class="saas-input" x-model.number="form.guests_count">
              <option value="1">1 personne</option>
              <option value="2" selected>2 personnes</option>
              <option value="3">3 personnes</option>
              <option value="4">4 personnes</option>
            </select>
          </div>
          <div>
            <label class="saas-label">Source</label>
            <select class="saas-input" x-model="form.source">
              <option value="manual">Manuel / Sur place</option>
              <option value="phone">Téléphone</option>
              <option value="online">En ligne</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <label class="saas-label">Notes <span style="font-size:11px;color:#9CA3AF;">(optionnel)</span></label>
          <textarea class="saas-input" rows="3" x-model="form.notes" style="resize:vertical;" placeholder="Demandes spéciales, arrivée tardive…"></textarea>
        </div>

        <div x-show="createError" style="margin-bottom:16px;padding:12px 14px;background:#FEF2F2;border:1px solid rgba(220,38,38,0.2);border-radius:10px;color:#B91C1C;font-size:14px;" x-text="createError"></div>

        <div class="saas-modal-footer">
          <button type="button" class="btn-saas-secondary" @click="showCreate=false" :disabled="createLoading">Annuler</button>
          <button type="submit" class="btn-saas-primary" :disabled="createLoading">
            <span x-show="!createLoading">Créer la réservation</span>
            <span x-show="createLoading" style="display:inline-flex;align-items:center;gap:8px;">
              <svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:16px;height:16px;stroke:white;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="3" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="3" stroke-linecap="round"></path></svg>
              Création…
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast -->
  <div x-show="toast" style="position:fixed;bottom:24px;right:24px;z-index:200;">
    <div :style="toast?.type === 'success' ? 'background:#DCFCE7;color:#166534;' : 'background:#FEE2E2;color:#991B1B;'"
      style="padding:14px 20px;border-radius:14px;box-shadow:0 18px 50px rgba(15,23,42,0.18);min-width:260px;">
      <p style="margin:0;font-size:14px;font-weight:600;" x-text="toast?.msg"></p>
    </div>
  </div>
</div>
