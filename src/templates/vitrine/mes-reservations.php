<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="mr-page" x-data="myBookingsPage('<?= $base ?>')" x-init="init()">

  <section class="mr-hero">
    <div class="mr-hero-bg-word">Réservations</div>
    <div class="mr-hero-content">
      <div class="mr-hero-rule"></div>
      <span class="mr-hero-tag">Sans email, sans compte</span>
      <h1 class="mr-hero-title">Mes<br><em>réservations.</em></h1>
      <p class="mr-hero-sub">Retrouvez et téléchargez vos réservations à tout moment, même sans consulter vos emails.</p>
    </div>
  </section>

  <section class="mr-main">

    <!-- Chargement initial (relecture de l'historique local de l'appareil) -->
    <div class="mr-loading" x-show="loading">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <span>Recherche de vos réservations sur cet appareil…</span>
    </div>

    <template x-if="!loading">
    <div>

      <!-- Liste des réservations trouvées -->
      <div class="mr-list" x-show="bookings.length">
        <template x-for="b in bookings" :key="b.id">
          <div class="mr-card">
            <div class="mr-card-top">
              <div>
                <div class="mr-card-estab" x-text="b.establishment_name || '—'"></div>
                <div class="mr-card-room" x-text="(b.room_type || '') + (b.room_number ? ' · N° ' + b.room_number : '')"></div>
              </div>
              <span class="mr-badge" :class="'mr-badge-' + b.status" x-text="statusLabel(b.status)"></span>
            </div>
            <div class="mr-card-grid">
              <div class="mr-card-item"><span>Arrivée</span><strong x-text="formatDate(b.check_in)"></strong></div>
              <div class="mr-card-item" x-show="b.booking_type !== 'passage'"><span>Départ</span><strong x-text="formatDate(b.check_out)"></strong></div>
              <div class="mr-card-item" x-show="b.booking_type === 'passage'"><span>Durée</span><strong x-text="(b.hours || '?') + ' heure(s)'"></strong></div>
              <div class="mr-card-item"><span>Total</span><strong x-text="formatPrice(b.total_amount)"></strong></div>
            </div>
            <div class="mr-card-actions">
              <a :href="pdfUrl(b)" class="mr-card-btn">📄 Télécharger le PDF</a>
              <button type="button" class="mr-card-btn mr-card-btn-cancel" x-show="canCancel(b) && cancelConfirmId !== b.id" @click="askCancel(b.id)">Annuler la réservation</button>
            </div>

            <div class="mr-cancel-confirm" x-show="cancelConfirmId === b.id" x-transition>
              <p class="mr-cancel-confirm-text">Confirmer l'annulation de cette réservation ? Cette action est définitive.</p>
              <p class="mr-search-error" x-show="cancelError" x-text="cancelError"></p>
              <div class="mr-card-actions">
                <button type="button" class="mr-card-btn mr-card-btn-cancel" :disabled="cancelling" @click="confirmCancel(b)">
                  <span x-show="!cancelling">Oui, annuler</span>
                  <span x-show="cancelling">Annulation…</span>
                </button>
                <button type="button" class="mr-card-btn" :disabled="cancelling" @click="cancelConfirmId = null; cancelError = null">Non, garder</button>
              </div>
            </div>
          </div>
        </template>
      </div>

      <button type="button" class="mr-toggle-search" x-show="bookings.length && !showSearchForm" @click="showSearchForm = true">
        Chercher une autre réservation (email + téléphone) →
      </button>

      <!-- Formulaire recherche email OU téléphone (affiché par défaut si aucune réservation locale) -->
      <p class="mr-empty" x-show="!bookings.length && showSearchForm" style="margin-bottom:16px;">
        Aucune réservation trouvée sur cet appareil. Retrouvez-la avec l'email ou le téléphone utilisé lors de la réservation :
      </p>
      <div class="mr-search-card" x-show="showSearchForm" x-transition>
        <h2 class="mr-search-title">Retrouver une réservation</h2>
        <p class="mr-search-sub">Renseignez l'email <strong>ou</strong> le téléphone utilisé lors de la réservation (un seul suffit).</p>
        <form class="mr-search-form" @submit.prevent="search()">
          <div class="mr-field">
            <label class="mr-label">Email</label>
            <input type="email" class="mr-input" placeholder="votre@email.com" x-model="searchForm.email">
          </div>
          <div class="mr-field">
            <label class="mr-label">Téléphone</label>
            <input type="tel" class="mr-input" placeholder="+225 01 23 45 67 89" x-model="searchForm.phone">
          </div>
          <p class="mr-search-error" x-show="searchError" x-text="searchError"></p>
          <button type="submit" class="mr-search-btn" :disabled="searching">
            <span x-show="!searching">Rechercher →</span>
            <span x-show="searching">Recherche…</span>
          </button>
        </form>
        <button type="button" class="mr-cancel-search" x-show="bookings.length" @click="showSearchForm = false">Annuler</button>
      </div>

    </div>
    </template>

  </section>

</div>
