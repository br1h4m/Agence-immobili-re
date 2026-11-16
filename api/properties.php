<?php
// api/properties.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Helpers\Auth;
use Immolode\Models\Property;

Response::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        // Détail d'un bien
        if (!empty($_GET['id'])) {
            $item = Property::getById($_GET['id']);
            if (!$item) {
                Response::notFound('Bien immobilier introuvable.');
            }
            Response::json($item);
        }

        if (!empty($_GET['reference'])) {
            $item = Property::getByReference($_GET['reference']);
            if (!$item) {
                Response::notFound('Bien immobilier introuvable.');
            }
            Response::json($item);
        }

        // Options pour les listes déroulantes de filtres
        if (isset($_GET['options'])) {
            $all = Property::getAll();
            $wilayas = array_values(array_unique(array_filter(array_column(array_column($all, 'location'), 'wilaya'))));
            $cities  = array_values(array_unique(array_filter(array_column(array_column($all, 'location'), 'city'))));
            $types   = array_values(array_unique(array_filter(array_column($all, 'type'))));
            sort($wilayas);
            sort($cities);
            sort($types);

            Response::json([
                'wilayas' => $wilayas,
                'cities'  => $cities,
                'types'   => $types
            ]);
        }

        // Liste filtrée
        $filters = $_GET;
        $properties = Property::getAll($filters);
        Response::json($properties);
        break;

    case 'POST':
        // Création ou mise à jour
        $input = Response::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        if ($action === 'update' || !empty($input['id'])) {
            // Modification
            $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                Response::error('ID du bien manquant.', 422);
            }
            $ok = Property::update($id, $input);
            if (!$ok) {
                Response::error('Impossible de mettre à jour le bien.', 400);
            }
            Response::success(Property::getById($id), 'Bien mis à jour avec succès.');
        }

        if ($action === 'status') {
            $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
            $status = (string) ($input['status'] ?? '');
            if (!$id || empty($status)) {
                Response::error('Paramètres id ou status manquants.', 422);
            }
            Property::updateStatus($id, $status);
            Response::success(null, 'Statut mis à jour.');
        }

        // Création
        $created = Property::create($input);
        Response::success($created, 'Bien créé avec succès.', 201);
        break;

    case 'PUT':
        $input = Response::getJsonInput();
        $id = (int) ($_GET['id'] ?? ($input['id'] ?? 0));
        if (!$id) {
            Response::error('ID du bien requis.', 422);
        }

        $ok = Property::update($id, $input);
        if (!$ok) {
            Response::error('Erreur lors de la mise à jour.', 400);
        }
        Response::success(Property::getById($id), 'Bien modifié avec succès.');
        break;

    case 'PATCH':
        $input = Response::getJsonInput();
        $id = (int) ($_GET['id'] ?? ($input['id'] ?? 0));
        $status = $input['status'] ?? ($_GET['status'] ?? '');
        if (!$id || empty($status)) {
            Response::error('ID et statut requis.', 422);
        }

        Property::updateStatus($id, (string) $status);
        Response::success(null, 'Statut mis à jour avec succès.');
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $input = Response::getJsonInput();
            $id = (int) ($input['id'] ?? 0);
        }
        if (!$id) {
            Response::error('ID du bien requis pour la suppression.', 422);
        }

        $ok = Property::delete($id);
        if (!$ok) {
            Response::error('Échec de la suppression du bien.', 400);
        }
        Response::success(null, 'Bien supprimé avec succès.');
        break;

    default:
        Response::error('Méthode non autorisée.', 405);
}
