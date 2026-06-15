<?php ?>
<!-- SECTION 1 — HERO CONTACT -->
<section class="pt-36 min-h-[360px] bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] flex items-center">
  <div class="max-w-6xl mx-auto px-6 text-center text-white">
    <h1 class="font-display text-[56px]">Parlons de votre projet</h1>
    <p class="mt-5 text-[18px] text-[rgba(255,255,255,0.8)]">Notre équipe répond sous 24h</p>
  </div>
</section>

<!-- SECTION 2 — CONTENU PRINCIPAL -->
<section class="bg-[#FAF7F2] py-[100px]">
  <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16">
    <!-- Colonne gauche — Formulaire -->
    <div x-data="{ sent: false }">
      <div class="glass-card-strong p-10 rounded-[32px]">
        <h2 class="font-display text-[32px] text-[#1B4332]">Envoyez-nous un message</h2>
        <form class="mt-8 space-y-6" @submit.prevent="sent = true">
          <div class="space-y-2">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[#C9A84C]">Nom complet</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#C9A84C]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 0112 15c2.192 0 4.21.788 5.879 2.105M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </span>
              <input type="text" class="w-full bg-[#FAF7F2] border border-[#C9A84C] rounded-[12px] py-3 pl-12 pr-4" placeholder="Votre nom" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[#C9A84C]">Email</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#C9A84C]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12l-4 4m0 0l-4-4m4 4V8" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 8v8a2 2 0 01-2 2H6a2 2 0 01-2-2V8" />
                </svg>
              </span>
              <input type="email" class="w-full bg-[#FAF7F2] border border-[#C9A84C] rounded-[12px] py-3 pl-12 pr-4" placeholder="votre@email.com" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[#C9A84C]">Téléphone</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#C9A84C]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.5a1 1 0 01.94.658l1 2.5a1 1 0 01-.217 1.053L7.5 9.5a11.042 11.042 0 005.657 5.657l1.789-1.789a1 1 0 011.053-.217l2.5 1a1 1 0 01.658.94V19a2 2 0 01-2 2H5a2 2 0 01-2-2V5z" />
                </svg>
              </span>
              <input type="tel" class="w-full bg-[#FAF7F2] border border-[#C9A84C] rounded-[12px] py-3 pl-12 pr-4" placeholder="+225 01 23 45 67 89" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[#C9A84C]">Sujet</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#C9A84C]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V10a2 2 0 012-2h2" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8V6a5 5 0 0110 0v2" />
                </svg>
              </span>
              <select class="w-full bg-[#FAF7F2] border border-[#C9A84C] rounded-[12px] py-3 pl-12 pr-4">
                <option>Question générale</option>
                <option>Support technique</option>
                <option>Partenariat hôtelier</option>
                <option>Presse & médias</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-[11px] uppercase tracking-[0.24em] text-[#C9A84C]">Message</label>
            <div class="relative">
              <span class="absolute left-4 top-4 text-[#C9A84C]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z" />
                </svg>
              </span>
              <textarea rows="5" class="w-full bg-[#FAF7F2] border border-[#C9A84C] rounded-[12px] py-3 pl-12 pr-4" placeholder="Décrivez votre besoin..."></textarea>
            </div>
          </div>

          <button type="submit" class="btn-gold w-full h-[52px]">Envoyer le message →</button>
        </form>
        <div x-show="sent" x-transition class="mt-8 glass-card p-6 rounded-[24px] border border-[#C9A84C] text-[#1B4332]">
          <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
            </svg>
            <div>
              <p class="font-semibold">Message envoyé !</p>
              <p class="text-[14px] text-[#4A5568]">Nous vous répondrons sous 24h.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Colonne droite — Infos contact -->
    <div class="space-y-6">
      <div class="glass-card p-6 rounded-[20px]">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[rgba(201,168,76,0.15)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div>
            <p class="text-[13px] uppercase tracking-[0.24em] text-[#1B4332]">Notre bureau</p>
            <h3 class="font-display text-[20px] text-[#1B4332] mt-2">Cocody, Abidjan</h3>
          </div>
        </div>
        <p class="mt-4 text-[14px] text-[#4A5568]">Côte d'Ivoire</p>
      </div>

      <div class="glass-card p-6 rounded-[20px]">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[rgba(201,168,76,0.15)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.5a1 1 0 01.94.658l1 2.5a1 1 0 01-.217 1.053L7.5 9.5a11.042 11.042 0 005.657 5.657l1.789-1.789a1 1 0 011.053-.217l2.5 1a1 1 0 01.658.94V19a2 2 0 01-2 2H5a2 2 0 01-2-2V5z" />
            </svg>
          </div>
          <div>
            <p class="text-[13px] uppercase tracking-[0.24em] text-[#1B4332]">Appelez-nous</p>
            <h3 class="font-display text-[20px] text-[#1B4332] mt-2">+225 01 23 45 67 89</h3>
          </div>
        </div>
        <p class="mt-4 text-[14px] text-[#4A5568]">Lun–Ven, 8h–18h</p>
      </div>

      <div class="glass-card p-6 rounded-[20px]">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[rgba(201,168,76,0.15)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-9 5V3" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8" />
            </svg>
          </div>
          <div>
            <p class="text-[13px] uppercase tracking-[0.24em] text-[#1B4332]">Écrivez-nous</p>
            <h3 class="font-display text-[20px] text-[#1B4332] mt-2">support@ivoire-stay.ci</h3>
          </div>
        </div>
        <p class="mt-4 text-[14px] text-[#4A5568]">Réponse sous 24h</p>
      </div>

      <div class="glass-card p-6 rounded-[20px]">
        <p class="text-[14px] font-semibold text-[#1B4332]">Réseaux sociaux</p>
        <div class="mt-4 space-y-3">
          <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>" class="btn-outline-gold block text-center">LinkedIn</a>
          <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>" class="btn-outline-gold block text-center">Twitter / X</a>
          <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>" class="btn-outline-gold block text-center">Instagram</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SECTION 3 — FAQ -->
<section class="bg-[#F0EBE1] py-[80px]">
  <div class="max-w-7xl mx-auto px-6 text-center mb-12">
    <h2 class="font-display text-[48px] text-[#1B4332]">Foire aux questions</h2>
    <p class="mt-4 text-[16px] text-[#4A5568] max-w-2xl mx-auto">Retrouvez les réponses aux questions les plus fréquentes sur Ivoire Stay.</p>
  </div>
  <div x-data="{ open: null }" class="max-w-7xl mx-auto px-6 space-y-3">
    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===1 ? null : 1">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Ivoire Stay est-il gratuit ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===1}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div x-show="open===1" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Oui ! Notre plan Starter est 100% gratuit et vous permet de gérer jusqu'à 10 chambres sans limitation de durée.</div>
    </div>

    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===2 ? null : 2">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Quels modes de paiement acceptez-vous ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===2}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div x-show="open===2" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Nous acceptons Orange Money, MTN Money, Wave et les virements bancaires pour les abonnements Pro et Business.</div>
    </div>

    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===3 ? null : 3">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Puis-je gérer plusieurs établissements ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===3}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div x-show="open===3" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Oui, avec notre plan Business vous pouvez gérer un nombre illimité d'établissements depuis un seul dashboard.</div>
    </div>

    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===4 ? null : 4">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Y a-t-il une application mobile ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===4}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div x-show="open===4" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Notre dashboard SaaS est une PWA (Progressive Web App) optimisée mobile. Une app native est en développement.</div>
    </div>

    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===5 ? null : 5">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Comment fonctionne le support ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===5}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div x-show="open===5" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Le support est disponible par email et chat 24h/24. Les clients Pro et Business bénéficient d'un support prioritaire.</div>
    </div>
  </div>
</section>
