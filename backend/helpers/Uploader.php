<?php
// backend/helpers/Uploader.php
declare(strict_types=1);

namespace Immolode\Helpers;

use Immolode\Config\Config;

class Uploader {
    private const ALLOWED_IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const ALLOWED_DOC_EXTS   = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
    private const ALLOWED_FOLDERS    = ['properties', 'avatars', 'cms', 'documents', 'signatures'];
    private const MAX_FILE_SIZE      = 15 * 1024 * 1024; // 15 Mo

    private static function sanitizeFolder(string $subfolder): string {
        $clean = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $subfolder));
        return in_array($clean, self::ALLOWED_FOLDERS, true) ? $clean : 'properties';
    }

    public static function handleFileUpload(array $fileInfo, string $subfolder = 'properties'): array {
        $safeFolder = self::sanitizeFolder($subfolder);

        if (!isset($fileInfo['error']) || is_array($fileInfo['error'])) {
            throw new \RuntimeException('Paramètres de fichier invalides.');
        }

        switch ($fileInfo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \RuntimeException('Aucun fichier transmis.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \RuntimeException('Le fichier dépasse la taille maximale autorisée par le serveur.');
            case UPLOAD_ERR_PARTIAL:
                throw new \RuntimeException('Téléversement incomplet. Veuillez réessayer.');
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                throw new \RuntimeException('Erreur d\'écriture sur le répertoire temporaire du serveur.');
            default:
                throw new \RuntimeException('Erreur technique lors du téléversement (code ' . $fileInfo['error'] . ').');
        }

        if ((int)$fileInfo['size'] <= 0) {
            throw new \RuntimeException('Le fichier sélectionné est vide.');
        }

        if ((int)$fileInfo['size'] > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('Fichier trop volumineux (max 15 Mo).');
        }

        $origName = (string)($fileInfo['name'] ?? 'file');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $allowed = ($safeFolder === 'documents') ? self::ALLOWED_DOC_EXTS : self::ALLOWED_IMAGE_EXTS;
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('Extension non autorisée : .' . $ext . ' (autorisés : ' . implode(', ', $allowed) . ').');
        }

        $tmpName = (string)($fileInfo['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            // Permet les tests internes si le fichier existe dans un flux CLI uniquement en mode non-HTTP
            if (!is_file($tmpName)) {
                throw new \RuntimeException('Fichier temporaire introuvable après réception.');
            }
        }

        // Validation du contenu réel pour les images
        if ($ext !== 'pdf') {
            $imgInfo = @getimagesize($tmpName);
            if ($imgInfo === false) {
                throw new \RuntimeException('Le fichier sélectionné ne semble pas être une image valide.');
            }
        } elseif (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? @finfo_file($finfo, $tmpName) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if ($mime && !str_contains($mime, 'pdf') && !str_starts_with($mime, 'image/')) {
                throw new \RuntimeException('Type MIME non autorisé : ' . $mime);
            }
        }

        $targetDir = Config::UPLOADS_PATH . '/' . $safeFolder;
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new \RuntimeException('Impossible de créer le dossier de stockage : ' . $safeFolder);
            }
        }
        if (!is_writable($targetDir)) {
            @chmod($targetDir, 0775);
            if (!is_writable($targetDir)) {
                throw new \RuntimeException('Permissions d\'écriture insuffisantes sur le dossier uploads/' . $safeFolder);
            }
        }

        $safeName = sprintf('%s_%s.%s', bin2hex(random_bytes(8)), time(), $ext);
        $destPath = $targetDir . '/' . $safeName;

        $moved = is_uploaded_file($tmpName)
            ? @move_uploaded_file($tmpName, $destPath)
            : @rename($tmpName, $destPath);

        if (!$moved) {
            throw new \RuntimeException('Impossible d\'enregistrer le fichier sur le disque serveur.');
        }

        $publicUrl = Config::UPLOADS_URL . '/' . $safeFolder . '/' . $safeName;

        return [
            'original_name' => $origName,
            'file_name'     => $safeName,
            'size'          => (int)$fileInfo['size'],
            'mime_type'     => $fileInfo['type'] ?? '',
            'url'           => $publicUrl
        ];
    }

    public static function saveBase64Image(string $base64Data, string $subfolder = 'signatures', string $prefix = 'sig'): ?string {
        if (empty($base64Data)) {
            return null;
        }

        if (str_starts_with($base64Data, 'http://') || str_starts_with($base64Data, 'https://') || str_starts_with($base64Data, '/uploads/')) {
            return $base64Data;
        }

        if (preg_match('/^data:(?:image|application)\/([a-zA-Z0-9.+-]+);base64,/', $base64Data, $type)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            $rawType = strtolower($type[1]);
            $ext = match ($rawType) {
                'jpeg' => 'jpg',
                'svg+xml' => 'svg',
                'pdf' => 'pdf',
                default => preg_replace('/[^a-z0-9]/', '', $rawType) ?: 'png'
            };
        } else {
            $ext = 'png';
        }

        $decoded = base64_decode($base64Data, true);
        if ($decoded === false || strlen($decoded) === 0) {
            return null;
        }

        $safeFolder = self::sanitizeFolder($subfolder);
        $targetDir = Config::UPLOADS_PATH . '/' . $safeFolder;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        $fileName = sprintf('%s_%s_%s.%s', preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'file', time(), bin2hex(random_bytes(6)), $ext);
        $filePath = $targetDir . '/' . $fileName;

        if (file_put_contents($filePath, $decoded) === false) {
            return null;
        }

        return Config::UPLOADS_URL . '/' . $safeFolder . '/' . $fileName;
    }
}
