<?php
/**
 * ============================================================================
 * api/export.php - Exportation réelle Excel (.xlsx) & CSV UTF-8 depuis MySQL
 * ============================================================================
 * Supporte Biens immobiliers, Demandes, Fiches client et Utilisateurs.
 * Permet d'exporter soit l'ensemble filtré, soit uniquement les éléments sélectionnés (ids).
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/helpers/XlsxHandler.php';

use Immolode\Helpers\XlsxHandler;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?: [];

$modelRaw = (string)($jsonInput['model'] ?? $_POST['model'] ?? $_GET['model'] ?? 'properties');
$formatRaw = strtolower(trim((string)($jsonInput['format'] ?? $_POST['format'] ?? $_GET['format'] ?? 'xlsx')));
$format = ($formatRaw === 'csv') ? 'csv' : 'xlsx';

$idsRaw = $jsonInput['ids'] ?? $_POST['ids'] ?? $_GET['ids'] ?? [];
if (is_string($idsRaw)) {
    $idsRaw = array_filter(array_map('trim', explode(',', $idsRaw)), fn($v) => $v !== '');
}
$selectedIds = array_values(array_unique(array_filter(array_map('intval', (array)$idsRaw), fn($id) => $id > 0)));

$model = match (strtolower(trim($modelRaw))) {
    'biens', 'properties' => 'properties',
    'demandes', 'contacts' => 'demandes',
    'fiches', 'fiches_client', 'fiches-client' => 'fiches_client',
    'users', 'utilisateurs' => 'users',
    default => 'properties'
};

$headers = [];
$rows = [];
$sheetTitle = 'Export';
$filePrefix = 'export';

try {
    if ($model === 'properties') {
        $sheetTitle = 'Biens immobiliers';
        $filePrefix = 'biens_immolode';
        $headers = ['ID', 'Référence', 'Titre', 'Type', 'Transaction', 'Prix (DA)', 'Période', 'Wilaya', 'Commune', 'Quartier', 'Surface (m²)', 'Chambres', 'Salles de bain', 'Statut', 'Date création'];

        $where = ["1=1"];
        $types = '';
        $params = [];

        if (!empty($selectedIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $where[] = "`id` IN ($placeholders)";
            $types .= str_repeat('i', count($selectedIds));
            array_push($params, ...$selectedIds);
        } else {
            $tx = trim((string)($jsonInput['transaction'] ?? $_GET['transaction'] ?? ''));
            $tp = trim((string)($jsonInput['type'] ?? $_GET['type'] ?? ''));
            $st = trim((string)($jsonInput['status'] ?? $_GET['status'] ?? ''));
            $sr = trim((string)($jsonInput['search'] ?? $_GET['search'] ?? ''));

            if ($tx !== '') { $where[] = "`transaction` = ?"; $types .= 's'; $params[] = $tx; }
            if ($tp !== '') { $where[] = "`type` = ?"; $types .= 's'; $params[] = $tp; }
            if ($st !== '') { $where[] = "`statut` = ?"; $types .= 's'; $params[] = $st; }
            if ($sr !== '') {
                $like = "%$sr%";
                $where[] = "(`titre` LIKE ? OR `reference` LIKE ? OR `commune` LIKE ? OR `wilaya` LIKE ?)";
                $types .= 'ssss';
                array_push($params, $like, $like, $like, $like);
            }
        }

        $sort = (string)($jsonInput['sort'] ?? $_GET['sort'] ?? 'recent');
        $order = match ($sort) {
            'price-asc' => 'ORDER BY `prix` ASC',
            'price-desc' => 'ORDER BY `prix` DESC',
            'surface-asc' => 'ORDER BY `surface` ASC',
            'surface-desc' => 'ORDER BY `surface` DESC',
            default => 'ORDER BY `date_creation` DESC, `id` DESC'
        };

        $sql = "SELECT * FROM `properties` WHERE " . implode(' AND ', $where) . " $order";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                $r['id'],
                $r['reference'],
                $r['titre'],
                $r['type'],
                $r['transaction'],
                $r['prix'],
                $r['periode_prix'] ?? '',
                $r['wilaya'],
                $r['commune'],
                $r['quartier'] ?? '',
                $r['surface'],
                $r['chambres'] ?? '',
                $r['salles_de_bain'] ?? '',
                $r['statut'],
                $r['date_creation']
            ];
        }
    } elseif ($model === 'demandes') {
        $sheetTitle = 'Demandes';
        $filePrefix = 'demandes_immolode';
        $headers = ['ID', 'Type', 'Client', 'Email', 'Téléphone', 'Sujet', 'Message', 'Statut', 'Lu', 'Date création'];

        $where = ["1=1"];
        $types = '';
        $params = [];

        if (!empty($selectedIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $where[] = "`id` IN ($placeholders)";
            $types .= str_repeat('i', count($selectedIds));
            array_push($params, ...$selectedIds);
        } else {
            $tp = trim((string)($jsonInput['type'] ?? $_GET['type'] ?? ''));
            $st = trim((string)($jsonInput['status'] ?? $_GET['status'] ?? ''));
            $sr = trim((string)($jsonInput['search'] ?? $_GET['search'] ?? ''));

            if ($tp !== '' && $tp !== 'Fiche client') { $where[] = "`type` = ?"; $types .= 's'; $params[] = $tp; }
            if ($st !== '') { $where[] = "`statut` = ?"; $types .= 's'; $params[] = $st; }
            if ($sr !== '') {
                $like = "%$sr%";
                $where[] = "(`nom` LIKE ? OR `email` LIKE ? OR `telephone` LIKE ? OR `sujet` LIKE ?)";
                $types .= 'ssss';
                array_push($params, $like, $like, $like, $like);
            }
        }

        $sql = "SELECT * FROM `demandes` WHERE " . implode(' AND ', $where) . " ORDER BY `date_creation` DESC, `id` DESC";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                $r['id'],
                $r['type'],
                $r['nom'],
                $r['email'],
                $r['telephone'],
                $r['sujet'] ?? '',
                $r['message'] ?? '',
                $r['statut'],
                !empty($r['is_read']) ? 'Oui' : 'Non',
                $r['date_creation']
            ];
        }
    } elseif ($model === 'fiches_client') {
        $sheetTitle = 'Fiches client';
        $filePrefix = 'fiches_client_immolode';
        $headers = ['ID', 'Référence', 'Nom', 'Prénom', 'Téléphone', 'Email', 'Opération', 'Type de bien', 'Statut', 'Lu', 'Date création'];

        $where = ["1=1"];
        $types = '';
        $params = [];

        if (!empty($selectedIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $where[] = "`id` IN ($placeholders)";
            $types .= str_repeat('i', count($selectedIds));
            array_push($params, ...$selectedIds);
        } else {
            $tx = trim((string)($jsonInput['transaction'] ?? $_GET['transaction'] ?? ''));
            $tb = trim((string)($jsonInput['typeBien'] ?? $_GET['typeBien'] ?? ''));
            $st = trim((string)($jsonInput['status'] ?? $_GET['status'] ?? ''));
            $sr = trim((string)($jsonInput['search'] ?? $_GET['search'] ?? ''));

            if ($tx !== '') { $where[] = "`transaction` = ?"; $types .= 's'; $params[] = $tx; }
            if ($tb !== '') { $where[] = "`type_bien` = ?"; $types .= 's'; $params[] = $tb; }
            if ($st !== '') { $where[] = "`statut` = ?"; $types .= 's'; $params[] = $st; }
            if ($sr !== '') {
                $like = "%$sr%";
                $where[] = "(`reference` LIKE ? OR `nom` LIKE ? OR `prenom` LIKE ? OR `telephone` LIKE ? OR `email` LIKE ?)";
                $types .= 'sssss';
                array_push($params, $like, $like, $like, $like, $like);
            }
        }

        $sql = "SELECT * FROM `fiches_client` WHERE " . implode(' AND ', $where) . " ORDER BY `date_creation` DESC, `id` DESC";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                $r['id'],
                $r['reference'],
                $r['nom'],
                $r['prenom'],
                $r['telephone'],
                $r['email'],
                $r['transaction'],
                $r['type_bien'],
                $r['statut'],
                !empty($r['is_read']) ? 'Oui' : 'Non',
                $r['date_creation']
            ];
        }
    } elseif ($model === 'users') {
        $sheetTitle = 'Utilisateurs';
        $filePrefix = 'membres_immolode';
        $headers = ['ID', 'Nom', 'Poste', 'Email', 'Téléphone', 'Rôle', 'Actif'];

        $where = ["1=1"];
        $types = '';
        $params = [];

        if (!empty($selectedIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $where[] = "`id` IN ($placeholders)";
            $types .= str_repeat('i', count($selectedIds));
            array_push($params, ...$selectedIds);
        }

        $sql = "SELECT `id`, `nom`, `poste`, `email`, `telephone`, `role`, `actif` FROM `utilisateurs` WHERE " . implode(' AND ', $where) . " ORDER BY `id` ASC";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                $r['id'],
                $r['nom'],
                $r['poste'] ?? '',
                $r['email'],
                $r['telephone'] ?? '',
                $r['role'] ?? '',
                !empty($r['actif']) ? 'Oui' : 'Non'
            ];
        }
    }

    $dateSuffix = date('Y-m-d');
    if ($format === 'csv') {
        $content = XlsxHandler::buildCsvString($headers, $rows);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filePrefix . '_' . $dateSuffix . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $content;
    } else {
        $binary = XlsxHandler::buildXlsxBinary($headers, $rows, $sheetTitle);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filePrefix . '_' . $dateSuffix . '.xlsx"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $binary;
    }
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
