<?php
/**
 * ============================================================================
 * api/import.php - Importation CSV & Excel (.xlsx / .xls) réelle via MySQLi
 * ============================================================================
 * Valide les en-têtes, types de données, vérifie la vraie signature binaire .xlsx,
 * détecte les doublons et renvoie un rapport précis (importés et erreurs).
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/helpers/XlsxHandler.php';

use Immolode\Helpers\XlsxHandler;

function normalize_import_model(string $m): string {
    $m = strtolower(trim($m));
    return match ($m) {
        'biens', 'properties' => 'properties',
        'demandes', 'contacts' => 'demandes',
        'utilisateurs', 'users' => 'users',
        'fiches', 'fiches_client', 'fiches-client' => 'fiches_client',
        default => $m,
    };
}

/**
 * Normalise un nom de colonne (enlève accents, BOM, unités entre parenthèses).
 */
function normalize_col_header(string $h): string {
    $h = preg_replace('/[\x{FEFF}]/u', '', $h);
    $h = trim(mb_strtolower($h, 'UTF-8'));
    $h = preg_replace('/\s*\([^)]*\)\s*/', '', $h); // ex: "Prix (DA)" -> "prix"
    $translit = [
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'î' => 'i', 'ï' => 'i',
        'ô' => 'o', 'ö' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', '’' => ' ', '\'' => ' '
    ];
    $h = strtr($h, $translit);
    $h = preg_replace('/[^a-z0-9]+/', '_', trim($h));
    return trim($h, '_');
}

function get_template_definition(string $model): array {
    return match ($model) {
        'properties' => [
            'headers' => ['titre', 'type', 'transaction', 'prix', 'wilaya', 'commune', 'quartier', 'surface', 'chambres', 'salles_de_bain', 'description'],
            'rows' => [
                ['Superbe Villa vue mer', 'Villa', 'Acheter', '45000000', 'Béjaïa', 'Tichy', 'Centre-ville', '250', '4', '2', 'Magnifique villa contemporaine avec jardin et vue panoramique.'],
                ['Appartement F4 moderne', 'Appartement', 'Louer', '85000', 'Alger', 'Hydra', 'Paradou', '120', '3', '1', 'Bel appartement calme et meublé dans quartier sécurisé.']
            ]
        ],
        'demandes' => [
            'headers' => ['type', 'nom', 'email', 'telephone', 'sujet', 'message', 'statut'],
            'rows' => [
                ['Contact', 'Salim Haddad', 'salim.haddad@gmail.com', '0550112233', 'Demande de visite appartement F4', 'Souhaite visiter le bien cette semaine.', 'Nouveau'],
                ['Estimation', 'Kamel Ouali', 'kamel.ouali@yahoo.fr', '0661445566', 'Estimation villa à Béjaïa', 'Villa R+2 de 240 m² à Tichy.', 'En cours']
            ]
        ],
        'users' => [
            'headers' => ['nom', 'email', 'role', 'poste', 'telephone', 'mot_de_passe'],
            'rows' => [
                ['Karim Benali', 'karim.benali@immolode.dz', 'Agent commercial', 'Conseiller senior', '0550123456', 'agent123'],
                ['Amel Dahmani', 'amel.dahmani@immolode.dz', 'Agent commercial', 'Conseillère location', '0661223344', 'agent123']
            ]
        ],
        'fiches_client' => [
            'headers' => ['nom', 'prenom', 'telephone', 'email', 'transaction', 'type_bien'],
            'rows' => [
                ['Mansouri', 'Nadir', '0555123456', 'nadir.mansouri@gmail.com', 'Demande', 'Appartement'],
                ['Brahimi', 'Sarah', '0770987654', 'sarah.brahimi@yahoo.fr', 'Offre', 'Villa']
            ]
        ],
        default => ['headers' => [], 'rows' => []]
    };
}

// ----------------------------------------------------------------------------
// Téléchargement de modèle (CSV ou véritable Excel .xlsx)
// ----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    $model = normalize_import_model($_GET['model'] ?? 'properties');
    $format = strtolower((string)($_GET['format'] ?? 'csv'));
    $tpl = get_template_definition($model);

    if (empty($tpl['headers'])) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => "Modèle inconnu: $model"]);
        exit;
    }

    if ($format === 'xlsx') {
        $binary = XlsxHandler::buildXlsxBinary($tpl['headers'], $tpl['rows'], 'Modele ' . $model);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="modele_' . $model . '.xlsx"');
        header('Content-Length: ' . strlen($binary));
        echo $binary;
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele_' . $model . '.csv"');
    echo XlsxHandler::buildCsvString($tpl['headers'], $tpl['rows']);
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
$parsedRows = [];

try {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['file']['tmp_name'];
        $origName = (string)($_FILES['file']['name'] ?? 'import.csv');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $rawContent = file_get_contents($tmpPath);

        if ($rawContent === false || strlen($rawContent) === 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Le fichier importé est vide.']);
            exit;
        }

        $isZipMagic = str_starts_with($rawContent, "PK\x03\x04");
        $isOle2Magic = str_starts_with($rawContent, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1");

        if ($ext === 'xlsx') {
            if (!$isZipMagic) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'error' => 'Le fichier porte l\'extension .xlsx mais ne correspond pas à un véritable classeur Excel (.xlsx valide). S\'il s\'agit d\'un CSV renommé, enregistrez-le au format .csv ou exportez un véritable classeur .xlsx.'
                ]);
                exit;
            }
            $parsedRows = XlsxHandler::parseXlsx($tmpPath);
        } elseif ($ext === 'xls') {
            if ($isZipMagic) {
                $parsedRows = XlsxHandler::parseXlsx($tmpPath);
            } elseif ($isOle2Magic) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'error' => 'Le format binaire hérité Excel 97-2003 (.xls) nécessite une conversion préalable en .xlsx ou .csv UTF-8.'
                ]);
                exit;
            } else {
                $parsedRows = XlsxHandler::parseXmlOrHtmlSpreadsheet($rawContent);
                if (empty($parsedRows)) {
                    http_response_code(422);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Fichier .xls illisible ou format invalide. Utilisez un véritable fichier .xlsx ou .csv.'
                    ]);
                    exit;
                }
            }
        } elseif ($ext === 'csv' || $ext === 'txt') {
            if ($isZipMagic || $isOle2Magic) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'error' => 'Le fichier binaire Excel ne peut pas être importé avec l\'extension .csv. Conservez son extension .xlsx.'
                ]);
                exit;
            }
            $parsedRows = XlsxHandler::parseCsv($rawContent);
        } else {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => "Extension non prise en charge (.$ext). Utilisez .xlsx, .xls ou .csv."]);
            exit;
        }
    } else {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (!empty($input['rows']) && is_array($input['rows'])) {
            $parsedRows = $input['rows'];
        } elseif (!empty($input['csv'])) {
            $parsedRows = XlsxHandler::parseCsv((string)$input['csv']);
        }
    }

    if (count($parsedRows) < 2) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Le fichier est vide ou ne contient aucune ligne de données après les en-têtes.']);
        exit;
    }

    $rawHeaders = array_shift($parsedRows);
    $headers = array_map(fn($h) => normalize_col_header((string)$h), $rawHeaders);

    $imported = 0;
    $errors = [];

    if ($model === 'properties') {
        $stmtCheckRef = $conn->prepare("SELECT `id` FROM `properties` WHERE `reference` = ? LIMIT 1");
        $stmtCheckDup = $conn->prepare("SELECT `id` FROM `properties` WHERE `titre` = ? AND `commune` = ? LIMIT 1");
        $stmtInsert = $conn->prepare("
            INSERT INTO `properties` (
                `reference`, `titre`, `type`, `transaction`, `prix`, `periode_prix`,
                `wilaya`, `commune`, `quartier`, `surface`, `chambres`,
                `salles_de_bain`, `description`, `images`, `statut`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '[]', ?)
        ");

        foreach ($parsedRows as $idx => $row) {
            $lineNum = $idx + 2;
            $data = [];
            foreach ($headers as $i => $h) {
                if ($h !== '') $data[$h] = trim((string)($row[$i] ?? ''));
            }

            $titre = $data['titre'] ?? $data['title'] ?? '';
            $type = ($data['type'] ?? '') !== '' ? $data['type'] : 'Appartement';
            $transRaw = strtolower($data['transaction'] ?? '');
            $trans = in_array($transRaw, ['louer', 'location'], true) ? 'Louer' : 'Acheter';
            $prixStr = str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $data['prix'] ?? $data['price'] ?? '0'));
            $prix = (float)$prixStr;
            $periode = ($data['periode_prix'] ?? $data['periode'] ?? '') ?: ($trans === 'Louer' ? 'mois' : null);
            $wilaya = ($data['wilaya'] ?? '') !== '' ? $data['wilaya'] : 'Béjaïa';
            $commune = ($data['commune'] ?? $data['city'] ?? '') !== '' ? ($data['commune'] ?? $data['city']) : $wilaya;
            $quartier = $data['quartier'] ?? $data['district'] ?? '';
            $surfaceStr = str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $data['surface'] ?? '0'));
            $surface = (float)$surfaceStr;
            if ($surface <= 0) $surface = 100.0;
            $chambres = ($data['chambres'] ?? $data['bedrooms'] ?? '') !== '' ? (int)$data['chambres'] : null;
            $sdb = ($data['salles_de_bain'] ?? $data['salles_de_bains'] ?? $data['bathrooms'] ?? '') !== ''
                ? (int)($data['salles_de_bain'] ?? $data['salles_de_bains'] ?? $data['bathrooms'])
                : null;
            $desc = ($data['description'] ?? '') !== '' ? $data['description'] : 'Bien importé via fichier.';
            $statut = ($data['statut'] ?? $data['status'] ?? '') !== '' ? ($data['statut'] ?? $data['status']) : 'Disponible';
            $refInput = $data['reference'] ?? $data['ref'] ?? '';

            if ($titre === '' || $prix <= 0) {
                $errors[] = "Ligne $lineNum : Titre ou prix invalide (titre=\"$titre\", prix=$prix).";
                continue;
            }

            if ($refInput !== '') {
                $stmtCheckRef->bind_param("s", $refInput);
                $stmtCheckRef->execute();
                if ($stmtCheckRef->get_result()->num_rows > 0) {
                    $errors[] = "Ligne $lineNum : La référence \"$refInput\" existe déjà (ignoré).";
                    continue;
                }
            }

            $stmtCheckDup->bind_param("ss", $titre, $commune);
            $stmtCheckDup->execute();
            if ($stmtCheckDup->get_result()->num_rows > 0) {
                $errors[] = "Ligne $lineNum : Le bien \"$titre\" à $commune existe déjà (ignoré).";
                continue;
            }

            $ref = $refInput !== '' ? $refInput : ('IML-' . str_pad((string)rand(100, 9999), 3, '0', STR_PAD_LEFT));
            $stmtInsert->bind_param(
                "ssssdssssdiiss",
                $ref, $titre, $type, $trans, $prix, $periode,
                $wilaya, $commune, $quartier, $surface, $chambres,
                $sdb, $desc, $statut
            );
            $stmtInsert->execute();
            $imported++;
        }
    } elseif ($model === 'demandes') {
        $stmtCheck = $conn->prepare("SELECT `id` FROM `demandes` WHERE `nom` = ? AND (`email` = ? OR `telephone` = ?) AND COALESCE(`sujet`, '') = ? LIMIT 1");
        $stmtInsert = $conn->prepare("
            INSERT INTO `demandes` (`type`, `nom`, `email`, `telephone`, `sujet`, `message`, `statut`, `is_read`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");

        foreach ($parsedRows as $idx => $row) {
            $lineNum = $idx + 2;
            $data = [];
            foreach ($headers as $i => $h) {
                if ($h !== '') $data[$h] = trim((string)($row[$i] ?? ''));
            }

            $typeRaw = strtolower($data['type'] ?? 'contact');
            $type = str_contains($typeRaw, 'estim') ? 'Estimation' : 'Contact';
            $nom = $data['nom'] ?? $data['client'] ?? '';
            $email = $data['email'] ?? '';
            $tel = $data['telephone'] ?? $data['phone'] ?? $data['tel'] ?? '';
            $sujet = ($data['sujet'] ?? $data['subject'] ?? '') !== '' ? ($data['sujet'] ?? $data['subject']) : 'Demande importée';
            $message = $data['message'] ?? '';
            $statut = ($data['statut'] ?? $data['status'] ?? '') !== '' ? ($data['statut'] ?? $data['status']) : 'Nouveau';

            if ($nom === '' || ($email === '' && $tel === '')) {
                $errors[] = "Ligne $lineNum : Nom et au moins un email ou téléphone obligatoires.";
                continue;
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ligne $lineNum : Adresse e-mail invalide ($email).";
                continue;
            }

            $stmtCheck->bind_param("ssss", $nom, $email, $tel, $sujet);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $errors[] = "Ligne $lineNum : Demande de \"$nom\" déjà présente (ignorée).";
                continue;
            }

            $stmtInsert->bind_param("sssssss", $type, $nom, $email, $tel, $sujet, $message, $statut);
            $stmtInsert->execute();
            $imported++;
        }
    } elseif ($model === 'users') {
        $stmtCheck = $conn->prepare("SELECT `id` FROM `utilisateurs` WHERE LOWER(`email`) = ? LIMIT 1");
        $stmtInsert = $conn->prepare("
            INSERT INTO `utilisateurs` (`nom`, `email`, `role`, `poste`, `telephone`, `mot_de_passe`, `actif`)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($parsedRows as $idx => $row) {
            $lineNum = $idx + 2;
            $data = [];
            foreach ($headers as $i => $h) {
                if ($h !== '') $data[$h] = trim((string)($row[$i] ?? ''));
            }

            $nom = $data['nom'] ?? $data['name'] ?? '';
            $email = strtolower($data['email'] ?? '');
            $roleRaw = strtolower($data['role'] ?? '');
            $role = in_array($roleRaw, ['administrateur', 'admin'], true) ? 'Administrateur'
                : ($roleRaw === 'gestionnaire' ? 'Gestionnaire' : 'Agent commercial');
            $poste = ($data['poste'] ?? '') !== '' ? $data['poste'] : 'Conseiller';
            $tel = $data['telephone'] ?? $data['phone'] ?? '';
            $pwd = ($data['mot_de_passe'] ?? $data['password'] ?? '') !== '' ? ($data['mot_de_passe'] ?? $data['password']) : 'agent123';

            if ($nom === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
        $stmtCheckRef = $conn->prepare("SELECT `id` FROM `fiches_client` WHERE `reference` = ? LIMIT 1");
        $stmtCheckDup = $conn->prepare("SELECT `id` FROM `fiches_client` WHERE `nom` = ? AND `telephone` = ? LIMIT 1");
        $stmtInsert = $conn->prepare("
            INSERT INTO `fiches_client` (
                `reference`, `nom`, `prenom`, `telephone`, `email`,
                `transaction`, `type_bien`, `statut`, `is_read`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");

        foreach ($parsedRows as $idx => $row) {
            $lineNum = $idx + 2;
            $data = [];
            foreach ($headers as $i => $h) {
                if ($h !== '') $data[$h] = trim((string)($row[$i] ?? ''));
            }

            $nom = $data['nom'] ?? '';
            $prenom = $data['prenom'] ?? '';
            $tel = $data['telephone'] ?? $data['phone'] ?? '';
            $email = $data['email'] ?? '';
            $transRaw = strtolower($data['transaction'] ?? $data['operation'] ?? '');
            $trans = in_array($transRaw, ['offre', 'mandat'], true) ? 'Offre' : 'Demande';
            $typeBien = ($data['type_bien'] ?? $data['typebien'] ?? $data['type_de_bien'] ?? '') ?: 'Appartement';
            $statut = ($data['statut'] ?? $data['status'] ?? '') ?: 'Nouveau';
            $refInput = $data['reference'] ?? '';

            if ($nom === '' || $tel === '') {
                $errors[] = "Ligne $lineNum : Nom et téléphone obligatoires.";
                continue;
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ligne $lineNum : Adresse e-mail invalide ($email).";
                continue;
            }

            if ($refInput !== '') {
                $stmtCheckRef->bind_param("s", $refInput);
                $stmtCheckRef->execute();
                if ($stmtCheckRef->get_result()->num_rows > 0) {
                    $errors[] = "Ligne $lineNum : La référence \"$refInput\" existe déjà (ignorée).";
                    continue;
                }
            }

            $stmtCheckDup->bind_param("ss", $nom, $tel);
            $stmtCheckDup->execute();
            if ($stmtCheckDup->get_result()->num_rows > 0) {
                $errors[] = "Ligne $lineNum : Fiche pour $nom ($tel) existe déjà.";
                continue;
            }

            $ref = $refInput !== '' ? $refInput : ('FC-' . date('Ymd') . '-' . str_pad((string)rand(100, 9999), 3, '0', STR_PAD_LEFT));
            $stmtInsert->bind_param("ssssssss", $ref, $nom, $prenom, $tel, $email, $trans, $typeBien, $statut);
            $stmtInsert->execute();
            $imported++;
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Modèle d'importation inconnu: $model"]);
        exit;
    }

    if ($imported === 0 && count($errors) > 0) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'imported' => 0,
            'errors' => $errors,
            'error' => "Aucune ligne n'a pu être importée (" . count($errors) . " erreur(s))."
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors,
        'message' => "$imported élément(s) importé(s) avec succès." . (count($errors) > 0 ? " " . count($errors) . " ligne(s) ignorée(s)." : "")
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
