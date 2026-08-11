<?php $base_url = $base_url ?? rtrim(APP_URL, '/'); ?>

<div style="max-width:820px;margin:0 auto;padding:64px 24px 96px;font-family:'Inter',sans-serif;color:#1B4332;">

  <div style="margin-bottom:8px;">
    <span
      style="display:inline-block;font-size:11px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:#C9A84C;">Informations
      légales</span>
  </div>
  <h1 style="font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:600;margin:0 0 8px;color:#1B4332;">
    Conditions générales d'utilisation</h1>
  <p style="font-size:13px;color:rgba(27,67,50,0.5);margin:0 0 32px;">Dernière mise à jour : <?= date('d/m/Y') ?></p>

  <!-- <div style="background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.25);border-radius:12px;padding:16px 20px;margin-bottom:40px;font-size:13px;line-height:1.7;color:rgba(27,67,50,0.75);">
    Ce document est un modèle générique destiné à couvrir le fonctionnement actuel de la plateforme.
    Il doit être relu et validé par un professionnel du droit avant toute mise en production réelle,
    notamment sur les clauses de responsabilité et de traitement des paiements.
  </div> -->

  <div style="font-size:15px;line-height:1.85;color:rgba(27,67,50,0.85);">

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      1. Objet</h2>
    <p>Les présentes conditions générales d'utilisation (« CGU ») régissent l'accès et l'utilisation de la plateforme
      Afristay (« la Plateforme »), éditée depuis la Côte d'Ivoire, qui propose :</p>
    <ul style="padding-left:20px;">
      <li>un service de réservation en ligne d'hébergements (hôtels, résidences) pour les voyageurs (« Vitrine ») ;</li>
      <li>un logiciel de gestion hôtelière en SaaS destiné aux établissements partenaires (« Espace hôtelier »).</li>
    </ul>
    <p>L'utilisation de la Plateforme, à quelque titre que ce soit, implique l'acceptation pleine et entière des
      présentes CGU.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      2. Définitions</h2>
    <ul style="padding-left:20px;">
      <li><strong>Voyageur</strong> : toute personne effectuant une recherche ou une réservation via la Vitrine.</li>
      <li><strong>Hôtelier / Établissement</strong> : professionnel disposant d'un compte sur l'Espace hôtelier pour
        gérer son ou ses établissements.</li>
      <li><strong>Réservation</strong> : engagement pris par un Voyageur auprès d'un Établissement via la Plateforme.
      </li>
    </ul>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      3. Rôle d'intermédiaire</h2>
    <p>Afristay agit comme intermédiaire technique entre Voyageurs et Établissements. La Plateforme n'est ni
      propriétaire, ni exploitante des établissements référencés : le contrat de séjour est conclu directement
      entre le Voyageur et l'Établissement. Afristay ne saurait être tenue responsable de la qualité du séjour,
      de l'exactitude des informations fournies par l'Établissement, ou d'un litige relatif au séjour lui-même.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      4. Compte et inscription (Espace hôtelier)</h2>
    <p>La création d'un compte hôtelier nécessite des informations exactes et à jour. L'accès à l'Espace hôtelier
      est réservé aux appareils sur lesquels l'application a été installée, avec une vérification de sécurité liée
      à l'appareil en complément de l'identifiant et du mot de passe. Chaque compte est personnel ; toute activité
      réalisée depuis un compte est réputée effectuée par son titulaire.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      5. Réservations et paiement</h2>
    <p>Une réservation peut être réglée sur place, à l'établissement, ou en ligne par Mobile Money via un
      prestataire de paiement tiers. Le paiement en ligne, lorsqu'il est proposé, est traité par un prestataire
      spécialisé ; Afristay ne stocke aucune donnée bancaire ou de paiement sur ses propres serveurs. Toute
      réservation payée en ligne est confirmée automatiquement à réception de la confirmation du prestataire
      de paiement. Sur le plan Starter, le paiement en ligne est activé par défaut et ne peut être désactivé par
      l'Établissement ; Afristay prélève à ce titre une commission sur chaque réservation payée en ligne, dont le
      taux total et la répartition sont indiqués sur la page Tarifs — une partie est déjà incluse dans le prix
      affiché et payé par le client (même prix, quel que soit le mode de paiement finalement choisi), le reste est
      prélevé sur le montant reversé à l'Établissement. Sur les plans Pro et Business, le paiement en ligne est
      optionnel et n'est soumis à aucune commission.</p>
    <p>Les conditions d'annulation et de modification sont propres à chaque Établissement et doivent être
      vérifiées avant la réservation.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      6. Abonnements (Espace hôtelier)</h2>
    <p>Le plan Starter donne accès à l'ensemble des fonctionnalités de gestion de l'Espace hôtelier (facturation,
      dépenses, rapports) sans abonnement, en contrepartie du paiement en ligne commissionné décrit à l'article 5.
      L'accès à la mise en avant vitrine (boost) et à la gestion de plusieurs établissements reste conditionné à un
      abonnement payant (plans Pro et Business). Les tarifs et fonctionnalités de chaque formule sont indiqués sur la
      page Tarifs et peuvent évoluer. Un abonnement Pro ou Business non renouvelé à son échéance repasse
      automatiquement sur le plan Starter ; les établissements excédant la limite du nouveau plan sont gelés selon les
      modalités indiquées dans l'Espace hôtelier.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      7. Obligations de l'utilisateur</h2>
    <p>L'utilisateur s'engage à fournir des informations exactes, à ne pas usurper l'identité d'un tiers, à ne pas
      perturber le fonctionnement de la Plateforme, et à respecter les lois en vigueur en Côte d'Ivoire.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      8. Propriété intellectuelle</h2>
    <p>L'ensemble des éléments de la Plateforme (marque, logo, interface, code) est protégé par le droit de la
      propriété intellectuelle. Toute reproduction non autorisée est interdite.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      9. Modification des CGU</h2>
    <p>Afristay se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés
      de toute modification substantielle.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      10. Droit applicable</h2>
    <p>Les présentes CGU sont soumises au droit ivoirien. Tout litige relève, à défaut de résolution amiable, de la
      compétence des juridictions de Côte d'Ivoire.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      11. Contact</h2>
    <p>Pour toute question relative aux présentes CGU : <a href="<?= $base_url ?>/contact" style="color:#C9A84C;">nous
        contacter</a>.</p>

  </div>

</div>