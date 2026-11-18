<?php
/**
 * ============================================================================
 * api/users.php - API de gestion des utilisateurs de l'administration
 * ============================================================================
 * Requêtes préparées MySQLi (sans PDO).
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
                $stmt = $conn->prepare("SELECT `id`, `nom`, `email`, `role`, `poste`, `telephone`, `avatar`, `actif`, `date_creation` FROM `utilisateurs` WHERE `id` = ?");
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

            $res = $conn->query("SELECT `id`, `nom`, `email`, `role`, `poste`, `telephone`, `avatar`, `actif`, `date_creation` FROM `utilisateurs` ORDER BY `id` ASC");
            $users = [];
            while ($row = $res->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
            break;

        // --------------------------------------------------------------------
        // 2. POST : Création, profil ou modification d'un utilisateur
        // --------------------------------------------------------------------
        case 'POST':
            $action = $input['action'] ?? $_GET['action'] ?? '';

            // Suppression multiple (batch delete)
            if ($action === 'bulk_delete') {
                $ids = $input['ids'] ?? [];
                if (is_array($ids) && count($ids) > 0) {
                    $currentUserId = $_SESSION['admin_user']['id'] ?? 0;
                    $stmtDel = $conn->prepare("DELETE FROM `utilisateurs` WHERE `id` = ? AND `id` != 1 AND `id` != ?");
                    $stmtDel->bind_param("ii", $delId, $currentUserId);
                    $delCount = 0;
                    foreach ($ids as $idItem) {
                        $delId = (int)$idItem;
                        if ($delId > 1 && $delId !== $currentUserId) {
                            $stmtDel->execute();
                            $delCount++;
                        }
                    }
                    echo json_encode(['success' => true, 'count' => $delCount, 'message' => "{$delCount} utilisateur(s) supprimé(s)"]);
                    exit;
                }
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Aucun ID fourni']);
                exit;
            }

            // Mise à jour de son propre profil (Mon Profil)
            if ($action === 'profile' || $action === 'update_profile') {
                $id = (int)($input['id'] ?? ($_SESSION['admin_user']['id'] ?? 1));
                $nom = trim($input['nom'] ?? '');
                $email = strtolower(trim($input['email'] ?? ''));
                $tel = trim($input['telephone'] ?? $input['phone'] ?? '');
                $avatar = !empty($input['avatar']) ? trim((string)$input['avatar']) : null;

                if (empty($nom) || empty($email)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'Le nom et l\'email sont requis']);
                    exit;
                }

                if (!empty($input['password'])) {
                    $hash = password_hash($input['password'], PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `telephone` = ?, `avatar` = ?, `mot_de_passe` = ? WHERE `id` = ?");
                    $stmt->bind_param("sssssi", $nom, $email, $tel, $avatar, $hash, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `telephone` = ?, `avatar` = ? WHERE `id` = ?");
                    $stmt->bind_param("ssssi", $nom, $email, $tel, $avatar, $id);
                }
                $stmt->execute();

                // Mise à jour de la session PHP
                if (!empty($_SESSION['admin_user'])) {
                    $_SESSION['admin_user']['nom'] = $nom;
                    $_SESSION['admin_user']['email'] = $email;
                    $_SESSION['admin_user']['avatar'] = $avatar;
                }

                echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
                exit;
            }

            // Mise à jour si ID présent
            if (!empty($input['id'])) {
                $id = (int)$input['id'];
                $nom = trim($input['nom'] ?? '');
                $email = strtolower(trim($input['email'] ?? ''));
                $role = trim($input['role'] ?? 'Agent commercial');
                $poste = trim($input['poste'] ?? 'Conseiller');
                $tel = trim($input['telephone'] ?? $input['phone'] ?? '');
                $avatar = !empty($input['avatar']) ? trim((string)$input['avatar']) : null;
                $actif = isset($input['actif']) ? (int)$input['actif'] : 1;

                if (!empty($input['password'])) {
                    $hash = password_hash($input['password'], PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `mot_de_passe` = ?, `role` = ?, `poste` = ?, `telephone` = ?, `avatar` = ?, `actif` = ? WHERE `id` = ?");
                    $stmt->bind_param("sssssssii", $nom, $email, $hash, $role, $poste, $tel, $avatar, $actif, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `role` = ?, `poste` = ?, `telephone` = ?, `avatar` = ?, `actif` = ? WHERE `id` = ?");
                    $stmt->bind_param("ssssssii", $nom, $email, $role, $poste, $tel, $avatar, $actif, $id);
                }
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour']);
                exit;
            }

            // Création d'un nouvel utilisateur
            $nom   = trim($input['nom'] ?? '');
            $email = strtolower(trim($input['email'] ?? ''));
            $pwd   = (string)($input['password'] ?? 'admin123');
            $role  = trim($input['role'] ?? 'Agent commercial');
            $poste = trim($input['poste'] ?? 'Conseiller');
            $tel   = trim($input['telephone'] ?? $input['phone'] ?? '');
            $avatar= !empty($input['avatar']) ? trim((string)$input['avatar']) : null;

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
                INSERT INTO `utilisateurs` (`nom`, `email`, `mot_de_passe`, `role`, `poste`, `telephone`, `avatar`, `actif`)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param("sssssss", $nom, $email, $hash, $role, $poste, $tel, $avatar);
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
