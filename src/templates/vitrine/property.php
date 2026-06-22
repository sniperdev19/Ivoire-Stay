<?php
$base = $base_url ?? rtrim(APP_URL, '/');
$pid  = (int) ($property_id ?? 0);
?>

<div class="prop-page" x-data="propertyPage('<?= $base ?>', <?= $pid ?>)" x-init="init()">

<!-- SKELETON / LOADING -->
<template x-if="loading">
  <div>
    <div style="height:480px;background:linear-gradient(135deg,#1B4332,#2D6A4F);"></div>
    <div style="max-width:1100px;margin:0 auto;padding:40px 24px;">
      <div style="height:24px;width:40%;background:#E5E7EB;border-radius:8px;margin-bottom:12px;"></div>
      <div style="height:16px;width:60%;background:#F3F4F6;border-radius:8px;"></div>
    </div>
  </div>
</template>

<!-- ERREUR -->
<template x-if="!loading && error">
  <div style="padding:100px 20px;text-align:center;">
    <div style="font-size:56px;margin-bottom:20px;">😕</div>
    <h2 style="font-size:22px;font-weight:700;color:#111827;margin-bottom:8px;" x-text="error"></h2>
    <a href="<?= $base ?>/search"
       style="display:inline-block;margin-top:20px;padding:12px 28px;background:#1B4332;color:white;border-radius:999px;text-decoration:none;font-family:Inter,sans-serif;font-size:14px;">
      ← Retour aux résultats
    </a>
  </div>
</template>

<!-- CONTENU DYNAMIQUE -->
<template x-if="!loading && !error && property">
  <div>

    <!-- CINEMATIC HERO -->
    <section class="prop-hero">
      <div class="prop-hero-bg"
           :style="photoUrl(property.cover_photo)
             ? 'background-image:url(' + photoUrl(property.cover_photo) + ')'
             : 'background:linear-gradient(135deg,#1B4332 0%,#2D6A4F 100%)'">
      </div>
      <div class="prop-hero-overlay"></div>
      <div class="prop-hero-content">
        <div class="prop-hero-left">
          <div class="prop-hero-rule"></div>
          <span class="prop-hero-tag" x-text="typeLabel(property.type) + ' · ' + property.city"></span>
          <h1 class="prop-hero-title" x-text="property.name"></h1>
          <div class="prop-hero-loc">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span x-text="(property.address ? property.address + ', ' : '') + property.city + ' · Côte d\'Ivoire'"></span>
          </div>
          <span class="prop-hero-badge">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Établissement vérifié Ivoire Stay
          </span>
        </div>
        <div class="prop-hero-right">
          <div class="prop-hero-price-card">
            <div class="prop-hero-price-label">À partir de</div>
            <template x-if="minPrice">
              <div class="prop-hero-price">
                <span x-text="new Intl.NumberFormat('fr-FR').format(minPrice)"></span>
                <small>FCFA</small>
              </div>
            </template>
            <template x-if="!minPrice">
              <div class="prop-hero-price" style="font-size:20px;line-height:1.3;">Sur<br>demande</div>
            </template>
            <div style="font-family:'Inter',sans-serif;font-size:11px;color:rgba(27,67,50,0.4);margin-top:2px;">/nuit</div>
            <template x-if="rooms.length > 0">
              <a :href="'<?= $base ?>/booking/' + rooms[0].id" class="prop-hero-book-btn">Réserver maintenant →</a>
            </template>
            <template x-if="rooms.length === 0">
              <a href="<?= $base ?>/contact" class="prop-hero-book-btn">Nous contacter →</a>
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- STATS BAND -->
    <div class="prop-stats-band">
      <div class="prop-stat">
        <span class="prop-stat-label">Chambres disponibles</span>
        <div class="prop-stat-value">
          <span x-text="property.available_rooms ?? 0"></span><span>+</span>
        </div>
      </div>
      <div class="prop-stat">
        <span class="prop-stat-label">Total des chambres</span>
        <div class="prop-stat-value"><span x-text="property.total_rooms ?? rooms.length"></span></div>
      </div>
      <div class="prop-stat">
        <span class="prop-stat-label">Type d'établissement</span>
        <div class="prop-stat-value" style="font-size:18px;padding-top:4px;" x-text="typeLabel(property.type)"></div>
      </div>
      <div class="prop-stat">
        <span class="prop-stat-label">Annulation gratuite</span>
        <div class="prop-stat-value" style="font-size:20px;padding-top:4px;">Sous 24h</div>
      </div>
    </div>

    <!-- ABOUT + SIDEBAR -->
    <section class="prop-about">
      <div class="prop-about-main">
        <div class="prop-about-rule"></div>
        <span class="prop-about-tag">À propos de l'établissement</span>
        <h2 class="prop-about-title" x-text="property.name"></h2>
        <template x-if="property.description">
          <p class="prop-about-body" x-text="property.description"></p>
        </template>
        <template x-if="!property.description">
          <p class="prop-about-body" style="color:rgba(27,67,50,0.35);font-style:italic;">Description non renseignée.</p>
        </template>
        <!-- Dates rapides -->
        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
          <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-family:Inter,sans-serif;font-size:11px;color:rgba(27,67,50,0.5);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Arrivée</label>
            <input type="date" x-model="checkIn" style="padding:8px 12px;border:1px solid rgba(27,67,50,0.15);border-radius:10px;font-family:Inter,sans-serif;font-size:13px;color:#1B4332;">
          </div>
          <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-family:Inter,sans-serif;font-size:11px;color:rgba(27,67,50,0.5);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Départ</label>
            <input type="date" x-model="checkOut" style="padding:8px 12px;border:1px solid rgba(27,67,50,0.15);border-radius:10px;font-family:Inter,sans-serif;font-size:13px;color:#1B4332;">
          </div>
        </div>
      </div>
      <div class="prop-about-side">
        <div class="prop-about-side-card">
          <h4>Informations pratiques</h4>
          <div class="prop-about-side-row"><span>Check-in</span><span>À partir de 14h00</span></div>
          <div class="prop-about-side-row"><span>Check-out</span><span>Jusqu'à 12h00</span></div>
          <template x-if="property.phone">
            <div class="prop-about-side-row"><span>Téléphone</span><span x-text="property.phone"></span></div>
          </template>
          <template x-if="property.email">
            <div class="prop-about-side-row"><span>Email</span><span x-text="property.email"></span></div>
          </template>
          <template x-if="property.website">
            <div class="prop-about-side-row">
              <span>Site web</span>
              <a :href="property.website" target="_blank" rel="noopener" style="color:#1B4332;" x-text="property.website"></a>
            </div>
          </template>
        </div>
        <div class="prop-about-side-card">
          <h4>Localisation</h4>
          <div style="width:100%;height:110px;background:rgba(27,67,50,0.05);border-radius:10px;display:flex;align-items:center;justify-content:center;color:rgba(27,67,50,0.2);font-family:'Inter',sans-serif;font-size:13px;">Carte interactive</div>
          <p style="font-family:'Inter',sans-serif;font-size:13px;color:rgba(27,67,50,0.55);margin-top:12px;line-height:1.7;"
             x-text="(property.address ? property.address + ', ' : '') + property.city + ', Côte d\'Ivoire'">
          </p>
        </div>
      </div>
    </section>

    <!-- ROOMS -->
    <section class="prop-rooms">
      <div class="prop-rooms-header">
        <div class="prop-rooms-rule"></div>
        <span class="prop-rooms-tag">Nos chambres</span>
        <div class="prop-rooms-rule"></div>
      </div>

      <template x-if="rooms.length === 0">
        <div style="text-align:center;padding:48px;color:rgba(27,67,50,0.35);font-family:Inter,sans-serif;">
          Aucune chambre disponible pour le moment.
        </div>
      </template>

      <div x-show="rooms.length > 0" class="prop-rooms-grid">
        <template x-for="room in rooms" :key="room.id">
          <div class="prop-room-card">
            <div class="prop-room-img" style="position:relative;">
              <template x-if="photoUrl(room.cover_photo)">
                <img :src="photoUrl(room.cover_photo)" :alt="room.type_name"
                     style="width:100%;height:100%;object-fit:cover;">
              </template>
              <template x-if="!photoUrl(room.cover_photo)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
              </template>
              <template x-if="room.is_available === false">
                <span style="position:absolute;top:10px;right:10px;background:rgba(220,38,38,0.88);color:white;font-size:10px;font-weight:700;padding:3px 8px;border-radius:6px;font-family:Inter,sans-serif;">
                  Non disponible
                </span>
              </template>
            </div>
            <div class="prop-room-body">
              <div class="prop-room-name" x-text="room.type_name + (room.number ? ' — Chambre ' + room.number : '')"></div>
              <p class="prop-room-desc">
                <template x-if="room.capacity">
                  <span>Jusqu'à <strong x-text="room.capacity"></strong> pers.</span>
                </template>
                <template x-if="room.floor">
                  <span> · Étage <span x-text="room.floor"></span></span>
                </template>
              </p>
              <div class="prop-room-footer">
                <div class="prop-room-price">
                  <span x-text="formatPrice(room.base_price)"></span> <small>/nuit</small>
                </div>
                <button class="prop-room-btn" @click="bookRoom(room)" type="button">Réserver →</button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </section>

  </div>
</template>

</div>
