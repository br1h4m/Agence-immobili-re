<?php
/**
 * ============================================================================
 * api/auth.php - Authentification de l'administration
 * ============================================================================
 * Requêtes MySQL préparées avec MySQLi (sans PDO).
 * Gestion simple des sessions PHP pour sécuriser le tableau de bord.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

// Gestion de session PHP native
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Entêtes CORS et format JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Récupération des données POST (JSON ou formulaire standard)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

try {
    switch ($method) {
        // --------------------------------------------------------------------
        // 1. GET : Vérification si une session admin est déjà active
        // --------------------------------------------------------------------
        case 'GET':
            if (!empty($_SESSION['admin_user'])) {
                echo json_encode([
                    'authenticated' => true,
                    'user' => $_SESSION['admin_user']
                ]);
            } else {
                echo json_encode([
                    'authenticated' => false,
                    'user' => null
                ]);
            }
            break;

        // --------------------------------------------------------------------
        // 2. POST : Connexion ou déconnexion
        // --------------------------------------------------------------------
        case 'POST':
            // Déconnexion
            if ($action === 'logout') {
                unset($_SESSION['admin_user']);
                session_destroy();
                echo json_encode(['success' => true, 'message' => 'Déconnexion réussie']);
                exit;
            }

            // Tentative de connexion
            $email = trim((string)($input['email'] ?? ''));
            $password = (string)($input['password'] ?? '');

            if (empty($email) || empty($password)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Veuillez saisir votre email et mot de passe']);
                exit;
            }

            // Raccourci pratique pour l'environnement local : si on entre "admin" et "admin"
            if ($email === 'admin' && $password === 'admin') {
                $email = 'admin@immolode.dz';
                $password = 'admin123';
            }

            // Recherche de l'utilisateur dans la table `utilisateurs` via MySQLi préparé
            $cleanEmail = strtolower($email);
            $stmt = $conn->prepare("SELECT * FROM `utilisateurs` WHERE LOWER(`email`) = ? AND `actif` = 1 LIMIT 1");
            $stmt->bind_param("s", $cleanEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Identifiants incorrects ou compte inactif']);
                exit;
            }

            // Vérification du mot de passe hashé
            $valid = password_verify($password, $user['mot_de_passe']);
            // Fallback mot de passe en clair pour test si non encore hashé
            if (!$valid && $password === $user['mot_de_passe']) {
                $valid = true;
            }

            if (!$valid) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Mot de passe incorrect']);
                exit;
            }

            // Session validée
            unset($user['mot_de_passe']); // Sécurité : on retire le mot de passe
            $_SESSION['admin_user'] = [
                'id' => $user['id'],
                'nom' => $user['nom'],
                'email' => $user['email'],
                'role' => $user['role'],
                'poste' => $user['poste'] ?? 'Gestionnaire'
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie',
                'user' => $_SESSION['admin_user'],
                'data' => [
                    'user' => $_SESSION['admin_user'],
                    'token' => session_id()
                ],
                'token' => session_id()
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
