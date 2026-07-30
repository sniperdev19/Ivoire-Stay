<?php
/**
 * Compresse rétroactivement les images déjà présentes dans public/assets/uploads/
 * (chambres, établissements, justificatifs de dépenses) avec la même logique que
 * UploadService (redimensionnement + réencodage, conversion PNG opaque → JPEG).
 *
 * Ne remplace un fichier que si la version compressée est réellement plus légère —
 * sinon l'original est conservé tel quel. Met à jour les chemins en base
 * (room_photos, establishment_photos, establishments.cover_photo, expenses.receipt_path)
 * quand l'extension change (ex. PNG → JPEG). L'ancien fichier n'est supprimé
 * qu'après confirmation de la mise à jour en base.
 *
 * Usage : php scripts/compress-existing-uploads.php
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

use Core\Database;
use Services\UploadService;

$publicAssets = BASE_PATH . '/public/assets';

$stats = ['scanned' => 0, 'compressed' => 0, 'skipped' => 0, 'errors' => 0, 'before' => 0, 'after' => 0];

/**
 * @param array{table:?string,id:?int,column:?string} $ref Référence BDD à mettre à jour, ou table=null si orpheline.
 */
function processFile(string $absPath, string $relPath, int $maxDim, int $jpegQ, int $webpQ, array $ref, array &$stats): void
{
    $stats['scanned']++;

    if (!file_exists($absPath)) {
        echo "  [absent] $relPath\n";
        $stats['errors']++;
        return;
    }

    $before = filesize($absPath);
    $dir    = dirname($absPath);
    $ext    = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    $tmpBase = 'tmp_' . bin2hex(random_bytes(8));

    try {
        $newFilename = UploadService::compressFile($absPath, $ext, $dir, $tmpBase, $maxDim, $jpegQ, $webpQ);
    } catch (\Throwable $e) {
        echo "  [erreur] $relPath — " . $e->getMessage() . "\n";
        $stats['errors']++;
        return;
    }

    $newAbsPath = $dir . '/' . $newFilename;
    $after      = @filesize($newAbsPath);

    if ($after === false || $after >= $before) {
        // Pas de gain (ou pire) : on garde l'original, on jette l'essai.
        @unlink($newAbsPath);
        $stats['skipped']++;
        printf("  [conservé] %-70s %8d octets (déjà optimal)\n", $relPath, $before);
        return;
    }

    $finalFilename = pathinfo($relPath, PATHINFO_FILENAME) . '.' . pathinfo($newFilename, PATHINFO_EXTENSION);
    $finalRelPath  = dirname($relPath) . '/' . $finalFilename;
    $finalAbsPath  = $dir . '/' . $finalFilename;

    // Ordre volontaire : le fichier final existe AVANT que la base ne le
    // référence, et l'original n'est supprimé qu'une fois la base à jour —
    // à chaque étape, soit l'ancien soit le nouveau chemin est valide, jamais
    // un chemin qui ne pointe vers rien.
    rename($newAbsPath, $finalAbsPath);

    if ($ref['table'] !== null) {
        Database::query(
            "UPDATE {$ref['table']} SET {$ref['column']} = ? WHERE id = ?",
            [$finalRelPath, $ref['id']]
        );
        if ($ref['table'] === 'establishment_photos' && !empty($ref['syncCoverForEstabId'])) {
            Database::query(
                "UPDATE establishments SET cover_photo = ? WHERE id = ? AND cover_photo = ?",
                [$finalRelPath, $ref['syncCoverForEstabId'], $relPath]
            );
        }
    }

    if ($finalAbsPath !== $absPath) {
        unlink($absPath);
    }

    $stats['compressed']++;
    $stats['before'] += $before;
    $stats['after']  += $after;
    printf("  [compressé] %-68s %8d -> %8d octets (%.0f%%)%s\n",
        $relPath, $before, $after, (1 - $after / $before) * 100,
        $finalFilename !== basename($relPath) ? "  [{$ext} -> " . pathinfo($finalFilename, PATHINFO_EXTENSION) . ']' : ''
    );
}

// ─── 1. Photos de chambres (room_photos) ───────────────────────────────────────
echo "== Photos de chambres ==\n";
$seen = [];
foreach (Database::query("SELECT id, file_path FROM room_photos")->fetchAll() as $row) {
    $abs = $publicAssets . '/' . $row['file_path'];
    $seen[realpath($abs) ?: $abs] = true;
    processFile($abs, $row['file_path'], UploadService::PHOTO_MAX_DIMENSION, UploadService::PHOTO_JPEG_QUALITY, UploadService::PHOTO_WEBP_QUALITY,
        ['table' => 'room_photos', 'id' => $row['id'], 'column' => 'file_path'], $stats);
}

// ─── 2. Photos d'établissements (establishment_photos + cover_photo) ───────────
echo "\n== Photos d'établissements ==\n";
foreach (Database::query("SELECT id, establishment_id, file_path FROM establishment_photos")->fetchAll() as $row) {
    $abs = $publicAssets . '/' . $row['file_path'];
    $seen[realpath($abs) ?: $abs] = true;
    processFile($abs, $row['file_path'], UploadService::PHOTO_MAX_DIMENSION, UploadService::PHOTO_JPEG_QUALITY, UploadService::PHOTO_WEBP_QUALITY,
        ['table' => 'establishment_photos', 'id' => $row['id'], 'column' => 'file_path', 'syncCoverForEstabId' => $row['establishment_id']], $stats);
}

// ─── 3. Justificatifs de dépenses ──────────────────────────────────────────────
echo "\n== Justificatifs de dépenses ==\n";
foreach (Database::query("SELECT id, receipt_path FROM expenses WHERE receipt_path IS NOT NULL AND receipt_path != ''")->fetchAll() as $row) {
    $abs = $publicAssets . '/' . $row['receipt_path'];
    $seen[realpath($abs) ?: $abs] = true;
    processFile($abs, $row['receipt_path'], UploadService::RECEIPT_MAX_DIMENSION, UploadService::RECEIPT_JPEG_QUALITY, UploadService::RECEIPT_WEBP_QUALITY,
        ['table' => 'expenses', 'id' => $row['id'], 'column' => 'receipt_path'], $stats);
}

// ─── 4. Fichiers orphelins (présents sur disque, plus référencés en base) ──────
// Compressés aussi (gain d'espace immédiat), sans mise à jour BDD (rien à mettre à jour).
echo "\n== Fichiers orphelins (non référencés en base) ==\n";
foreach (['rooms', 'establishments'] as $sub) {
    $pattern = $publicAssets . '/uploads/' . $sub . '/*/*.{jpg,jpeg,png,webp}';
    foreach (glob($pattern, GLOB_BRACE) as $abs) {
        $real = realpath($abs);
        if (isset($seen[$real])) continue;
        $rel = 'uploads/' . $sub . '/' . basename(dirname($abs)) . '/' . basename($abs);
        processFile($abs, $rel, UploadService::PHOTO_MAX_DIMENSION, UploadService::PHOTO_JPEG_QUALITY, UploadService::PHOTO_WEBP_QUALITY,
            ['table' => null, 'id' => null, 'column' => null], $stats);
    }
}

echo "\n─────────────────────────────────────────\n";
printf("Fichiers examinés  : %d\n", $stats['scanned']);
printf("Compressés         : %d\n", $stats['compressed']);
printf("Conservés (déjà optimaux) : %d\n", $stats['skipped']);
printf("Erreurs            : %d\n", $stats['errors']);
if ($stats['before'] > 0) {
    printf("Poids total        : %s -> %s (%.1f%% de réduction)\n",
        formatBytes($stats['before']), formatBytes($stats['after']),
        (1 - $stats['after'] / $stats['before']) * 100
    );
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) return round($bytes / 1024 / 1024, 2) . ' Mo';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' Ko';
    return $bytes . ' o';
}
