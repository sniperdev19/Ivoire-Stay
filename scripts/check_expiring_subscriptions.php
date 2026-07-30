<?php
/**
 * Rappel d'expiration d'abonnement SaaS — déclenchement manuel/debug.
 *
 *   php scripts/check_expiring_subscriptions.php
 *
 * En usage normal, ce job s'exécute automatiquement (sans cron) dès la
 * première requête authentifiée de la journée — cf. Services\SchedulerService
 * et Core\Middleware::checkAuth(). Ce script appelle la même logique et reste
 * utile pour forcer une exécution ou déboguer sans attendre une requête réelle.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

use Services\SchedulerService;

echo SchedulerService::runExpiringSubscriptions(verbose: true) . "\n";
echo "Terminé.\n";
