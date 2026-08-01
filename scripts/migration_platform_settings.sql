-- Réglages plateforme éditables depuis /admin/settings (Core\Settings) : simple
-- table clé/valeur, avec les fichiers config/*.php comme valeurs par défaut/de
-- secours tant qu'aucune ligne n'a été enregistrée pour une clé donnée — cf.
-- Core\Settings::get() (jamais d'erreur fatale si cette table manque encore,
-- lue à CHAQUE requête depuis config/config.php pour AGENTS_ENABLED).
CREATE TABLE `platform_settings` (
  `key`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value`      text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
