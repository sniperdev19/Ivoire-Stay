<?php

namespace Services;

class UploadService
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_EXTS  = ['jpg', 'jpeg', 'png', 'webp'];

    // Photos (chambres, établissements) : plein écran sur la fiche établissement,
    // 1920px est largement suffisant même en écran retina pour l'usage web.
    // Publiques : réutilisées par scripts/compress-existing-uploads.php.
    public const PHOTO_MAX_DIMENSION = 1920;
    public const PHOTO_JPEG_QUALITY  = 82;
    public const PHOTO_WEBP_QUALITY  = 82;

    // Justificatifs de dépenses : qualité un peu plus haute pour garder le texte lisible.
    public const RECEIPT_MAX_DIMENSION = 1600;
    public const RECEIPT_JPEG_QUALITY  = 85;
    public const RECEIPT_WEBP_QUALITY  = 85;

    public static function uploadRoomPhoto(array $file, int $roomId): string
    {
        self::validate($file);

        $dir = UPLOAD_PATH . '/rooms/' . $roomId;
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $base     = 'photo_' . bin2hex(random_bytes(12));
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = self::compressFile($file['tmp_name'], $ext, $dir, $base, self::PHOTO_MAX_DIMENSION, self::PHOTO_JPEG_QUALITY, self::PHOTO_WEBP_QUALITY);

        return 'uploads/rooms/' . $roomId . '/' . $filename;
    }

    public static function uploadEstabPhoto(array $file, int $estabId): string
    {
        self::validate($file);

        $dir = UPLOAD_PATH . '/establishments/' . $estabId;
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $base     = 'cover_' . bin2hex(random_bytes(10));
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = self::compressFile($file['tmp_name'], $ext, $dir, $base, self::PHOTO_MAX_DIMENSION, self::PHOTO_JPEG_QUALITY, self::PHOTO_WEBP_QUALITY);

        return 'uploads/establishments/' . $estabId . '/' . $filename;
    }

    public static function uploadReceipt(array $file, int $expenseId): string
    {
        self::validate($file);

        $dir = UPLOAD_PATH . '/receipts';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $base     = 'receipt_' . $expenseId . '_' . bin2hex(random_bytes(12));
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = self::compressFile($file['tmp_name'], $ext, $dir, $base, self::RECEIPT_MAX_DIMENSION, self::RECEIPT_JPEG_QUALITY, self::RECEIPT_WEBP_QUALITY);

        return 'uploads/receipts/' . $filename;
    }

    private static function validate(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Erreur lors du téléversement du fichier');
        }
        // compressFile() lit le fichier directement (GD) plutôt que via
        // move_uploaded_file() — on doit donc revalider explicitement que
        // tmp_name provient bien d'un upload HTTP réel, garantie que
        // move_uploaded_file() offrait implicitement.
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('Fichier invalide');
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

    /**
     * Redimensionne (jamais d'agrandissement) et réencode l'image pour
     * réduire son poids sur disque, en conservant la qualité visuelle :
     *  - JPEG/WebP : réencodés dans le même format à qualité cible.
     *  - PNG opaque (cas très majoritaire pour des photos d'hôtel/chambre) :
     *    converti en JPEG, un format bien plus compact pour de la photo —
     *    PNG (sans perte) est fait pour des graphismes à aplats, pas des
     *    photographies, et le gain observé est marginal voire négatif.
     *  - PNG avec transparence réelle : reste en PNG.
     * Repli sur une simple copie si GD ne parvient pas à lire l'image
     * (format inattendu, fichier corrompu) — on préfère un upload non
     * compressé à un upload en échec.
     *
     * Publique : réutilisée telle quelle par scripts/compress-existing-uploads.php
     * pour recompresser les fichiers déjà sur disque, en dehors de tout upload HTTP.
     *
     * @return string Nom de fichier final (avec son extension, potentiellement
     *                différente de l'extension d'origine si converti en JPEG).
     */
    public static function compressFile(
        string $srcPath,
        string $originalExt,
        string $destDir,
        string $baseName,
        int $maxDimension,
        int $jpegQuality,
        int $webpQuality
    ): string {
        $originalExt = strtolower($originalExt);

        $info = @getimagesize($srcPath);
        if ($info === false) {
            return self::fallbackCopy($srcPath, $destDir, $baseName, $originalExt);
        }
        [$width, $height, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($srcPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($srcPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($srcPath),
            default        => null,
        };
        if (!$src) {
            return self::fallbackCopy($srcPath, $destDir, $baseName, $originalExt);
        }

        $scale     = min(1, $maxDimension / max($width, $height));
        $newWidth  = $scale < 1 ? max(1, (int) round($width * $scale)) : $width;
        $newHeight = $scale < 1 ? max(1, (int) round($height * $scale)) : $height;

        $convertToJpeg = $type === IMAGETYPE_PNG && !self::pngHasTransparency($src, $width, $height);
        $keepsAlpha    = $type !== IMAGETYPE_JPEG && !$convertToJpeg;

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        if ($keepsAlpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        } else {
            // Fond blanc : couvre le cas où l'image source a un fond
            // transparent non détecté par l'échantillonnage (marge de sécurité).
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
        }
        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($src);

        if ($type === IMAGETYPE_JPEG || $convertToJpeg) {
            $filename = $baseName . '.jpg';
            $ok = imagejpeg($canvas, $destDir . '/' . $filename, $jpegQuality);
        } elseif ($type === IMAGETYPE_PNG) {
            $filename = $baseName . '.png';
            $ok = imagepng($canvas, $destDir . '/' . $filename, 6);
        } else {
            $filename = $baseName . '.webp';
            $ok = imagewebp($canvas, $destDir . '/' . $filename, $webpQuality);
        }
        imagedestroy($canvas);

        if (!$ok) {
            return self::fallbackCopy($srcPath, $destDir, $baseName, $originalExt);
        }
        return $filename;
    }

    /** Échantillonnage borné (~100x100 points max, quelle que soit la taille de l'image). */
    private static function pngHasTransparency(\GdImage $img, int $width, int $height): bool
    {
        if (!imageistruecolor($img)) {
            $transparentIndex = imagecolortransparent($img);
            if ($transparentIndex >= 0) return true;
        }

        $trueColor = imageistruecolor($img);
        $stepX     = max(1, (int) floor($width / 100));
        $stepY     = max(1, (int) floor($height / 100));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                if ($trueColor) {
                    $alpha = (imagecolorat($img, $x, $y) >> 24) & 0x7F;
                } else {
                    $index = imagecolorat($img, $x, $y);
                    $alpha = imagecolorsforindex($img, $index)['alpha'];
                }
                if ($alpha > 0) return true; // 0 = opaque, 127 = transparent (échelle GD)
            }
        }
        return false;
    }

    private static function fallbackCopy(string $srcPath, string $destDir, string $baseName, string $originalExt): string
    {
        $filename = $baseName . '.' . $originalExt;
        if (!copy($srcPath, $destDir . '/' . $filename)) {
            throw new \RuntimeException('Échec de l\'enregistrement du fichier');
        }
        return $filename;
    }
}
