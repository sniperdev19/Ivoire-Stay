-- Nouveaux outils de gestion admin (2026-07-29) : messages de contact
-- persistés, newsletter (abonnés + campagnes), annonces vitrine. La
-- notification broadcast aux propriétaires ne nécessite aucune migration
-- (réutilise la table `notifications` existante avec un nouveau type
-- 'platform_announcement').

-- Messages reçus via le formulaire de contact public (/contact). Auparavant
-- seulement envoyés par email (MailService::sendContact), jamais persistés —
-- aucune trace consultable depuis l'admin. Le message est stocké AVANT
-- tentative d'envoi email (source de vérité), l'email reste un canal
-- best-effort en plus (voir PublicController::sendContact()).
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         int NOT NULL AUTO_INCREMENT,
  `name`       varchar(150) NOT NULL,
  `email`      varchar(200) NOT NULL,
  `phone`      varchar(30) DEFAULT NULL,
  `subject`    varchar(200) DEFAULT NULL,
  `message`    text NOT NULL,
  `read_at`    datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Abonnés newsletter (formulaire footer de la vitrine publique). Jeton
-- opaque de désabonnement (même principe que bookings.guest_token) : le
-- lien "se désabonner" de chaque campagne ne doit pas nécessiter de compte.
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id`                int NOT NULL AUTO_INCREMENT,
  `email`             varchar(200) NOT NULL,
  `unsubscribe_token` char(64) NOT NULL,
  `subscribed_at`     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at`   datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unsubscribe_token` (`unsubscribe_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historique des campagnes envoyées. Envoi synchrone au clic "Envoyer" côté
-- admin (AdminNewsletterController::send()) — même logique assumée que
-- Services\BackupService : le volume actuel ne justifie pas de file d'attente
-- asynchrone, à revoir si la liste d'abonnés grossit significativement.
CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
  `id`              int NOT NULL AUTO_INCREMENT,
  `subject`         varchar(200) NOT NULL,
  `body`            text NOT NULL,
  `recipient_count` int NOT NULL DEFAULT '0',
  `sent_at`         datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Annonces affichées en bandeau sur la vitrine publique (maintenance,
-- nouveauté...). Une seule active à la fois PAR CONSTRUCTION (voir
-- AdminAnnouncementController) : activer une annonce désactive
-- systématiquement toutes les autres.
CREATE TABLE IF NOT EXISTS `announcements` (
  `id`         int NOT NULL AUTO_INCREMENT,
  `title`      varchar(200) NOT NULL,
  `message`    text NOT NULL,
  `is_active`  tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
