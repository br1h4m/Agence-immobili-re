<?php
// api/fiches-client.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Helpers\Uploader;
use Immolode\Models\ClientFiche;

Response::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $item = ClientFiche::getById($_GET['id']);
            if (!$item) {
                Response::notFound('Fiche client introuvable.');
            }
            Response::json($item);
        }

        if (!empty($_GET['reference'])) {
            $item = ClientFiche::getByReference($_GET['reference']);
            if (!$item) {
                Response::notFound('Fiche client introuvable.');
            }
            Response::json($item);
        }

        $fiches = ClientFiche::getAll($_GET);
        Response::json($fiches);
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
            ClientFiche::updateStatus($id, $status);
            Response::success(null, 'Statut du dossier client mis à jour.');
        }

        // Si un fichier direct a été transmis via $_FILES
        if (!empty($_FILES['piece_identite_file'])) {
            try {
                $uploadResult = Uploader::handleFileUpload($_FILES['piece_identite_file'], 'documents');
                $input['pieceIdentite'] = [
                    'name'    => $uploadResult['original_name'],
                    'size'    => $uploadResult['size'],
                    'type'    => $uploadResult['mime_type'],
                    'dataUrl' => $uploadResult['url']
                ];
            } catch (\Throwable $t) {
                Response::error('Erreur téléversement pièce d\'identité : ' . $t->getMessage(), 400);
            }
        }

        // Validation des champs obligatoires
        if (empty($input['nom']) || empty($input['prenom']) || empty($input['telephone'])) {
            Response::error('Nom, prénom et téléphone sont obligatoires pour créer un dossier client.', 422);
        }

        $created = ClientFiche::create($input);
        Response::success($created, 'Dossier client validé et enregistré avec succès.', 201);
        break;

    case 'PATCH':
        $input = Response::getJsonInput();
        $id = (int) ($_GET['id'] ?? ($input['id'] ?? 0));
        $status = (string) ($input['status'] ?? ($_GET['status'] ?? ''));

        if (!$id || empty($status)) {
            Response::error('ID et statut requis.', 422);
        }

        ClientFiche::updateStatus($id, $status);
        Response::success(null, 'Statut mis à jour.');
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $input = Response::getJsonInput();
            $id = (int) ($input['id'] ?? 0);
        }
        if (!$id) {
            Response::error('ID du dossier client requis.', 422);
        }

        ClientFiche::delete($id);
        Response::success(null, 'Fiche client supprimée.');
        break;

    default:
        Response::error('Méthode non autorisée.', 405);
}
