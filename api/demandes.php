<?php
// api/demandes.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Models\Demande;

Response::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $item = Demande::getById($_GET['id']);
            if (!$item) {
                Response::notFound('Demande introuvable.');
            }
            Response::json($item);
        }

        $demandes = Demande::getAll($_GET);
        Response::json($demandes);
        break;

    case 'POST':
        $input = Response::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        if ($action === 'status') {
            $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
            $status = (string) ($input['status'] ?? '');
            if (!$id || empty($status)) {
                Response::error('ID et statut requis.', 422);
            }
            Demande::updateStatus($id, $status);
            Response::success(null, 'Statut de la demande mis à jour.');
        }

        if (empty($input['email']) && empty($input['phone'])) {
            Response::error('Veuillez fournir au moins un moyen de contact (email ou téléphone).', 422);
        }

        $created = Demande::create($input);
        Response::success($created, 'Votre demande a été enregistrée avec succès.', 201);
        break;

    case 'PATCH':
        $input = Response::getJsonInput();
        $id = (int) ($_GET['id'] ?? ($input['id'] ?? 0));
        $status = (string) ($input['status'] ?? ($_GET['status'] ?? ''));

        if (!$id || empty($status)) {
            Response::error('ID et statut requis.', 422);
        }

        Demande::updateStatus($id, $status);
        Response::success(null, 'Statut mis à jour.');
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $input = Response::getJsonInput();
            $id = (int) ($input['id'] ?? 0);
        }
        if (!$id) {
            Response::error('ID de la demande requis.', 422);
        }

        Demande::delete($id);
        Response::success(null, 'Demande supprimée avec succès.');
        break;

    default:
        Response::error('Méthode non autorisée.', 405);
}
