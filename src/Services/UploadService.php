<?php

namespace Services;

class UploadService
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_EXTS  = ['jpg', 'jpeg', 'png', 'webp'];

    public static function uploadRoomPhoto(array $file, int $roomId): string
    {
        self::validate($file);

        $dir = UPLOAD_PATH . '/rooms/' . $roomId;
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'photo_' . bin2hex(random_bytes(12)) . '.' . $ext;
        $dest     = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Échec du déplacement du fichier');
        }

        return 'uploads/rooms/' . $roomId . '/' . $filename;
    }

    public static function uploadEstabPhoto(array $file, int $estabId): string
    {
        self::validate($file);

        $dir = UPLOAD_PATH . '/establishments/' . $estabId;
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'cover_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $dest     = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Échec du déplacement du fichier');
        }

        return 'uploads/establishments/' . $estabId . '/' . $filename;
    }

    public static function uploadReceipt(array $file, int $expenseId): string
    {
        self::validate($file);

        $dir = UPLOAD_PATH . '/receipts';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'receipt_' . $expenseId . '_' . time() . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            throw new \RuntimeException('Échec du déplacement du justificatif');
        }

        return 'uploads/receipts/' . $filename;
    }

    private static function validate(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Erreur lors du téléversement du fichier');
        }
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            throw new \InvalidArgumentException('Fichier trop volumineux (max 5 Mo)');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            throw new \InvalidArgumentException('Extension non autorisée');
        }
    }
}
