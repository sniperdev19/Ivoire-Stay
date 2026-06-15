<?php
// Template page propriété injecté dans le layout vitrine.
// Variables disponibles : $title, $property_id
?>

<div x-data="{
  property: null,
  rooms: [],
  availability: {},
  loading: true,
  error: null,
  activePhoto: 0,
  lightboxOpen: false,
  selectedRoom: null,
  checkIn: '',
  checkOut: '',

  async init() {
    const id = <?= json_encode($property_id) ?>;
    if (!id) {
      this.error = 'Établissement introuvable.';
      this.loading = false;
      return;
    }
    await this.loadProperty(id);
  },

  async loadProperty(id) {
    this.loading = true;
    this.error = null;
    try {
      const res = await fetch('/api/public/property/' + id);
      const data = await res.json();
      if (data.success) {
        this.property = data.data.establishment ?? data.data ?? null;
        this.rooms = Array.isArray(data.data.rooms) ? data.data.rooms : [];
        this.activePhoto = 0;
        this.rooms.forEach(room => { if (room?.id) this.loadAvailability(room.id); });
      } else {
        this.error = 'Établissement introuvable.';
      }
    } catch (e) {
      this.error = 'Erreur de chargement.';
    } finally {
      this.loading = false;
    }
  },

  async loadAvailability(roomId) {
    if (!roomId) return;
    try {
      const res = await fetch('/api/public/availability/' + roomId);
      const data = await res.json();
      this.availability = { ...this.availability, [roomId]: data.data ?? {} };
    } catch (e) {
      // Pas de message d'erreur utilisateur pour les disponibilités
    }
  },

  formatPrice(p) {
    return p == null ? '' : new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
  },

  getStars(n) {
    const count = Number.isInteger(n) ? n : 4;
    return '★'.repeat(count) + '☆'.repeat(5 - count);
  },

  nextPhoto() {
    this.activePhoto = (this.activePhoto + 1) % (this.property?.photos?.length || 1);
  },

  prevPhoto() {
    this.activePhoto = (this.activePhoto - 1 + (this.property?.photos?.length || 1)) % (this.property?.photos?.length || 1);
  },

  bookRoom(room) {
    if (!this.checkIn || !this.checkOut) return;
    const params = new URLSearchParams({ check_in: this.checkIn, check_out: this.checkOut });
    window.location.href = '/booking/' + room.id + '?' + params;
  }
}"
 x-init="init()"
 class="w-full">

  <style>
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    .shimmer-bg { background: linear-gradient(90deg,#f0ebe1 25%,#e8ddd0 50%,#f0ebe1 75%); background-size:200% 100%; animation: shimmer 1.5s infinite; }
  </style>

  <!-- ÉTAT LOADING -->
  <div x-show="loading" class="space-y-6 px-4 py-10">
    <div class="glass-card h-[560px] overflow-hidden">
      <div class="h-full shimmer-bg"></div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6">
      <div class="space-y-4">
        <div class="glass-card h-24 shimmer-bg"></div>
        <div class="glass-card h-40 shimmer-bg"></div>
        <div class="glass-card h-64 shimmer-bg"></div>
      </div>
      <div class="glass-card h-[340px] shimmer-bg"></div>
    </div>
  </div>

  <!-- ÉTAT ERREUR -->
  <div x-show="error && !loading" class="px-4 py-16">
    <div class="glass-card p-8 text-[var(--color-forest)]">
      <h2 class="font-display text-[28px] mb-3">Oups, impossible de charger l'établissement</h2>
      <p class="text-[#4A5568] mb-6">Vérifiez votre connexion ou réessayez ultérieurement.</p>
      <button @click="init()" class="btn-gold">Réessayer</button>
    </div>
  </div>

  <!-- SECTION 1 — GALERIE PHOTOS -->
  <section x-show="property && !loading" class="pt-36 relative h-[560px] bg-[var(--color-forest)] overflow-hidden">
    <img x-bind:src="property.photos?.[activePhoto]?.url || 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200'"
         alt="Photo principale de l'établissement"
         class="w-full h-full object-cover" />
    <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(15,43,32,0.7) 0%, transparent 50%);"></div>

    <div class="absolute bottom-8 left-8 text-white max-w-3xl">
      <span class="inline-flex items-center rounded-full bg-white/90 text-[var(--color-gold)] px-4 py-2 text-sm font-medium"> <span x-text="property.type"></span> </span>
      <h1 class="font-display text-[48px] mt-4"> <span x-text="property.name"></span> </h1>
      <div class="mt-3 flex items-center gap-3 text-[16px] text-white/85">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#ffffff;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span x-text="property.city + ', ' + property.address"></span>
      </div>
      <div class="mt-3 text-[20px] text-[var(--color-gold)]" x-text="getStars(property.stars)"></div>
    </div>

    <button x-show="property.photos?.length > 1" @click="prevPhoto()"
            class="glass-card absolute left-4 top-1/2 -translate-y-1/2 h-12 w-12 rounded-full flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-forest)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </button>

    <button x-show="property.photos?.length > 1" @click="nextPhoto()"
            class="glass-card absolute right-4 top-1/2 -translate-y-1/2 h-12 w-12 rounded-full flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-forest)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </button>

    <div class="glass-card absolute bottom-4 right-4 px-4 py-2 rounded-full text-sm text-[var(--color-forest)]">
      <span x-text="(activePhoto + 1) + ' / ' + (property.photos?.length || 1)"></span>
    </div>

    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2">
      <template x-for="(photo, index) in property.photos" :key="photo.id ?? index">
        <button @click="activePhoto = index"
                class="h-2.5 w-2.5 rounded-full bg-white/50 transition-transform duration-200"
                :class="{ 'scale-110 bg-[var(--color-gold)]': activePhoto === index }"></button>
      </template>
    </div>

    <button @click="lightboxOpen = true"
            class="glass-card-strong absolute top-4 right-4 px-4 py-2 rounded-full inline-flex items-center gap-2 text-[var(--color-forest)]">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16M12 4v16"/>
      </svg>
      Voir toutes les photos
    </button>
  </section>

  <!-- SECTION 2 — CONTENU PRINCIPAL -->
  <section x-show="property && !loading" class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-8">

    <!-- COLONNE GAUCHE -->
    <div class="space-y-10">

      <!-- Bloc À propos -->
      <div>
        <h2 class="font-display text-[32px] text-[var(--color-forest)]">À propos de cet établissement</h2>
        <p class="mt-4 text-[16px] text-[#4A5568] leading-8"
           x-text="property.description ?? 'Un établissement de qualité situé au cœur de la Côte d\'Ivoire, offrant confort et services premium à ses hôtes.'">
        </p>
      </div>

      <!-- Bloc Équipements -->
      <div>
        <h2 class="font-display text-[32px] text-[var(--color-forest)]">Équipements & Services</h2>
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <template x-for="(amenity, index) in (property.amenities ?? ['WiFi gratuit','Parking','Piscine','Climatisation','Restaurant','Salle de sport','Room service','Bar'])" :key="amenity + index">
            <div class="glass-card px-4 py-2 rounded-full flex items-center gap-3" style="border-radius:50px;">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span class="text-[14px] text-[var(--color-forest)]" x-text="amenity"></span>
            </div>
          </template>
        </div>
      </div>

      <!-- Bloc Chambres disponibles -->
      <div id="chambres" x-init="rooms.forEach(r => loadAvailability(r.id))">
        <h2 class="font-display text-[32px] text-[var(--color-forest)]">Nos chambres</h2>
        <div class="mt-6 space-y-4">
          <template x-for="room in rooms" :key="room.id">
            <div class="glass-card overflow-hidden rounded-[20px] hover:-translate-y-1 transition-transform duration-300 flex flex-col md:flex-row">
              <div class="relative w-full md:w-[200px] h-[160px] flex-shrink-0">
                <img x-bind:src="room.photos?.[0]?.url || 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800'"
                     alt="Photo de chambre"
                     class="w-full h-full object-cover" />
                <div class="absolute top-3 left-3 rounded-full bg-[rgba(27,67,50,0.9)] px-3 py-1 text-white text-[13px]">
                  <span x-text="formatPrice(room.base_price)"></span><span class="text-xs">/nuit</span>
                </div>
              </div>
              <div class="flex-1 p-5">
                <div class="font-display text-[22px] text-[var(--color-forest)]" x-text="room.name"></div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-[14px] text-[#4A5568]">
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.766 0 5.347.836 7.579 2.273M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span x-text="(room.capacity ?? 2) + ' personnes'"></span>
                  </div>
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M4 6h16M5 6a2 2 0 00-2 2v11h18V8a2 2 0 00-2-2M15 6V4a3 3 0 00-6 0v2"/>
                    </svg>
                    <span x-text="(room.size ?? '25') + ' m²'"></span>
                  </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                  <template x-for="amenity in (room.amenities ?? []).slice(0, 3)" :key="amenity">
                    <span class="inline-flex items-center gap-2 rounded-full bg-[rgba(201,168,76,0.15)] px-3 py-2 text-[13px] text-[var(--color-forest)]">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                      </svg>
                      <span x-text="amenity"></span>
                    </span>
                  </template>
                </div>
              </div>
              <div class="p-5 flex items-end justify-between gap-3">
                <div class="text-sm text-[#4A5568]" x-text="availability[room.id]?.status ?? 'Disponibilité en cours...' "></div>
                <button @click="bookRoom(room)"
                        :disabled="!checkIn || !checkOut"
                        class="btn-gold rounded-[16px] px-5 py-3 disabled:opacity-50 disabled:cursor-not-allowed">
                  Réserver
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Bloc Localisation -->
      <div>
        <h2 class="font-display text-[32px] text-[var(--color-forest)]">Localisation</h2>
        <div class="glass-card overflow-hidden rounded-[24px] mt-6">
          <div class="bg-[#E8DDD0] h-[280px] flex flex-col items-center justify-center text-center px-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="font-display text-[20px] text-[var(--color-forest)] mb-2" x-text="property.city + ', ' + property.address"></p>
            <p class="text-[16px] text-[var(--color-forest)]">(Carte interactive disponible prochainement)</p>
          </div>
        </div>
      </div>
    </div>

    <!-- COLONNE DROITE -->
    <aside class="sticky top-24 self-start space-y-6">
      <div class="glass-card-strong p-7 rounded-[28px]">
        <div class="text-[12px] uppercase tracking-[0.18em] text-[#718096]">À partir de</div>
        <div class="mt-3 flex items-end gap-2">
          <div class="font-display text-[36px] text-[var(--color-forest)] font-semibold"
               x-text="formatPrice(Math.min(...rooms.map(r => r.base_price).filter(Boolean)) || 0)"></div>
          <div class="text-[14px] text-[#718096]">/nuit</div>
        </div>
        <div class="mt-2 flex items-center gap-2 text-[var(--color-gold)]">
          <span x-text="getStars(property.stars)"></span>
          <span class="text-[14px]">Excellent</span>
        </div>

        <div class="mt-6 space-y-4">
          <div>
            <label class="block text-[11px] uppercase tracking-[0.22em] text-[var(--color-gold)]">Arrivée</label>
            <input type="date" x-model="checkIn" class="w-full mt-2 px-4 py-3 rounded-[12px] bg-[var(--color-cream)]" style="border:1px solid rgba(201,168,76,0.3);" />
          </div>
          <div>
            <label class="block text-[11px] uppercase tracking-[0.22em] text-[var(--color-gold)]">Départ</label>
            <input type="date" x-model="checkOut" :min="checkIn" class="w-full mt-2 px-4 py-3 rounded-[12px] bg-[var(--color-cream)]" style="border:1px solid rgba(201,168,76,0.3);" />
          </div>
        </div>

        <button @click="document.querySelector('#chambres').scrollIntoView({ behavior: 'smooth' })"
                class="btn-gold w-full h-[52px] rounded-[16px] mt-6">Choisir une chambre ↓</button>

        <div class="mt-6 space-y-3 text-[13px] text-[#4A5568]">
          <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Annulation gratuite 24h avant</span>
          </div>
          <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Paiement sécurisé Mobile Money</span>
          </div>
          <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Confirmation instantanée</span>
          </div>
        </div>
      </div>

      <div class="glass-card p-5 rounded-[24px]">
        <div class="text-[15px] text-[var(--color-forest)] font-semibold">Besoin d'aide ?</div>
        <p class="mt-3 text-[14px] text-[#4A5568]">Notre équipe est disponible 24h/24</p>
        <a href="tel:+2250123456789" class="mt-4 inline-block text-[var(--color-gold)] font-medium">+225 01 23 45 67 89</a>
      </div>
    </aside>
  </section>

  <!-- SECTION 3 — LIGHTBOX -->
  <div x-show="lightboxOpen" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="relative w-full max-w-6xl h-full bg-white/95 rounded-[24px] overflow-hidden">
      <button @click="lightboxOpen = false" class="absolute top-4 right-4 z-20 glass-card p-3 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-forest)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
      <div class="h-full flex flex-col">
        <div class="relative flex-1 bg-[var(--color-forest)]">
          <img x-bind:src="property.photos?.[activePhoto]?.url || 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200'"
               alt="Photo agrandie"
               class="w-full h-full object-cover" />
          <div class="absolute inset-0 bg-black/30"></div>
        </div>
        <div class="bg-white p-6">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="font-display text-[24px] text-[var(--color-forest)]" x-text="property.name"></div>
              <div class="mt-2 text-[14px] text-[#4A5568]" x-text="(activePhoto + 1) + ' / ' + (property.photos?.length || 1) + ' photos'"></div>
            </div>
            <div class="flex items-center gap-2">
              <button @click="prevPhoto()" class="glass-card p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-forest)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
              </button>
              <button @click="nextPhoto()" class="glass-card p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-forest)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="mt-6 flex flex-wrap gap-2 items-center justify-center">
            <template x-for="(photo, index) in property.photos" :key="photo.id ?? index">
              <button @click="activePhoto = index"
                      class="h-3 w-3 rounded-full bg-[#CBD5E1] transition-transform duration-200"
                      :class="{ 'bg-[var(--color-gold)] scale-110': activePhoto === index }"></button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

