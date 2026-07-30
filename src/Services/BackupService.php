<?php

namespace Services;

use Core\Database;

/**
 * Sauvegarde de la base de données — dump SQL pur PHP/PDO (pas de dépendance
 * au binaire `mysqldump`, potentiellement absent/non exécutable sur
 * l'hébergement), gzippé, écrit dans storage/backups/ (hors public/, jamais
 * accessible par URL). Déclenché quotidiennement via SchedulerService (pas
 * de cron en prod — accès phpMyAdmin uniquement) et consultable/déclenchable
 * manuellement depuis /admin/backups (superadmin).
 */
class BackupService
{
    private const KEEP       = 7;
    private const BATCH_SIZE = 500;

    public static function run(bool $verbose = false): string
    {
        $log = fn(string $line) => $verbose ? print($line . "\n") : null;

        $result  = self::dump();
        $deleted = self::prune();

        $log("Dump créé : {$result['filename']} ({$result['tables']} tables, {$result['rows']} lignes, " . self::humanSize($result['bytes']) . ')');
        if ($deleted > 0) $log("{$deleted} ancien(s) dump(s) purgé(s) (rétention : " . self::KEEP . ' jours)');

        return "Dump {$result['filename']} — {$result['tables']} tables, {$result['rows']} lignes, " . self::humanSize($result['bytes']) . ($deleted > 0 ? " — {$deleted} purgé(s)" : '');
    }

    /**
     * Génère un dump SQL complet de la base, gzippé, écrit directement en
     * streaming (jamais tout le dump — ni même une table entière — en
     * mémoire en même temps, quelle que soit la taille future des données).
     *
     * @return array{filename:string, path:string, tables:int, rows:int, bytes:int}
     */
    public static function dump(): array
    {
        $pdo = Database::getInstance();
        $dir = self::backupDir();

        $filename = 'backup_' . date('Y-m-d_His') . '.sql.gz';
        $finalPath = $dir . '/' . $filename;
        $tmpPath   = $dir . '/.' . $filename . '.part';

        $gz = gzopen($tmpPath, 'wb9');
        if ($gz === false) throw new \RuntimeException("Impossible d'ouvrir le fichier de dump en écriture : {$tmpPath}");

        gzwrite($gz, "-- Sauvegarde Afristay — " . date('Y-m-d H:i:s') . "\n");
        gzwrite($gz, "SET NAMES utf8mb4;\n");
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables   = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $rowCount = 0;

        foreach ($tables as $table) {
            $rowCount += self::dumpTable($pdo, $gz, $table);
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);

        // Renommage atomique : storage/backups/ ne voit jamais un .gz à moitié écrit.
        rename($tmpPath, $finalPath);

        return [
            'filename' => $filename,
            'path'     => $finalPath,
            'tables'   => count($tables),
            'rows'     => $rowCount,
            'bytes'    => filesize($finalPath),
        ];
    }

    /** Écrit le DROP/CREATE + les données d'une table dans le flux gzip. Retourne le nombre de lignes écrites. */
    private static function dumpTable(\PDO $pdo, $gz, string $table): int
    {
        gzwrite($gz, "-- Table `{$table}`\n");
        gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");

        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
        gzwrite($gz, $create['Create Table'] . ";\n\n");

        // Clé primaire pour un LIMIT/OFFSET stable — pas toujours `id` (ex. `rate_limits`.bucket, `token_blacklist`.jti).
        $pk = $pdo->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'")->fetch(\PDO::FETCH_ASSOC);
        $orderBy = $pk ? " ORDER BY `{$pk['Column_name']}`" : '';

        $totalRows = 0;
        $offset    = 0;

        while (true) {
            $rows = $pdo->query("SELECT * FROM `{$table}`{$orderBy} LIMIT {$offset}, " . self::BATCH_SIZE)->fetchAll(\PDO::FETCH_ASSOC);
            if (!$rows) break;

            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';

            $valueLines = [];
            foreach ($rows as $row) {
                $values = array_map(
                    fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    $row
                );
                $valueLines[] = '(' . implode(', ', $values) . ')';
            }

            gzwrite($gz, "INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $valueLines) . ";\n");

            $rowsInBatch = count($rows);
            $totalRows  += $rowsInBatch;
            $offset     += self::BATCH_SIZE;
            unset($rows, $valueLines);

            if ($rowsInBatch < self::BATCH_SIZE) break;
        }

        gzwrite($gz, "\n");
        return $totalRows;
    }

    /** Supprime les dumps au-delà de la rétention. Retourne le nombre supprimé. */
    public static function prune(int $keep = self::KEEP): int
    {
        $files = glob(self::backupDir() . '/backup_*.sql.gz') ?: [];
        rsort($files); // tri lexicographique descendant = du plus récent au plus ancien (nom = timestamp)

        $toDelete = array_slice($files, $keep);
        foreach ($toDelete as $file) unlink($file);

        return count($toDelete);
    }

    /** Liste les dumps disponibles pour l'admin (le plus récent d'abord). */
    public static function listBackups(): array
    {
        $files = glob(self::backupDir() . '/backup_*.sql.gz') ?: [];
        rsort($files);

        return array_map(function (string $path) {
            $filename = basename($path);
            // Le nom encode déjà la date de création (backup_Y-m-d_His.sql.gz) — plus fiable que filemtime()
            // si le fichier a été déplacé/copié entre serveurs.
            preg_match('/^backup_(\d{4}-\d{2}-\d{2})_(\d{2})(\d{2})(\d{2})\.sql\.gz$/', $filename, $m);
            $createdAt = $m ? "{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}" : date('Y-m-d H:i:s', filemtime($path));

            return [
                'filename'   => $filename,
                'size'       => filesize($path),
                'created_at' => $createdAt,
            ];
        }, $files);
    }

    private static function backupDir(): string
    {
        $dir = STORAGE_PATH . '/backups';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
        return round($bytes / 1048576, 1) . ' Mo';
    }
}
