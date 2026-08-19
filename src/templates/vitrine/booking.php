<?php $base = $base_url ?? rtrim(APP_URL, '/'); ?>

<div class="booking-page" x-data="bookingPage('<?= $base ?>', <?= (int)($room_id ?? 0) ?>)" x-init="init()">

<section class="bk-hero">
  <div class="bk-hero-overlay"></div>
  <a href="<?= $base ?>/search" class="bk-hero-back"
     @click.prevent="goBackToProperty()">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    <span x-text="room?.establishment_name ? 'Retour à ' + room.establishment_name : 'Retour aux résultats'"></span>
  </a>
  <div class="bk-hero-ghost">Réserver</div>
  <div class="bk-hero-content">
    <div class="bk-hero-rule"></div>
    <span class="bk-hero-tag">Finaliser votre séjour</span>
    <h1 class="bk-hero-title">Réservez<br>en toute <em>sérénité.</em></h1>
    <p class="bk-hero-sub">Paiement sécurisé, confirmation instantanée. Annulation gratuite sous 24h.</p>
  </div>
</section>

<div class="bk-steps-bar" x-show="!booking && !error">
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

<!-- ERROR STATE (chambre introuvable ou indisponible) -->
<div x-show="error && !loading" x-transition class="bk-success">
  <div class="bk-success-icon" style="background:rgba(220,38,38,0.08);color:#DC2626;">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
  </div>
  <h2 class="bk-success-title">Chambre<br><em>indisponible</em></h2>
  <p class="bk-success-sub" x-text="error"></p>
  <div class="bk-success-btns">
    <a href="<?= $base ?>/search" class="bk-success-btn-p">Voir d'autres chambres</a>
    <a href="<?= $base ?>/" class="bk-success-btn-o">Retour à l'accueil</a>
  </div>
</div>

<!-- SUCCESS STATE -->
<div x-show="booking" x-transition class="bk-success">
  <div class="bk-success-icon">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <h2 class="bk-success-title">Réservation<br><em>confirmée !</em></h2>
  <p class="bk-success-sub" x-text="payMode === 'online' ? 'Paiement reçu. Votre séjour est confirmé et garanti.' : 'Votre réservation est enregistrée. Vous réglerez à l\'arrivée.'"></p>
  <div class="bk-success-card">
    <div class="bk-panel-row">
      <span class="bk-panel-label">Référence</span>
      <strong class="bk-success-ref" x-text="booking?.reference ?? booking?.booking_id ?? '—'"></strong>
    </div>
    <div class="bk-panel-divider"></div>
    <div class="bk-panel-row"><span class="bk-panel-label">Arrivée</span><span class="bk-panel-value" x-text="formatDate(form.check_in) || '—'"></span></div>
    <div class="bk-panel-row" x-show="!isPassage"><span class="bk-panel-label">Départ</span><span class="bk-panel-value" x-text="formatDate(form.check_out) || '—'"></span></div>
    <div class="bk-panel-row" x-show="isPassage"><span class="bk-panel-label">Durée</span><span class="bk-panel-value" x-text="form.hours + ' heure' + (form.hours > 1 ? 's' : '')"></span></div>
    <div class="bk-panel-row"><span class="bk-panel-label">Total</span><span class="bk-panel-value" x-text="formatPrice(booking?.total_amount ?? totalPrice)"></span></div>
    <div class="bk-panel-divider"></div>
    <div class="bk-panel-row">
      <span class="bk-panel-label">Paiement</span>
      <span x-text="payMode === 'online' ? '✓ Payé en ligne' : 'À régler sur place'" :style="payMode === 'online' ? 'color:#16A34A;font-weight:600;' : 'color:#6B7280;'"></span>
    </div>
  </div>
  <div class="bk-success-actions">
    <button type="button" class="bk-success-action" x-show="guestPushSupported && !guestPushEnabled" @click="enableGuestPushReminder()">
      <span>🔔</span> Recevoir un rappel
    </button>
    <p x-show="guestPushEnabled" style="color:#16A34A;font-size:13px;font-weight:600;margin:0;">✓ Rappel activé. Vous serez notifié la veille.</p>

    <a :href="bookingPdfUrl()" x-show="bookingPdfUrl()" class="bk-success-action">
      <span>📄</span> Télécharger le PDF
    </a>
  </div>
  <p class="bk-success-hint">
    Pas accès à vos emails ? Retrouvez cette réservation à tout moment depuis
    <a href="<?= $base ?>/mes-reservations">Mes réservations</a>.
  </p>

  <div class="bk-success-btns">
    <a href="<?= $base ?>/search" class="bk-success-btn-p">Découvrir d'autres séjours</a>
    <a href="<?= $base ?>/" class="bk-success-btn-o">Retour à l'accueil</a>
  </div>
</div>

<!-- MAIN 2-COL LAYOUT -->
<div class="bk-layout" x-show="!booking && !error">

  <aside class="bk-panel">
    <div class="bk-panel-img-placeholder" style="position:relative;touch-action:pan-y;"
         @touchstart.passive="touchStartX = $event.touches[0].clientX"
         @touchend="swipeRoomPhoto($event)">
      <template x-if="room && roomPhotos()[photoIdx]">
        <img :src="photoUrl(roomPhotos()[photoIdx])"
             :alt="room.type_name || 'Chambre'"
             style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;">
      </template>
      <template x-if="!room || !roomPhotos()[photoIdx]">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
      </template>
      <div x-show="room && roomPhotos().length > 1"
           style="position:absolute;bottom:8px;left:0;right:0;display:flex;justify-content:center;gap:2px;z-index:2;">
        <template x-for="(p, i) in roomPhotos()" :key="i">
          <button type="button" @click.stop="photoIdx = i"
                  style="width:26px;height:26px;padding:0;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;">
            <span :style="(photoIdx === i
                    ? 'width:8px;height:8px;background:white;'
                    : 'width:6px;height:6px;background:rgba(255,255,255,0.55);')
                    + 'border-radius:50%;display:block;box-shadow:0 0 0 1px rgba(0,0,0,0.35);transition:width 0.15s,height 0.15s;'"></span>
          </button>
        </template>
      </div>
    </div>
    <div class="bk-panel-header">
      <div class="bk-panel-rule"></div>
      <span class="bk-panel-tag">Votre sélection</span>
      <div class="bk-panel-room-name" x-text="room ? (room.type_name || room.name || 'Chambre') : '…'"></div>
      <div class="bk-panel-hotel" x-text="room ? (room.establishment_name || room.hotel_name || '') + (room.city ? ' · ' + room.city : '') : '…'"></div>
      <div class="bk-panel-meta" x-show="room && (room.capacity || room.beds_count)">
        <template x-if="room && room.capacity"><span x-text="room.capacity + ' pers. max'"></span></template>
        <template x-if="room && room.capacity && room.beds_count"><span> · </span></template>
        <template x-if="room && room.beds_count"><span x-text="room.beds_count + ' ' + (room.bed_type || 'lit(s)')"></span></template>
      </div>
      <div class="bk-panel-amenities" x-show="room && room.type_amenities && room.type_amenities.length">
        <template x-for="a in (room?.type_amenities || [])" :key="a">
          <span class="bk-panel-amenity" x-text="a"></span>
        </template>
      </div>
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
            <input type="date" class="bk-input" x-model="form.check_in" :min="todayStr()">
            <span x-show="errors.check_in" class="bk-error" x-text="errors.check_in"></span>
          </div>
          <div class="bk-field">
            <label class="bk-label">Date de départ</label>
            <input type="date" class="bk-input" x-model="form.check_out" :min="form.check_in || todayStr()">
            <span x-show="errors.check_out" class="bk-error" x-text="errors.check_out"></span>
          </div>
        </div>

        <!-- Planning de disponibilité de la chambre -->
        <div class="bk-availability">
          <div class="bk-avail-header">
            <button type="button" class="bk-avail-nav" @click="calPrevMonth()" :disabled="isCurrentCalMonth()" aria-label="Mois précédent">‹</button>
            <span class="bk-avail-month" x-text="calMonthLabel()"></span>
            <button type="button" class="bk-avail-nav" @click="calNextMonth()" aria-label="Mois suivant">›</button>
          </div>
          <div class="bk-avail-legend">
            <span><i class="bk-avail-dot bk-avail-dot-free"></i>Disponible</span>
            <span><i class="bk-avail-dot bk-avail-dot-booked"></i>Déjà réservé</span>
            <span><i class="bk-avail-dot bk-avail-dot-selected"></i>Sélectionné</span>
          </div>
          <div class="bk-avail-grid">
            <template x-for="(d, dowIdx) in ['L','M','M','J','V','S','D']" :key="dowIdx">
              <div class="bk-avail-dow" x-text="d"></div>
            </template>
            <template x-for="(cell, i) in calDays()" :key="i">
              <button type="button" class="bk-avail-cell"
                      :class="{
                        'bk-avail-empty':    !cell,
                        'bk-avail-booked':   cell && cell.booked,
                        'bk-avail-past':     cell && cell.past,
                        'bk-avail-in-range': cell && cell.inRange,
                        'bk-avail-selected': cell && (cell.date === form.check_in || cell.date === form.check_out)
                      }"
                      :disabled="!cell || cell.booked || cell.past"
                      :title="cell && cell.booked ? 'Déjà réservé' : ''"
                      @click="cell && calSelectDate(cell.date)"
                      x-text="cell ? cell.day : ''">
              </button>
            </template>
          </div>
          <p class="bk-avail-hint" x-show="availabilityLoading">Chargement du planning…</p>
          <p class="bk-avail-hint" x-show="!availabilityLoading">Cliquez sur une date d'arrivée puis de départ pour les reporter dans le formulaire.</p>
        </div>

        <!-- Nuit(s) au tarif week-end (vendredi/samedi/dimanche) incluses dans le séjour -->
        <div x-show="weekendNightsCount > 0" x-transition class="bk-weekend-notice">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m0 16v1m8.485-8.485h-1M4.515 12h-1m14.142 5.657l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M12 8a4 4 0 00-4 4c0 1.5.8 2.8 2 3.5V17h4v-1.5c1.2-.7 2-2 2-3.5a4 4 0 00-4-4z"/></svg>
          <span>
            Votre séjour inclut <strong x-text="weekendNightsCount + ' nuit' + (weekendNightsCount > 1 ? 's' : '') + ' week-end'"></strong>
            (vendredi, samedi ou dimanche), facturée<span x-text="weekendNightsCount > 1 ? 's' : ''"></span>
            <strong x-text="formatPrice(room?.weekend_price || 0)"></strong> la nuit au lieu de
            <span x-text="formatPrice(room?.base_price || 0)"></span>.
          </span>
        </div>

        <!-- Forfait passage : dates identiques -->
        <div x-show="isPassage" x-transition class="bk-passage-block">
          <div class="bk-passage-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
            Forfait passage (même jour)
          </div>
          <p class="bk-passage-hint">Vous avez sélectionné la même date pour l'arrivée et le départ. Choisissez la durée de votre séjour en heures.</p>
          <div class="bk-field" style="max-width:260px;">
            <label class="bk-label">Durée du passage</label>
            <select class="bk-input bk-select" x-model.number="form.hours">
              <option value="1">1 heure</option>
              <option value="2">2 heures</option>
              <option value="3">3 heures</option>
              <option value="4">4 heures</option>
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
            <input type="text" class="bk-input" placeholder="Kouamé" x-model="form.first_name" required
                   minlength="2" maxlength="60" autocomplete="given-name">
            <span x-show="errors.first_name" class="bk-error" x-text="errors.first_name"></span>
          </div>
          <div class="bk-field">
            <label class="bk-label">Nom</label>
            <input type="text" class="bk-input" placeholder="Kobenan" x-model="form.last_name" required
                   minlength="2" maxlength="60" autocomplete="family-name">
            <span x-show="errors.last_name" class="bk-error" x-text="errors.last_name"></span>
          </div>
        </div>
        <div class="bk-field">
          <label class="bk-label">Email</label>
          <input type="email" class="bk-input" placeholder="votre@email.com" x-model="form.email" required
                 maxlength="120" autocomplete="email" inputmode="email">
          <span x-show="errors.email" class="bk-error" x-text="errors.email"></span>
        </div>
        <div class="bk-field">
          <label class="bk-label">Téléphone</label>
          <input type="tel" class="bk-input" placeholder="+225 01 23 45 67 89" x-model="form.phone" required
                 minlength="8" maxlength="20" pattern="[0-9+\s]+" autocomplete="tel" inputmode="tel">
          <span x-show="errors.phone" class="bk-error" x-text="errors.phone"></span>
        </div>
        <div class="bk-field-row">
          <div class="bk-field">
            <label class="bk-label">Type de pièce <span style="font-weight:400;">(optionnel)</span></label>
            <select class="bk-input bk-select" x-model="form.id_doc_type">
              <option value="">Sélectionner…</option>
              <option value="CNI">Carte Nationale d'Identité</option>
              <option value="Passeport">Passeport</option>
              <option value="Autre">Autre</option>
            </select>
          </div>
          <div class="bk-field">
            <label class="bk-label">Numéro de pièce <span style="font-weight:400;">(optionnel)</span></label>
            <input type="text" class="bk-input" placeholder="Ex : CI001234567" x-model="form.id_doc_number"
                   maxlength="30">
          </div>
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

      <!-- Récapitulatif -->
      <div class="bk-label" style="margin-bottom:16px;">Récapitulatif</div>
      <div class="bk-recap-grid">
        <div class="bk-recap-item"><span class="bk-recap-label">Arrivée</span><span class="bk-recap-value" x-text="formatDate(form.check_in) || '—'"></span></div>
        <div class="bk-recap-item" x-show="!isPassage"><span class="bk-recap-label">Départ</span><span class="bk-recap-value" x-text="formatDate(form.check_out) || '—'"></span></div>
        <div class="bk-recap-item" x-show="isPassage"><span class="bk-recap-label">Durée</span><span class="bk-recap-value" x-text="form.hours + 'h'"></span></div>
        <div class="bk-recap-item"><span class="bk-recap-label">Voyageur</span><span class="bk-recap-value" x-text="form.first_name + ' ' + form.last_name"></span></div>
        <div class="bk-recap-item"><span class="bk-recap-label">Total</span><span class="bk-recap-value" x-text="formatPrice(totalPrice)"></span></div>
      </div>

      <!-- Choix du mode de paiement (uniquement si l'établissement accepte le paiement en ligne) -->
      <template x-if="onlinePaymentAvailable">
        <div>
          <div class="bk-label" style="margin-bottom:16px;">Comment souhaitez-vous payer ?</div>
          <div class="bk-payment-opts" style="margin-bottom:28px;">
            <div class="bk-pay-opt" :class="payMode==='online'?'bk-selected':''" @click="payMode='online'">
              <div class="bk-pay-radio"><div class="bk-pay-radio-dot" :style="payMode==='online'?'opacity:1;transform:scale(1)':''"></div></div>
              <div class="bk-pay-icon" style="background:#1B4332;font-size:16px;">💳</div>
              <div>
                <div class="bk-pay-name">Payer en ligne</div>
                <div class="bk-pay-sub">Paiement sécurisé via GeniusPay · Wave</div>
              </div>
            </div>
            <div class="bk-pay-opt" :class="payMode==='onsite'?'bk-selected':''" @click="payMode='onsite'">
              <div class="bk-pay-radio"><div class="bk-pay-radio-dot" :style="payMode==='onsite'?'opacity:1;transform:scale(1)':''"></div></div>
              <div class="bk-pay-icon" style="background:#6B7280;font-size:16px;">🏨</div>
              <div>
                <div class="bk-pay-name">Payer sur place</div>
                <div class="bk-pay-sub">Réglez à l'arrivée à l'hôtel · Réservation garantie</div>
              </div>
            </div>
          </div>
        </div>
      </template>
      <template x-if="!onlinePaymentAvailable && passageOnsiteOnly">
        <div class="bk-label" style="margin-bottom:16px;">Pour un passage de moins de 4h, le règlement se fait uniquement sur place à l'arrivée.</div>
      </template>
      <template x-if="!onlinePaymentAvailable && !passageOnsiteOnly">
        <div class="bk-label" style="margin-bottom:16px;">Le paiement en ligne arrive bientôt. Réglez votre séjour directement sur place pour l'instant.</div>
      </template>

      <!-- Méthode Mobile Money (uniquement si paiement en ligne) -->
      <div x-show="payMode==='online'" x-transition>
        <div class="bk-label" style="margin-bottom:16px;">Choisissez votre opérateur</div>
        <div class="bk-payment-opts" style="margin-bottom:8px;">
          <div class="bk-pay-opt" :class="payMethod==='wave'?'bk-selected':''" @click="payMethod='wave'">
            <div class="bk-pay-radio"><div class="bk-pay-radio-dot" :style="payMethod==='wave'?'opacity:1;transform:scale(1)':''"></div></div>
            <div class="bk-pay-icon" style="background:#00B9F2;">WV</div>
            <div><div class="bk-pay-name">Wave</div><div class="bk-pay-sub">Sans frais de transaction</div></div>
          </div>
        </div>
      </div>

      <!-- Info sur place -->
      <div x-show="payMode==='onsite'" x-transition class="bk-onsite-info">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="width:18px;height:18px;flex-shrink:0;color:#2D6A4F;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Votre réservation sera confirmée immédiatement. Vous réglerez le montant directement à l'hôtel le jour de votre arrivée.</span>
      </div>

      <!-- Erreur paiement -->
      <div x-show="bookingError" class="bk-payment-error" x-text="bookingError"></div>

      <div class="bk-form-nav" style="margin-top:28px;">
        <button type="button" class="bk-btn-prev" @click="step=2" :disabled="submitting">← Retour</button>
        <button type="button" class="bk-btn-next" @click="submitBooking()" :disabled="submitting">
          <span x-show="!submitting" x-text="payMode==='online' ? 'Payer ' + formatPrice(totalPrice) + ' →' : 'Confirmer la réservation →'"></span>
          <span x-show="submitting" style="display:inline-flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span x-text="payMode==='online' ? 'Redirection…' : 'Confirmation…'"></span>
          </span>
        </button>
      </div>
      <p style="font-family:'Inter',sans-serif;font-size:12px;color:rgba(27,67,50,0.38);line-height:1.7;margin-top:20px;">En confirmant, vous acceptez nos conditions d'utilisation. Annulation gratuite 24h avant l'arrivée.</p>
    </div>

  </div>
</div>

</div>
