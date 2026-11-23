<?php
/**
 * ============================================================================
 * api/fiches-client.php - Gestion des dossiers et fiches clients
 * ============================================================================
 * Requêtes préparées MySQLi (sans PDO).
 * Permet l'enregistrement des dossiers clients (signature, pièce jointe, etc.)
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

function format_fiche_row(array $r): array {
    return array_merge($r, [
        'id'        => (int)$r['id'],
        'phone'     => $r['telephone'],
        'typeBien'  => $r['type_bien'],
        'idBien'    => $r['id_bien'],
        'status'    => $r['statut'],
        'createdAt' => $r['date_creation']
    ]);
}

try {
    switch ($method) {
        // --------------------------------------------------------------------
        // 1. GET : Liste ou détail d'une fiche client
        // --------------------------------------------------------------------
        case 'GET':
            if (!empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $conn->prepare("SELECT * FROM `fiches_client` WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                if (!$item) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Fiche client introuvable']);
                    exit;
                }
                echo json_encode(format_fiche_row($item));
                exit;
            }

            if (!empty($_GET['reference'])) {
                $ref = trim($_GET['reference']);
                $stmt = $conn->prepare("SELECT * FROM `fiches_client` WHERE `reference` = ?");
                $stmt->bind_param("s", $ref);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                if (!$item) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Fiche client introuvable']);
                    exit;
                }
                echo json_encode(format_fiche_row($item));
                exit;
            }

            // Liste de toutes les fiches
            $stmt = $conn->prepare("SELECT * FROM `fiches_client` ORDER BY `date_creation` DESC");
            $stmt->execute();
            $res = $stmt->get_result();
            $list = [];
            while ($row = $res->fetch_assoc()) {
                $list[] = format_fiche_row($row);
            }
            echo json_encode($list);
            break;

        // --------------------------------------------------------------------
        // 2. POST : Enregistrement d'une nouvelle fiche client
        // --------------------------------------------------------------------
        case 'POST':
            $action = $input['action'] ?? $_GET['action'] ?? '';

            // Modification rapide de statut
            if ($action === 'status') {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                $statut = trim((string)($input['status'] ?? $input['statut'] ?? ''));
                if (!$id || empty($statut)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'ID et statut requis']);
                    exit;
                }
                $stmt = $conn->prepare("UPDATE `fiches_client` SET `statut` = ? WHERE `id` = ?");
                $stmt->bind_param("si", $statut, $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
                exit;
            }

            $nom       = trim($input['nom'] ?? '');
            $prenom    = trim($input['prenom'] ?? '');
            $tel       = trim($input['telephone'] ?? $input['phone'] ?? '');
            $email     = trim($input['email'] ?? '');
            $trans     = trim($input['transaction'] ?? 'Acheter');
            $typeBien  = trim($input['type_bien'] ?? $input['typeBien'] ?? 'Appartement');
            $idBien    = !empty($input['id_bien']) ? (int)$input['id_bien'] : null;
            $pieceId   = !empty($input['piece_identite']) ? (string)$input['piece_identite'] : null;
            $signature = !empty($input['signature']) ? (string)$input['signature'] : null;

            if (empty($nom) || empty($prenom) || empty($tel)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Le nom, prénom et numéro de téléphone sont requis']);
                exit;
            }

            $ref = 'FC-' . date('Ymd') . '-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("
                INSERT INTO `fiches_client` (
                    `reference`, `nom`, `prenom`, `telephone`, `email`,
                    `transaction`, `type_bien`, `id_bien`, `piece_identite`, `signature`, `statut`
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 'Nouveau'
                )
            ");
            $stmt->bind_param(
                "ssssssisss",
                $ref, $nom, $prenom, $tel, $email,
                $trans, $typeBien, $idBien, $pieceId, $signature
            );
            $stmt->execute();
            $newId = $conn->insert_id;

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $newId,
                'reference' => $ref,
                'message' => 'Fiche client enregistrée avec succès'
            ]);
            break;

        // --------------------------------------------------------------------
        // 3. DELETE : Suppression d'une fiche client
        // --------------------------------------------------------------------
        case 'DELETE':
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM `fiches_client` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Fiche client supprimée']);
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
