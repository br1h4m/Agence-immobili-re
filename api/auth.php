<?php
// api/auth.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Helpers\Auth;
use Immolode\Models\User;

Response::handlePreflight();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Vérification de la session active
        $user = Auth::user();
        Response::json([
            'authenticated' => (bool) $user,
            'user'          => $user
        ]);
        break;

    case 'POST':
        $input = Response::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        if ($action === 'logout') {
            Auth::logout();
            Response::success(null, 'Déconnexion effectuée.');
        }

        // Login
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            Response::error('Veuillez renseigner votre email et mot de passe.', 422);
        }

        // Cas pratique : si l'admin tape 'admin' et 'admin', on mappe vers le compte de test
        if ($email === 'admin' && $password === 'admin') {
            $email = 'admin@immolode.dz';
            $password = 'admin123';
        }

        $user = User::authenticate($email, $password);
        if (!$user) {
            Response::error('Identifiants incorrects. Veuillez réessayer.', 401);
        }

        Auth::login($user);
        Response::success([
            'user'  => $user,
            'token' => session_id()
        ], 'Connexion réussie.');
        break;

    default:
        Response::error('Méthode non autorisée.', 405);
}
