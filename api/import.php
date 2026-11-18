<?php
/**
 * ============================================================================
 * api/import.php - Importation CSV / Excel de biens, membres et fiches client
 * ============================================================================
 * Requêtes préparées MySQLi (sans PDO).
 * Analyse les fichiers CSV encodés en UTF-8 ou Windows-1252 avec délimiteur ',' ou ';'.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Téléchargement de modèles CSV d'exemple
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'template') {
    $model = trim($_GET['model'] ?? 'properties');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_' . $model . '.csv"');

    $output = fopen('php://output', 'w');
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($model === 'properties' || $model === 'biens') {
        fputcsv($output, ['titre', 'type', 'transaction', 'prix', 'surface', 'wilaya', 'commune', 'quartier', 'chambres', 'description'], ';');
        fputcsv($output, ['Superbe Appartement F4 Vue Mer', 'Appartement', 'Acheter', '22000000', '120', 'Béjaïa', 'Béjaïa', 'Tichy', '3', 'Magnifique appartement avec vue dégagée.'], ';');
        fputcsv($output, ['Villa Contemporaine avec Piscine', 'Villa', 'Acheter', '65000000', '350', 'Alger', 'Alger', 'Hydra', '5', 'Villa haut standing dans un quartier calme et sécurisé.'], ';');
    } elseif ($model === 'users' || $model === 'utilisateurs') {
        fputcsv($output, ['nom', 'email', 'role', 'poste', 'telephone'], ';');
        fputcsv($output, ['Karim Amrani', 'k.amrani@immolode.dz', 'Agent commercial', 'Conseiller immobilier', '0550123456'], ';');
        fputcsv($output, ['Sarah Belkacem', 's.belkacem@immolode.dz', 'Gestionnaire', 'Responsable des visites', '0550987654'], ';');
    } elseif ($model === 'fiches_client' || $model === 'fiches') {
        fputcsv($output, ['nom', 'prenom', 'telephone', 'email', 'transaction', 'type_bien'], ';');
        fputcsv($output, ['Meziane', 'Yacine', '0661234567', 'yacine.meziane@gmail.com', 'Acheter', 'Villa'], ';');
        fputcsv($output, ['Boudiaf', 'Amina', '0770987654', 'amina.boudiaf@yahoo.fr', 'Louer', 'Appartement'], ';');
    }

    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Veuillez sélectionner un fichier CSV ou Excel valide']);
    exit;
}

$type = trim($_POST['type'] ?? 'properties');
$filePath = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, ['csv', 'txt'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Format non supporté. Veuillez exporter votre fichier au format CSV (séparateur virgule ou point-virgule).']);
    exit;
}

$handle = fopen($filePath, 'r');
if (!$handle) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Impossible d\'ouvrir le fichier']);
    exit;
}

// Détection de l'encodage et suppression du BOM UTF-8
$firstLine = fgets($handle);
rewind($handle);

$delimiter = ',';
if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
    $delimiter = ';';
}

$header = fgetcsv($handle, 4096, $delimiter);
if (!$header) {
    fclose($handle);
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Fichier vide ou illisible']);
    exit;
}

// Nettoyage des clés d'en-tête (minuscules, sans BOM)
$cleanHeader = [];
foreach ($header as $col) {
    $col = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim((string)$col));
    $col = strtolower(trim((string)$col));
    $cleanHeader[] = $col;
}

$importedCount = 0;
$errors = [];

try {
    if ($type === 'properties' || $type === 'biens') {
        $stmt = $conn->prepare("
            INSERT INTO `properties` (
                `reference`, `titre`, `type`, `transaction`, `prix`, `surface`,
                `wilaya`, `commune`, `quartier`, `chambres`, `description`, `statut`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Disponible')
        ");

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if (empty(array_filter($row))) continue;
            $data = @array_combine($cleanHeader, $row);
            if (!$data) continue;

            $titre = trim($data['titre'] ?? $data['title'] ?? 'Bien importé');
            $typeBien = trim($data['type'] ?? 'Appartement');
            $trans = trim($data['transaction'] ?? 'Acheter');
            $prix = (float)($data['prix'] ?? $data['price'] ?? 0);
            $surface = (float)($data['surface'] ?? 0);
            $wilaya = trim($data['wilaya'] ?? 'Alger');
            $commune = trim($data['commune'] ?? $data['ville'] ?? '');
            $quartier = trim($data['quartier'] ?? '');
            $chambres = isset($data['chambres']) ? (int)$data['chambres'] : 3;
            $desc = trim($data['description'] ?? '');
            $ref = 'IML-IMP-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT) . '-' . time() % 1000;

            if ($prix > 0) {
                $stmt->bind_param(
                    'ssssddsssis',
                    $ref, $titre, $typeBien, $trans, $prix, $surface,
                    $wilaya, $commune, $quartier, $chambres, $desc
                );
                if ($stmt->execute()) {
                    $importedCount++;
                }
            }
        }
    } elseif ($type === 'users' || $type === 'utilisateurs') {
        $stmtCheck = $conn->prepare("SELECT `id` FROM `utilisateurs` WHERE LOWER(`email`) = ?");
        $stmtInsert = $conn->prepare("
            INSERT INTO `utilisateurs` (`nom`, `email`, `mot_de_passe`, `role`, `poste`, `telephone`, `actif`)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $defaultPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if (empty(array_filter($row))) continue;
            $data = @array_combine($cleanHeader, $row);
            if (!$data) continue;

            $nom = trim($data['nom'] ?? $data['name'] ?? '');
            $email = strtolower(trim($data['email'] ?? ''));
            $role = trim($data['role'] ?? 'Agent commercial');
            $poste = trim($data['poste'] ?? 'Conseiller');
            $tel = trim($data['telephone'] ?? $data['phone'] ?? '');

            if (!empty($nom) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmtCheck->bind_param('s', $email);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows === 0) {
                    $stmtInsert->bind_param('ssssss', $nom, $email, $defaultPasswordHash, $role, $poste, $tel);
                    if ($stmtInsert->execute()) {
                        $importedCount++;
                    }
                }
            }
        }
    } elseif ($type === 'fiches_client' || $type === 'fiches') {
        $stmt = $conn->prepare("
            INSERT INTO `fiches_client` (
                `reference`, `nom`, `prenom`, `telephone`, `email`,
                `transaction`, `type_bien`, `statut`, `is_read`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Nouveau', 0)
        ");

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if (empty(array_filter($row))) continue;
            $data = @array_combine($cleanHeader, $row);
            if (!$data) continue;

            $nom = trim($data['nom'] ?? '');
            $prenom = trim($data['prenom'] ?? '');
            $tel = trim($data['telephone'] ?? $data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $trans = trim($data['transaction'] ?? 'Acheter');
            $typeBien = trim($data['type_bien'] ?? $data['type'] ?? 'Appartement');
            $ref = 'FC-' . date('Ymd') . '-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);

            if (!empty($nom) && !empty($tel)) {
                $stmt->bind_param('sssssss', $ref, $nom, $prenom, $tel, $email, $trans, $typeBien);
                if ($stmt->execute()) {
                    $importedCount++;
                }
            }
        }
    }
} finally {
    fclose($handle);
}

echo json_encode([
    'success' => true,
    'count'   => $importedCount,
    'message' => "{$importedCount} élément(s) importé(s) avec succès !"
]);
