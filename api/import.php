<?php
/**
 * ============================================================================
 * api/import.php - Importation CSV / Excel pour Biens, Membres et Fiches Client
 * ============================================================================
 * Utilise MySQLi pur avec requêtes préparées (sans PDO).
 * Valide les en-têtes, types de données, détecte les doublons et renvoie
 * un rapport précis d'importation (lignes importées et erreurs).
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

// Normalisation des alias de modèles (ex: 'biens' -> 'properties', 'utilisateurs' -> 'users')
function normalize_import_model(string $m): string {
    $m = strtolower(trim($m));
    return match ($m) {
        'biens', 'properties' => 'properties',
        'utilisateurs', 'users' => 'users',
        'fiches', 'fiches_client', 'fiches-client' => 'fiches_client',
        default => $m,
    };
}

// Si demande de modèle type en téléchargement
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    $model = normalize_import_model($_GET['model'] ?? 'properties');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_' . $model . '.csv"');
    
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 pour Excel

    if ($model === 'properties') {
        fputcsv($out, ['titre', 'type', 'transaction', 'prix', 'wilaya', 'commune', 'quartier', 'surface', 'chambres', 'salles_de_bain', 'description']);
        fputcsv($out, ['Superbe Villa vue mer', 'Villa', 'Acheter', '45000000', 'Béjaïa', 'Tichy', 'Centre-ville', '250', '4', '2', 'Magnifique villa contemporaine avec jardin et vue panoramique.']);
        fputcsv($out, ['Appartement F4 moderne', 'Appartement', 'Louer', '85000', 'Alger', 'Hydra', 'Paradou', '120', '3', '1', 'Bel appartement calme et meublé dans quartier sécurisé.']);
    } elseif ($model === 'users') {
        fputcsv($out, ['nom', 'email', 'role', 'poste', 'telephone', 'mot_de_passe']);
        fputcsv($out, ['Karim Benali', 'karim.benali@immolode.dz', 'Agent', 'Conseiller senior', '0550123456', 'agent123']);
        fputcsv($out, ['Amel Dahmani', 'amel.dahmani@immolode.dz', 'Agent', 'Conseillère location', '0661223344', 'agent123']);
    } elseif ($model === 'fiches_client') {
        fputcsv($out, ['nom', 'prenom', 'telephone', 'email', 'transaction', 'type_bien']);
        fputcsv($out, ['Mansouri', 'Nadir', '0555123456', 'nadir.mansouri@gmail.com', 'Demande', 'Appartement']);
        fputcsv($out, ['Brahimi', 'Sarah', '0770987654', 'sarah.brahimi@yahoo.fr', 'Offre', 'Villa']);
    }
    fclose($out);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$model = normalize_import_model($_POST['model'] ?? $_GET['model'] ?? 'properties');
$csvContent = '';

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $csvContent = file_get_contents($_FILES['file']['tmp_name']);
} else {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $csvContent = (string)($input['csv'] ?? '');
}

if (empty($csvContent)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Aucun fichier ou contenu CSV transmis.']);
    exit;
}

// Découpage propre des lignes CSV
$lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
if (count($lines) < 2) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Le fichier CSV est vide ou ne contient que la ligne d\'en-tête.']);
    exit;
}

// Détection du délimiteur (; ou , ou \t)
$firstLine = $lines[0];
$delimiter = ',';
if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
    $delimiter = ';';
} elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
    $delimiter = "\t";
}

$headers = str_getcsv(array_shift($lines), $delimiter);
$headers = array_map(function($h) {
    return strtolower(trim(preg_replace('/[\x{FEFF}\x{EF}\x{BB}\x{BF}]/u', '', (string)$h)));
}, $headers);

$imported = 0;
$errors = [];

try {
    if ($model === 'properties') {
        $stmtCheck = $conn->prepare("SELECT `id` FROM `properties` WHERE `titre` = ? AND `commune` = ? LIMIT 1");
        $stmtInsert = $conn->prepare("
            INSERT INTO `properties` (
                `reference`, `titre`, `type`, `transaction`, `prix`,
                `wilaya`, `commune`, `quartier`, `surface`, `chambres`,
                `salles_de_bain`, `description`, `images`, `statut`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '[]', 'Disponible')
        ");

        foreach ($lines as $idx => $line) {
            $lineNum = $idx + 2;
            if (empty(trim($line))) continue;
            $row = str_getcsv($line, $delimiter);
            $data = [];
            foreach ($headers as $i => $h) {
                $data[$h] = trim((string)($row[$i] ?? ''));
            }

            $titre = $data['titre'] ?? $data['title'] ?? '';
            $type = $data['type'] ?? 'Appartement';
            $trans = in_array(strtolower($data['transaction'] ?? ''), ['louer', 'location']) ? 'Louer' : 'Acheter';
            $prix = (float)preg_replace('/[^0-9.]/', '', $data['prix'] ?? $data['price'] ?? '0');
            $wilaya = $data['wilaya'] ?? 'Béjaïa';
            $commune = $data['commune'] ?? $data['city'] ?? 'Béjaïa';
            $quartier = $data['quartier'] ?? $data['district'] ?? '';
            $surface = (float)preg_replace('/[^0-9.]/', '', $data['surface'] ?? '100');
            $chambres = !empty($data['chambres']) ? (int)$data['chambres'] : 3;
            $sdb = !empty($data['salles_de_bain']) ? (int)$data['salles_de_bain'] : 1;
            $desc = $data['description'] ?? 'Bien importé via fichier.';

            if (empty($titre) || $prix <= 0) {
                $errors[] = "Ligne $lineNum : Titre ou prix invalide.";
                continue;
            }

            // Vérification doublon
            $stmtCheck->bind_param("ss", $titre, $commune);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $errors[] = "Ligne $lineNum : Le bien \"$titre\" à $commune existe déjà (ignoré).";
                continue;
            }

            $ref = 'IML-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);
            $stmtInsert->bind_param(
                "ssssdsssddis",
                $ref, $titre, $type, $trans, $prix,
                $wilaya, $commune, $quartier, $surface, $chambres,
                $sdb, $desc
            );
            $stmtInsert->execute();
            $imported++;
        }
    } elseif ($model === 'users') {
        $stmtCheck = $conn->prepare("SELECT `id` FROM `utilisateurs` WHERE LOWER(`email`) = ? LIMIT 1");
        $stmtInsert = $conn->prepare("
            INSERT INTO `utilisateurs` (`nom`, `email`, `role`, `poste`, `telephone`, `mot_de_passe`, `actif`)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($lines as $idx => $line) {
            $lineNum = $idx + 2;
            if (empty(trim($line))) continue;
            $row = str_getcsv($line, $delimiter);
            $data = [];
            foreach ($headers as $i => $h) {
                $data[$h] = trim((string)($row[$i] ?? ''));
            }

            $nom = $data['nom'] ?? $data['name'] ?? '';
            $email = strtolower($data['email'] ?? '');
            $role = in_array(strtolower($data['role'] ?? ''), ['administrateur', 'admin']) ? 'Administrateur' : 'Agent commercial';
            $poste = $data['poste'] ?? 'Conseiller';
            $tel = $data['telephone'] ?? $data['phone'] ?? '';
            $pwd = !empty($data['mot_de_passe']) ? $data['mot_de_passe'] : 'agent123';

            if (empty($nom) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ligne $lineNum : Nom ou e-mail invalide ($email).";
                continue;
            }

            $stmtCheck->bind_param("s", $email);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $errors[] = "Ligne $lineNum : L'e-mail $email existe déjà.";
                continue;
            }

            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            $stmtInsert->bind_param("ssssss", $nom, $email, $role, $poste, $tel, $hash);
            $stmtInsert->execute();
            $imported++;
        }
    } elseif ($model === 'fiches_client') {
        $stmtCheck = $conn->prepare("SELECT `id` FROM `fiches_client` WHERE `nom` = ? AND `telephone` = ? LIMIT 1");
        $stmtInsert = $conn->prepare("
            INSERT INTO `fiches_client` (
                `reference`, `nom`, `prenom`, `telephone`, `email`,
                `transaction`, `type_bien`, `statut`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Nouveau')
        ");

        foreach ($lines as $idx => $line) {
            $lineNum = $idx + 2;
            if (empty(trim($line))) continue;
            $row = str_getcsv($line, $delimiter);
            $data = [];
            foreach ($headers as $i => $h) {
                $data[$h] = trim((string)($row[$i] ?? ''));
            }

            $nom = $data['nom'] ?? '';
            $prenom = $data['prenom'] ?? '';
            $tel = $data['telephone'] ?? $data['phone'] ?? '';
            $email = $data['email'] ?? '';
            $trans = in_array(strtolower($data['transaction'] ?? ''), ['offre', 'mandat']) ? 'Offre' : 'Demande';
            $typeBien = $data['type_bien'] ?? $data['typebien'] ?? 'Appartement';

            if (empty($nom) || empty($tel)) {
                $errors[] = "Ligne $lineNum : Nom et téléphone obligatoires.";
                continue;
            }

            $stmtCheck->bind_param("ss", $nom, $tel);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $errors[] = "Ligne $lineNum : Fiche pour $nom ($tel) existe déjà.";
                continue;
            }

            $ref = 'IML-DOS-' . date('Y') . '-' . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $stmtInsert->bind_param("sssssss", $ref, $nom, $prenom, $tel, $email, $trans, $typeBien);
            $stmtInsert->execute();
            $imported++;
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Modèle d'importation inconnu: $model"]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors,
        'message' => "$imported élément(s) importé(s) avec succès." . (count($errors) > 0 ? " " . count($errors) . " ligne(s) ignorée(s)." : "")
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
