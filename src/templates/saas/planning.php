<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>

<?php $pageJs = 'saas-planning'; $pageCss = 'saas-planning'; ?>
<div x-data="planningPage('<?= $base_url ?>')"
     x-init="init()"
     @keydown.escape.window="closeDetail()">

  <!-- HEADER -->
  <div class="plan-header-row" style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 class="plan-title" style="font-size:26px;font-weight:700;color:#111827;margin:0;font-family:Georgia,serif;">Planning des Chambres</h1>
      <p class="plan-subtitle" style="font-size:13px;color:#9CA3AF;margin:8px 0 0;">Calendrier interactif d'attribution des chambres et de suivi des séjours.</p>
    </div>
    <div class="plan-view-toggle">
      <button type="button" class="plan-view-btn" :class="{ active: viewMode === 'week' }" @click="viewMode = 'week'">Semaine</button>
      <button type="button" class="plan-view-btn" :class="{ active: viewMode === 'month' }" @click="viewMode = 'month'">Mois</button>
    </div>
  </div>

  <!-- Légende des statuts -->
  <div class="plan-legend-row">
    <?php
      $statuses = [
        ['confirmed',  '#16a34a', 'Confirmée'],
        ['checked_in', '#2563EB', 'Arrivée'],
        ['pending',    '#D97706', 'En attente'],
        ['cancelled',  '#DC2626', 'Annulée'],
      ];
      foreach ($statuses as [$s, $c, $l]):
    ?>
    <div class="plan-legend-item">
      <div class="plan-legend-dot" style="background:<?= $c ?>;"></div>
      <span><?= $l ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Barre de navigation + action -->
  <div class="plan-toolbar">
    <span class="plan-toolbar-label" x-text="viewMode === 'week' ? weekLabel : monthLabel"></span>

    <div class="plan-toolbar-actions">
      <template x-if="viewMode === 'week'">
        <div style="display:flex;gap:8px;">
          <button @click="prevWeek()" class="btn-saas-secondary" style="padding:8px 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <button @click="goTodayWeek()" class="btn-saas-secondary" style="padding:8px 14px;font-size:12px;">Aujourd'hui</button>
          <button @click="nextWeek()" class="btn-saas-secondary" style="padding:8px 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </template>
      <template x-if="viewMode === 'month'">
        <div style="display:flex;gap:8px;">
          <button @click="prevMonth()" class="btn-saas-secondary" style="padding:8px 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <button @click="goToday()" class="btn-saas-secondary" style="padding:8px 14px;font-size:12px;">Aujourd'hui</button>
          <button @click="nextMonth()" class="btn-saas-secondary" style="padding:8px 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </template>

      <a href="<?= $base_url ?>/saas/bookings" class="btn-saas-primary plan-add-btn">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span class="plan-add-btn-label">Réservation</span>
      </a>
    </div>
  </div>

  <!-- Erreur -->
  <div x-show="error"
       style="padding:12px 16px;background:#FEF3C7;border:1px solid rgba(217,119,6,0.3);border-radius:10px;color:#92400E;font-size:13px;margin-bottom:16px;"
       x-text="error">
  </div>

  <!-- Chargement -->
  <div x-show="loading" class="saas-card" style="padding:40px;text-align:center;">
    <div style="width:32px;height:32px;border-radius:50%;border:3px solid rgba(201,168,76,0.2);border-top-color:#C9A84C;animation:spin 0.8s linear infinite;margin:0 auto 12px;"></div>
    <div style="font-size:14px;color:#9CA3AF;">Chargement du planning...</div>
  </div>

  <!-- Planning hebdomadaire (timeline chambres) -->
  <div x-show="!loading && viewMode === 'week'" class="saas-card plan-week-card">
    <div class="plan-week-grid">
      <!-- Ligne d'en-tête -->
      <div class="plan-week-row plan-week-header-row">
        <div class="plan-week-roomcell plan-week-head-label">Chambre / Catégorie</div>
        <template x-for="d in weekDays" :key="d.ymd">
          <div class="plan-week-daycell plan-week-daycol" :class="{ today: d.isToday }">
            <span x-text="d.dow + ' ' + String(d.dayNum).padStart(2,'0')"></span>
          </div>
        </template>
        <div class="plan-week-restcell plan-week-head-label">Reste de la semaine</div>
      </div>

      <template x-if="!rooms.length">
        <div class="plan-week-empty">Aucune chambre configurée pour le moment.</div>
      </template>

      <!-- Lignes chambres -->
      <template x-for="room in rooms" :key="room.id">
        <div class="plan-week-row">
          <div class="plan-week-roomcell">
            <div class="plan-week-room-number" x-text="'Ch. ' + room.number"></div>
            <div class="plan-week-room-type" x-text="room.room_type?.name || ''"></div>
          </div>

          <template x-for="d in weekDays" :key="d.ymd">
            <div class="plan-week-daycell" :class="{ today: d.isToday }"></div>
          </template>

          <template x-for="bar in getRoomWeekBars(room.id)" :key="bar.booking.id">
            <div class="plan-week-bar"
                 :style="{ gridColumn: bar.colStart + ' / ' + bar.colEnd, gridRow: 1, background: chipColor(bar.booking.status) }"
                 @click="openDetail(bar.booking)"
                 :title="bar.booking.client_name">
              <span x-text="'Séjour ' + bar.booking.client_name"></span>
              <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
          </template>

          <template x-if="getRoomStatusBlock(room)">
            <div class="plan-week-status-block" :class="'status-' + room.status"
                 :style="{ gridColumn: getRoomStatusBlock(room).col + ' / ' + (getRoomStatusBlock(room).col + 1), gridRow: 1 }">
              <svg x-show="getRoomStatusBlock(room).kind === 'cleaning'" xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17l10-10 3 3L9 20l-4 1 1-4z"/></svg>
              <svg x-show="getRoomStatusBlock(room).kind === 'maintenance'" xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a1 1 0 00.86 1.5h18.64a1 1 0 00.86-1.5L13.71 3.86a1 1 0 00-1.72 0z"/></svg>
              <span x-text="getRoomStatusBlock(room).label"></span>
            </div>
          </template>

          <div class="plan-week-restcell"></div>
        </div>
      </template>
    </div>
  </div>

  <!-- Calendrier mensuel -->
  <div x-show="!loading && viewMode === 'month'" class="saas-card" style="padding:0;overflow:hidden;">

    <!-- En-têtes des jours -->
    <div class="cal-month-header">
      <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $d): ?>
      <div class="cal-month-dow"><?= $d ?></div>
      <?php endforeach; ?>
    </div>

    <!-- Grille 6 semaines × 7 jours -->
    <div class="cal-month-grid">
      <template x-for="(cell, idx) in calendarCells" :key="idx">
        <div :class="'cal-month-cell' + (!cell.isCurrentMonth ? ' outside' : '') + (cell.isToday ? ' today' : '')">
          <div class="cal-cell-number" x-text="cell.day"></div>
          <div class="cal-cell-events">
            <template x-for="b in getBookingsForDay(cell.ymd).slice(0, 3)" :key="b.id">
              <div class="cal-chip"
                   :style="'background:' + chipColor(b.status)"
                   @click.stop="openDetail(b)"
                   :title="b.client_name + ' · ' + roomName(b.room_id)">
                <span class="cal-chip-name" x-text="b.client_name"></span>
                <span class="cal-chip-room" x-text="roomName(b.room_id)"></span>
              </div>
            </template>
            <div x-show="getBookingsForDay(cell.ymd).length > 3"
                 class="cal-more"
                 x-text="'+' + (getBookingsForDay(cell.ymd).length - 3) + ' autre(s)'">
            </div>
          </div>
        </div>
      </template>
    </div>

  </div>

  <!-- Modal détail réservation -->
  <div x-cloak x-show="showDetail"
       class="saas-modal-bg"
       @click.self="closeDetail()">
    <div class="saas-modal" style="max-width:480px;">
      <div class="saas-modal-header">
        <div class="modal-header-info">
          <p class="modal-eyebrow">Réservation #<span x-text="selectedBooking?.id"></span></p>
          <h2 class="modal-title-lg" style="font-size:17px;" x-text="selectedBooking?.client_name"></h2>
        </div>
        <button type="button" class="saas-modal-close" @click="closeDetail()">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:14px;">
          <div class="modal-card modal-grid">
            <div>
              <p class="modal-label">Statut</p>
              <span :class="statusBadge(selectedBooking?.status)" x-text="statusLabel(selectedBooking?.status)"></span>
            </div>
            <div>
              <p class="modal-label">Chambre</p>
              <p class="modal-value" x-text="roomName(selectedBooking?.room_id)"></p>
            </div>
          </div>

          <div class="modal-strip">
            <div class="modal-strip-row">
              <div>
                <p class="modal-label">Arrivée</p>
                <p class="modal-value" x-text="formatDate(selectedBooking?.check_in)"></p>
              </div>
              <span class="modal-strip-arrow">→</span>
              <div>
                <p class="modal-label">Départ</p>
                <p class="modal-value" x-text="formatDate(selectedBooking?.check_out)"></p>
              </div>
            </div>
          </div>

          <div class="modal-card" style="background:rgba(201,168,76,0.06);border-color:rgba(201,168,76,0.15);display:flex;align-items:center;justify-content:space-between;">
            <p class="modal-label" style="margin:0;">Montant total</p>
            <p class="modal-amount" x-text="formatPrice(selectedBooking?.total_amount ?? selectedBooking?.total_price)"></p>
          </div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <a :href="'<?= $base_url ?>/saas/bookings'" class="btn-saas-secondary">
          Voir les réservations →
        </a>
        <button @click="closeDetail()" class="btn-saas-primary">Fermer</button>
      </div>
    </div>
  </div>

</div>
