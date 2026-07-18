<?php
/**
 * Synchronisation du gel des établissements en excédent — déclenchement manuel/debug.
 *
 *   php scripts/check_establishment_freeze.php
 *
 * En usage normal, ce job s'exécute automatiquement (sans cron) dès la
 * première requête authentifiée de la journée — cf. Services\SchedulerService
 * et Core\Middleware::checkAuth(). Ce script appelle la même logique et reste
 * utile pour forcer une exécution ou déboguer sans attendre une requête réelle.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

use Services\SchedulerService;

echo SchedulerService::runEstablishmentFreezeSync(verbose: true) . "\n";
echo "Terminé.\n";
