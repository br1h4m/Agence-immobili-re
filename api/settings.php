<?php
/**
 * ============================================================================
 * api/settings.php - API des paramètres généraux de l'agence Immolode
 * ============================================================================
 * Requêtes MySQL préparées avec MySQLi (sans PDO).
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        // --------------------------------------------------------------------
        // 1. GET : Récupération de l'ensemble des paramètres
        // --------------------------------------------------------------------
        case 'GET':
            $res = $conn->query("SELECT `cle`, `valeur` FROM `parametres`");
            $settings = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $settings[$row['cle']] = $row['valeur'];
                }
            }
            echo json_encode($settings);
            break;

        // --------------------------------------------------------------------
        // 2. POST : Enregistrement ou mise à jour des paramètres
        // --------------------------------------------------------------------
        case 'POST':
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?: $_POST;

            if (empty($input)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Aucune donnée fournie']);
                exit;
            }

            // Préparation de la requête d'insertion ou mise à jour
            $stmt = $conn->prepare("
                INSERT INTO `parametres` (`cle`, `valeur`)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`)
            ");

            foreach ($input as $cle => $valeur) {
                if (is_array($valeur) || is_object($valeur)) {
                    $valStr = json_encode($valeur, JSON_UNESCAPED_UNICODE);
                } else {
                    $valStr = (string)$valeur;
                }
                $cleStr = (string)$cle;
                $stmt->bind_param("ss", $cleStr, $valStr);
                $stmt->execute();
            }

            // Renvoi des paramètres à jour
            $res = $conn->query("SELECT `cle`, `valeur` FROM `parametres`");
            $updated = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $updated[$row['cle']] = $row['valeur'];
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès',
                'data' => $updated
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
