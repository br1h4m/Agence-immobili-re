<?php
// api/users.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Helpers\Auth;
use Immolode\Models\User;

Response::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $user = User::getById($_GET['id']);
            if (!$user) {
                Response::notFound('Utilisateur introuvable.');
            }
            Response::json($user);
        }

        $users = User::getAll();
        Response::json($users);
        break;

    case 'POST':
        $input = Response::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        if (!empty($input['id'])) {
            $id = (int) $input['id'];
            $ok = User::update($id, $input);
            if (!$ok) {
                Response::error('Impossible de mettre à jour l\'utilisateur.', 400);
            }
            Response::success(User::getById($id), 'Utilisateur mis à jour.');
        }

        if (empty($input['nom']) || empty($input['email'])) {
            Response::error('Nom et adresse email requis.', 422);
        }

        $existing = User::getByEmail($input['email']);
        if ($existing) {
            Response::error('Un utilisateur avec cet email existe déjà.', 409);
        }

        $created = User::create($input);
        Response::success($created, 'Utilisateur créé avec succès.', 201);
        break;

    case 'DELETE':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $input = Response::getJsonInput();
            $id = (int) ($input['id'] ?? 0);
        }
        if (!$id) {
            Response::error('ID de l\'utilisateur requis.', 422);
        }

        if ($id === 1) {
            Response::error('Impossible de supprimer le compte administrateur principal.', 403);
        }

        User::delete($id);
        Response::success(null, 'Utilisateur supprimé.');
        break;

    default:
        Response::error('Méthode non autorisée.', 405);
}
