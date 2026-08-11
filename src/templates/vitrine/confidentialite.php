<?php $base_url = $base_url ?? rtrim(APP_URL, '/'); ?>

<div style="max-width:820px;margin:0 auto;padding:64px 24px 96px;font-family:'Inter',sans-serif;color:#1B4332;">

  <div style="margin-bottom:8px;">
    <span
      style="display:inline-block;font-size:11px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:#C9A84C;">Informations
      légales</span>
  </div>
  <h1 style="font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:600;margin:0 0 8px;color:#1B4332;">
    Politique de confidentialité</h1>
  <p style="font-size:13px;color:rgba(27,67,50,0.5);margin:0 0 32px;">Dernière mise à jour : <?= date('d/m/Y') ?></p>

  <!-- <div
    style="background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.25);border-radius:12px;padding:16px 20px;margin-bottom:40px;font-size:13px;line-height:1.7;color:rgba(27,67,50,0.75);">
    Ce document est un modèle générique décrivant le traitement des données tel qu'implémenté actuellement.
    Il doit être relu et validé par un professionnel du droit avant toute mise en production réelle.
  </div> -->

  <div style="font-size:15px;line-height:1.85;color:rgba(27,67,50,0.85);">

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      1. Responsable du traitement</h2>
    <p>Afristay, plateforme opérée depuis la Côte d'Ivoire, est responsable du traitement des données à caractère
      personnel collectées via le présent site et l'Espace hôtelier, conformément à la loi ivoirienne
      n°2013-450 relative à la protection des données à caractère personnel.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      2. Données collectées</h2>
    <ul style="padding-left:20px;">
      <li><strong>Compte hôtelier</strong> : nom, email, téléphone, mot de passe, nom et adresse de l'établissement.
      </li>
      <li><strong>Réservation voyageur</strong> : nom, prénom, email, téléphone, dates de séjour, et le cas échéant type
        et numéro de pièce d'identité.</li>
      <li><strong>Paiement</strong> : aucune donnée bancaire n'est stockée par Afristay. Les paiements Mobile Money sont
        traités directement par le prestataire de paiement partenaire.</li>
      <li><strong>Navigation</strong> : adresse IP, données techniques de connexion, utilisées notamment pour la
        sécurité.</li>
      <li><strong>Appareil</strong> : un identifiant technique lié à l'installation de l'application est conservé pour
        réserver l'accès à l'Espace hôtelier aux appareils l'ayant installée.</li>
    </ul>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      3. Finalités</h2>
    <ul style="padding-left:20px;">
      <li>Gestion des comptes et de l'authentification ;</li>
      <li>Traitement des réservations et communication avec les Établissements ;</li>
      <li>Envoi d'emails transactionnels (confirmation de réservation, facture, notifications) ;</li>
      <li>Sécurité de la Plateforme (prévention de la fraude, limitation des tentatives d'accès abusives) ;</li>
      <li>Amélioration du service.</li>
    </ul>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      4. Destinataires des données</h2>
    <p>Les données sont accessibles à l'équipe Afristay et à l'Établissement concerné par une réservation, dans la
      stricte mesure nécessaire au traitement de celle-ci. Elles peuvent également être transmises à :</p>
    <ul style="padding-left:20px;">
      <li>notre prestataire de paiement Mobile Money, pour le traitement des transactions en ligne ;</li>
      <li>notre prestataire d'envoi d'emails, pour l'envoi des communications transactionnelles.</li>
    </ul>
    <p>Aucune donnée n'est vendue à des tiers à des fins commerciales.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      5. Durée de conservation</h2>
    <p>Les données sont conservées pendant la durée nécessaire aux finalités décrites ci-dessus, et au minimum
      pendant la durée de la relation contractuelle (compte actif, historique de réservations), sauf obligation
      légale de conservation plus longue.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      6. Sécurité</h2>
    <p>Des mesures techniques sont mises en œuvre pour protéger les données : mots de passe hachés, communications
      chiffrées, limitation des tentatives de connexion, contrôle d'accès par établissement, et pour l'Espace
      hôtelier une vérification supplémentaire liée à l'appareil utilisé.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      7. Vos droits</h2>
    <p>Conformément à la réglementation applicable, vous disposez d'un droit d'accès, de rectification, de
      suppression et d'opposition concernant vos données personnelles. Pour exercer ces droits, contactez-nous via
      <a href="<?= $base_url ?>/contact" style="color:#C9A84C;">le formulaire de contact</a>. Vous disposez
      également du droit d'introduire une réclamation auprès de l'autorité ivoirienne compétente en matière de
      protection des données personnelles (ARTCI).
    </p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      8. Cookies</h2>
    <p>La Plateforme utilise uniquement des cookies techniques nécessaires à son fonctionnement, à l'exclusion de tout
      cookie publicitaire ou de traçage tiers.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      9. Modification de la politique</h2>
    <p>Cette politique peut être mise à jour. La date de dernière mise à jour figure en haut de cette page.</p>

    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;margin:32px 0 12px;color:#1B4332;">
      10. Contact</h2>
    <p>Pour toute question relative au traitement de vos données : <a href="<?= $base_url ?>/contact"
        style="color:#C9A84C;">nous contacter</a>.</p>

  </div>

</div>