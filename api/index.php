<?php
// api/index.php
// Routeur centralisé pour l'API REST en PHP
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;

Response::handlePreflight();

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// Nettoyer le préfixe /api ou /api/
$route = preg_replace('#^.*?/api/?#', '', $path);
$route = trim($route, '/');
$segments = explode('/', $route);
$resource = $segments[0] ?? '';

// Extraire l'ID si présent dans l'URI (ex: /api/properties/12 -> $_GET['id'] = 12)
if (!empty($segments[1]) && is_numeric($segments[1])) {
    $_GET['id'] = (int) $segments[1];
}

$endpoints = [
    'properties'    => __DIR__ . '/properties.php',
    'demandes'      => __DIR__ . '/demandes.php',
    'fiches-client' => __DIR__ . '/fiches-client.php',
    'auth'          => __DIR__ . '/auth.php',
    'users'         => __DIR__ . '/users.php',
    'settings'      => __DIR__ . '/settings.php',
    'stats'         => __DIR__ . '/stats.php',
    'upload'        => __DIR__ . '/upload.php',
];

if (isset($endpoints[$resource])) {
    require $endpoints[$resource];
    exit;
}

// Si la ressource est vide, page d'accueil de l'API
if (empty($resource)) {
    Response::json([
        'api'       => 'Immolode REST API',
        'version'   => '1.0.0',
        'backend'   => 'PHP ' . PHP_VERSION,
        'endpoints' => array_keys($endpoints)
    ]);
}

Response::notFound("Point d'accès API introuvable : /api/{$resource}");
