<?php
// api/upload.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Helpers\Uploader;

Response::handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée.', 405);
}

// 1. Fichier direct transmis dans $_FILES
if (!empty($_FILES['file'])) {
    $folder = $_POST['folder'] ?? 'properties';
    try {
        $result = Uploader::handleFileUpload($_FILES['file'], $folder);
        Response::success($result, 'Fichier téléversé avec succès.');
    } catch (\Throwable $t) {
        Response::error($t->getMessage(), 400);
    }
}

// 2. Fichier transmis en base64
$input = Response::getJsonInput();
if (!empty($input['base64'])) {
    $folder = $input['folder'] ?? 'signatures';
    $prefix = $input['prefix'] ?? 'file';
    $url = Uploader::saveBase64Image($input['base64'], $folder, $prefix);
    if (!$url) {
        Response::error('Impossible de décoder l\'image Base64.', 400);
    }
    Response::success(['url' => $url], 'Image enregistrée avec succès.');
}

Response::error('Aucun fichier ou donnée Base64 reçue.', 422);
