<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs  = 'saas-settings'; ?>
<?php $pageCss = 'saas-settings'; ?>

<?php if (AGENTS_ENABLED): ?>
<script src="<?= $base_url ?>/assets/vendor/qrcode/qrcode.min.js"></script>
<?php endif; ?>

<div x-data="settingsPage('<?= $base_url ?>', <?= ONLINE_PAYMENTS_ENABLED ? 'true' : 'false' ?>)" x-init="init()">

  <!-- En-tête -->
  <div style="margin-bottom:20px;">
    <h1 class="stg-page-title">Paramètres</h1>
    <p class="stg-page-sub">Gérez les informations de votre établissement et abonnement</p>
  </div>

  <div class="stg-layout">

    <!-- Navigation latérale -->
    <nav class="stg-side-nav">
      <button x-show="canSeeSettings" @click="activeTab='general'"
              :class="activeTab==='general' ? 'stg-side-item-active' : ''"
              class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span>Général</span>
      </button>
      <button x-show="canSeeSettings && !creatingEstab" @click="activeTab='team'"
              :class="activeTab==='team' ? 'stg-side-item-active' : ''"
              class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>Membres</span>
      </button>
      <button x-show="canSeeSettings" @click="activeTab='subscription'"
              :class="activeTab==='subscription' ? 'stg-side-item-active' : ''"
              class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        <span>Abonnement</span>
      </button>
      <button @click="activeTab='account'"
              :class="activeTab==='account' ? 'stg-side-item-active' : ''"
              class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <span>Compte</span>
      </button>
      <button @click="activeTab='notifications'"
              :class="activeTab==='notifications' ? 'stg-side-item-active' : ''"
              class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span>Notifications</span>
      </button>
    </nav>

    <!-- Contenu -->
    <div class="stg-content">

    <!-- Bannière nouvel établissement -->
    <div x-show="isNewEstab && !loading" class="stg-welcome" x-cloak>
      <div class="stg-welcome-emoji">👋</div>
      <div>
        <div class="stg-welcome-title">Bienvenue sur Afristay !</div>
        <div class="stg-welcome-desc">Commencez par configurer votre établissement. Remplissez le formulaire ci-dessous, puis cliquez sur « Créer l'établissement ».</div>
      </div>
    </div>

    <!-- Bannière ajout d'un établissement supplémentaire (plan Premium+) -->
    <div x-show="addingEstab && !loading" class="stg-welcome" x-cloak>
      <div class="stg-welcome-emoji">🏨</div>
      <div>
        <div class="stg-welcome-title">Nouvel établissement</div>
        <div class="stg-welcome-desc">Remplissez le formulaire ci-dessous pour ajouter cet établissement à votre compte, puis cliquez sur « Créer l'établissement ».</div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════
         ONGLET : Général
         ════════════════════════════════════════════════════════ -->
    <div x-show="activeTab==='general'">

      <div class="stg-section-head" style="margin-bottom:16px;">
        <div class="stg-section-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div style="flex:1;">
          <h3 class="stg-section-title" x-text="creatingEstab ? 'Créer votre établissement' : 'Informations de l\'établissement'"></h3>
          <p class="stg-section-sub">Ces informations apparaissent sur votre vitrine publique</p>
        </div>
        <?php if (AGENTS_ENABLED): ?>
        <button type="button" class="btn-saas-secondary" x-show="!creatingEstab" @click="openMyQrCode()">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4zm0 5h4v11h-4V9zM9 4h1v6H9V4zM4 11h6v1H4v-1zm5 3h1v6H9v-6zm-5 2h1v1H4v-1z"/></svg>
          Mon QR code
        </button>
        <?php endif; ?>
      </div>

      <!-- Carrousel : navigation entre les cartes -->
      <div class="stg-carousel-nav">
        <button type="button" class="btn-saas-secondary stg-carousel-btn" @click="generalPrev()" :disabled="generalCardIndex === 0">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          <span>Précédent</span>
        </button>

        <div class="stg-carousel-dots">
          <template x-for="(key, i) in generalSections" :key="key">
            <button type="button" class="stg-carousel-dot" :class="i === generalCardIndex ? 'stg-carousel-dot-active' : ''"
                    @click="generalCardIndex = i" :aria-label="generalSectionLabels[key]" :title="generalSectionLabels[key]"></button>
          </template>
        </div>

        <button type="button" class="btn-saas-secondary stg-carousel-btn" @click="generalNext()" :disabled="generalCardIndex === generalSections.length - 1">
          <span x-show="generalCardIndex < generalSections.length - 1">Suivant : <span x-text="generalSectionLabels[generalSections[generalCardIndex + 1]]"></span></span>
          <span x-show="generalCardIndex === generalSections.length - 1">Dernière section</span>
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>

      <!-- Carte : Identité -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'identity'">
        <div class="stg-group-label">Identité</div>
        <div class="stg-form-grid">
          <div class="stg-col-2">
            <label class="saas-label">Nom de l'établissement</label>
            <div class="stg-input-wrap">
              <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              <input class="saas-input stg-input-has-icon" x-model="form.name" placeholder="Ex : Hôtel Le Président">
            </div>
          </div>
          <div>
            <label class="saas-label">Type</label>
            <select class="saas-input" x-model="form.type">
              <option value="hotel">Hôtel</option>
              <option value="residence">Résidence</option>
              <option value="villa">Villa</option>
            </select>
          </div>
          <div>
            <label class="saas-label">Ville</label>
            <div class="stg-input-wrap">
              <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <input class="saas-input stg-input-has-icon" x-model="form.city" placeholder="Ex : Abidjan">
            </div>
          </div>
          <div class="stg-col-2">
            <label class="saas-label">Adresse</label>
            <div class="stg-input-wrap">
              <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
              <input class="saas-input stg-input-has-icon" x-model="form.address" placeholder="Ex : Plateau, Rue des Jardins">
            </div>
          </div>
        </div>
        <div class="stg-form-actions" x-show="!creatingEstab">
          <button class="btn-saas-primary" @click="saveIdentity()" :disabled="savingIdentity">
            <div x-show="savingIdentity" class="stg-btn-spinner"></div>
            <span x-show="!savingIdentity">Enregistrer</span>
          </button>
        </div>
      </div>

      <!-- Carte : Localisation (auto-enregistrée dès la localisation) -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'location'">
        <div class="stg-group-label">Localisation</div>
        <div class="stg-locate">
          <div class="stg-locate-info">
            <template x-if="form.latitude && form.longitude">
              <div class="stg-locate-done">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <div>
                  <div class="stg-locate-done-title">Position enregistrée</div>
                  <div class="stg-locate-done-coords" x-text="form.latitude.toFixed(5) + ', ' + form.longitude.toFixed(5)"></div>
                </div>
                <a :href="'https://www.openstreetmap.org/?mlat=' + form.latitude + '&mlon=' + form.longitude + '#map=16/' + form.latitude + '/' + form.longitude"
                   target="_blank" rel="noopener" class="stg-locate-view">Voir sur la carte →</a>
              </div>
            </template>
            <template x-if="!form.latitude || !form.longitude">
              <p class="stg-locate-empty">Aucune position enregistrée. Cliquez sur le bouton pour localiser votre établissement, cela permettra à vos clients de le retrouver facilement sur la carte de votre vitrine.</p>
            </template>
          </div>
          <button type="button" class="btn-saas-secondary stg-locate-btn" @click="locateMe()" :disabled="locating">
            <div x-show="locating" class="stg-btn-spinner" style="border-top-color:#C9A84C;border-color:rgba(201,168,76,0.35);"></div>
            <svg x-show="!locating" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span x-text="locating ? 'Localisation en cours…' : (form.latitude ? 'Actualiser ma position' : 'Localiser mon hôtel ou résidence')"></span>
          </button>
        </div>
        <p x-show="locateError" x-text="locateError" class="stg-locate-error"></p>
      </div>

      <!-- Carte : Contact -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'contact'">
        <div class="stg-group-label">Contact</div>
        <div class="stg-form-grid">
          <div>
            <label class="saas-label">Téléphone</label>
            <div class="stg-input-wrap">
              <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              <input class="saas-input stg-input-has-icon" x-model="form.phone" placeholder="+225 07 00 00 00 00">
            </div>
          </div>
          <div>
            <label class="saas-label">Email</label>
            <div class="stg-input-wrap">
              <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <input class="saas-input stg-input-has-icon" type="email" x-model="form.email" placeholder="contact@hotel.ci">
            </div>
          </div>
          <div class="stg-col-2">
            <label class="saas-label">Site web <span class="stg-label-optional">(optionnel)</span></label>
            <div class="stg-input-wrap">
              <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18"/></svg>
              <input class="saas-input stg-input-has-icon" x-model="form.website" placeholder="https://…">
            </div>
          </div>
        </div>
        <div class="stg-form-actions" x-show="!creatingEstab">
          <button class="btn-saas-primary" @click="saveContact()" :disabled="savingContact">
            <div x-show="savingContact" class="stg-btn-spinner"></div>
            <span x-show="!savingContact">Enregistrer</span>
          </button>
        </div>
      </div>

      <!-- Carte : Paiement des réservations (auto-enregistré au clic) -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'payment'">
        <div class="stg-group-label">Paiement des réservations</div>
        <div class="stg-payment-toggle" :class="(onlinePaymentComingSoon || onlinePaymentLocked) ? 'stg-locked' : ''">
          <div class="stg-payment-toggle-info">
            <div class="stg-payment-toggle-title">
              Paiement en ligne
              <span x-show="onlinePaymentComingSoon" class="stg-pro-badge">Bientôt</span>
              <span x-show="!onlinePaymentComingSoon && onlinePaymentLocked" class="stg-pro-badge">PRO</span>
            </div>
            <p class="stg-payment-toggle-desc" x-show="onlinePaymentComingSoon">
              Fonctionnalité en cours de développement, disponible prochainement. Vos clients règlent pour l'instant sur place.
            </p>
            <p class="stg-payment-toggle-desc" x-show="!onlinePaymentComingSoon && !onlinePaymentLocked && !creatingEstab">
              Désactivez pour n'accepter que le paiement sur place : vos clients devront régler directement à l'arrivée, sans option de paiement en ligne sur votre vitrine.
            </p>
            <p class="stg-payment-toggle-desc" x-show="!onlinePaymentComingSoon && onlinePaymentLocked">
              Réservé aux plans Premium. Passez à niveau pour proposer le paiement en ligne à vos clients.
            </p>
            <p class="stg-payment-toggle-desc" x-show="!onlinePaymentComingSoon && !onlinePaymentLocked && creatingEstab">
              Disponible une fois votre établissement créé.
            </p>
          </div>
          <button type="button" class="stg-switch" :class="(!onlinePaymentComingSoon && !onlinePaymentLocked && form.online_payment_enabled) ? 'stg-switch-on' : ''"
                  @click="toggleOnlinePayment()" :disabled="onlinePaymentComingSoon || onlinePaymentLocked || creatingEstab || savingPayment"
                  :aria-pressed="(!onlinePaymentComingSoon && !onlinePaymentLocked && form.online_payment_enabled) ? 'true' : 'false'" aria-label="Activer ou désactiver le paiement en ligne">
            <span class="stg-switch-dot"></span>
          </button>
        </div>
      </div>

      <!-- Carte : Visibilité (auto-enregistrée au clic) -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'visibility'">
        <div class="stg-group-label">Visibilité</div>
        <div class="stg-payment-toggle" :class="boostLocked ? 'stg-locked' : ''">
          <div class="stg-payment-toggle-info">
            <div class="stg-payment-toggle-title">
              Boost vitrine
              <span x-show="boostLocked" class="stg-pro-badge">PREMIUM+</span>
            </div>
            <p class="stg-payment-toggle-desc" x-show="!boostLocked && !creatingEstab">
              Mettez votre établissement en avant en tête des résultats de recherche publics, avant les établissements non boostés.
            </p>
            <p class="stg-payment-toggle-desc" x-show="boostLocked">
              Réservé au plan Premium+. Passez à niveau pour être mis en avant dans la recherche.
            </p>
            <p class="stg-payment-toggle-desc" x-show="!boostLocked && creatingEstab">
              Disponible une fois votre établissement créé.
            </p>
          </div>
          <button type="button" class="stg-switch" :class="(!boostLocked && form.is_boosted) ? 'stg-switch-on' : ''"
                  @click="toggleBoost()" :disabled="boostLocked || creatingEstab || savingBoost"
                  :aria-pressed="(!boostLocked && form.is_boosted) ? 'true' : 'false'" aria-label="Activer ou désactiver le boost vitrine">
            <span class="stg-switch-dot"></span>
          </button>
        </div>
      </div>

      <!-- Carte : Présentation -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'presentation'">
        <div class="stg-group-label">Présentation</div>
        <div class="stg-form-grid">
          <div class="stg-col-2">
            <label class="saas-label">Description</label>
            <textarea class="saas-input" rows="4" x-model="form.description" placeholder="Décrivez votre établissement en quelques phrases…"></textarea>
          </div>
        </div>
        <div class="stg-form-actions" x-show="!creatingEstab">
          <button class="btn-saas-primary" @click="savePresentation()" :disabled="savingPresentation">
            <div x-show="savingPresentation" class="stg-btn-spinner"></div>
            <span x-show="!savingPresentation">Enregistrer</span>
          </button>
        </div>
      </div>

      <!-- Carte : Horaires -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'hours'">
        <div class="stg-group-label">Horaires</div>
        <div class="stg-form-grid">
          <div>
            <label class="saas-label">Check-in (arrivée) à partir de</label>
            <input type="time" class="saas-input" x-model="form.check_in_time">
          </div>
          <div>
            <label class="saas-label">Check-out (départ) jusqu'à</label>
            <input type="time" class="saas-input" x-model="form.check_out_time">
          </div>
        </div>
        <p style="font-size:12px;color:#9CA3AF;margin:8px 0 0;">Affichés dans « Informations pratiques » sur la fiche publique de l'établissement.</p>
        <div class="stg-form-actions" x-show="!creatingEstab">
          <button class="btn-saas-primary" @click="saveHours()" :disabled="savingHours">
            <div x-show="savingHours" class="stg-btn-spinner"></div>
            <span x-show="!savingHours">Enregistrer</span>
          </button>
        </div>
      </div>

      <!-- Carte : Photos (auto-enregistrées à l'ajout/suppression) -->
      <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="generalSections[generalCardIndex] === 'photos'">
        <div class="stg-group-label">Photos <span class="stg-label-optional">(10 maximum)</span></div>
        <div class="stg-photo-grid">
          <template x-for="photo in photos" :key="photo.id">
            <div class="stg-photo-slot stg-photo-filled">
              <img :src="photoUrl(photo.file_path)" alt="Photo de l'établissement">
              <span x-show="photo.is_cover" class="stg-photo-cover-badge">Couverture</span>
              <button type="button" class="stg-photo-remove" @click="removePhoto(photo.id)" :disabled="photoDeleting === photo.id" title="Supprimer">✕</button>
            </div>
          </template>
          <template x-if="photos.length < 10">
            <label class="stg-photo-slot stg-photo-empty" :class="photoUploading ? 'stg-photo-loading' : ''">
              <input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;" @change="addPhoto($event)" :disabled="photoUploading">
              <div x-show="photoUploading" class="stg-btn-spinner"></div>
              <template x-if="!photoUploading">
                <div style="text-align:center;">
                  <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                  <div style="font-size:12px;margin-top:6px;">Ajouter</div>
                </div>
              </template>
            </label>
          </template>
        </div>
        <p x-show="photoError" x-text="photoError" class="stg-locate-error"></p>
      </div>

      <!-- Carte : création de l'établissement (uniquement en mode création) -->
      <div class="saas-card" style="padding:24px;" x-show="generalSections[generalCardIndex] === 'create'" x-cloak>
        <div class="stg-feedback">
          <template x-if="saveSuccess">
            <div class="stg-alert stg-alert-success">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              Établissement créé avec succès !
            </div>
          </template>
          <template x-if="saveError">
            <div class="stg-alert stg-alert-error">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span x-text="saveError"></span>
            </div>
          </template>
        </div>

        <div class="stg-form-actions">
          <button type="button" class="btn-saas-secondary" x-show="addingEstab" @click="cancelAddEstablishment()" :disabled="saving">Annuler</button>
          <button class="btn-saas-primary" @click="saveGeneral()" :disabled="saving">
            <div x-show="saving" class="stg-btn-spinner"></div>
            <span x-show="!saving">Créer l'établissement</span>
          </button>
        </div>
      </div>
    </div>

  <!-- ════════════════════════════════════════════════════════
       ONGLET : Membres
       ════════════════════════════════════════════════════════ -->
  <div x-show="activeTab==='team'">
    <div class="saas-card" style="padding:24px;">

      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;">
        <div class="stg-section-head">
          <div class="stg-section-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          </div>
          <div>
            <h3 class="stg-section-title">Équipe</h3>
            <p class="stg-section-sub">Créez des accès pour votre personnel de réception. Ils pourront gérer les réservations et encaisser les paiements, sans accéder à la Comptabilité, aux Dépenses ni aux Paramètres.</p>
          </div>
        </div>
        <button type="button" class="btn-saas-primary" style="flex-shrink:0;" @click="openAddMember()">+ Ajouter un membre</button>
      </div>

      <div class="stg-divider"></div>

      <div x-show="teamLoading" style="display:flex;justify-content:center;padding:30px 0;">
        <svg class="spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:24px;height:24px;stroke:#C9A84C;" fill="none"><circle cx="12" cy="12" r="10" stroke-width="4" stroke-opacity="0.25"></circle><path d="M22 12a10 10 0 00-10-10" stroke-width="4" stroke-linecap="round"></path></svg>
      </div>

      <div x-show="!teamLoading && teamMembers.length === 0" style="text-align:center;padding:30px 0;color:#9CA3AF;font-size:13px;">
        Aucun membre pour l'instant. Ajoutez votre premier réceptionniste.
      </div>

      <div x-show="!teamLoading && teamMembers.length > 0" style="overflow-x:auto;">
        <table class="saas-table" style="width:100%;">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Rôle</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <template x-for="m in teamMembers" :key="m.id">
              <tr>
                <td style="font-weight:600;color:#111827;" x-text="m.name"></td>
                <td x-text="m.email"></td>
                <td x-text="m.phone || '—'"></td>
                <td><span class="badge badge-info">Réceptionniste</span></td>
                <td style="text-align:right;white-space:nowrap;">
                  <button type="button" class="btn-saas-secondary" style="padding:6px 12px;font-size:12px;" @click="openEditMember(m)">Modifier</button>
                  <button type="button" class="btn-saas-danger" style="padding:6px 12px;font-size:12px;margin-left:6px;" @click="deleteMember(m)">Supprimer</button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <p x-show="teamError" x-text="teamError" class="stg-locate-error" style="margin-top:10px;"></p>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════
       ONGLET : Abonnement
       ════════════════════════════════════════════════════════ -->
  <div x-show="activeTab==='subscription'">

    <!-- Bannière retour paiement -->
    <div x-show="paymentSuccess" x-cloak
         style="display:flex;align-items:center;gap:12px;background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:.93rem;">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>Paiement confirmé ! Votre abonnement est actif.</span>
    </div>
    <div x-show="paymentError" x-cloak
         style="display:flex;align-items:center;gap:12px;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:.93rem;">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span x-text="paymentError"></span>
    </div>

    <!-- Hero plan actuel -->
    <div class="stg-sub-hero">
      <div>
        <div class="stg-sub-pill">Plan actuel</div>
        <div class="stg-sub-plan" x-text="plans[subscription?.plan]?.name ?? planLabel(subscription?.plan)"></div>
        <div class="stg-sub-valid">
          Valide jusqu'au <strong x-text="formatDate(subscription?.expires_at)"></strong>
        </div>
      </div>
      <div class="stg-sub-right">
        <div class="stg-sub-stat">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Chambres&nbsp;: <strong x-text="establishment?.total_rooms ?? 0"></strong>
        </div>
        <a href="<?= $base_url ?>/saas/checkout" class="btn-saas-primary">
          Gérer mon abonnement →
        </a>
      </div>
    </div>

    <!-- Cards des 3 plans -->
    <div class="stg-plans">
      <template x-for="p in ['starter','pro','business']" :key="p">
        <div class="stg-plan" :class="subscription?.plan === p ? 'stg-plan-current' : ''">
          <div class="stg-plan-head">
            <div class="stg-plan-name" x-text="plans[p]?.name ?? planLabel(p)"></div>
            <div x-show="subscription?.plan === p" class="badge badge-success">Actif</div>
          </div>
          <div class="stg-plan-price">
            <span x-text="(plans[p]?.prices?.monthly ?? 0) === 0 ? 'Gratuit' : ((plans[p]?.prices?.monthly ?? 0).toLocaleString('fr-FR') + ' FCFA/mois')"></span>
          </div>
          <div class="stg-plan-yr" x-show="(plans[p]?.prices?.yearly ?? 0) > 0">
            ou <span x-text="(plans[p]?.prices?.yearly ?? 0).toLocaleString('fr-FR')"></span> FCFA/an
          </div>
          <div class="stg-plan-rooms">
            <span x-text="(plans[p]?.max_rooms ?? 0) >= 999999 ? 'Chambres illimitées' : ('Jusqu\'à ' + (plans[p]?.max_rooms ?? 0) + ' chambres')"></span>
          </div>
          <ul class="stg-plan-features">
            <template x-for="[feat, enabled] in Object.entries(plans[p]?.features ?? {})" :key="feat">
              <li>
                <span class="stg-fi" :class="enabled ? 'stg-fi-ok' : 'stg-fi-no'" x-text="enabled ? '✓' : '✗'"></span>
                <span :class="enabled ? 'stg-ft-ok' : 'stg-ft-no'" x-text="featureLabels[feat] ?? feat"></span>
              </li>
            </template>
          </ul>
          <div>
            <template x-if="subscription?.plan !== p">
              <button class="btn-saas-primary" style="width:100%;" @click="window.location='<?= $base_url ?>/saas/checkout?plan=' + p">Choisir ce plan</button>
            </template>
            <template x-if="subscription?.plan === p">
              <div>
                <div class="stg-plan-active">✓ Plan actif</div>
                <template x-if="p !== 'starter'">
                  <div style="margin-top:10px;">
                    <button type="button" class="btn-saas-primary" style="width:100%;"
                            @click="window.location='<?= $base_url ?>/saas/checkout?plan=' + p">
                      Renouveler mon abonnement →
                    </button>
                    <div style="display:flex;gap:8px;margin-top:8px;">
                      <button type="button" x-show="p === 'business'" class="btn-saas-secondary" style="flex:1;font-size:12px;padding:8px 10px;" :disabled="planActionLoading" @click="downgradeSubscription('pro')">Rétrograder en Pro</button>
                      <button type="button" class="btn-saas-secondary" style="flex:1;font-size:12px;padding:8px 10px;color:#DC2626;" :disabled="planActionLoading" @click="cancelSubscription()">Annuler l'abonnement</button>
                    </div>
                  </div>
                </template>
              </div>
            </template>
          </div>
        </div>
      </template>
    </div>

    <p x-show="planActionError" x-text="planActionError" class="stg-locate-error" style="margin-top:10px;"></p>

    <!-- Historique des paiements d'abonnement -->
    <div class="saas-card" style="padding:20px 24px;margin-top:24px;">
      <div class="stg-group-label" style="margin-bottom:14px;">Historique de facturation</div>
      <template x-if="!loadingSubHistory && subHistory.length === 0">
        <p style="font-size:13px;color:#9CA3AF;margin:0;">Aucune transaction pour le moment.</p>
      </template>
      <template x-if="subHistory.length > 0">
        <div style="overflow-x:auto;">
          <table class="saas-table" style="width:100%;">
            <thead>
              <tr>
                <th>Date</th>
                <th>Plan</th>
                <th>Période</th>
                <th>Montant</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="sub in subHistory" :key="sub.id">
                <tr>
                  <td x-text="formatDate(sub.created_at)"></td>
                  <td x-text="plans[sub.plan]?.name ?? planLabel(sub.plan)"></td>
                  <td x-text="sub.billing === 'yearly' ? 'Annuel' : 'Mensuel'"></td>
                  <td style="font-weight:700;" x-text="(Number(sub.amount) || 0).toLocaleString('fr-FR') + ' FCFA'"></td>
                  <td><span :class="subHistoryStatusCfg(sub.status).badge" x-text="subHistoryStatusCfg(sub.status).label"></span></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════
       ONGLET : Compte
       ════════════════════════════════════════════════════════ -->
  <div x-show="activeTab==='account'">
    <div class="saas-card" style="padding:24px;margin-bottom:16px;">

      <!-- Avatar + infos utilisateur -->
      <div class="stg-account-hero">
        <div class="stg-avatar-wrap">
          <div class="stg-avatar" :style="userAvatarUrl ? ('background-image:url(\'' + userAvatarUrl + '\')') : ''">
            <span x-show="!userAvatarUrl" x-text="(currentUser.name||'U').split(' ').map(s=>s[0]).slice(0,2).join('')"></span>
          </div>
          <label class="stg-avatar-edit" :class="avatarUploading ? 'stg-avatar-edit-loading' : ''" title="Changer la photo">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <input type="file" accept="image/png,image/jpeg,image/webp" style="display:none" :disabled="avatarUploading" @change="uploadAvatar($event)">
          </label>
          <button type="button" class="stg-avatar-remove" x-show="userAvatarUrl" @click="removeAvatar()" title="Supprimer la photo">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div>
          <div class="stg-account-name"  x-text="currentUser.name"></div>
          <div class="stg-account-email" x-text="currentUser.email"></div>
          <div class="stg-account-role">
            <span class="badge" :class="planColor(currentUser.role)">
              <span x-text="currentUser.role"></span>
            </span>
          </div>
        </div>
      </div>
      <p x-show="avatarError" class="stg-payment-toggle-desc" style="color:#DC2626;margin-top:10px;" x-text="avatarError"></p>

      <!-- Formulaire profil -->
      <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;margin-top:22px;">
        <div>
          <label class="saas-label">Nom complet</label>
          <input class="saas-input" x-model="profileForm.name" placeholder="Votre nom">
        </div>
        <div>
          <label class="saas-label">Téléphone <span class="stg-label-optional">(optionnel)</span></label>
          <input class="saas-input" x-model="profileForm.phone" placeholder="+225 07 00 00 00 00">
        </div>
      </div>
      <div x-show="profileError" class="stg-alert stg-alert-error" style="margin-top:14px;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="profileError"></span>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:16px;">
        <button class="btn-saas-primary" @click="saveProfile()" :disabled="profileSaving">
          <span x-show="!profileSaving">Enregistrer le profil</span>
          <span x-show="profileSaving">Enregistrement…</span>
        </button>
      </div>
    </div>

    <!-- Panneaux sécurité / danger -->
    <div class="stg-panels" style="margin-bottom:16px;">
      <div class="stg-panel stg-panel-security">
        <div class="stg-panel-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div class="stg-panel-title">Sécurité</div>
        <div class="stg-panel-desc">Modifiez votre mot de passe pour sécuriser l'accès à votre compte.</div>
        <button class="btn-saas-secondary" @click="openPasswordModal()">Changer le mot de passe</button>
      </div>

      <div class="stg-panel stg-panel-danger">
        <div class="stg-panel-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div class="stg-panel-title">Zone de danger</div>
        <div class="stg-panel-desc">Supprimer définitivement votre compte et toutes les données associées. Cette action n'est pas libre-service : notre support la traite manuellement pour confirmer votre identité.</div>
        <a class="btn-saas-danger" style="font-size:12px;" :href="supportMailtoUrl">Contacter le support</a>
      </div>
    </div>

    <!-- Appareils connectés (sessions actives + historique de connexion) -->
    <div class="saas-card" style="padding:24px;">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
          <div class="stg-panel-title" style="margin-bottom:2px;">Appareils connectés</div>
          <p class="stg-payment-toggle-desc">Sessions actives et historique de connexion sur votre compte.</p>
        </div>
        <button type="button" class="btn-saas-secondary" style="font-size:12px;" x-show="activeSessionsCount > 1"
                @click="revokeOtherSessions()" :disabled="sessionsActionLoading">
          Déconnecter les autres appareils
        </button>
      </div>

      <div style="margin-top:14px;">
        <template x-for="s in sessions" :key="s.id">
          <div class="stg-session-row">
            <div class="stg-session-info">
              <div class="stg-session-device">
                <span x-text="s.device_label || 'Appareil inconnu'"></span>
                <span class="badge badge-gold"    x-show="s.is_current"              style="margin-left:6px;">Cet appareil</span>
                <span class="badge badge-success" x-show="s.is_active && !s.is_current" style="margin-left:6px;">Active</span>
                <span class="badge stg-badge-muted" x-show="!s.is_active"            style="margin-left:6px;">Expirée</span>
              </div>
              <div class="stg-session-meta">
                <span x-text="formatDate(s.created_at)"></span>
                <span x-show="s.ip_address">&nbsp;·&nbsp;<span x-text="s.ip_address"></span></span>
              </div>
            </div>
            <button type="button" class="btn-saas-secondary" style="font-size:12px;" x-show="s.is_active && !s.is_current"
                    @click="revokeSession(s.id)" :disabled="sessionsActionLoading">
              Déconnecter
            </button>
          </div>
        </template>
        <p x-show="!sessionsLoading && sessions.length===0" style="font-size:13px;color:#9CA3AF;">Aucune connexion enregistrée.</p>
      </div>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════
       ONGLET : Notifications
       ════════════════════════════════════════════════════════ -->
  <div x-show="activeTab==='notifications'">
    <div class="saas-card" style="padding:24px;">

      <!-- Notifications push (hors application) -->
      <div class="stg-payment-toggle" style="margin-bottom:20px;">
        <div class="stg-payment-toggle-info">
          <div class="stg-payment-toggle-title">Notifications hors application</div>
          <p class="stg-payment-toggle-desc" x-show="!pushUnsupported">
            Recevez une alerte sur cet appareil (nouvelle réservation, paiement reçu…) même quand Afristay n'est pas ouvert.
          </p>
          <p class="stg-payment-toggle-desc" x-show="pushUnsupported" style="color:#DC2626;">
            Votre navigateur ne supporte pas les notifications push.
          </p>
          <p class="stg-payment-toggle-desc" x-show="pushError" style="color:#DC2626;" x-text="pushError"></p>
        </div>
        <button type="button" class="stg-switch" :class="pushEnabled ? 'stg-switch-on' : ''"
                @click="togglePush()" :disabled="pushUnsupported || pushLoading"
                :aria-pressed="pushEnabled ? 'true' : 'false'" aria-label="Activer ou désactiver les notifications">
          <span class="stg-switch-dot"></span>
        </button>
      </div>

      <!-- Types de notification -->
      <div class="stg-payment-toggle" style="flex-direction:column;align-items:stretch;">
        <div class="stg-payment-toggle-info" style="margin-bottom:12px;">
          <div class="stg-payment-toggle-title">Types de notification</div>
          <p class="stg-payment-toggle-desc">Désactivez les notifications que vous ne souhaitez pas recevoir (centre de notifications + push).</p>
        </div>
        <div style="display:flex;flex-direction:column;gap:2px;">
          <template x-for="item in notifTypeList" :key="item.type">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-top:1px solid rgba(0,0,0,0.05);">
              <span style="font-size:13px;color:#374151;" x-text="item.label"></span>
              <button type="button" class="stg-switch" :class="!isNotifMuted(item.type) ? 'stg-switch-on' : ''"
                      @click="toggleNotifType(item.type)" :disabled="notifPrefsSaving"
                      :aria-pressed="!isNotifMuted(item.type) ? 'true' : 'false'" :aria-label="'Activer ou désactiver : ' + item.label">
                <span class="stg-switch-dot"></span>
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ MODAL : Changer le mot de passe ═══ -->
  <div x-cloak x-show="showPasswordModal" class="saas-modal-bg" @click.self="closePasswordModal()" @keydown.escape.window="closePasswordModal()">
    <div class="saas-modal" style="max-width:420px;" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <h2 style="font-size:16px;font-weight:700;margin:0;">Changer le mot de passe</h2>
        <button type="button" class="saas-modal-close" @click="closePasswordModal()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:14px;">
          <div>
            <label class="saas-label">Mot de passe actuel</label>
            <input class="saas-input" type="password" x-model="passwordForm.current" placeholder="••••••••" autocomplete="current-password">
          </div>
          <div>
            <label class="saas-label">Nouveau mot de passe</label>
            <input class="saas-input" type="password" x-model="passwordForm.new" placeholder="Min. 8 caractères, avec une lettre et un chiffre" autocomplete="new-password">
          </div>
          <div>
            <label class="saas-label">Confirmer le nouveau mot de passe</label>
            <input class="saas-input" type="password" x-model="passwordForm.confirm" placeholder="••••••••" autocomplete="new-password">
          </div>
          <div x-show="passwordError" class="stg-alert stg-alert-error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="passwordError"></span>
          </div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="closePasswordModal()" :disabled="passwordSaving">Annuler</button>
        <button type="button" class="btn-saas-primary" @click="changePassword()" :disabled="passwordSaving">
          <span x-show="!passwordSaving">Enregistrer</span>
          <span x-show="passwordSaving">Enregistrement…</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ═══ MODAL : Ajouter / modifier un membre ═══ -->
  <div x-cloak x-show="showTeamModal" class="saas-modal-bg" @click.self="showTeamModal=false" @keydown.escape.window="showTeamModal=false">
    <div class="saas-modal" style="max-width:440px;" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <h2 style="font-size:16px;font-weight:700;margin:0;" x-text="teamEditing ? 'Modifier le membre' : 'Ajouter un membre'"></h2>
        <button type="button" class="saas-modal-close" @click="showTeamModal=false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:14px;">
          <div>
            <label class="saas-label">Nom complet</label>
            <input class="saas-input" x-model="teamForm.name" placeholder="Ex : Awa Koné">
          </div>
          <div x-show="!teamEditing && establishments.length > 1">
            <label class="saas-label">Établissement</label>
            <select class="saas-input" x-model="teamForm.establishment_id">
              <template x-for="e in establishments" :key="e.id">
                <option :value="e.id" x-text="e.name"></option>
              </template>
            </select>
          </div>
          <div>
            <label class="saas-label">Email <span x-show="teamEditing" class="stg-label-optional">(non modifiable)</span></label>
            <input class="saas-input" type="email" x-model="teamForm.email" :disabled="!!teamEditing" placeholder="reception@hotel.ci">
          </div>
          <div>
            <label class="saas-label">Téléphone <span class="stg-label-optional">(optionnel)</span></label>
            <input class="saas-input" x-model="teamForm.phone" placeholder="+225 07 00 00 00 00">
          </div>
          <div>
            <label class="saas-label" x-text="teamEditing ? 'Nouveau mot de passe' : 'Mot de passe'"></label>
            <input class="saas-input" type="password" x-model="teamForm.password" placeholder="Min. 8 caractères">
            <p x-show="teamEditing" style="font-size:11px;color:#9CA3AF;margin:6px 2px 0;">Laissez vide pour ne pas changer le mot de passe.</p>
          </div>
          <div x-show="teamFormError" class="stg-alert stg-alert-error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="teamFormError"></span>
          </div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="showTeamModal=false" :disabled="teamSubmitting">Annuler</button>
        <button type="button" class="btn-saas-primary" @click="saveMember()" :disabled="teamSubmitting">
          <span x-show="!teamSubmitting" x-text="teamEditing ? 'Enregistrer' : 'Créer le membre'"></span>
          <span x-show="teamSubmitting">Enregistrement…</span>
        </button>
      </div>
    </div>
  </div>

  <?php if (AGENTS_ENABLED): ?>
  <!-- ═══ MODAL : Mon QR code (scanné par un agent commercial) ═══ -->
  <div x-cloak x-show="showQrModal" class="saas-modal-bg" @click.self="showQrModal=false" @keydown.escape.window="showQrModal=false">
    <div class="saas-modal" style="max-width:380px;text-align:center;" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <h2 style="font-size:16px;font-weight:700;margin:0;">Mon QR code</h2>
        <button type="button" class="saas-modal-close" @click="showQrModal=false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <template x-if="!qrAgentLinked">
          <div>
            <p style="font-size:13px;color:#6B7280;margin-bottom:16px;">
              Un agent commercial Afristay peut scanner ce code pour être identifié comme celui qui vous a fait inscrire.
            </p>
            <div id="qr-code-canvas" style="display:flex;justify-content:center;margin-bottom:12px;"></div>
            <div x-show="qrCodeToken" style="display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap;">
              <p style="font-size:11px;color:#9CA3AF;word-break:break-all;margin:0;">Code : <span x-text="qrCodeToken"></span></p>
              <button type="button" @click="copyQrCode()" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#B8860B;background:#FEF3C7;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copier
              </button>
            </div>
            <p style="font-size:11px;color:#9CA3AF;margin-top:8px;">Vous pouvez aussi envoyer ce code à votre agent s'il n'est pas sur place.</p>
          </div>
        </template>
        <template x-if="qrAgentLinked">
          <div style="padding:12px 0;">
            <div style="font-size:32px;margin-bottom:10px;">✅</div>
            <p style="font-size:13px;color:#374151;font-weight:600;margin-bottom:4px;">Ce QR code est désactivé</p>
            <p style="font-size:12px;color:#9CA3AF;">Votre établissement est déjà rattaché à un agent commercial, un nouveau scan n'aurait plus aucun effet.</p>
          </div>
        </template>
        <p x-show="qrCodeLoading" style="font-size:13px;color:#6B7280;">Chargement…</p>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
