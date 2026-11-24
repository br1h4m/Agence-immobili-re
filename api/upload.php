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

// Détection dépassement post_max_size côté PHP (qui vide $_FILES et $_POST)
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if (empty($_FILES) && empty($_POST) && $contentLength > 0) {
    $rawBody = file_get_contents('php://input');
    if ($rawBody === '' || $rawBody === false) {
        Response::error(
            'Le fichier dépasse la taille maximale de requête autorisée par PHP (post_max_size=' . ini_get('post_max_size') . ').',
            413
        );
    }
}

// 1. Fichier direct transmis dans $_FILES
if (!empty($_FILES['file'])) {
    $folder = (string)($_POST['folder'] ?? $_GET['folder'] ?? 'properties');
    try {
        $result = Uploader::handleFileUpload($_FILES['file'], $folder);
        Response::json([
            'success' => true,
            'message' => 'Fichier téléversé avec succès.',
            'url'     => $result['url'],
            'data'    => $result
        ], 200);
    } catch (\Throwable $t) {
        Response::error($t->getMessage(), 400);
    }
}

// 2. Fichier transmis en base64
$input = Response::getJsonInput();
if (!empty($input['base64'])) {
    $folder = (string)($input['folder'] ?? 'signatures');
    $prefix = (string)($input['prefix'] ?? 'file');
    $url = Uploader::saveBase64Image((string)$input['base64'], $folder, $prefix);
    if (!$url) {
        Response::error('Impossible de décoder l\'image Base64.', 400);
    }
    Response::json([
        'success' => true,
        'message' => 'Image enregistrée avec succès.',
        'url'     => $url,
        'data'    => ['url' => $url]
    ], 200);
}

Response::error('Aucun fichier reçu dans la requête d\'upload.', 422);
