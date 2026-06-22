<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="booking-page" x-data="bookingPage('<?= $base ?>', <?= (int)($room_id ?? 0) ?>)" x-init="init()">

<section class="bk-hero">
  <div class="bk-hero-overlay"></div>
  <div class="bk-hero-ghost">Réserver</div>
  <div class="bk-hero-content">
    <div class="bk-hero-rule"></div>
    <span class="bk-hero-tag">Finaliser votre séjour</span>
    <h1 class="bk-hero-title">Réservez<br>en toute <em>sérénité.</em></h1>
    <p class="bk-hero-sub">Paiement sécurisé, confirmation instantanée. Annulation gratuite sous 24h.</p>
  </div>
</section>

<div class="bk-steps-bar" x-show="!booking">
  <div class="bk-step-tab" :class="step===1?'bk-active':step>1?'bk-done':''">
    <span class="bk-step-num">1</span>
    <span>Dates &amp; chambre</span>
  </div>
  <div class="bk-step-sep"></div>
  <div class="bk-step-tab" :class="step===2?'bk-active':step>2?'bk-done':''">
    <span class="bk-step-num">2</span>
    <span>Vos coordonnées</span>
  </div>
  <div class="bk-step-sep"></div>
  <div class="bk-step-tab" :class="step===3?'bk-active':''">
    <span class="bk-step-num">3</span>
    <span>Paiement &amp; confirmation</span>
  </div>
</div>

<!-- SUCCESS STATE -->
<div x-show="booking" x-transition class="bk-success">
  <div class="bk-success-icon">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <h2 class="bk-success-title">Réservation<br><em>confirmée !</em></h2>
  <p class="bk-success-sub">Votre séjour est bien enregistré. Un email de confirmation vous a été envoyé.</p>
  <div class="bk-success-card">
    <div class="bk-panel-row">
      <span class="bk-panel-label">Référence</span>
      <strong class="bk-success-ref" x-text="booking?.reference ?? booking?.booking_id ?? '—'"></strong>
    </div>
    <div class="bk-panel-divider"></div>
    <div class="bk-panel-row"><span class="bk-panel-label">Arrivée</span><span class="bk-panel-value" x-text="formatDate(form.check_in) || '—'"></span></div>
    <div class="bk-panel-row"><span class="bk-panel-label">Départ</span><span class="bk-panel-value" x-text="formatDate(form.check_out) || '—'"></span></div>
    <div class="bk-panel-row"><span class="bk-panel-label">Total</span><span class="bk-panel-value" x-text="formatPrice(totalPrice)"></span></div>
  </div>
  <div class="bk-success-btns">
    <a href="<?= $base ?>/search" class="bk-success-btn-p">Découvrir d'autres séjours</a>
    <a href="<?= $base ?>/" class="bk-success-btn-o">Retour à l'accueil</a>
  </div>
</div>

<!-- MAIN 2-COL LAYOUT -->
<div class="bk-layout" x-show="!booking">

  <aside class="bk-panel">
    <div class="bk-panel-img-placeholder">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
    </div>
    <div class="bk-panel-header">
      <div class="bk-panel-rule"></div>
      <span class="bk-panel-tag">Votre sélection</span>
      <div class="bk-panel-room-name" x-text="room ? (room.type_name || room.name || 'Chambre') : '…'"></div>
      <div class="bk-panel-hotel" x-text="room ? (room.establishment_name || room.hotel_name || '') + (room.city ? ' · ' + room.city : '') : '…'"></div>
    </div>
    <div class="bk-panel-divider"></div>
    <div class="bk-panel-row">
      <span class="bk-panel-label">Arrivée</span>
      <span class="bk-panel-value" x-text="form.check_in || '—'"></span>
    </div>
    <div class="bk-panel-row">
      <span class="bk-panel-label">Départ</span>
      <span class="bk-panel-value" x-text="form.check_out || '—'"></span>
    </div>
    <div class="bk-panel-row">
      <span class="bk-panel-label">Durée</span>
      <span class="bk-panel-value" x-text="isPassage ? (form.hours + ' heure' + (form.hours > 1 ? 's' : '')) : (nights ? nights + ' nuit' + (nights > 1 ? 's' : '') : '—')"></span>
    </div>
    <div class="bk-panel-divider"></div>
    <div class="bk-panel-row">
      <span class="bk-panel-label" x-text="isPassage ? 'Prix / heure' : 'Prix / nuit'"></span>
      <span class="bk-panel-value" x-text="isPassage ? (room?.passage_price ? formatPrice(room.passage_price) : '—') : (room ? formatPrice(room.base_price) : '—')"></span>
    </div>
    <div class="bk-panel-divider"></div>
    <div class="bk-panel-row">
      <span class="bk-panel-total-label">Total</span>
      <span class="bk-panel-total-value" x-text="formatPrice(totalPrice)"></span>
    </div>
  </aside>

  <div class="bk-form-area">

    <!-- STEP 1 -->
    <div x-show="step===1" x-transition>
      <div class="bk-section-title">Choisissez vos <em>dates</em></div>
      <div class="bk-section-rule"></div>
      <form class="bk-form" @submit.prevent="nextStep()">
        <div class="bk-field-row">
          <div class="bk-field">
            <label class="bk-label">Date d'arrivée</label>
            <input type="date" class="bk-input" x-model="form.check_in">
            <span x-show="errors.check_in" class="bk-error" x-text="errors.check_in"></span>
          </div>
          <div class="bk-field">
            <label class="bk-label">Date de départ</label>
            <input type="date" class="bk-input" x-model="form.check_out">
            <span x-show="errors.check_out" class="bk-error" x-text="errors.check_out"></span>
          </div>
        </div>

        <!-- Forfait passage : dates identiques -->
        <div x-show="isPassage" x-transition class="bk-passage-block">
          <div class="bk-passage-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
            Forfait passage — même jour
          </div>
          <p class="bk-passage-hint">Vous avez sélectionné la même date pour l'arrivée et le départ. Choisissez la durée de votre séjour en heures.</p>
          <div class="bk-field" style="max-width:260px;">
            <label class="bk-label">Durée du passage</label>
            <select class="bk-input bk-select" x-model.number="form.hours">
              <option value="1">1 heure</option>
              <option value="2">2 heures</option>
              <option value="3">3 heures</option>
              <option value="4">4 heures</option>
              <option value="6">6 heures</option>
              <option value="8">8 heures</option>
              <option value="12">12 heures</option>
            </select>
            <span x-show="errors.hours" class="bk-error" x-text="errors.hours"></span>
          </div>
          <div x-show="room && !room.passage_price" class="bk-passage-warn">
            Tarif passage non configuré pour cette chambre. Le montant sera précisé à la confirmation.
          </div>
        </div>

        <div class="bk-form-nav">
          <button type="submit" class="bk-btn-next">Étape suivante : Coordonnées →</button>
        </div>
      </form>
    </div>

    <!-- STEP 2 -->
    <div x-show="step===2" x-transition>
      <div class="bk-section-title">Vos <em>coordonnées</em></div>
      <div class="bk-section-rule"></div>
      <form class="bk-form" @submit.prevent="step=3">
        <div class="bk-field-row">
          <div class="bk-field">
            <label class="bk-label">Prénom</label>
            <input type="text" class="bk-input" placeholder="Kouamé" x-model="form.first_name" required>
            <span x-show="errors.first_name" class="bk-error" x-text="errors.first_name"></span>
          </div>
          <div class="bk-field">
            <label class="bk-label">Nom</label>
            <input type="text" class="bk-input" placeholder="Kobenan" x-model="form.last_name" required>
            <span x-show="errors.last_name" class="bk-error" x-text="errors.last_name"></span>
          </div>
        </div>
        <div class="bk-field">
          <label class="bk-label">Email</label>
          <input type="email" class="bk-input" placeholder="votre@email.com" x-model="form.email" required>
          <span x-show="errors.email" class="bk-error" x-text="errors.email"></span>
        </div>
        <div class="bk-field">
          <label class="bk-label">Téléphone</label>
          <input type="tel" class="bk-input" placeholder="+225 01 23 45 67 89" x-model="form.phone" required>
          <span x-show="errors.phone" class="bk-error" x-text="errors.phone"></span>
        </div>
        <div class="bk-form-nav">
          <button type="button" class="bk-btn-prev" @click="step=1">← Retour</button>
          <button type="submit" class="bk-btn-next">Étape suivante : Paiement →</button>
        </div>
      </form>
    </div>

    <!-- STEP 3 -->
    <div x-show="step===3" x-transition>
      <div class="bk-section-title">Paiement &amp; <em>confirmation</em></div>
      <div class="bk-section-rule"></div>

      <div class="bk-label" style="margin-bottom:16px;">Récapitulatif</div>
      <div class="bk-recap-grid">
        <div class="bk-recap-item"><span class="bk-recap-label">Arrivée</span><span class="bk-recap-value" x-text="formatDate(form.check_in) || '—'"></span></div>
        <div class="bk-recap-item"><span class="bk-recap-label">Départ</span><span class="bk-recap-value" x-text="formatDate(form.check_out) || '—'"></span></div>
        <div class="bk-recap-item"><span class="bk-recap-label">Voyageur</span><span class="bk-recap-value" x-text="form.first_name + ' ' + form.last_name"></span></div>
        <div class="bk-recap-item"><span class="bk-recap-label">Total</span><span class="bk-recap-value" x-text="formatPrice(totalPrice)"></span></div>
      </div>

      <div class="bk-label" style="margin-bottom:16px;">Mode de paiement</div>
      <div class="bk-payment-opts">
        <div class="bk-pay-opt" :class="payMethod==='orange'?'bk-selected':''" @click="payMethod='orange'">
          <div class="bk-pay-radio"><div class="bk-pay-radio-dot"></div></div>
          <div class="bk-pay-icon" style="background:#FF6B00;">OM</div>
          <div><div class="bk-pay-name">Orange Money</div><div class="bk-pay-sub">Paiement instantané via Orange CI</div></div>
        </div>
        <div class="bk-pay-opt" :class="payMethod==='wave'?'bk-selected':''" @click="payMethod='wave'">
          <div class="bk-pay-radio"><div class="bk-pay-radio-dot"></div></div>
          <div class="bk-pay-icon" style="background:#00B9F2;">WV</div>
          <div><div class="bk-pay-name">Wave</div><div class="bk-pay-sub">Sans frais de transaction</div></div>
        </div>
        <div class="bk-pay-opt" :class="payMethod==='mtn'?'bk-selected':''" @click="payMethod='mtn'">
          <div class="bk-pay-radio"><div class="bk-pay-radio-dot"></div></div>
          <div class="bk-pay-icon" style="background:#FFCB00;color:#1B1B1B;">MTN</div>
          <div><div class="bk-pay-name">MTN Mobile Money</div><div class="bk-pay-sub">Disponible 24h/24</div></div>
        </div>
      </div>

      <div class="bk-form-nav" style="margin-top:28px;">
        <button type="button" class="bk-btn-prev" @click="step=2">← Retour</button>
        <button type="button" class="bk-btn-next" @click="submitBooking()">
          Confirmer et payer — <span x-text="formatPrice(totalPrice)"></span>
        </button>
      </div>
      <p style="font-family:'Inter',sans-serif;font-size:12px;color:rgba(27,67,50,0.38);line-height:1.7;margin-top:20px;">En confirmant, vous acceptez nos conditions d'utilisation. Annulation gratuite 24h avant l'arrivée.</p>
    </div>

  </div>
</div>

</div>
