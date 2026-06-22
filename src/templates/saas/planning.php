<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>

<?php $pageJs = 'saas-planning'; $pageCss = 'saas-planning'; ?>
<div x-data="planningPage('<?= $base_url ?>')"
     x-init="init()"
     @keydown.escape.window="closeDetail()">

  <!-- HEADER -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0 0 4px;">Planning</h1>
      <p style="font-size:13px;color:#9CA3AF;margin:0;text-transform:capitalize;" x-text="monthLabel"></p>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

      <!-- Légende statuts -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-right:8px;">
        <?php
          $statuses = [
            ['confirmed',  '#16a34a', 'Confirmée'],
            ['checked_in', '#2563EB', 'Arrivée'],
            ['pending',    '#D97706', 'En attente'],
            ['cancelled',  '#DC2626', 'Annulée'],
          ];
          foreach ($statuses as [$s, $c, $l]):
        ?>
        <div style="display:flex;align-items:center;gap:5px;">
          <div style="width:10px;height:10px;border-radius:3px;background:<?= $c ?>;flex-shrink:0;"></div>
          <span style="font-size:11px;color:#6B7280;"><?= $l ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Navigation mois -->
      <button @click="prevMonth()" class="btn-saas-secondary" style="padding:8px 12px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <button @click="goToday()" class="btn-saas-secondary" style="padding:8px 14px;font-size:12px;">Aujourd'hui</button>
      <button @click="nextMonth()" class="btn-saas-secondary" style="padding:8px 12px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>

      <a href="<?= $base_url ?>/saas/bookings" class="btn-saas-primary">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Réservation
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

  <!-- Calendrier mensuel -->
  <div x-show="!loading" class="saas-card" style="padding:0;overflow:hidden;">

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
        <div>
          <p style="font-size:12px;color:#9CA3AF;margin:0;">
            Réservation #<span x-text="selectedBooking?.id"></span>
          </p>
          <h2 style="font-size:17px;font-weight:700;color:#111827;margin:4px 0 0;"
              x-text="selectedBooking?.client_name"></h2>
        </div>
        <button @click="closeDetail()"
                style="background:none;border:none;cursor:pointer;padding:4px;color:#6B7280;">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:16px;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:13px;color:#6B7280;">Statut</span>
            <span :class="statusBadge(selectedBooking?.status)"
                  x-text="statusLabel(selectedBooking?.status)"></span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:13px;color:#6B7280;">Chambre</span>
            <span style="font-size:13px;font-weight:600;color:#111827;"
                  x-text="roomName(selectedBooking?.room_id)"></span>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
              <div style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">Check-in</div>
              <div style="font-size:13px;font-weight:600;color:#111827;"
                   x-text="formatDate(selectedBooking?.check_in)"></div>
            </div>
            <div style="background:#FAFAFA;border-radius:10px;padding:12px;">
              <div style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">Check-out</div>
              <div style="font-size:13px;font-weight:600;color:#111827;"
                   x-text="formatDate(selectedBooking?.check_out)"></div>
            </div>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;background:rgba(201,168,76,0.06);border:1px solid rgba(201,168,76,0.15);border-radius:10px;">
            <span style="font-size:13px;color:#6B7280;">Montant total</span>
            <span style="font-size:16px;font-weight:800;color:#1B4332;"
                  x-text="formatPrice(selectedBooking?.total_amount ?? selectedBooking?.total_price)"></span>
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
