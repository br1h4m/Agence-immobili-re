<?php
/**
 * ============================================================================
 * api/demandes.php - API de gestion des demandes (Contact, Estimation, etc.)
 * ============================================================================
 * Traitement en PHP simple avec requêtes MySQL préparées (MySQLi sans PDO).
 * Répond au format JSON pour les requêtes AJAX et les formulaires du site.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

// Entêtes CORS et type de contenu JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Récupération des données brutes (JSON ou formulaire x-www-form-urlencoded)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

function format_demande_row(array $r): array {
    $details = json_decode($r['details_bien'] ?? '{}', true) ?: [];
    return array_merge($r, [
        'id'        => (int)$r['id'],
        'status'    => $r['statut'],
        'phone'     => $r['telephone'],
        'subject'   => $r['sujet'],
        'details'   => $details,
        'is_read'   => (int)($r['is_read'] ?? 0),
        'isRead'    => (bool)($r['is_read'] ?? 0),
        'createdAt' => $r['date_creation']
    ]);
}

try {
    switch ($method) {
        // --------------------------------------------------------------------
        // 1. GET : Récupération d'une ou plusieurs demandes
        // --------------------------------------------------------------------
        case 'GET':
            if (!empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $conn->prepare("SELECT * FROM `demandes` WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $demande = $res->fetch_assoc();

                if (!$demande) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Demande introuvable']);
                    exit;
                }
                echo json_encode(format_demande_row($demande));
            } else {
                $statut = trim($_GET['statut'] ?? '');
                if ($statut !== '') {
                    $stmt = $conn->prepare("SELECT * FROM `demandes` WHERE `statut` = ? ORDER BY `date_creation` DESC");
                    $stmt->bind_param("s", $statut);
                } else {
                    $stmt = $conn->prepare("SELECT * FROM `demandes` ORDER BY `date_creation` DESC");
                }
                $stmt->execute();
                $res = $stmt->get_result();
                $list = [];
                while ($row = $res->fetch_assoc()) {
                    $list[] = format_demande_row($row);
                }
                echo json_encode($list);
            }
            break;

        // --------------------------------------------------------------------
        // 2. POST : Enregistrement d'une nouvelle demande (Contact / Estimation)
        // --------------------------------------------------------------------
        case 'POST':
            $action = $input['action'] ?? $_GET['action'] ?? '';

            // Modification rapide de statut
            if ($action === 'status') {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                $nouveauStatut = trim((string)($input['status'] ?? $input['statut'] ?? ''));
                if (!$id || empty($nouveauStatut)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'ID et statut requis']);
                    exit;
                }
                $stmt = $conn->prepare("UPDATE `demandes` SET `statut` = ? WHERE `id` = ?");
                $stmt->bind_param("si", $nouveauStatut, $id);
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
                $stmt = $conn->prepare("UPDATE `demandes` SET `is_read` = 1 WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Demande marquée comme lue']);
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
                $stmt = $conn->prepare("UPDATE `demandes` SET `is_read` = 0 WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Demande marquée comme non lue']);
                exit;
            }

            // Marquer plusieurs comme lu
            if ($action === 'bulk_mark_read') {
                $ids = $input['ids'] ?? [];
                if (!is_array($ids) || empty($ids)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'Liste des IDs requise']);
                    exit;
                }
                $intIds = array_map('intval', $ids);
                $inClause = implode(',', $intIds);
                $conn->query("UPDATE `demandes` SET `is_read` = 1 WHERE `id` IN ($inClause)");
                echo json_encode(['success' => true, 'message' => count($intIds) . ' demande(s) marquée(s) comme lue(s)']);
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
                $conn->query("DELETE FROM `demandes` WHERE `id` IN ($inClause)");
                echo json_encode(['success' => true, 'message' => count($intIds) . ' demande(s) supprimée(s)']);
                exit;
            }

            // Création d'une nouvelle demande
            $type        = trim($input['type'] ?? 'Contact');
            $nom         = trim($input['nom'] ?? ($input['firstname'] ?? '') . ' ' . ($input['lastname'] ?? ''));
            if ($nom === '') {
                $nom = 'Visiteur';
            }
            $email       = trim($input['email'] ?? '');
            $tel         = trim($input['phone'] ?? $input['telephone'] ?? '');
            $sujet       = trim($input['subject'] ?? $input['sujet'] ?? 'Demande de contact');
            $message     = trim($input['message'] ?? '');
            $bien_id     = !empty($input['bien_id']) ? (int)$input['bien_id'] : null;
            $detailsRaw  = $input['details_bien'] ?? $input['details'] ?? null;
            $details_bien= $detailsRaw !== null ? (is_array($detailsRaw) ? json_encode($detailsRaw, JSON_UNESCAPED_UNICODE) : (string)$detailsRaw) : null;

            if (empty($email) && empty($tel)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Veuillez renseigner un email ou un téléphone.']);
                exit;
            }

            $stmt = $conn->prepare("
                INSERT INTO `demandes` (`type`, `nom`, `email`, `telephone`, `sujet`, `message`, `details_bien`, `bien_id`, `statut`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Nouveau')
            ");
            $stmt->bind_param("sssssssi", $type, $nom, $email, $tel, $sujet, $message, $details_bien, $bien_id);
            $stmt->execute();
            $newId = $conn->insert_id;

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $newId,
                'message' => 'Votre demande a bien été enregistrée. Nous vous contacterons rapidement.'
            ]);
            break;

        // --------------------------------------------------------------------
        // 3. PATCH : Mise à jour du statut
        // --------------------------------------------------------------------
        case 'PATCH':
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $statut = trim((string)($input['status'] ?? $input['statut'] ?? ''));

            if (!$id || empty($statut)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'ID et statut requis']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE `demandes` SET `statut` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $statut, $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
            break;

        // --------------------------------------------------------------------
        // 4. DELETE : Suppression d'une demande
        // --------------------------------------------------------------------
        case 'DELETE':
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM `demandes` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Demande supprimée']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode HTTP non supportée']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
