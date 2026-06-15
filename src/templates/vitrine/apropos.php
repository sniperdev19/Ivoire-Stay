<?php ?>
<!-- SECTION 1 — HERO APROPOS -->
<section class="pt-36 min-h-[480px] bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] relative overflow-hidden flex items-center">
  <div aria-hidden="true" class="absolute w-[360px] h-[360px] rounded-full bg-[rgba(201,168,76,0.22)] blur-[80px] -top-16 -left-16"></div>
  <div aria-hidden="true" class="absolute w-[260px] h-[260px] rounded-full bg-[rgba(201,168,76,0.18)] blur-[70px] bottom-10 right-10"></div>
  <div class="max-w-6xl mx-auto px-6 text-center relative z-10">
    <span class="inline-block px-5 py-2 text-[13px] tracking-[0.24em] text-[#C9A84C] rounded-full border border-[#C9A84C] bg-[rgba(201,168,76,0.2)]">Notre histoire</span>
    <h1 class="font-display text-[64px] leading-[0.95] text-white mt-8">Simplifier l'hôtellerie ivoirienne</h1>
    <p class="mx-auto mt-6 max-w-2xl text-[18px] text-[rgba(255,255,255,0.8)] leading-[1.8]">Ivoire Stay est né d'une conviction : les hôteliers africains méritent des outils de gestion aussi puissants que les meilleures plateformes mondiales.</p>
  </div>
</section>

<!-- SECTION 2 — NOTRE MISSION -->
<section class="bg-[#FAF7F2] py-[100px]">
  <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-14 items-center">
    <div class="relative">
      <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600" alt="Bureau hotelier" class="w-full h-[480px] object-cover rounded-[32px]" />
      <div class="glass-card-strong absolute bottom-6 left-6 p-5 rounded-[24px] max-w-[240px]">
        <div class="flex items-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14s1.5 2 4 2 4-2 4-2 1.5-2 1.5-5.5A3.5 3.5 0 0013 3h-2A3.5 3.5 0 007.5 8.5C7.5 12 8 14 8 14z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v6m-3-3h6" />
          </svg>
          <span class="text-sm font-semibold text-[#1B4332]">Fondé en 2024</span>
        </div>
        <p class="mt-3 text-[13px] text-[#4A5568]">Abidjan, CI</p>
      </div>
    </div>
    <div class="space-y-6">
      <span class="inline-block px-5 py-2 text-[13px] tracking-[0.24em] text-[#C9A84C] rounded-full border border-[#C9A84C] bg-[rgba(201,168,76,0.2)]">Notre mission</span>
      <h2 class="font-display text-[48px] text-[#1B4332]">Une plateforme née en Afrique, pour l'Afrique</h2>
      <div class="space-y-4 text-[16px] text-[#4A5568] leading-[1.8]">
        <p>Nous avons constaté que les hôteliers ivoiriens géraient encore leurs réservations sur des cahiers ou des tableurs. Ivoire Stay change cela.</p>
        <p>Notre SaaS permet à n'importe quel établissement, du petit hôtel de quartier au resort de luxe, de gérer chambres, réservations, clients et finances en un seul endroit.</p>
        <p>Côté voyageurs, notre vitrine offre une expérience de recherche et réservation fluide, adaptée aux réalités locales : paiement Mobile Money, interface en français.</p>
      </div>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="btn-gold inline-flex items-center justify-center px-8 py-4">Démarrer gratuitement</a>
    </div>
  </div>
</section>

<!-- SECTION 3 — CHIFFRES CLÉS -->
<section class="bg-[#1B4332] py-[80px]">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 text-center text-white">
      <div class="border-r border-[rgba(201,168,76,0.3)] pr-6 sm:pr-0 sm:border-r-0 sm:border-b sm:pb-6 xl:border-r xl:pb-0">
        <div class="font-display text-[56px] font-bold text-[#C9A84C]">500+</div>
        <p class="mt-3 text-[14px] text-[rgba(255,255,255,0.7)]">Établissements partenaires</p>
      </div>
      <div class="border-r border-[rgba(201,168,76,0.3)] pr-6 sm:pr-0 sm:border-r-0 sm:border-b sm:pb-6 xl:border-r xl:pb-0">
        <div class="font-display text-[56px] font-bold text-[#C9A84C]">12 000+</div>
        <p class="mt-3 text-[14px] text-[rgba(255,255,255,0.7)]">Réservations traitées</p>
      </div>
      <div class="border-r border-[rgba(201,168,76,0.3)] pr-6 sm:pr-0 sm:border-r-0 sm:border-b sm:pb-6 xl:border-r xl:pb-0">
        <div class="font-display text-[56px] font-bold text-[#C9A84C]">4.9/5</div>
        <p class="mt-3 text-[14px] text-[rgba(255,255,255,0.7)]">Satisfaction client</p>
      </div>
      <div class="pl-6 sm:pl-0">
        <div class="font-display text-[56px] font-bold text-[#C9A84C]">3</div>
        <p class="mt-3 text-[14px] text-[rgba(255,255,255,0.7)]">Villes couvertes</p>
      </div>
    </div>
  </div>
</section>

<!-- SECTION 4 — ÉQUIPE -->
<section class="bg-[#FAF7F2] py-[100px]">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-14">
      <span class="inline-block px-5 py-2 text-[13px] tracking-[0.24em] text-[#C9A84C] rounded-full border border-[#C9A84C] bg-[rgba(201,168,76,0.2)]">Notre équipe</span>
      <h2 class="font-display text-[48px] text-[#1B4332] mt-6">Les talents derrière Ivoire Stay</h2>
    </div>
    <div class="grid gap-6 md:grid-cols-3">
      <div class="glass-card p-7 rounded-[28px] text-center">
        <div class="mx-auto flex h-[80px] w-[80px] items-center justify-center rounded-full bg-[rgba(201,168,76,0.25)]">
          <span class="font-display text-[28px] text-[#C9A84C]">KK</span>
        </div>
        <h3 class="font-display text-[22px] text-[#1B4332] mt-6">Kouamé Koffi</h3>
        <p class="text-[13px] text-[#C9A84C] uppercase tracking-[0.2em] mt-2">CEO & Fondateur</p>
        <p class="mt-4 text-[14px] text-[#4A5568] leading-[1.8]">Entrepreneur tech depuis 10 ans, ex-ingénieur à Orange CI</p>
      </div>
      <div class="glass-card p-7 rounded-[28px] text-center">
        <div class="mx-auto flex h-[80px] w-[80px] items-center justify-center rounded-full bg-[rgba(201,168,76,0.25)]">
          <span class="font-display text-[28px] text-[#C9A84C]">AY</span>
        </div>
        <h3 class="font-display text-[22px] text-[#1B4332] mt-6">Aya Yao</h3>
        <p class="text-[13px] text-[#C9A84C] uppercase tracking-[0.2em] mt-2">CTO</p>
        <p class="mt-4 text-[14px] text-[#4A5568] leading-[1.8]">Développeuse fullstack, passionnée par les solutions africaines</p>
      </div>
      <div class="glass-card p-7 rounded-[28px] text-center">
        <div class="mx-auto flex h-[80px] w-[80px] items-center justify-center rounded-full bg-[rgba(201,168,76,0.25)]">
          <span class="font-display text-[28px] text-[#C9A84C]">MB</span>
        </div>
        <h3 class="font-display text-[22px] text-[#1B4332] mt-6">Marcel Bamba</h3>
        <p class="text-[13px] text-[#C9A84C] uppercase tracking-[0.2em] mt-2">Head of Growth</p>
        <p class="mt-4 text-[14px] text-[#4A5568] leading-[1.8]">Spécialiste marketing digital et hôtellerie en Côte d'Ivoire</p>
      </div>
    </div>
  </div>
</section>

<!-- SECTION 5 — CTA FINAL -->
<section class="bg-[#1B4332] py-[100px]">
  <div class="max-w-6xl mx-auto px-6 text-center text-white">
    <h2 class="font-display text-[48px]">Rejoignez l'aventure Ivoire Stay</h2>
    <p class="mx-auto mt-5 max-w-2xl text-[18px] text-[rgba(255,255,255,0.85)] leading-[1.8]">Que vous soyez hôtelier ou voyageur, Ivoire Stay a quelque chose pour vous</p>
    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="btn-gold px-8 py-4">Créer mon compte</a>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/contact" class="btn-outline-gold px-8 py-4 text-white border-white">Nous contacter</a>
    </div>
  </div>
</section>
