<?php
// api/settings.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Helpers\Auth;
use Immolode\Models\Setting;

Response::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $settings = Setting::getAll();
        Response::json($settings);
        break;

    case 'POST':
        $input = Response::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        Setting::updateAll($input);
        Response::success(Setting::getAll(), 'Paramètres enregistrés avec succès.');
        break;

    default:
        Response::error('Méthode non autorisée.', 405);
}
