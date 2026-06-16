<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php
// Template page réservation injectée dans le layout vitrine.
// Variables disponibles : $title, $room_id
/** @var int|string|null $room_id Identifiant de la chambre, injecté par Response::render(). */
$room_id = $room_id ?? null;
$pageCss = 'booking';
$pageJs  = 'booking';
?>

<div x-data="bookingPage('<?= rtrim($base_url, '/') ?>', <?= json_encode($room_id) ?>)"
 x-init="init()"
 class="pt-36 min-h-screen bg-[var(--color-cream)]">

  <!-- BARRE DE PROGRESSION -->
  <div class="sticky top-20 z-40 glass-card-strong bg-[rgba(250,247,242,0.95)] backdrop-blur-md border-b border-[rgba(201,168,76,0.2)] py-4 px-4 max-w-4xl mx-auto">
    <div class="flex items-center gap-3">
      <div class="flex items-center gap-3 w-full">
        <div class="flex items-center gap-3 w-full">
          <div class="flex flex-col items-center gap-2">
            <div class="flex items-center justify-center h-10 w-10 rounded-full"
                 x-bind:class="step === 1 ? 'bg-[var(--color-gold)] text-white' : step > 1 ? 'bg-[var(--color-forest)] text-white' : 'bg-[rgba(201,168,76,0.15)] text-[var(--color-gold)] border border-[var(--color-gold)]'">
              <template x-show="step > 1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </template>
              <template x-show="step === 1">
                1
              </template>
            </div>
            <div class="text-[12px] text-[#4A5568] uppercase">Dates</div>
          </div>

          <div class="flex-1 h-0.5"
               x-bind:class="step > 1 ? 'bg-[var(--color-gold)]' : 'bg-[rgba(201,168,76,0.2)]'"></div>

          <div class="flex flex-col items-center gap-2">
            <div class="flex items-center justify-center h-10 w-10 rounded-full"
                 x-bind:class="step === 2 ? 'bg-[var(--color-gold)] text-white' : step > 2 ? 'bg-[var(--color-forest)] text-white' : 'bg-[rgba(201,168,76,0.15)] text-[var(--color-gold)] border border-[var(--color-gold)]'">
              <template x-show="step > 2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </template>
              <template x-show="step === 2">
                2
              </template>
            </div>
            <div class="text-[12px] text-[#4A5568] uppercase">Vos informations</div>
          </div>

          <div class="flex-1 h-0.5"
               x-bind:class="step > 2 ? 'bg-[var(--color-gold)]' : 'bg-[rgba(201,168,76,0.2)]'"></div>

          <div class="flex flex-col items-center gap-2">
            <div class="flex items-center justify-center h-10 w-10 rounded-full"
                 x-bind:class="step === 3 ? 'bg-[var(--color-gold)] text-white' : 'bg-[rgba(201,168,76,0.15)] text-[var(--color-gold)] border border-[var(--color-gold)]'">
              3
            </div>
            <div class="text-[12px] text-[#4A5568] uppercase">Confirmation</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-8">

    <!-- COLONNE GAUCHE - ÉTAPES -->
    <div class="space-y-8">

      <!-- ÉTAPE 1 - Dates -->
      <section x-show="step === 1" class="space-y-6">
        <div>
          <h2 class="font-display text-[32px] text-[var(--color-forest)]">Choisissez vos dates</h2>
          <p class="mt-2 text-[14px] text-[#718096]">Sélectionnez vos dates d'arrivée et de départ</p>
        </div>
        <div class="glass-card-strong p-8 rounded-[28px]">
          <div class="space-y-6">
            <div>
              <label class="block text-[11px] uppercase tracking-[0.24em] text-[var(--color-gold)]">DATE D'ARRIVÉE</label>
              <input type="date"
                     x-model="form.check_in"
                     :min="new Date().toISOString().split('T')[0]"
                     class="w-full mt-3 px-5 py-4 rounded-[16px] bg-[var(--color-cream)] text-[16px]"
                     style="border:1px solid rgba(201,168,76,0.3);"
                     :class="errors.check_in ? 'border-red-500' : ''" />
              <p x-show="errors.check_in" class="mt-2 text-[12px] text-red-600" x-text="errors.check_in"></p>
            </div>

            <div>
              <label class="block text-[11px] uppercase tracking-[0.24em] text-[var(--color-gold)]">DATE DE DÉPART</label>
              <input type="date"
                     x-model="form.check_out"
                     :min="form.check_in || new Date().toISOString().split('T')[0]"
                     class="w-full mt-3 px-5 py-4 rounded-[16px] bg-[var(--color-cream)] text-[16px]"
                     style="border:1px solid rgba(201,168,76,0.3);"
                     :class="errors.check_out ? 'border-red-500' : ''" />
              <p x-show="errors.check_out" class="mt-2 text-[12px] text-red-600" x-text="errors.check_out"></p>
            </div>

            <div x-show="nights > 0" class="glass-card p-5 rounded-[16px] bg-[rgba(201,168,76,0.08)] border border-[rgba(201,168,76,0.2)]">
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c2.21 0 4 1.79 4 4s-1.79 4-4 4-4-1.79-4-4 1.79-4 4-4zm0 14a7.975 7.975 0 01-7.5-5.5M12 21c4.418 0 8-3.582 8-8 0-1.657-.507-3.197-1.373-4.48"/>
                  </svg>
                  <div>
                    <div class="font-display text-[24px] text-[var(--color-forest)]" x-text="nights + ' nuit(s)'"></div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="font-display text-[28px] text-[var(--color-forest)]" x-text="formatPrice(totalPrice)"></div>
                  <div class="text-[12px] text-[#718096]">/séjour</div>
                </div>
              </div>
            </div>

            <button @click.prevent="nextStep()"
                    class="btn-gold w-full h-[52px] rounded-[16px]">Continuer → Vos informations</button>
          </div>
        </div>
      </section>

      <!-- ÉTAPE 2 - Informations client -->
      <section x-show="step === 2" class="space-y-6">
        <div>
          <h2 class="font-display text-[32px] text-[var(--color-forest)]">Vos informations</h2>
          <p class="mt-2 text-[14px] text-[#718096]">Ces informations seront utilisées pour votre réservation</p>
        </div>
        <div class="glass-card-strong p-8 rounded-[28px] space-y-6">
          <div class="space-y-4">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[var(--color-gold)]">NOM COMPLET</label>
            <div class="relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.766 0 5.347.836 7.579 2.273M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <input type="text"
                     x-model="form.client_name"
                     placeholder="Jean Kouassi"
                     class="w-full pl-12 pr-5 py-4 rounded-[12px] bg-[var(--color-cream)] text-[16px]"
                     style="border:1px solid rgba(201,168,76,0.3);"
                     :class="errors.client_name ? 'border-red-500' : ''" />
            </div>
            <p x-show="errors.client_name" class="text-[12px] text-red-600" x-text="errors.client_name"></p>
          </div>

          <div class="space-y-4">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[var(--color-gold)]">ADRESSE EMAIL</label>
            <div class="relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m0 0l4-4m-4 4l4 4"/>
              </svg>
              <input type="email"
                     x-model="form.client_email"
                     placeholder="jean@exemple.ci"
                     class="w-full pl-12 pr-5 py-4 rounded-[12px] bg-[var(--color-cream)] text-[16px]"
                     style="border:1px solid rgba(201,168,76,0.3);"
                     :class="errors.client_email ? 'border-red-500' : ''" />
            </div>
            <p x-show="errors.client_email" class="text-[12px] text-red-600" x-text="errors.client_email"></p>
          </div>

          <div class="space-y-3">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[var(--color-gold)]">TÉLÉPHONE (Mobile Money)</label>
            <div class="relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h2l.4 2M7 13h10l4-8H5.4M7 13l-1.2 6.4a1 1 0 001 1.2h11.2a1 1 0 001-1.2L17 13M7 13h10"/>
              </svg>
              <input type="tel"
                     x-model="form.client_phone"
                     placeholder="+225 07 00 00 00 00"
                     class="w-full pl-12 pr-5 py-4 rounded-[12px] bg-[var(--color-cream)] text-[16px]"
                     style="border:1px solid rgba(201,168,76,0.3);"
                     :class="errors.client_phone ? 'border-red-500' : ''" />
            </div>
            <p class="text-[11px] italic text-[#718096]">Ce numéro sera utilisé pour le paiement Mobile Money</p>
            <p x-show="errors.client_phone" class="text-[12px] text-red-600" x-text="errors.client_phone"></p>
          </div>

          <div class="glass-card p-4 rounded-[16px] border border-[rgba(27,67,50,0.15)] bg-[rgba(27,67,50,0.06)] flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mt-1" style="color:#1B4332;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <div>
              <p class="text-[14px] text-[var(--color-forest)]">Paiement 100% sécurisé via Orange Money, MTN Money ou Wave</p>
              <p class="text-[13px] text-[var(--color-gold)] font-semibold mt-2">Orange Money · MTN Money · Wave</p>
            </div>
          </div>

          <div class="flex items-center justify-between gap-4">
            <button @click.prevent="prevStep()" class="btn-outline-gold">← Retour</button>
            <button @click.prevent="nextStep()" class="btn-gold">Vérifier ma réservation →</button>
          </div>
        </div>
      </section>

      <!-- ÉTAPE 3 - Récapitulatif avant paiement -->
      <section x-show="step === 3 && !booking" class="space-y-6">
        <div>
          <h2 class="font-display text-[32px] text-[var(--color-forest)]">Récapitulatif de votre réservation</h2>
        </div>
        <div class="glass-card-strong p-8 rounded-[28px] space-y-6">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
            <img x-bind:src="room?.photos?.[0]?.url || 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800'"
                 alt="Photo chambre"
                 class="h-[100px] w-[100px] rounded-[12px] object-cover" />
            <div class="flex-1">
              <div class="font-display text-[20px] text-[var(--color-forest)]" x-text="room?.name"></div>
              <div class="mt-2 text-[14px] text-[#718096]" x-text="room?.establishment_name ?? ''"></div>
            </div>
          </div>

          <div class="border-t border-[rgba(201,168,76,0.2)] pt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <div class="text-[14px] text-[#718096]">Arrivée</div>
                <div class="font-display text-[18px] text-[var(--color-forest)]" x-text="formatDate(form.check_in)"></div>
              </div>
              <div>
                <div class="text-[14px] text-[#718096]">Départ</div>
                <div class="font-display text-[18px] text-[var(--color-forest)]" x-text="formatDate(form.check_out)"></div>
              </div>
              <div>
                <div class="text-[14px] text-[#718096]">Durée</div>
                <div class="font-display text-[18px] text-[var(--color-forest)]" x-text="nights + ' nuit(s)'"></div>
              </div>
              <div>
                <div class="text-[14px] text-[#718096]">Client</div>
                <div class="font-display text-[18px] text-[var(--color-forest)]" x-text="form.client_name"></div>
              </div>
              <div>
                <div class="text-[14px] text-[#718096]">Email</div>
                <div class="font-display text-[18px] text-[var(--color-forest)]" x-text="form.client_email"></div>
              </div>
              <div>
                <div class="text-[14px] text-[#718096]">Téléphone</div>
                <div class="font-display text-[18px] text-[var(--color-forest)]" x-text="form.client_phone"></div>
              </div>
            </div>
          </div>

          <div class="border-t border-[rgba(201,168,76,0.2)] pt-4 flex items-center justify-between">
            <div class="font-display text-[24px] text-[var(--color-forest)]">Total à payer</div>
            <div class="font-display text-[32px] text-[var(--color-gold)]" x-text="formatPrice(totalPrice)"></div>
          </div>

          <div x-show="bookingError" class="glass-card p-4 rounded-[16px] bg-[rgba(248,113,113,0.08)] border border-[rgba(248,113,113,0.2)] text-red-700 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-text="bookingError"></span>
          </div>

          <div class="flex flex-col sm:flex-row items-center gap-4">
            <button @click.prevent="step = 2" class="btn-outline-gold w-full">← Modifier</button>
            <button @click.prevent="submitBooking()"
                    :disabled="submitting"
                    class="btn-gold w-full relative flex items-center justify-center gap-2">
              <svg x-show="submitting" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v4m0 8v4m8-8h-4M4 12H0"/>
              </svg>
              <span x-show="!submitting">Confirmer et payer</span>
              <span x-show="submitting">Traitement...</span>
            </button>
          </div>
        </div>
      </section>

      <!-- ÉTAPE 3 - Succès -->
      <section x-show="step === 3 && booking" class="space-y-8">
        <div class="text-center py-16">
          <div class="mx-auto mb-6 flex h-[120px] w-[120px] items-center justify-center rounded-full border-2 border-[var(--color-gold)] bg-[rgba(201,168,76,0.15)] animate-[scaleIn_0.5s_ease-out]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[var(--color-gold)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h2 class="font-display text-[40px] text-[var(--color-forest)]">Réservation confirmée !</h2>
          <p class="mt-4 text-[16px] text-[#4A5568]">Votre réservation a été enregistrée avec succès. Un email de confirmation vous sera envoyé.</p>
        </div>
        <div class="glass-card p-6 rounded-[20px] max-w-md mx-auto space-y-4">
          <div class="font-display text-[24px] text-[var(--color-gold)] font-semibold" x-text="'N° de réservation ' + (booking.id ?? booking.booking_id)"></div>
          <div class="text-[16px] text-[var(--color-forest)] font-display" x-text="room?.name"></div>
          <div class="text-[14px] text-[#718096]" x-text="formatDate(form.check_in) + ' → ' + formatDate(form.check_out)"></div>
          <div class="text-[18px] text-[var(--color-forest)] font-semibold">Montant total : <span x-text="formatPrice(totalPrice)"></span></div>
        </div>
        <div class="glass-card p-5 rounded-[20px] bg-[rgba(27,67,50,0.06)]">
          <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mt-1" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h2l.4 2M7 13h10l4-8H5.4M7 13l-1.2 6.4a1 1 0 001 1.2h11.2a1 1 0 001-1.2L17 13M7 13h10"/>
            </svg>
            <p class="text-[14px] text-[var(--color-forest)]">Vous recevrez un SMS sur votre numéro <strong x-text="form.client_phone"></strong> pour finaliser le paiement Mobile Money.</p>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <a href="/" class="btn-gold w-full sm:w-auto text-center">Retour à l'accueil</a>
          <a href="/search" class="btn-outline-gold w-full sm:w-auto text-center">Voir d'autres établissements</a>
        </div>
      </section>
    </div>

    <!-- COLONNE DROITE - RÉCAPITULATIF -->
    <aside x-show="(step === 1 || step === 2) && !loading" class="hidden lg:block sticky top-24 self-start">
      <div class="glass-card-strong p-7 rounded-[28px]">
        <div class="text-[12px] uppercase tracking-[0.24em] text-[var(--color-gold)]">Votre sélection</div>
        <div class="border-t border-[rgba(201,168,76,0.2)] mt-4 pt-4 space-y-4">
          <div x-show="room" class="space-y-4">
            <img x-bind:src="room.photos?.[0]?.url || 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800'"
                 alt="Photo de la chambre"
                 class="w-full h-[160px] rounded-[16px] object-cover" />
            <div>
              <div class="font-display text-[22px] text-[var(--color-forest)]" x-text="room.name"></div>
              <div class="mt-2 inline-flex rounded-full bg-[rgba(201,168,76,0.15)] px-3 py-1 text-[13px] text-[var(--color-forest)]" x-text="room.type"></div>
            </div>
            <div class="flex items-center gap-2 text-[#718096]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span x-text="room.establishment_name ?? ''"></span>
            </div>
          </div>

          <div x-show="form.check_in && form.check_out" class="border-t border-[rgba(201,168,76,0.2)] pt-4">
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <div class="text-[12px] uppercase tracking-[0.2em] text-[var(--color-gold)]">Arrivée</div>
                <div class="text-[14px] text-[var(--color-forest)]" x-text="formatDate(form.check_in)"></div>
              </div>
              <div class="space-y-1">
                <div class="text-[12px] uppercase tracking-[0.2em] text-[var(--color-gold)]">Départ</div>
                <div class="text-[14px] text-[var(--color-forest)]" x-text="formatDate(form.check_out)"></div>
              </div>
            </div>
          </div>

          <div class="border-t border-[rgba(201,168,76,0.2)] pt-4 space-y-3">
            <div class="flex items-center justify-between text-[14px] text-[#4A5568]">
              <span x-text="formatPrice(room.base_price)"></span>
              <span x-text="nights + ' x nuit(s)'">0 x nuit(s)</span>
            </div>
            <div class="flex items-center justify-between text-[14px] text-[#4A5568]">
              <span>Taxes & frais</span>
              <span>Inclus</span>
            </div>
            <div class="border-t border-[rgba(201,168,76,0.2)] pt-3 flex items-center justify-between">
              <span class="font-display text-[26px] text-[var(--color-forest)]">Total</span>
              <span class="font-display text-[26px] text-[var(--color-gold)]" x-text="formatPrice(totalPrice)"></span>
            </div>
          </div>

          <div class="space-y-3 mt-4">
            <div class="flex items-start gap-3 text-[13px] text-[#4A5568]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-1" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span>Annulation gratuite 24h avant</span>
            </div>
            <div class="flex items-start gap-3 text-[13px] text-[#4A5568]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-1" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span>Confirmation immédiate</span>
            </div>
            <div class="flex items-start gap-3 text-[13px] text-[#4A5568]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-1" style="color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span>Support disponible 24h/24</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- SKELETON COLONNE DROITE LORS DU CHARGEMENT -->
      <aside x-show="loading" class="hidden lg:block sticky top-24 self-start">
        <div class="space-y-4">
          <div class="glass-card h-48 shimmer-bg rounded-[28px]"></div>
          <div class="glass-card h-40 shimmer-bg rounded-[28px]"></div>
          <div class="glass-card h-32 shimmer-bg rounded-[28px]"></div>
        </div>
      </aside>
    </div>
  </div>

