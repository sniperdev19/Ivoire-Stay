<?php
$base = $base_url ?? rtrim(APP_URL, '/');

$proMonthly      = \Services\PlanPricingService::price('pro', 'monthly');
$proYearly       = \Services\PlanPricingService::price('pro', 'yearly');
$proYearlyPerMo  = round($proYearly / 12);
$bizMonthly      = \Services\PlanPricingService::price('business', 'monthly');
$bizYearly       = \Services\PlanPricingService::price('business', 'yearly');
$bizYearlyPerMo  = round($bizYearly / 12);
$fmt = fn($n) => number_format($n, 0, ',', ' ');
?>

<div class="pricing-page" x-data="pricingPage()">

  <section class="pr-hero">
    <div class="pr-hero-overlay"></div>
    <div class="pr-hero-ghost">Tarifs</div>
    <div class="pr-hero-content">
      <div class="pr-hero-rule"></div>
      <span class="pr-hero-tag">Plans &amp; tarifs</span>
      <h1 class="pr-hero-title">Des tarifs<br>clairs &amp; <em>flexibles.</em></h1>
      <p class="pr-hero-sub">Commencez gratuitement et évoluez selon vos besoins. Aucun engagement, annulation à tout
        moment.</p>
    </div>
  </section>

  <div class="pr-toggle-wrap">
    <div class="pr-toggle">
      <span :class="!annual ? 'active' : ''">Mensuel</span>
      <button class="pr-switch" :class="annual ? 'pr-switch-active' : ''" @click="annual = !annual" type="button">
        <div class="pr-switch-thumb"></div>
      </button>
      <span :class="annual ? 'active' : ''">Annuel</span>
      <span class="pr-save-badge">–20%</span>
    </div>
  </div>

  <section class="pr-plans">
    <!-- STARTER -->
    <div class="pr-card">
      <div class="pr-card-top"></div>
      <div class="pr-card-body">
        <div class="pr-plan-name">Gratuit</div>
        <p class="pr-plan-tagline">Pour tester et démarrer sans engagement.</p>
        <div class="pr-price-block">
          <div class="pr-price">0 <small>FCFA</small></div>
          <div class="pr-price-period">Toujours gratuit</div>
        </div>
        <div class="pr-divider"></div>
        <ul class="pr-feature-list">
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Jusqu'à <strong>10 chambres</strong></span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Réservations en ligne</span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Support par email</span></li>
          <li class="pr-feature pr-feature-disabled"><span class="pr-feature-check"><svg
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#1B4332" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Facturation</span></li>
          <li class="pr-feature pr-feature-disabled"><span class="pr-feature-check"><svg
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#1B4332" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Gestion des paiements</span></li>
          <li class="pr-feature pr-feature-disabled"><span class="pr-feature-check"><svg
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#1B4332" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Rapports &amp; Analyses</span></li>
        </ul>
        <a href="<?= $base ?>/register?plan=starter" class="pr-card-btn pr-btn-outline">Démarrer gratuitement</a>
      </div>
    </div>

    <!-- PRO -->
    <div class="pr-card pr-card-popular">
      <div class="pr-card-top pr-card-top-gold"></div>
      <span class="pr-popular-badge">Le plus populaire</span>
      <div class="pr-card-body">
        <div class="pr-plan-name">Pro</div>
        <p class="pr-plan-tagline">Pour les établissements qui veulent accélérer.</p>
        <div class="pr-price-block">
          <div class="pr-price"><span x-text="annual ? '<?= $fmt($proYearlyPerMo) ?>' : '<?= $fmt($proMonthly) ?>'"><?= $fmt($proMonthly) ?></span> <small>FCFA</small></div>
          <div class="pr-price-period">/mois, HT</div>
          <div class="pr-old-price" x-show="annual"><?= $fmt($proMonthly) ?> FCFA/mois sans engagement annuel</div>
        </div>
        <div class="pr-divider"></div>
        <ul class="pr-feature-list">
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Chambres <strong>illimitées</strong></span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Facturation &amp; gestion des paiements</span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Suivi des dépenses</span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Rapports &amp; Analyses</span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Export PDF</span></li>
          <li class="pr-feature pr-feature-disabled"><span class="pr-feature-check"><svg
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#1B4332" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Boost vitrine</span></li>
          <li class="pr-feature pr-feature-disabled"><span class="pr-feature-check"><svg
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#1B4332" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Multi-établissements</span></li>
        </ul>
        <a :href="'<?= $base ?>/register?plan=pro&billing=' + (annual ? 'yearly' : 'monthly')"
          class="pr-card-btn pr-btn-gold">Choisir Pro →</a>
      </div>
    </div>

    <!-- BUSINESS -->
    <div class="pr-card">
      <div class="pr-card-top" style="background:var(--forest);"></div>
      <div class="pr-card-body">
        <div class="pr-plan-name">Business</div>
        <p class="pr-plan-tagline">Pour les groupes hôteliers et gestionnaires multi-sites.</p>
        <div class="pr-price-block">
          <div class="pr-price"><span x-text="annual ? '<?= $fmt($bizYearlyPerMo) ?>' : '<?= $fmt($bizMonthly) ?>'"><?= $fmt($bizMonthly) ?></span> <small>FCFA</small></div>
          <div class="pr-price-period">/mois, HT</div>
          <div class="pr-old-price" x-show="annual"><?= $fmt($bizMonthly) ?> FCFA/mois sans engagement annuel</div>
        </div>
        <div class="pr-divider"></div>
        <ul class="pr-feature-list">
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Tout le plan Pro inclus</span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span><strong>Multi-établissements</strong> illimités</span></li>
          <li class="pr-feature"><span class="pr-feature-check"><svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg></span><span>Boost vitrine</span></li>
        </ul>
        <a :href="'<?= $base ?>/register?plan=business&billing=' + (annual ? 'yearly' : 'monthly')"
          class="pr-card-btn pr-btn-outline">Choisir Business →</a>
      </div>
    </div>
  </section>

  <!-- COMPARISON TABLE -->
  <section class="pr-compare">
    <div class="pr-compare-header">
      <span class="pr-compare-tag">Comparatif détaillé</span>
      <h2 class="pr-compare-title">Comparez les plans</h2>
    </div>
    <div class="pr-table-wrap">
    <table class="pr-table">
      <thead>
        <tr>
          <th>Fonctionnalité</th>
          <th>Gratuit</th>
          <th>Pro</th>
          <th>Business</th>
        </tr>
      </thead>
      <tbody>
        <tr class="pr-cat-row">
          <td colspan="4">Gestion des chambres</td>
        </tr>
        <tr>
          <td>Nombre de chambres</td>
          <td>10</td>
          <td>Illimité</td>
          <td>Illimité</td>
        </tr>
        <tr>
          <td>Établissements</td>
          <td>1</td>
          <td>1</td>
          <td>Illimité</td>
        </tr>
        <tr>
          <td>Réservations en ligne</td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr class="pr-cat-row">
          <td colspan="4">Facturation &amp; paiements</td>
        </tr>
        <tr>
          <td>Facturation</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr>
          <td>Gestion des paiements</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr>
          <td>Contrôle du paiement en ligne</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr>
          <td>Export PDF</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr class="pr-cat-row">
          <td colspan="4">Suivi &amp; analyses</td>
        </tr>
        <tr>
          <td>Suivi des dépenses</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr>
          <td>Rapports &amp; Analyses</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr class="pr-cat-row">
          <td colspan="4">Visibilité &amp; multi-sites</td>
        </tr>
        <tr>
          <td>Boost vitrine</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr>
          <td>Multi-établissements</td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-dash">—</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
        <tr class="pr-cat-row">
          <td colspan="4">Support</td>
        </tr>
        <tr>
          <td>Support par email</td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
          <td><span class="pr-check">✓</span></td>
        </tr>
      </tbody>
    </table>
    </div>
  </section>

  <!-- FAQ -->
  <section class="pr-faq">
    <div class="pr-faq-left">
      <div class="pr-faq-ghost">FAQ</div>
      <div class="pr-faq-content">
        <div class="pr-faq-rule"></div>
        <span class="pr-faq-tag">Questions fréquentes</span>
        <h2 class="pr-faq-title">Tout ce<br>que vous <em>voulez<br>savoir.</em></h2>
      </div>
    </div>
    <div class="pr-faq-right">
      <div class="pr-faq-item" :class="open===1?'pr-faq-open':''">
        <button class="pr-faq-q" @click="open=open===1?null:1" type="button"><span>Puis-je changer de plan à tout moment
            ?</span><span class="pr-faq-icon">+</span></button>
        <div class="pr-faq-a" x-show="open===1" x-transition>Oui, vous pouvez changer de plan à tout moment depuis votre
          tableau de bord. La mise à niveau est immédiate, le rétrogradage prend effet à la prochaine période.</div>
      </div>
      <div class="pr-faq-item" :class="open===2?'pr-faq-open':''">
        <button class="pr-faq-q" @click="open=open===2?null:2" type="button"><span>Y a-t-il une période d'essai
            ?</span><span class="pr-faq-icon">+</span></button>
        <div class="pr-faq-a" x-show="open===2" x-transition>Le plan Gratuit est gratuit et sans limite de temps. Vous
          pouvez tester les fonctionnalités Pro pendant 14 jours sans carte bancaire.</div>
      </div>
      <div class="pr-faq-item" :class="open===3?'pr-faq-open':''">
        <button class="pr-faq-q" @click="open=open===3?null:3" type="button"><span>Comment fonctionnent les paiements
            SaaS ?</span><span class="pr-faq-icon">+</span></button>
        <div class="pr-faq-a" x-show="open===3" x-transition>Vos abonnements sont facturés mensuellement ou annuellement
          par Wave. Une facture PDF est émise automatiquement.</div>
      </div>
      <div class="pr-faq-item" :class="open===4?'pr-faq-open':''">
        <button class="pr-faq-q" @click="open=open===4?null:4" type="button"><span>Mes données sont-elles sécurisées
            ?</span><span class="pr-faq-icon">+</span></button>
        <div class="pr-faq-a" x-show="open===4" x-transition>Oui. Toutes les données sont chiffrées, hébergées en
          Afrique de l'Ouest et sauvegardées quotidiennement. Vous restez propriétaire de vos données.</div>
      </div>
      <div class="pr-faq-item" :class="open===5?'pr-faq-open':''">
        <button class="pr-faq-q" @click="open=open===5?null:5" type="button"><span>Puis-je gérer plusieurs hôtels
            ?</span><span class="pr-faq-icon">+</span></button>
        <div class="pr-faq-a" x-show="open===5" x-transition>La gestion multi-établissements est disponible
          exclusivement sur le plan Business, sans limite de nombre d'établissements.</div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="pr-cta">
    <div class="pr-cta-bar"></div>
    <div class="pr-cta-body">
      <div>
        <h2 class="pr-cta-title">Prêt à rejoindre<br><em>Afristay ?</em></h2>
        <p class="pr-cta-sub">Commencez gratuitement, sans carte bancaire. Upgrade quand vous voulez.</p>
      </div>
      <div class="pr-cta-btns">
        <a href="<?= $base ?>/register?plan=starter" class="pr-cta-btn-p">Démarrer gratuitement →</a>
        <a href="<?= $base ?>/contact" class="pr-cta-btn-o">Contacter l'équipe</a>
      </div>
    </div>
  </section>

</div>