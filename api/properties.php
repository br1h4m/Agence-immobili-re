<?php
/**
 * ============================================================================
 * api/properties.php - API REST des biens immobiliers
 * ============================================================================
 * Traitement en PHP simple avec requêtes MySQL préparées (MySQLi sans PDO).
 * Permet de récupérer, ajouter, modifier ou supprimer des biens.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

// Entêtes CORS et format JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

/**
 * Fonction de formatage pour assurer la compatibilité ascendante
 * avec les composants JavaScript et les vues PHP
 */
function format_property_row(array $r): array {
    $images = json_decode($r['images'] ?? '[]', true) ?: [];
    $equip  = json_decode($r['equipements'] ?? '[]', true) ?: [];

    return array_merge($r, [
        'id'          => (int)$r['id'],
        'title'       => $r['titre'],
        'price'       => (float)$r['prix'],
        'pricePeriod' => $r['periode_prix'] ?? null,
        'surface'     => (float)$r['surface'],
        'landSurface' => !empty($r['surface_terrain']) ? (float)$r['surface_terrain'] : null,
        'bedrooms'    => isset($r['chambres']) ? (int)$r['chambres'] : null,
        'bathrooms'   => isset($r['salles_de_bain']) ? (int)$r['salles_de_bain'] : null,
        'floor'       => isset($r['etage']) ? (int)$r['etage'] : null,
        'floors'      => isset($r['etages']) ? (int)$r['etages'] : null,
        'year'        => isset($r['annee']) ? (int)$r['annee'] : null,
        'garage'      => !empty($r['garage']),
        'status'      => $r['statut'],
        'isFeatured'  => !empty($r['a_la_une']),
        'isNew'       => !empty($r['nouveau']),
        'images'      => $images,
        'features'    => $equip,
        'location'    => [
            'wilaya'   => $r['wilaya'],
            'city'     => $r['commune'],
            'district' => $r['quartier'] ?? '',
            'address'  => implode(', ', array_filter([$r['quartier'] ?? '', $r['commune'], $r['wilaya']]))
        ],
        'createdAt'   => $r['date_creation']
    ]);
}

try {
    switch ($method) {
        // --------------------------------------------------------------------
        // 1. GET : Consultation des biens
        // --------------------------------------------------------------------
        case 'GET':
            // Consultation d'un bien par son identifiant ID
            if (!empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $conn->prepare("SELECT * FROM `properties` WHERE `id` = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();

                if (!$item) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Bien introuvable']);
                    exit;
                }
                echo json_encode(format_property_row($item));
                exit;
            }

            // Consultation d'un bien par sa référence (ex: IML-001)
            if (!empty($_GET['reference'])) {
                $ref = trim($_GET['reference']);
                $stmt = $conn->prepare("SELECT * FROM `properties` WHERE `reference` = ?");
                $stmt->bind_param("s", $ref);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();

                if (!$item) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Bien introuvable']);
                    exit;
                }
                echo json_encode(format_property_row($item));
                exit;
            }

            // Options pour les filtres (Wilayas, villes, types)
            if (isset($_GET['options'])) {
                $wRes = $conn->query("SELECT DISTINCT `wilaya` FROM `properties` WHERE `wilaya` IS NOT NULL AND `wilaya` != '' ORDER BY `wilaya` ASC");
                $cRes = $conn->query("SELECT DISTINCT `commune` FROM `properties` WHERE `commune` IS NOT NULL AND `commune` != '' ORDER BY `commune` ASC");
                $tRes = $conn->query("SELECT DISTINCT `type` FROM `properties` WHERE `type` IS NOT NULL AND `type` != '' ORDER BY `type` ASC");

                $wilayas = [];
                while ($w = $wRes->fetch_assoc()) { $wilayas[] = $w['wilaya']; }
                $cities = [];
                while ($c = $cRes->fetch_assoc()) { $cities[] = $c['commune']; }
                $types = [];
                while ($t = $tRes->fetch_assoc()) { $types[] = $t['type']; }

                echo json_encode(['wilayas' => $wilayas, 'cities' => $cities, 'types' => $types]);
                exit;
            }

            // Liste avec filtres optionnels
            $conditions = ["1=1"];
            $types = "";
            $params = [];

            if (!empty($_GET['transaction'])) {
                $conditions[] = "`transaction` = ?";
                $types .= "s";
                $params[] = $_GET['transaction'];
            }
            if (!empty($_GET['type'])) {
                $conditions[] = "`type` = ?";
                $types .= "s";
                $params[] = $_GET['type'];
            }
            if (!empty($_GET['wilaya'])) {
                $conditions[] = "`wilaya` = ?";
                $types .= "s";
                $params[] = $_GET['wilaya'];
            }
            if (!empty($_GET['statut'])) {
                $conditions[] = "`statut` = ?";
                $types .= "s";
                $params[] = $_GET['statut'];
            }
            if (!empty($_GET['a_la_une'])) {
                $conditions[] = "`a_la_une` = 1";
            }

            $sql = "SELECT * FROM `properties` WHERE " . implode(' AND ', $conditions) . " ORDER BY `date_creation` DESC";
            if (!empty($_GET['limit'])) {
                $sql .= " LIMIT " . (int)$_GET['limit'];
            }

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $list = [];
            while ($r = $res->fetch_assoc()) {
                $list[] = format_property_row($r);
            }
            echo json_encode($list);
            break;

        // --------------------------------------------------------------------
        // 2. POST : Ajout d'un bien ou mise à jour
        // --------------------------------------------------------------------
        case 'POST':
            $action = $input['action'] ?? $_GET['action'] ?? '';

            // Modification rapide de statut
            if ($action === 'status') {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                $status = trim((string)($input['status'] ?? $input['statut'] ?? ''));
                if (!$id || empty($status)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'ID et statut requis']);
                    exit;
                }
                $stmt = $conn->prepare("UPDATE `properties` SET `statut` = ? WHERE `id` = ?");
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
                exit;
            }

            // Génération d'une référence automatique si absente
            $ref = trim($input['reference'] ?? '');
            if (empty($ref)) {
                $ref = 'IML-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);
            }

            $titre     = trim($input['titre'] ?? $input['title'] ?? '');
            $type      = trim($input['type'] ?? 'Appartement');
            $trans     = trim($input['transaction'] ?? 'Acheter');
            $prix      = (float)($input['prix'] ?? $input['price'] ?? 0);
            $periode   = !empty($input['periode_prix']) ? trim($input['periode_prix']) : null;
            $wilaya    = trim($input['wilaya'] ?? '');
            $commune   = trim($input['commune'] ?? $input['city'] ?? '');
            $quartier  = trim($input['quartier'] ?? '');
            $surface   = (float)($input['surface'] ?? 0);
            $surfTer   = !empty($input['surface_terrain']) ? (float)$input['surface_terrain'] : null;
            $chambres  = isset($input['chambres']) ? (int)$input['chambres'] : null;
            $sdb       = isset($input['salles_de_bain']) ? (int)$input['salles_de_bain'] : null;
            $etage     = isset($input['etage']) ? (int)$input['etage'] : null;
            $etages    = isset($input['etages']) ? (int)$input['etages'] : null;
            $annee     = isset($input['annee']) ? (int)$input['annee'] : null;
            $garage    = !empty($input['garage']) ? 1 : 0;
            // Gestion des alias d'objets (location, features, etc.)
            if (isset($input['location']) && is_array($input['location'])) {
                if (empty($wilaya) && !empty($input['location']['wilaya'])) $wilaya = trim($input['location']['wilaya']);
                if (empty($commune) && !empty($input['location']['city'])) $commune = trim($input['location']['city']);
                if (empty($quartier) && !empty($input['location']['district'])) $quartier = trim($input['location']['district']);
            }

            $equipRaw = $input['equipements'] ?? $input['features'] ?? null;
            $equip = is_array($equipRaw) ? json_encode($equipRaw, JSON_UNESCAPED_UNICODE) : (string)($equipRaw ?? '[]');

            $aLaUne  = (!empty($input['a_la_une']) || !empty($input['isFeatured'])) ? 1 : 0;
            $nouveau = (!empty($input['nouveau']) || !empty($input['isNew'])) ? 1 : 0;

            if (empty($titre) || $prix <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Le titre et un prix valide sont obligatoires']);
                exit;
            }

            // Si c'est une modification d'un bien existant (ID fourni)
            $existingId = (int)($input['id'] ?? 0);
            if ($action === 'update' || $existingId > 0) {
                $stmt = $conn->prepare("
                    UPDATE `properties` SET
                        `titre` = ?, `type` = ?, `transaction` = ?, `prix` = ?, `periode_prix` = ?,
                        `wilaya` = ?, `commune` = ?, `quartier` = ?, `surface` = ?, `surface_terrain` = ?,
                        `chambres` = ?, `salles_de_bain` = ?, `etage` = ?, `etages` = ?, `annee` = ?, `garage` = ?,
                        `equipements` = ?, `images` = ?, `description` = ?, `statut` = ?, `a_la_une` = ?, `nouveau` = ?
                    WHERE `id` = ?
                ");
                $stmt->bind_param(
                    'sssdssssddiiiiiissssiii',
                    $titre, $type, $trans, $prix, $periode,
                    $wilaya, $commune, $quartier, $surface, $surfTer,
                    $chambres, $sdb, $etage, $etages, $annee, $garage,
                    $equip, $imgs, $desc, $statut, $aLaUne, $nouveau,
                    $existingId
                );
                $stmt->execute();

                echo json_encode([
                    'success' => true,
                    'id' => $existingId,
                    'message' => 'Bien mis à jour avec succès'
                ]);
                exit;
            }

            // Insertion préparée MySQLi pour un nouveau bien
            $stmt = $conn->prepare("
                INSERT INTO `properties` (
                    `reference`, `titre`, `type`, `transaction`, `prix`, `periode_prix`,
                    `wilaya`, `commune`, `quartier`, `surface`, `surface_terrain`,
                    `chambres`, `salles_de_bain`, `etage`, `etages`, `annee`, `garage`,
                    `equipements`, `images`, `description`, `statut`, `a_la_une`, `nouveau`
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?
                )
            ");
            $stmt->bind_param(
                'ssssdssssddiiiiiissssii',
                $ref, $titre, $type, $trans, $prix, $periode,
                $wilaya, $commune, $quartier, $surface, $surfTer,
                $chambres, $sdb, $etage, $etages, $annee, $garage,
                $equip, $imgs, $desc, $statut, $aLaUne, $nouveau
            );
            $stmt->execute();
            $newId = $conn->insert_id;

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $newId,
                'message' => 'Bien créé avec succès',
                'reference' => $ref
            ]);
            break;

        // --------------------------------------------------------------------
        // 3. DELETE : Suppression d'un bien
        // --------------------------------------------------------------------
        case 'DELETE':
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM `properties` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Bien supprimé avec succès']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode HTTP non autorisée']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
