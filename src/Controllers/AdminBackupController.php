<?php

namespace Controllers;

use Core\{Request, Response};
use Services\BackupService;

/**
 * Sauvegardes de la base de données — superadmin uniquement (routes gardées
 * ['auth', 'role:superadmin'] dans config/routes.php). Voir BackupService
 * pour la mécanique du dump.
 */
class AdminBackupController
{
    // Doit correspondre exactement au format produit par BackupService::dump() —
    // rejeté avant tout accès disque (protection path traversal sur download()).
    private const FILENAME_RE = '/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/';

    public function index(Request $req, array $params = []): void
    {
        Response::success(BackupService::listBackups());
    }

    public function store(Request $req, array $params = []): void
    {
        try {
            Response::success(['summary' => BackupService::run()], 'Sauvegarde créée');
        } catch (\Throwable $e) {
            error_log('[AdminBackupController] Échec sauvegarde manuelle — ' . $e->getMessage());
            Response::error('Échec de la sauvegarde : ' . $e->getMessage());
        }
    }

    public function download(Request $req, array $params = []): void
    {
        $filename = $params['filename'] ?? '';
        if (!preg_match(self::FILENAME_RE, $filename)) Response::notFound('Fichier invalide');

        $path = STORAGE_PATH . '/backups/' . $filename;
        if (!is_file($path)) Response::notFound('Sauvegarde introuvable');

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
