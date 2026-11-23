<?php
/**
 * ============================================================================
 * api/users.php - API de gestion des utilisateurs de l'administration
 * ============================================================================
 * Requêtes préparées MySQLi (sans PDO).
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

try {
    switch ($method) {
        // --------------------------------------------------------------------
        // 1. GET : Liste ou consultation d'un utilisateur
        // --------------------------------------------------------------------
        case 'GET':
            if (!empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $conn->prepare("SELECT `id`, `nom`, `email`, `role`, `poste`, `telephone`, `actif`, `date_creation` FROM `utilisateurs` WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                if (!$user) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
                    exit;
                }
                echo json_encode($user);
                exit;
            }

            $res = $conn->query("SELECT `id`, `nom`, `email`, `role`, `poste`, `telephone`, `actif`, `date_creation` FROM `utilisateurs` ORDER BY `id` ASC");
            $users = [];
            while ($row = $res->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
            break;

        // --------------------------------------------------------------------
        // 2. POST : Création ou modification d'un utilisateur
        // --------------------------------------------------------------------
        case 'POST':
            // Mise à jour si ID présent
            if (!empty($input['id'])) {
                $id = (int)$input['id'];
                $nom = trim($input['nom'] ?? '');
                $email = strtolower(trim($input['email'] ?? ''));
                $role = trim($input['role'] ?? 'Agent');
                $poste = trim($input['poste'] ?? 'Conseiller');
                $tel = trim($input['telephone'] ?? $input['phone'] ?? '');
                $actif = isset($input['actif']) ? (int)$input['actif'] : 1;

                if (!empty($input['password'])) {
                    $hash = password_hash($input['password'], PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `mot_de_passe` = ?, `role` = ?, `poste` = ?, `telephone` = ?, `actif` = ? WHERE `id` = ?");
                    $stmt->bind_param("ssssssii", $nom, $email, $hash, $role, $poste, $tel, $actif, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `role` = ?, `poste` = ?, `telephone` = ?, `actif` = ? WHERE `id` = ?");
                    $stmt->bind_param("sssssii", $nom, $email, $role, $poste, $tel, $actif, $id);
                }
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour']);
                exit;
            }

            // Création d'un nouvel utilisateur
            $nom   = trim($input['nom'] ?? '');
            $email = strtolower(trim($input['email'] ?? ''));
            $pwd   = (string)($input['password'] ?? 'admin123');
            $role  = trim($input['role'] ?? 'Agent');
            $poste = trim($input['poste'] ?? 'Conseiller');
            $tel   = trim($input['telephone'] ?? $input['phone'] ?? '');

            if (empty($nom) || empty($email)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Nom et email obligatoires']);
                exit;
            }

            // Vérification de l'unicité de l'email
            $checkStmt = $conn->prepare("SELECT `id` FROM `utilisateurs` WHERE LOWER(`email`) = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'error' => 'Un compte existe déjà avec cette adresse e-mail']);
                exit;
            }

            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("
                INSERT INTO `utilisateurs` (`nom`, `email`, `mot_de_passe`, `role`, `poste`, `telephone`, `actif`)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param("ssssss", $nom, $email, $hash, $role, $poste, $tel);
            $stmt->execute();
            $newId = $conn->insert_id;

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $newId,
                'message' => 'Utilisateur créé avec succès'
            ]);
            break;

        // --------------------------------------------------------------------
        // 3. DELETE : Suppression d'un utilisateur
        // --------------------------------------------------------------------
        case 'DELETE':
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'ID requis']);
                exit;
            }

            if ($id === 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Impossible de supprimer le compte administrateur principal']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM `utilisateurs` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé']);
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
