<?php
/**
 * Sauvegarde de la base de données — déclenchement manuel/debug.
 *
 *   php scripts/check_db_backup.php
 *
 * En usage normal, ce job s'exécute automatiquement (sans cron) dès la
 * première requête authentifiée de la journée — cf. Services\SchedulerService
 * et Core\Middleware::checkAuth(). Ce script appelle la même logique et reste
 * utile pour forcer une exécution ou déboguer sans attendre une requête réelle.
 * Contrairement au job automatique (une fois par jour max), ce script peut
 * être relancé plusieurs fois dans la même journée : chaque appel produit un
 * nouveau dump distinct.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

use Services\SchedulerService;

echo SchedulerService::runDbBackup(verbose: true) . "\n";
echo "Terminé.\n";
