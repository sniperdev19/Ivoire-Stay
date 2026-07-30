<?php

namespace Services;

use Core\Database;

/**
 * Automatisation "par code" des tâches quotidiennes — pas de cron/Task
 * Scheduler externe requis. `maybeRunDueJobs()` est appelée depuis
 * Core\Middleware sur chaque requête API authentifiée : la première requête
 * de la journée (staff ou superadmin) déclenche les jobs du jour, les
 * suivantes sont des no-op (marqueur `scheduled_job_runs`, réclamation
 * atomique via INSERT ... ON DUPLICATE KEY UPDATE — pas de double exécution
 * même avec des requêtes concurrentes).
 *
 * Limite assumée : si personne ne se connecte à l'app un jour donné, le job
 * de ce jour-là part avec le retard jusqu'à la prochaine connexion. Accepté
 * comme compromis pour un SaaS à connexions quotidiennes, en échange de zéro
 * configuration serveur. scripts/check_daily_arrivals.php et
 * scripts/check_expiring_subscriptions.php restent utilisables en CLI pour un
 * déclenchement manuel/debug — ils appellent ces mêmes méthodes.
 */
class SchedulerService
{
    private const JOBS = ['daily_arrivals', 'expiring_subscriptions', 'establishment_freeze_sync', 'expire_pending_bookings', 'db_backup'];
    private const SUBSCRIPTION_REMINDER_WINDOW_DAYS = 3;

    public static function maybeRunDueJobs(): void
    {
        foreach (self::JOBS as $job) {
            if (self::claimJob($job)) {
                self::run($job);
            }
        }
    }

    /** @return bool true si CETTE requête vient de réclamer le job pour aujourd'hui. */
    private static function claimJob(string $job): bool
    {
        $stmt = Database::query(
            "INSERT INTO scheduled_job_runs (job_name, last_run_date, last_run_at)
             VALUES (?, CURDATE(), NOW())
             ON DUPLICATE KEY UPDATE
               last_run_at   = IF(last_run_date < VALUES(last_run_date), VALUES(last_run_at), last_run_at),
               last_run_date = IF(last_run_date < VALUES(last_run_date), VALUES(last_run_date), last_run_date)",
            [$job]
        );
        return $stmt->rowCount() > 0;
    }

    private static function run(string $job): void
    {
        try {
            $summary = match ($job) {
                'daily_arrivals'           => self::runDailyArrivals(),
                'expiring_subscriptions'   => self::runExpiringSubscriptions(),
                'establishment_freeze_sync' => self::runEstablishmentFreezeSync(),
                'expire_pending_bookings'  => self::runExpirePendingBookings(),
                'db_backup'                => self::runDbBackup(),
                default                    => 'job inconnu',
            };
            error_log("[SchedulerService] {$job} — {$summary}");
        } catch (\Throwable $e) {
            error_log("[SchedulerService] Échec du job {$job} — " . $e->getMessage());
        }
    }

    /**
     * Rappels arrivée/départ du lendemain — équipe (in-app) + voyageur (email/push).
     * Idempotent via bookings.arrival_reminder_sent_at / departure_reminder_sent_at.
     */
    public static function runDailyArrivals(bool $verbose = false): string
    {
        $pdo = Database::getInstance();
        $log = fn(string $line) => $verbose ? print($line . "\n") : null;

        $arrivals = $pdo->query("
            SELECT b.id, b.check_in, r.establishment_id, r.number as room_number,
                   e.name as establishment_name,
                   COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name,
                   COALESCE(pc.email, u.email) as client_email
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN establishments e ON e.id = r.establishment_id
            LEFT JOIN public_clients pc ON pc.id = b.public_client_id
            LEFT JOIN users u ON u.id = b.user_id
            WHERE b.check_in = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
              AND b.status IN ('confirmed', 'checked_in')
              AND b.arrival_reminder_sent_at IS NULL
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $log(count($arrivals) . ' arrivée(s) demain à traiter.');

        $stmtMarkArrival = $pdo->prepare("UPDATE bookings SET arrival_reminder_sent_at = NOW() WHERE id = ?");
        $arrivalsByEstab = [];

        foreach ($arrivals as $b) {
            $arrivalsByEstab[$b['establishment_id']] = ($arrivalsByEstab[$b['establishment_id']] ?? 0) + 1;

            if (!empty($b['client_email'])) {
                try {
                    MailService::stayReminder([
                        'client_email'       => $b['client_email'],
                        'client_name'        => $b['client_name'],
                        'establishment_name' => $b['establishment_name'],
                        'room_number'        => $b['room_number'],
                        'check_in'           => $b['check_in'],
                    ]);
                } catch (\Throwable $e) {
                    $log("✗ Échec email rappel séjour (réservation #{$b['id']}) : " . $e->getMessage());
                }
            }

            PushService::sendToBooking(
                (int) $b['id'],
                'Votre séjour approche',
                'Arrivée prévue demain à ' . $b['establishment_name'],
                ['audience' => 'guest', 'booking_id' => (int) $b['id']]
            );

            $stmtMarkArrival->execute([$b['id']]);
        }

        foreach ($arrivalsByEstab as $estabId => $count) {
            NotificationService::arrivalReminder((int) $estabId, $count);
            $log("→ Établissement #{$estabId} : {$count} arrivée(s) demain (équipe notifiée)");
        }

        $departures = $pdo->query("
            SELECT b.id, r.establishment_id
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.check_out = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
              AND b.status IN ('confirmed', 'checked_in')
              AND b.departure_reminder_sent_at IS NULL
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $log(count($departures) . ' départ(s) demain à traiter.');

        $stmtMarkDeparture = $pdo->prepare("UPDATE bookings SET departure_reminder_sent_at = NOW() WHERE id = ?");
        $departuresByEstab = [];

        foreach ($departures as $b) {
            $departuresByEstab[$b['establishment_id']] = ($departuresByEstab[$b['establishment_id']] ?? 0) + 1;
            $stmtMarkDeparture->execute([$b['id']]);
        }

        foreach ($departuresByEstab as $estabId => $count) {
            NotificationService::departureReminder((int) $estabId, $count);
            $log("→ Établissement #{$estabId} : {$count} départ(s) demain (équipe notifiée)");
        }

        return count($arrivals) . ' arrivée(s), ' . count($departures) . ' départ(s) traités';
    }

    /**
     * Rappel d'expiration d'abonnement — email + notification in-app au
     * propriétaire. Idempotent via subscriptions.expiry_reminder_sent_at.
     */
    public static function runExpiringSubscriptions(bool $verbose = false): string
    {
        $pdo   = Database::getInstance();
        $log   = fn(string $line) => $verbose ? print($line . "\n") : null;
        $plans = require BASE_PATH . '/config/plans.php';

        $rows = $pdo->query("
            SELECT s.id as subscription_id, s.plan, s.expires_at,
                   e.id as establishment_id, e.plan_expires_at, u.id as owner_user_id, u.name, u.email
            FROM subscriptions s
            JOIN establishments e ON e.id = s.establishment_id
            JOIN users u ON u.id = e.owner_id
            WHERE s.status = 'active'
              AND s.expiry_reminder_sent_at IS NULL
              AND s.expires_at IS NOT NULL
              AND s.expires_at <= DATE_ADD(NOW(), INTERVAL " . self::SUBSCRIPTION_REMINDER_WINDOW_DAYS . " DAY)
              AND s.expires_at > NOW()
              AND e.plan != 'starter'
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $log(count($rows) . ' abonnement(s) à rappeler.');

        $stmtMark = $pdo->prepare("UPDATE subscriptions SET expiry_reminder_sent_at = NOW() WHERE id = ?");

        foreach ($rows as $row) {
            $daysLeft  = max(1, (int) ceil((strtotime($row['expires_at']) - time()) / 86400));
            $planLabel = $plans[$row['plan']]['name'] ?? $row['plan'];

            try {
                MailService::subscriptionExpiringSoon($row['email'], $row['name'], $planLabel, $row['expires_at'], $daysLeft);
                NotificationService::subscriptionExpiring((int) $row['owner_user_id'], $planLabel, $daysLeft);
                $stmtMark->execute([$row['subscription_id']]);
                $log("→ Rappel envoyé à {$row['email']} (établissement #{$row['establishment_id']}, expire dans {$daysLeft}j)");
            } catch (\Throwable $e) {
                $log("✗ Échec envoi à {$row['email']} : " . $e->getMessage());
            }
        }

        return count($rows) . ' abonnement(s) traité(s)';
    }

    /**
     * Annule automatiquement les réservations en ligne restées 'pending'
     * (jamais confirmées par l'établissement) dont la date d'arrivée est
     * dépassée. Sans ce job elles restent 'pending' indéfiniment et
     * continuent de bloquer la chambre dans Booking::isRoomAvailable()
     * (qui n'exclut que 'cancelled'/'checked_out').
     */
    public static function runExpirePendingBookings(bool $verbose = false): string
    {
        $pdo = Database::getInstance();
        $log = fn(string $line) => $verbose ? print($line . "\n") : null;

        $expired = $pdo->query("
            SELECT b.id, r.establishment_id, r.number as room_number,
                   COALESCE(CONCAT(pc.first_name, ' ', pc.last_name), u.name) as client_name,
                   COALESCE(pc.email, u.email) as client_email,
                   e.name as establishment_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN establishments e ON e.id = r.establishment_id
            LEFT JOIN public_clients pc ON pc.id = b.public_client_id
            LEFT JOIN users u ON u.id = b.user_id
            WHERE b.status = 'pending'
              AND b.check_in < CURDATE()
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $log(count($expired) . ' réservation(s) pending expirée(s) à annuler.');

        $stmtCancel = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");

        foreach ($expired as $b) {
            $stmtCancel->execute([$b['id']]);

            NotificationService::bookingCancelled(
                (int) $b['establishment_id'],
                $b['client_name'] ?? 'Client',
                $b['room_number'] ?? '?',
                (int) $b['id']
            );

            if (!empty($b['client_email'])) {
                try {
                    MailService::bookingCancelledGuest($b);
                } catch (\Throwable $e) {
                    $log("✗ Échec email annulation (réservation #{$b['id']}) : " . $e->getMessage());
                }
            }

            $log("→ Réservation #{$b['id']} (établissement #{$b['establishment_id']}) annulée — jamais confirmée avant la date d'arrivée.");
        }

        return count($expired) . ' réservation(s) pending expirée(s) annulée(s)';
    }

    /**
     * Recalcule le gel des établissements en excédent (EstablishmentFreezeService)
     * pour les owners multi-établissements. Nécessaire car un abonnement expiré
     * n'est jamais physiquement redescendu à 'starter' en base (PlanGate::getPlan()
     * le traite comme tel à la volée) — sans ce job, un owner qui laisse son
     * abonnement expirer sans agir ne verrait jamais ses établissements en excédent
     * gelés. Volume négligeable : seuls les owners avec plusieurs établissements
     * sont concernés.
     */
    public static function runEstablishmentFreezeSync(bool $verbose = false): string
    {
        $pdo = Database::getInstance();
        $log = fn(string $line) => $verbose ? print($line . "\n") : null;

        $ownerIds = $pdo->query("
            SELECT owner_id FROM establishments GROUP BY owner_id HAVING COUNT(*) > 1
        ")->fetchAll(\PDO::FETCH_COLUMN);

        $log(count($ownerIds) . ' owner(s) multi-établissements à vérifier.');

        foreach ($ownerIds as $ownerId) {
            EstablishmentFreezeService::recompute((int) $ownerId);
        }

        return count($ownerIds) . ' owner(s) synchronisé(s)';
    }

    /**
     * Sauvegarde quotidienne de la base (BackupService) — pas de shell/cron
     * en prod, même mécanique que les autres jobs de cette classe. Dump SQL
     * gzippé dans storage/backups/, rétention 7 jours glissants.
     */
    public static function runDbBackup(bool $verbose = false): string
    {
        return BackupService::run($verbose);
    }
}
