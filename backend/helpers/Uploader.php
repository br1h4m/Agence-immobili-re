<?php
// backend/helpers/Uploader.php
declare(strict_types=1);

namespace Immolode\Helpers;

use Immolode\Config\Config;

class Uploader {
    private const ALLOWED_IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const ALLOWED_DOC_EXTS   = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    private const MAX_FILE_SIZE      = 15 * 1024 * 1024; // 15 Mo

    public static function handleFileUpload(array $fileInfo, string $subfolder = 'properties'): array {
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
                throw new \RuntimeException('Le fichier dépasse la taille maximale autorisée.');
            default:
                throw new \RuntimeException('Erreur inconnue lors du téléversement.');
        }

        if ($fileInfo['size'] > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('Fichier trop volumineux (max 15 Mo).');
        }

        $origName = $fileInfo['name'] ?? 'file';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $allowed = ($subfolder === 'documents') ? self::ALLOWED_DOC_EXTS : self::ALLOWED_IMAGE_EXTS;
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('Extension de fichier non autorisée : .' . $ext);
        }

        $targetDir = Config::UPLOADS_PATH . '/' . trim($subfolder, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $safeName = sprintf('%s_%s.%s', bin2hex(random_bytes(8)), time(), $ext);
        $destPath = $targetDir . '/' . $safeName;

        if (!move_uploaded_file($fileInfo['tmp_name'], $destPath)) {
            throw new \RuntimeException('Impossible de déplacer le fichier téléversé.');
        }

        $publicUrl = Config::UPLOADS_URL . '/' . trim($subfolder, '/') . '/' . $safeName;

        return [
            'original_name' => $origName,
            'file_name'     => $safeName,
            'size'          => $fileInfo['size'],
            'mime_type'     => $fileInfo['type'] ?? '',
            'url'           => $publicUrl
        ];
    }

    public static function saveBase64Image(string $base64Data, string $subfolder = 'signatures', string $prefix = 'sig'): ?string {
        if (empty($base64Data)) {
            return null;
        }

        // Si c'est déjà une URL classique, on la conserve
        if (str_starts_with($base64Data, 'http://') || str_starts_with($base64Data, 'https://') || str_starts_with($base64Data, '/uploads/')) {
            return $base64Data;
        }

        // Reconnaissance format data:image/xxx;base64,...
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            $ext = strtolower($type[1]); // png, jpeg, etc.
            if ($ext === 'jpeg') $ext = 'jpg';
            if ($ext === 'svg+xml') $ext = 'svg';
        } else {
            $ext = 'png';
        }

        $decoded = base64_decode($base64Data);
        if ($decoded === false) {
            return null;
        }

        $targetDir = Config::UPLOADS_PATH . '/' . trim($subfolder, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = sprintf('%s_%s_%s.%s', $prefix, time(), bin2hex(random_bytes(6)), $ext);
        $filePath = $targetDir . '/' . $fileName;

        file_put_contents($filePath, $decoded);

        return Config::UPLOADS_URL . '/' . trim($subfolder, '/') . '/' . $fileName;
    }
}
