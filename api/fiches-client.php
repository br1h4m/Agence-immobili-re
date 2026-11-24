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
require_once __DIR__ . '/../backend/config/Config.php';
require_once __DIR__ . '/../backend/helpers/Uploader.php';

use Immolode\Helpers\Uploader;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

function format_fiche_row(array $r): array {
    $pieceRaw = trim((string)($r['piece_identite'] ?? ''));
    $pieceObj = null;
    if ($pieceRaw !== '') {
        $isUrl = str_starts_with($pieceRaw, '/') || str_starts_with($pieceRaw, 'http://') || str_starts_with($pieceRaw, 'https://');
        $pieceUrl = $isUrl ? $pieceRaw : ('/uploads/documents/' . $pieceRaw);
        $pieceObj = [
            'name'    => basename($pieceRaw),
            'url'     => $pieceUrl,
            'dataUrl' => $pieceUrl
        ];
    }

    return array_merge($r, [
        'id'            => (int)$r['id'],
        'phone'         => $r['telephone'],
        'typeBien'      => $r['type_bien'],
        'idBien'        => $r['id_bien'],
        'pieceIdentite' => $pieceObj,
        'status'        => $r['statut'],
        'is_read'       => (int)($r['is_read'] ?? 0),
        'isRead'        => (bool)($r['is_read'] ?? 0),
        'createdAt'     => $r['date_creation']
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

            // Marquer comme lu
            if ($action === 'mark_read') {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                if (!$id) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'ID requis']);
                    exit;
                }
                $stmt = $conn->prepare("UPDATE `fiches_client` SET `is_read` = 1 WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Fiche client marquée comme lue']);
                exit;
            }

            // Marquer comme non lu
            if ($action === 'mark_unread') {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                if (!$id) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'ID requis']);
                    exit;
                }
                $stmt = $conn->prepare("UPDATE `fiches_client` SET `is_read` = 0 WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Fiche client marquée comme non lue']);
                exit;
            }

            // Marquer plusieurs comme lu / non lu
            if ($action === 'bulk_mark_read') {
                $ids = $input['ids'] ?? [];
                $isReadVal = isset($input['is_read']) ? (int)(bool)$input['is_read'] : 1;
                if (!is_array($ids) || empty($ids)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'Liste des IDs requise']);
                    exit;
                }
                $intIds = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
                if (empty($intIds)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'IDs invalides']);
                    exit;
                }
                $inClause = implode(',', $intIds);
                $conn->query("UPDATE `fiches_client` SET `is_read` = $isReadVal WHERE `id` IN ($inClause)");
                echo json_encode(['success' => true, 'message' => count($intIds) . ' fiche(s) mise(s) à jour']);
                exit;
            }

            // Suppression multiple
            if ($action === 'bulk_delete') {
                $ids = $input['ids'] ?? [];
                if (!is_array($ids) || empty($ids)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'Liste des IDs requise']);
                    exit;
                }
                $intIds = array_map('intval', $ids);
                $inClause = implode(',', $intIds);
                $conn->query("DELETE FROM `fiches_client` WHERE `id` IN ($inClause)");
                echo json_encode(['success' => true, 'message' => count($intIds) . ' fiche(s) supprimée(s)']);
                exit;
            }

            $nom       = trim($input['nom'] ?? '');
            $prenom    = trim($input['prenom'] ?? '');
            $tel       = trim($input['telephone'] ?? $input['phone'] ?? '');
            $email     = trim($input['email'] ?? '');
            $trans     = trim($input['transaction'] ?? 'Acheter');
            $typeBien  = trim($input['type_bien'] ?? $input['typeBien'] ?? 'Appartement');
            $idBien    = !empty($input['id_bien']) ? (int)$input['id_bien'] : null;
            $pieceRaw  = $input['piece_identite'] ?? $input['pieceIdentite'] ?? null;
            $pieceId   = null;
            if (is_array($pieceRaw)) {
                if (!empty($pieceRaw['dataUrl']) && str_starts_with((string)$pieceRaw['dataUrl'], 'data:')) {
                    $pieceId = Uploader::saveBase64Image((string)$pieceRaw['dataUrl'], 'documents', 'cni');
                } elseif (!empty($pieceRaw['url'])) {
                    $pieceId = (string)$pieceRaw['url'];
                } elseif (!empty($pieceRaw['name'])) {
                    $pieceId = basename((string)$pieceRaw['name']);
                }
            } elseif (!empty($pieceRaw)) {
                $pieceStr = (string)$pieceRaw;
                if (str_starts_with($pieceStr, 'data:')) {
                    $pieceId = Uploader::saveBase64Image($pieceStr, 'documents', 'cni');
                } else {
                    $pieceId = $pieceStr;
                }
            }

            $signatureRaw = !empty($input['signature']) ? (string)$input['signature'] : null;
            $signature = $signatureRaw;
            if ($signatureRaw && str_starts_with($signatureRaw, 'data:image/')) {
                $savedSig = Uploader::saveBase64Image($signatureRaw, 'signatures', 'sig');
                if ($savedSig) {
                    $signature = $savedSig;
                }
            }

            if (empty($nom) || empty($prenom) || empty($tel)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Le nom, prénom et numéro de téléphone sont requis']);
                exit;
            }

            $ref = !empty($input['reference']) ? trim((string)$input['reference']) : ('FC-' . date('Ymd') . '-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT));

            $stmt = $conn->prepare("
                INSERT INTO `fiches_client` (
                    `reference`, `nom`, `prenom`, `telephone`, `email`,
                    `transaction`, `type_bien`, `id_bien`, `piece_identite`, `signature`, `statut`, `is_read`
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 'Nouveau', 0
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
