-- Primes agents commerciaux, en plus du versement forfaitaire tous les 5
-- premiers-abonnements déjà existant (agent_reward_per_5, config/plans.php,
-- inchangé) — cf. config/agent_bonuses.php pour les 4 nouvelles primes.
-- Gérées par CommissionService, versées via agent_payouts (même circuit de
-- validation superadmin que l'existant : /admin/agents → "Marquer payé"/"Rejeter").

-- Registre de TOUTES les primes ponctuelles décernées, un mécanisme unique
-- pour des primes de cardinalités différentes :
--   - `first_to_5`     : scope_key = 'global'                → une seule ligne, JAMAIS deux (course globale)
--   - `first_business` : scope_key = agent_id                → une seule ligne par agent
--   - `monthly_top`    : scope_key = '<AAAA-MM>:<agent_id>'  → un agent ne peut gagner qu'une fois le même mois,
--                                                                mais plusieurs agents peuvent être ex æquo ce mois-là
--   - `fast_conversion`: scope_key = establishment_id         → un établissement ne peut déclencher qu'une prime
-- Le versement forfaitaire tous les 5 (CommissionService::maybeCreateBatchPayout)
-- ne passe PAS par cette table — il continue de créer directement un agent_payouts,
-- comme avant.
-- La contrainte UNIQUE (type, scope_key) est LA protection anti-double-versement
-- (y compris en cas de requêtes concurrentes) : CommissionService tente
-- l'INSERT et traite une violation de contrainte comme "déjà décerné, rien à faire".
CREATE TABLE `agent_bonus_awards` (
  `id`         int NOT NULL AUTO_INCREMENT,
  `agent_id`   int NOT NULL,
  `type`       varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_key`  varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount`     decimal(10,2) NOT NULL,
  `payout_id`  int DEFAULT NULL,
  `awarded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_type_scope` (`type`, `scope_key`),
  KEY `agent_id` (`agent_id`),
  KEY `payout_id` (`payout_id`),
  CONSTRAINT `agent_bonus_awards_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agent_bonus_awards_ibfk_2` FOREIGN KEY (`payout_id`) REFERENCES `agent_payouts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- `plan` devient facultatif : les primes "premier arrivé", "top du mois" et
-- "premier client Business" ne sont pas rattachées à un plan précis — `label`
-- porte alors la description affichée (admin/agents.php et le dashboard agent).
-- Les versements par palier (tier_pro/tier_business) continuent de renseigner
-- `plan` comme avant, `label` précisant simplement le palier atteint.
ALTER TABLE `agent_payouts`
  MODIFY `plan` enum('pro','business') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `label` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `plan`;
