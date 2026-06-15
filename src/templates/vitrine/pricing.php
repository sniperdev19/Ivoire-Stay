<?php ?>
<!-- SECTION 1 — HERO PRICING -->
<section x-data="{ annual: false }" class="pt-36 min-h-[400px] bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] flex items-center">
  <div class="max-w-6xl mx-auto px-6 text-center text-white">
    <span class="inline-block px-5 py-2 text-[13px] tracking-[0.24em] text-[#C9A84C] rounded-full border border-[#C9A84C] bg-[rgba(201,168,76,0.2)]">Tarifs transparents</span>
    <h1 class="font-display text-[64px] mt-8">Choisissez votre plan</h1>
    <p class="mt-5 text-[18px] text-[rgba(255,255,255,0.8)] max-w-2xl mx-auto leading-[1.8]">Démarrez gratuitement, évoluez selon vos besoins</p>
    <div class="mt-10 inline-flex items-center rounded-full bg-[rgba(255,255,255,0.12)] p-1">
      <button @click="annual = false" :class="annual ? 'text-white' : 'text-[#1B4332] bg-white rounded-full'" class="px-6 py-3 text-sm font-semibold">Mensuel</button>
      <button @click="annual = true" :class="annual ? 'text-[#1B4332] bg-white rounded-full' : 'text-white'" class="px-6 py-3 text-sm font-semibold relative">
        Annuel
        <span class="ml-3 inline-flex items-center rounded-full bg-[#C9A84C] px-3 py-1 text-[12px] text-[#1B4332] font-semibold">-20%</span>
      </button>
    </div>
  </div>
</section>

<!-- SECTION 2 — CARDS PLANS -->
<section class="bg-[#FAF7F2] py-[80px]">
  <div class="max-w-6xl mx-auto px-6 grid gap-6 lg:grid-cols-3">
    <div class="glass-card p-8 rounded-[28px]">
      <span class="inline-flex rounded-full bg-[#D9E8D6] px-4 py-2 text-[13px] font-semibold text-[#1B4332]">Gratuit</span>
      <h2 class="font-display text-[36px] text-[#1B4332] mt-6">Starter</h2>
      <div class="mt-6 flex items-end gap-2">
        <span class="font-display text-[52px] font-bold text-[#C9A84C]">0 FCFA</span>
        <span class="text-[16px] text-[#4A5568]">/mois</span>
      </div>
      <p class="mt-4 text-[15px] text-[#4A5568]">Pour démarrer sans risque</p>
      <div class="my-6 h-px bg-[rgba(201,168,76,0.18)]"></div>
      <ul class="space-y-3 text-[14px] text-[#4A5568]">
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Jusqu'à 10 chambres</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Gestion des réservations</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>1 établissement</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Support email</li>
        <li class="flex items-center gap-3 text-[#9CA3AF]"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>Facturation PDF</li>
        <li class="flex items-center gap-3 text-[#9CA3AF]"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>Rapports financiers</li>
        <li class="flex items-center gap-3 text-[#9CA3AF]"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>Multi-établissements</li>
      </ul>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="btn-outline-gold mt-8 block text-center">Démarrer gratuitement</a>
    </div>

    <div class="glass-card-strong relative p-8 rounded-[28px] border-2 border-[#C9A84C]">
      <div class="absolute -top-5 left-1/2 -translate-x-1/2 bg-[#C9A84C] text-white px-4 py-2 rounded-full inline-flex items-center gap-2 shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.96a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.375 2.455a1 1 0 00-.364 1.118l1.287 3.96c.3.921-.755 1.688-1.54 1.118l-3.375-2.455a1 1 0 00-1.176 0l-3.375 2.455c-.784.57-1.838-.197-1.539-1.118l1.286-3.96a1 1 0 00-.364-1.118L2.172 9.387c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.96z"/></svg>
        <span class="text-[13px] font-semibold">Le plus populaire</span>
      </div>
      <span class="inline-flex rounded-full bg-[rgba(201,168,76,0.12)] px-4 py-2 text-[13px] font-semibold text-[#1B4332]">Pro</span>
      <h2 class="font-display text-[36px] text-[#1B4332] mt-6">Pro</h2>
      <div class="mt-6 flex items-end gap-2">
        <span x-text="annual ? '7 200 FCFA' : '9 000 FCFA'" class="font-display text-[52px] font-bold text-[#C9A84C]"></span>
        <span class="text-[16px] text-[#4A5568]">/mois</span>
      </div>
      <p class="mt-4 text-[15px] text-[#4A5568]">Le plan idéal pour développer votre établissement</p>
      <div class="my-6 h-px bg-[rgba(201,168,76,0.18)]"></div>
      <ul class="space-y-3 text-[14px] text-[#4A5568]">
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Chambres illimitées</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Réservations illimitées</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>1 établissement</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Facturation & PDF</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Paiements & dépenses</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Rapports financiers</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Support prioritaire</li>
        <li class="flex items-center gap-3 text-[#9CA3AF]"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>Multi-établissements</li>
        <li class="flex items-center gap-3 text-[#9CA3AF]"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>Boost vitrine</li>
      </ul>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="btn-gold mt-8 block text-center">Choisir Pro</a>
    </div>

    <div class="glass-card p-8 rounded-[28px] bg-[#1B4332] text-white">
      <span class="inline-flex rounded-full bg-[#C9A84C] px-4 py-2 text-[13px] font-semibold text-[#1B4332]">Entreprise</span>
      <h2 class="font-display text-[36px] mt-6">Business</h2>
      <div class="mt-6 flex items-end gap-2">
        <span class="font-display text-[52px] font-bold text-[#C9A84C]">20 000 FCFA</span>
        <span class="text-[16px] text-white/80">/mois</span>
      </div>
      <p class="mt-4 text-[15px] text-white/80">Le plan le plus complet pour les groupes et les chaînes</p>
      <div class="my-6 h-px bg-[rgba(255,255,255,0.12)]"></div>
      <ul class="space-y-3 text-[14px] text-white/80">
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Tout le plan Pro</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Établissements illimités</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Boost vitrine prioritaire</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>API access</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Manager dédié</li>
        <li class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#C9A84C]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>SLA 99.9% uptime</li>
      </ul>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/contact" class="btn-gold mt-8 block text-center text-[#1B4332]">Contacter l'équipe</a>
    </div>
  </div>
</section>

<!-- SECTION 3 — TABLEAU COMPARATIF -->
<section class="bg-[#F0EBE1] py-[80px]">
  <div class="max-w-5xl mx-auto px-6 text-center mb-12">
    <h2 class="font-display text-[48px] text-[#1B4332]">Comparaison détaillée</h2>
  </div>
  <div class="max-w-6xl mx-auto px-6 overflow-hidden glass-card rounded-[24px]">
    <table class="w-full border-collapse text-left">
      <thead class="bg-[#1B4332] text-white">
        <tr>
          <th class="px-6 py-5 font-display text-[18px]">&nbsp;</th>
          <th class="px-6 py-5">Starter</th>
          <th class="px-6 py-5 bg-[rgba(201,168,76,0.08)]">Pro</th>
          <th class="px-6 py-5">Business</th>
        </tr>
      </thead>
      <tbody>
        <tr class="bg-[rgba(201,168,76,0.04)]">
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Chambres</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">10 max</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]">∞</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">∞</td>
        </tr>
        <tr>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Établissements</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">1</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]">1</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">∞</td>
        </tr>
        <tr class="bg-[rgba(201,168,76,0.04)]">
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Réservations</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">∞</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]">∞</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">∞</td>
        </tr>
        <tr>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Facturation PDF</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#9CA3AF]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
        </tr>
        <tr class="bg-[rgba(201,168,76,0.04)]">
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Rapports</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#9CA3AF]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
        </tr>
        <tr>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Multi-établissement</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#9CA3AF]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
        </tr>
        <tr class="bg-[rgba(201,168,76,0.04)]">
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Boost vitrine</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#9CA3AF]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-[#16a34a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></td>
        </tr>
        <tr>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Support</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Email</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)] bg-[rgba(201,168,76,0.08)]">Priorit</td>
          <td class="px-6 py-5 border border-[rgba(201,168,76,0.15)]">Dédié</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<!-- SECTION 4 — FAQ PRICING -->
<section class="bg-[#FAF7F2] py-[80px]">
  <div class="max-w-7xl mx-auto px-6 text-center mb-12">
    <h2 class="font-display text-[48px] text-[#1B4332]">Questions fréquentes</h2>
    <p class="mt-4 text-[16px] text-[#4A5568] max-w-2xl mx-auto">Trouvez la réponse à vos interrogations sur les plans Ivoire Stay.</p>
  </div>
  <div x-data="{ open: null }" class="max-w-7xl mx-auto px-6 space-y-3">
    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===1 ? null : 1">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Puis-je changer de plan à tout moment ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===1}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div x-show="open===1" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Oui, vous pouvez upgrader ou downgrader votre plan à tout moment. Le changement prend effet immédiatement.</div>
    </div>
    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===2 ? null : 2">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Y a-t-il des frais cachés ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===2}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div x-show="open===2" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Non. Les prix affichés sont tout inclus. Seuls les SMS de confirmation sont facturés à l'usage.</div>
    </div>
    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===3 ? null : 3">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Comment fonctionne la période d'essai ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===3}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div x-show="open===3" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Le plan Starter est gratuit à vie. Les plans payants n'ont pas de période d'essai mais vous pouvez annuler à tout moment.</div>
    </div>
    <div class="glass-card rounded-[16px] p-5">
      <button type="button" class="w-full flex items-center justify-between" @click="open = open===4 ? null : 4">
        <span class="font-display text-[18px] text-[#1B4332] text-left">Acceptez-vous le paiement Mobile Money ?</span>
        <svg xmlns="http://www.w3.org/2000/svg" :class="{'rotate-180': open===4}" class="h-5 w-5 text-[#1B4332] transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div x-show="open===4" x-transition class="mt-4 text-[15px] text-[#4A5568] leading-[1.8]">Oui ! Orange Money, MTN Money et Wave sont acceptés pour tous les abonnements payants.</div>
    </div>
  </div>
</section>

<!-- SECTION 5 — CTA FINAL -->
<section class="bg-[#1B4332] py-[80px]">
  <div class="max-w-6xl mx-auto px-6 text-center text-white">
    <h2 class="font-display text-[52px]">Prêt à moderniser votre établissement ?</h2>
    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/register" class="btn-gold px-8 py-4">Démarrer gratuitement →</a>
      <a href="<?= $base_url ?? rtrim(APP_URL, '/') ?>/contact" class="btn-outline-gold px-8 py-4 text-white border-white">Parler à un expert</a>
    </div>
  </div>
</section>
