-- Vide complètement la base hotel_sync (structure conservée, données supprimées).
-- ⚠️ Irréversible. Tous les comptes, établissements, réservations, factures,
-- paiements, notifications, abonnements push, demandes de retrait, jetons de
-- sécurité (rate limiting, blacklist JWT, webauthn, reset mot de passe),
-- sessions/appareils connectés, agents commerciaux et leurs rattachements/
-- versements/primes, réglages plateforme, messages de contact, abonnés/
-- campagnes newsletter, annonces vitrine, etc. sont supprimés.
--
-- Chaque ligne est un DELETE indépendant, dans l'ordre des dépendances de clés
-- étrangères — donc ça fonctionne même exécuté ligne par ligne (ex: collé une
-- requête à la fois dans phpMyAdmin), sans dépendre d'un SET FOREIGN_KEY_CHECKS
-- qui ne survit pas toujours d'une requête à l'autre selon le client SQL.
--
-- Usage :
--   mysql -u root hotel_sync < scripts/wipe-db.sql
-- ou : coller tout le contenu dans l'onglet SQL de phpMyAdmin et exécuter.

-- Casse la dépendance circulaire users <-> establishments avant de commencer.
UPDATE users SET establishment_id = NULL;

DELETE FROM guest_push_subscriptions;
DELETE FROM payments;
DELETE FROM invoices;
DELETE FROM room_amenities;
DELETE FROM room_photos;
DELETE FROM bookings;
DELETE FROM public_clients;
DELETE FROM rooms;
DELETE FROM room_types;
DELETE FROM establishment_photos;
DELETE FROM expenses;
DELETE FROM payout_requests;
DELETE FROM push_subscriptions;
DELETE FROM notifications;
DELETE FROM subscriptions;
DELETE FROM password_resets;
DELETE FROM email_verifications;
DELETE FROM webauthn_credentials;
DELETE FROM webauthn_challenges;
DELETE FROM device_attestations;
DELETE FROM token_blacklist;
DELETE FROM rate_limits;
DELETE FROM scheduled_job_runs;
DELETE FROM contact_messages;
DELETE FROM newsletter_campaigns;
DELETE FROM newsletter_subscribers;
DELETE FROM announcements;
DELETE FROM platform_settings;
DELETE FROM agent_bonus_awards;
DELETE FROM agent_referrals;
DELETE FROM agent_establishments;
DELETE FROM agent_payouts;
DELETE FROM agents;
DELETE FROM user_sessions;
DELETE FROM establishments;
DELETE FROM users;

-- Remet les compteurs auto_increment à 1 (DELETE, contrairement à TRUNCATE,
-- ne les réinitialise pas). Chaque ligne reste indépendante. Les tables dont
-- la clé primaire n'est pas un AUTO_INCREMENT (webauthn_challenges, jti de
-- token_blacklist, bucket de rate_limits, job_name de scheduled_job_runs) ne
-- sont pas concernées.
ALTER TABLE guest_push_subscriptions AUTO_INCREMENT = 1;
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE invoices AUTO_INCREMENT = 1;
ALTER TABLE room_amenities AUTO_INCREMENT = 1;
ALTER TABLE room_photos AUTO_INCREMENT = 1;
ALTER TABLE bookings AUTO_INCREMENT = 1;
ALTER TABLE public_clients AUTO_INCREMENT = 1;
ALTER TABLE rooms AUTO_INCREMENT = 1;
ALTER TABLE room_types AUTO_INCREMENT = 1;
ALTER TABLE establishment_photos AUTO_INCREMENT = 1;
ALTER TABLE expenses AUTO_INCREMENT = 1;
ALTER TABLE payout_requests AUTO_INCREMENT = 1;
ALTER TABLE push_subscriptions AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE subscriptions AUTO_INCREMENT = 1;
ALTER TABLE password_resets AUTO_INCREMENT = 1;
ALTER TABLE email_verifications AUTO_INCREMENT = 1;
ALTER TABLE webauthn_credentials AUTO_INCREMENT = 1;
ALTER TABLE device_attestations AUTO_INCREMENT = 1;
ALTER TABLE contact_messages AUTO_INCREMENT = 1;
ALTER TABLE newsletter_campaigns AUTO_INCREMENT = 1;
ALTER TABLE newsletter_subscribers AUTO_INCREMENT = 1;
ALTER TABLE announcements AUTO_INCREMENT = 1;
ALTER TABLE agent_bonus_awards AUTO_INCREMENT = 1;
ALTER TABLE agent_referrals AUTO_INCREMENT = 1;
ALTER TABLE agent_establishments AUTO_INCREMENT = 1;
ALTER TABLE agent_payouts AUTO_INCREMENT = 1;
ALTER TABLE agents AUTO_INCREMENT = 1;
ALTER TABLE user_sessions AUTO_INCREMENT = 1;
ALTER TABLE establishments AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
