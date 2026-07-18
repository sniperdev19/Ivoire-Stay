<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>

<?php $pageJs = 'saas-bookings'; $pageCss = ['saas-bookings', 'saas-rooms']; ?>
<div x-data="bookingsPage('<?= $base_url ?>')"
 x-init="init()"
 @keydown.escape.window="showCreate=false; showDetail=false">

  <!-- En-tête -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h1 style="font-size:26px;font-weight:700;margin:0;color:#111827;font-family:Georgia,serif;">Réservations Actives</h1>
      <p style="margin:8px 0 0;color:#9CA3AF;font-size:13px;">Suivi des entrées, sorties et des réservations de chambres de l'établissement en temps réel.</p>
    </div>
    <button type="button" class="btn-booking-new" @click="openCreate()" :disabled="establishment?.frozen_at" :style="establishment?.frozen_at ? 'opacity:0.5;cursor:not-allowed;' : ''">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Nouvelle réservation
    </button>
  </div>

  <!-- Bandeau établissement gelé (limite d'établissements du plan dépassée) -->
  <div x-show="establishment?.frozen_at" x-cloak
    style="display:flex;align-items:flex-start;gap:12px;background:#FEF2F2;border:1px solid rgba(220,38,38,0.25);border-radius:12px;padding:14px 16px;margin-bottom:20px;">
    <span style="font-size:18px;line-height:1;">🧊</span>
    <div style="font-size:13px;color:#991B1B;">
      <strong>Établissement gelé</strong> — la limite d'établissements de votre plan actuel est dépassée.
      Impossible de créer une nouvelle réservation.
      <span x-show="!establishment?.frozen_hard_at || new Date(establishment.frozen_hard_at) > new Date()">
        La gestion des réservations déjà faites (encaisser, annuler, check-in, check-out) reste possible.
      </span>
      <span x-show="establishment?.frozen_hard_at && new Date(establishment.frozen_hard_at) <= new Date()">
        Le délai de grâce est dépassé : plus aucune action n'est possible sur cet établissement.
      </span>
      Mettez à niveau votre abonnement pour le réactiver.
    </div>
  </div>

  <!-- Onglets statut -->
  <div class="status-tabs-row">
    <template x-for="s in ['all','pending','confirmed','checked_in','checked_out','cancelled']" :key="s">
      <button type="button" class="status-tab" :class="{ active: filterStatus === s }"
        @click="filterStatus = s; applyFilters()">
        <span x-text="s === 'all' ? 'Toutes' : statusConfig(s).label"></span>
        <span class="tab-count" x-text="countByStatus(s)"></span>
      </button>
    </template>
  </div>

  <!-- Filtres -->
  <div class="booking-filters-grid">
    <label class="saas-label booking-filters-search">
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
    <div class="booking-filters-actions" style="display:flex;gap:10px;">
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
      <div style="overflow-x:auto;">
        <table class="saas-table booking-table" style="min-width:980px;">
          <thead>
            <tr>
              <th>Client</th>
              <th>Chambre / Catégorie</th>
              <th>Type</th>
              <th>Arrivée</th>
              <th>Départ / Durée</th>
              <th style="text-align:right;">Montant estimé</th>
              <th>Statut</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="b in filteredBookings" :key="b.id">
              <tr class="booking-row" @click="openDetail(b)" style="cursor:pointer;">
                <td>
                  <div style="font-weight:600;color:#111827;" x-text="b.client_name"></div>
                  <div style="font-size:12px;color:#6B7280;margin-top:2px;" x-text="[b.client_phone, b.client_email].filter(Boolean).join(' · ')"></div>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <span class="booking-room-chip" x-text="'Ch. ' + b.room_number"></span>
                    <span style="font-size:12px;color:#9CA3AF;" x-text="b.room_type"></span>
                  </div>
                </td>
                <td><span class="booking-type-label" :style="{ color: typeColor(b.booking_type) }" x-text="typeLabel(b.booking_type).toUpperCase()"></span></td>
                <td style="color:#374151;" x-text="formatDate(b.check_in)"></td>
                <td>
                  <template x-if="b.booking_type === 'passage'">
                    <span style="color:#374151;" x-text="b.hours + ' heure' + (b.hours > 1 ? 's' : '')"></span>
                  </template>
                  <template x-if="b.booking_type !== 'passage'">
                    <span style="color:#374151;" x-text="formatDate(b.check_out) + ' · ' + b.nights + ' nuit' + (b.nights > 1 ? 's' : '')"></span>
                  </template>
                </td>
                <td style="font-weight:700;color:#111827;text-align:right;" x-text="formatPrice(b.total_price)"></td>
                <td><span :class="statusConfig(b.status).badge" x-text="statusConfig(b.status).label"></span></td>
                <td style="text-align:right;">
                  <div style="display:flex;gap:6px;justify-content:flex-end;">
                    <button type="button" class="booking-action-btn" @click.stop="openDetail(b)" title="Modifier">
                      <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="booking-action-btn danger" @click.stop="deleteBooking(b.id)" title="Supprimer">
                      <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-top:1px solid rgba(0,0,0,0.06);">
        <span style="color:#6B7280;font-size:13px;">Page <span x-text="page"></span> sur <span x-text="totalPages"></span></span>
        <div style="display:flex;gap:10px;">
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page-1)" :disabled="page<=1" :style="{ opacity: page<=1?'0.4':'1', cursor: page<=1?'not-allowed':'pointer' }">← Préc.</button>
          <button type="button" class="btn-saas-secondary" style="padding:8px 14px;font-size:13px;" @click="goToPage(page+1)" :disabled="page>=totalPages" :style="{ opacity: page>=totalPages?'0.4':'1', cursor: page>=totalPages?'not-allowed':'pointer' }">Suiv. →</button>
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
          <div style="background:#F9FAFB;border-radius:12px;padding:16px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
              <div>
                <p class="saas-label" style="margin-bottom:4px;">Montant total</p>
                <p style="margin:0;font-size:20px;font-weight:700;color:#111827;" x-text="formatPrice(selectedBooking?.total_amount ?? selectedBooking?.total_price)"></p>
              </div>
              <div x-show="selectedBooking?.invoice_number">
                <p class="saas-label" style="margin-bottom:4px;">Facture</p>
                <p style="margin:0;font-weight:600;color:#111827;" x-text="selectedBooking?.invoice_number"></p>
                <span :class="invoiceStatus(selectedBooking?.invoice_status).badge" style="margin-top:4px;" x-text="invoiceStatus(selectedBooking?.invoice_status).label"></span>
              </div>
            </div>

            <!-- État du paiement -->
            <div x-show="bookingPayRemaining" class="saas-pay-summary">
              <template x-if="bookingPayRemaining && bookingPayRemaining.remaining <= 0">
                <div class="saas-pay-paid">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                  Facture soldée — payée intégralement
                </div>
              </template>
              <template x-if="bookingPayRemaining && bookingPayRemaining.remaining > 0">
                <div>
                  <div class="saas-pay-progress">
                    <div class="saas-pay-progress-bar"
                      :style="'width:' + Math.min(100, bookingPayRemaining.ttc > 0 ? (bookingPayRemaining.paid / bookingPayRemaining.ttc) * 100 : 0) + '%'"></div>
                  </div>
                  <div class="saas-pay-stats">
                    <span>Encaissé : <strong style="color:var(--saas-success);" x-text="formatPrice(bookingPayRemaining.paid)"></strong></span>
                    <span>Restant dû : <strong style="color:var(--saas-warning);" x-text="formatPrice(bookingPayRemaining.remaining)"></strong></span>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Encaissement (disponible sur tous les plans — action de base, pas la Comptabilité) -->
          <div x-show="bookingPayRemaining && bookingPayRemaining.remaining > 0" class="saas-pay-card">
            <div class="saas-pay-card-header">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
              <span>Encaisser un paiement</span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
              <div>
                <label class="saas-label">Montant (FCFA)</label>
                <input type="number" min="1" step="1" class="saas-input" x-model.number="payForm.amount" placeholder="Ex: 10000" />
              </div>
              <div>
                <label class="saas-label">Méthode</label>
                <select class="saas-input" x-model="payForm.method">
                  <option value="cash">Espèces</option>
                  <option value="mobile_money">Mobile Money</option>
                  <option value="card">Carte</option>
                  <option value="bank_transfer">Virement bancaire</option>
                </select>
              </div>
              <div>
                <label class="saas-label">Type</label>
                <select class="saas-input" x-model="payForm.type">
                  <option value="full">Complet</option>
                  <option value="partial">Acompte / partiel</option>
                </select>
              </div>
            </div>

            <div x-show="payError" style="margin-top:10px;font-size:13px;color:var(--saas-danger);" x-text="payError"></div>

            <div style="margin-top:16px;">
              <button type="button" class="btn-saas-primary" style="width:100%;justify-content:center;" @click="submitBookingPayment()" :disabled="paySubmitting">
                <span x-show="!paySubmitting">Encaisser</span>
                <span x-show="paySubmitting">Enregistrement…</span>
              </button>
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
        <div style="display:flex;gap:10px;">
          <button type="button" class="btn-saas-secondary" @click="duplicateBooking(selectedBooking)">Dupliquer</button>
          <button type="button" class="btn-saas-secondary" @click="showDetail=false">Fermer</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ MODAL CRÉATION ═══ -->
  <div x-cloak x-show="showCreate" class="saas-modal-bg" @click.self="showCreate=false" @keydown.escape.window="showCreate=false">
    <form class="saas-modal type-modal" @click.stop @submit.prevent="createBooking()">
      <div class="type-modal-body">
        <!-- Colonne gauche : formulaire -->
        <div class="type-modal-form">
         <div class="type-modal-scroll">
          <div class="type-modal-title">
            <div class="type-modal-eyebrow">Nouvelle réservation</div>
            <div class="type-modal-heading">
              <span class="room-modal-dot"></span>
              <h2>Créer une réservation</h2>
              <button type="button" class="type-modal-close" @click="showCreate=false">✕</button>
            </div>
          </div>

          <div style="display:grid;gap:18px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M5 15h14"/></svg>
                  Chambre *
                </label>
                <select class="saas-input" x-model="form.room_id" required>
                  <option value="">Sélectionner une chambre</option>
                  <template x-for="room in rooms" :key="room.id">
                    <option :value="room.id" x-text="'Chambre ' + room.number + ' (' + (room.room_type?.name || room.type_name || '') + ')'"></option>
                  </template>
                </select>
              </div>
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                  Type de séjour
                </label>
                <select class="saas-input" x-model="form.booking_type">
                  <option value="nuit">Nuit</option>
                  <option value="weekend">Week-end</option>
                  <option value="passage">Passage (horaire)</option>
                </select>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  Date d'arrivée *
                </label>
                <input type="date" class="saas-input" x-model="form.check_in" required />
              </div>
              <div x-show="form.booking_type !== 'passage'">
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  Date de départ *
                </label>
                <input type="date" class="saas-input" x-model="form.check_out" :required="form.booking_type !== 'passage'" />
              </div>
              <!-- Heures si passage -->
              <div x-show="form.booking_type === 'passage'">
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Durée <span style="font-weight:400;color:#6B7280;">(heures)</span> *
                </label>
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
            <div x-show="form.public_client_id" style="padding:10px 14px;background:rgba(37,99,235,0.06);border:1px solid rgba(37,99,235,0.15);border-radius:10px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
              <span style="font-size:13px;color:#1D4ED8;font-weight:600;">Client existant sélectionné — coordonnées pré-remplies</span>
              <button type="button" @click="form.public_client_id=''" style="background:none;border:none;color:#1D4ED8;font-size:12px;font-weight:600;cursor:pointer;text-decoration:underline;">Changer</button>
            </div>

            <!-- Recherche client existant (évite de retaper les coordonnées d'un habitué) -->
            <div x-show="!form.public_client_id" style="position:relative;">
              <label class="room-field-label">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Client existant <span style="font-weight:400;color:#6B7280;">(nom ou téléphone — optionnel)</span>
              </label>
              <input type="text" class="saas-input" x-model="clientSearch" placeholder="Rechercher un client déjà enregistré…" autocomplete="off" />
              <div x-show="clientSuggestions.length > 0" style="position:absolute;z-index:20;left:0;right:0;margin-top:4px;background:white;border:1px solid rgba(0,0,0,0.08);border-radius:10px;box-shadow:0 12px 30px rgba(15,23,42,0.12);overflow:hidden;">
                <template x-for="c in clientSuggestions" :key="c.id">
                  <button type="button" @click="selectExistingClient(c)" style="display:flex;width:100%;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;background:none;border:none;border-bottom:1px solid rgba(0,0,0,0.04);cursor:pointer;text-align:left;">
                    <span style="font-size:13px;font-weight:600;color:#111827;" x-text="c.name"></span>
                    <span style="font-size:12px;color:#6B7280;" x-text="c.phone || ''"></span>
                  </button>
                </template>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  Prénom *
                </label>
                <input type="text" class="saas-input" x-model="form.first_name" required placeholder="Kouamé" :disabled="!!form.public_client_id" />
              </div>
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  Nom *
                </label>
                <input type="text" class="saas-input" x-model="form.last_name" required placeholder="Kobenan" :disabled="!!form.public_client_id" />
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  Téléphone *
                </label>
                <input type="tel" class="saas-input" x-model="form.client_phone" required placeholder="+225 07 00 00 00" :disabled="!!form.public_client_id" />
              </div>
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  Email <span style="font-weight:400;color:#6B7280;">(optionnel)</span>
                </label>
                <input type="email" class="saas-input" x-model="form.client_email" placeholder="email@exemple.com" :disabled="!!form.public_client_id" />
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/></svg>
                  Nombre de voyageurs
                </label>
                <select class="saas-input" x-model.number="form.guests_count">
                  <option value="1">1 personne</option>
                  <option value="2" selected>2 personnes</option>
                  <option value="3">3 personnes</option>
                  <option value="4">4 personnes</option>
                </select>
              </div>
              <div>
                <label class="room-field-label">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h1A1.5 1.5 0 0113 9.5v0a1.5 1.5 0 001.5 1.5h.5a2 2 0 012 2v0a2 2 0 002 2h1.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Source
                </label>
                <select class="saas-input" x-model="form.source">
                  <option value="manual">Manuel / Sur place</option>
                  <option value="phone">Téléphone</option>
                  <option value="online">En ligne</option>
                </select>
              </div>
            </div>

            <div>
              <label class="room-field-label">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Notes <span style="font-weight:400;color:#6B7280;">(optionnel)</span>
              </label>
              <textarea class="saas-input" rows="3" x-model="form.notes" style="resize:vertical;" placeholder="Demandes spéciales, arrivée tardive, préférences de couchage…"></textarea>
            </div>

            <!-- Encaissement optionnel -->
            <div style="padding:14px;background:#F9FAFB;border-radius:12px;border:1px solid rgba(0,0,0,0.06);">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0;">
                <input type="checkbox" x-model="form.record_payment" style="width:16px;height:16px;" />
                <span style="font-weight:600;color:#111827;font-size:14px;">Encaisser un paiement à la création</span>
              </label>

              <div x-show="form.record_payment" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:14px;">
                <div>
                  <label class="saas-label">Montant encaissé</label>
                  <select class="saas-input" x-model="form.payment_type">
                    <option value="full">Montant total</option>
                    <option value="partial">Acompte / montant partiel</option>
                  </select>
                </div>
                <div>
                  <label class="saas-label">Méthode de paiement</label>
                  <select class="saas-input" x-model="form.payment_method">
                    <option value="cash">Espèces</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="card">Carte</option>
                    <option value="bank_transfer">Virement bancaire</option>
                  </select>
                </div>
                <div x-show="form.payment_type === 'partial'" style="grid-column:span 2;">
                  <label class="saas-label">Montant de l'acompte (FCFA) *</label>
                  <input type="number" min="1" step="1" class="saas-input" x-model.number="form.payment_amount"
                    :required="form.record_payment && form.payment_type === 'partial'" placeholder="Ex: 10000" />
                </div>
              </div>
            </div>
          </div>

          <div x-show="createError" style="margin-top:6px;padding:14px;border-radius:14px;background:rgba(254,226,226,0.4);border:1px solid rgba(220,38,38,0.2);color:#991B1B;">
            <strong>Erreur :</strong> <span x-text="createError"></span>
          </div>
         </div>

          <div class="type-modal-footer">
            <button type="button" class="btn-saas-secondary" @click="showCreate=false" :disabled="createLoading">Annuler</button>
            <button type="submit" class="btn-saas-primary" :disabled="createLoading">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <span x-show="!createLoading">Créer la réservation</span>
              <span x-show="createLoading">Création…</span>
            </button>
          </div>
        </div>

        <!-- Colonne droite : aperçu live -->
        <div class="type-preview-panel">
          <div class="type-preview-live"><span class="type-preview-dot"></span> Fiche de facturation en direct</div>

          <div class="type-preview-card">
            <div class="room-preview-image" :style="selectedRoomPhotoUrl ? { backgroundImage: 'url(' + selectedRoomPhotoUrl + ')' } : {}">
              <span class="room-preview-badge">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span x-text="selectedRoomForBooking ? 'Chambre ' + selectedRoomForBooking.number : 'Chambre —'"></span>
              </span>
              <span class="room-preview-status-pill" style="color:#92400E;background:rgba(217,119,6,0.15);" x-text="typeLabel(form.booking_type).toUpperCase()"></span>
            </div>
            <div class="type-preview-info">
              <span class="booking-source-pill">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h1A1.5 1.5 0 0113 9.5v0a1.5 1.5 0 001.5 1.5h.5a2 2 0 012 2v0a2 2 0 002 2h1.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="sourceLabel(form.source)"></span>
              </span>

              <div class="booking-preview-section-label" style="margin-top:16px;">Fiche client</div>
              <div class="booking-client-row">
                <span class="booking-client-avatar">?</span>
                <div>
                  <div class="booking-client-name" x-text="bookingClientName || 'Client non renseigné'"></div>
                  <div class="booking-client-sub" x-text="form.client_phone || 'Téléphone obligatoire'"></div>
                </div>
              </div>

              <div class="booking-preview-section-label" style="margin-top:18px;">Calcul des prestations</div>
              <div class="booking-calc-box">
                <div class="booking-calc-row">
                  <span>Tarif de la catégorie :</span>
                  <strong x-text="formatPrice(bookingRate)"></strong>
                </div>
                <div class="booking-calc-row">
                  <span>Durée du séjour :</span>
                  <strong x-text="bookingDurationLabel"></strong>
                </div>
                <div class="booking-calc-row">
                  <span>Nombre d'occupants :</span>
                  <strong x-text="(form.guests_count || 1) + ' voyageur' + (form.guests_count > 1 ? 's' : '')"></strong>
                </div>
                <div class="booking-calc-row total">
                  <span>Total estimé :</span>
                  <strong x-text="formatPrice(bookingEstimatedTotal)"></strong>
                </div>
              </div>
            </div>
          </div>

          <div class="type-financial-panel">
            <div class="type-financial-title" style="display:flex;align-items:center;gap:8px;">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-.34-.02-.675-.058-1.006z"/></svg>
              PMS intégration sécurisée
            </div>
            <div style="font-size:13px;line-height:1.6;color:rgba(255,255,255,0.75);">
              La création affectera cette chambre
              <span x-text="selectedRoomForBooking ? ('n°' + selectedRoomForBooking.number) : ''"></span>
              comme occupée ou réservée selon la date choisie.
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Toast -->
  <div x-show="toast" style="position:fixed;bottom:24px;right:24px;z-index:200;">
    <div :style="toast?.type === 'success' ? 'background:#DCFCE7;color:#166534;' : 'background:#FEE2E2;color:#991B1B;'"
      style="padding:14px 20px;border-radius:14px;box-shadow:0 18px 50px rgba(15,23,42,0.18);min-width:260px;">
      <p style="margin:0;font-size:14px;font-weight:600;" x-text="toast?.msg"></p>
    </div>
  </div>
</div>
