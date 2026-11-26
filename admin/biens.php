<?php
/**
 * admin/biens.php - Gestion du catalogue des biens immobiliers (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/xlsx.php';

$currentUser = require_admin($conn);
$activePage = 'biens';
$pageHeading = 'Biens immobiliers';
$breadcrumbs = [['label' => 'Biens']];
$b = BASE_URL;
$topbarAction = ['label' => 'Nouveau bien', 'href' => $b . '/admin/bien-edit.php', 'icon' => 'plus'];

// -------------------------------------------------------------------------
// 1. Téléchargement du modèle d'import CSV / Excel
// -------------------------------------------------------------------------
if (isset($_GET['template'])) {
    $fmt = $_GET['template'] === 'xlsx' ? 'xlsx' : 'csv';
    $headers = ['titre', 'type', 'transaction', 'prix', 'wilaya', 'commune', 'quartier', 'surface', 'chambres', 'salles_de_bain', 'description'];
    $sampleRows = [
        ['Superbe Villa vue mer', 'Villa', 'Acheter', '45000000', 'Béjaïa', 'Tichy', 'Centre-ville', '250', '4', '2', 'Magnifique villa contemporaine avec jardin et vue panoramique.'],
        ['Appartement F4 moderne', 'Appartement', 'Louer', '85000', 'Alger', 'Hydra', 'Paradou', '120', '3', '1', 'Bel appartement calme et meublé dans quartier sécurisé.']
    ];
    if ($fmt === 'xlsx') {
        $bin = xlsx_build_binary($headers, $sampleRows, 'Modele Biens');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="modele_properties.xlsx"');
        echo $bin;
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modele_properties.csv"');
        echo csv_build_string($headers, $sampleRows);
    }
    exit;
}

// -------------------------------------------------------------------------
// 2. Filtres GET (utilisés aussi pour l'export)
// -------------------------------------------------------------------------
$search      = trim((string)($_GET['search'] ?? ''));
$transaction = trim((string)($_GET['transaction'] ?? ''));
$type        = trim((string)($_GET['type'] ?? ''));
$status      = trim((string)($_GET['status'] ?? ''));
$sort        = trim((string)($_GET['sort'] ?? 'recent'));
$highlightId = (int)($_GET['highlight'] ?? $_GET['id'] ?? 0);

// -------------------------------------------------------------------------
// 3. Traitements PHP POST (statut, suppression, bulk, export sélection, import)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $propId = (int)($_POST['id'] ?? 0);
        $newStatut = trim((string)($_POST['statut'] ?? ''));
        if ($propId > 0 && $newStatut !== '') {
            $stmt = $conn->prepare("UPDATE `properties` SET `statut` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $newStatut, $propId);
            $stmt->execute();
            set_flash("Statut mis à jour : {$newStatut}");
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'delete_one') {
        $propId = (int)($_POST['id'] ?? 0);
        if ($propId > 0) {
            $stmt = $conn->prepare("DELETE FROM `properties` WHERE `id` = ?");
            $stmt->bind_param("i", $propId);
            $stmt->execute();
            set_flash('Le bien a été supprimé avec succès.');
        }
        header('Location: ' . $b . '/admin/biens.php');
        exit;
    }

    if ($action === 'bulk_delete') {
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])), fn($v) => $v > 0));
        if (!empty($ids)) {
            $in = implode(',', $ids);
            $conn->query("DELETE FROM `properties` WHERE `id` IN ($in)");
            $c = count($ids);
            set_flash($c . ' bien' . ($c > 1 ? 's' : '') . ' supprimé' . ($c > 1 ? 's' : '') . ' avec succès.');
        }
        header('Location: ' . $b . '/admin/biens.php');
        exit;
    }

    if ($action === 'bulk_export_xlsx' || $action === 'bulk_export_csv') {
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])), fn($v) => $v > 0));
        if (!empty($ids)) {
            $in = implode(',', $ids);
            $resEx = $conn->query("SELECT * FROM `properties` WHERE `id` IN ($in) ORDER BY `date_creation` DESC, `id` DESC");
            $expHeaders = ['ID', 'Référence', 'Titre', 'Type', 'Transaction', 'Prix (DA)', 'Période', 'Wilaya', 'Commune', 'Quartier', 'Surface (m²)', 'Chambres', 'Salles de bain', 'Statut', 'Date création'];
            $expRows = [];
            while ($r = $resEx->fetch_assoc()) {
                $expRows[] = [$r['id'], $r['reference'], $r['titre'], $r['type'], $r['transaction'], $r['prix'], $r['periode_prix'] ?? '', $r['wilaya'], $r['commune'], $r['quartier'] ?? '', $r['surface'], $r['chambres'] ?? '', $r['salles_de_bain'] ?? '', $r['statut'], $r['date_creation']];
            }
            $dateSuffix = date('Y-m-d');
            if ($action === 'bulk_export_csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="biens_selection_' . $dateSuffix . '.csv"');
                echo csv_build_string($expHeaders, $expRows);
            } else {
                $bin = xlsx_build_binary($expHeaders, $expRows, 'Biens sélectionnés');
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="biens_selection_' . $dateSuffix . '.xlsx"');
                echo $bin;
            }
            exit;
        }
        set_flash('Aucun bien sélectionné pour l\'export.', 'error');
        header('Location: ' . $b . '/admin/biens.php');
        exit;
    }

    if ($action === 'import_file') {
        try {
            $parsedRows = spreadsheet_parse_upload($_FILES['import_file'] ?? []);
            if (count($parsedRows) < 2) {
                throw new RuntimeException('Le fichier est vide ou ne contient aucune ligne de données.');
            }
            $rawHeaders = array_shift($parsedRows);
            $headers = array_map(fn($h) => normalize_import_header((string)$h), $rawHeaders);

            $stmtCheckRef = $conn->prepare("SELECT `id` FROM `properties` WHERE `reference` = ? LIMIT 1");
            $stmtCheckDup = $conn->prepare("SELECT `id` FROM `properties` WHERE `titre` = ? AND `commune` = ? LIMIT 1");
            $stmtInsert = $conn->prepare("
                INSERT INTO `properties` (
                    `reference`, `titre`, `type`, `transaction`, `prix`, `periode_prix`,
                    `wilaya`, `commune`, `quartier`, `surface`, `chambres`,
                    `salles_de_bain`, `description`, `images`, `statut`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '[]', ?)
            ");

            $imported = 0;
            $errCount = 0;
            foreach ($parsedRows as $row) {
                $data = [];
                foreach ($headers as $i => $h) {
                    if ($h !== '') $data[$h] = trim((string)($row[$i] ?? ''));
                }
                $titre = $data['titre'] ?? $data['title'] ?? '';
                $tp = ($data['type'] ?? '') !== '' ? $data['type'] : 'Appartement';
                $transRaw = strtolower($data['transaction'] ?? '');
                $tx = in_array($transRaw, ['louer', 'location'], true) ? 'Louer' : 'Acheter';
                $prix = (float)str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $data['prix'] ?? $data['price'] ?? '0'));
                $periode = ($data['periode_prix'] ?? $data['periode'] ?? '') ?: ($tx === 'Louer' ? 'mois' : null);
                $wilaya = ($data['wilaya'] ?? '') !== '' ? $data['wilaya'] : 'Béjaïa';
                $commune = ($data['commune'] ?? $data['city'] ?? '') !== '' ? ($data['commune'] ?? $data['city']) : $wilaya;
                $quartier = $data['quartier'] ?? $data['district'] ?? '';
                $surface = (float)str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $data['surface'] ?? '0'));
                if ($surface <= 0) $surface = 100.0;
                $chambres = ($data['chambres'] ?? $data['bedrooms'] ?? '') !== '' ? (int)($data['chambres'] ?? $data['bedrooms']) : null;
                $sdb = ($data['salles_de_bain'] ?? $data['salles_de_bains'] ?? $data['bathrooms'] ?? '') !== '' ? (int)($data['salles_de_bain'] ?? $data['salles_de_bains'] ?? $data['bathrooms']) : null;
                $desc = ($data['description'] ?? '') !== '' ? $data['description'] : 'Bien importé via fichier.';
                $stat = ($data['statut'] ?? $data['status'] ?? '') !== '' ? ($data['statut'] ?? $data['status']) : 'Disponible';
                $refInput = $data['reference'] ?? $data['ref'] ?? '';

                if ($titre === '' || $prix <= 0) { $errCount++; continue; }
                if ($refInput !== '') {
                    $stmtCheckRef->bind_param("s", $refInput);
                    $stmtCheckRef->execute();
                    if ($stmtCheckRef->get_result()->num_rows > 0) { $errCount++; continue; }
                }
                $stmtCheckDup->bind_param("ss", $titre, $commune);
                $stmtCheckDup->execute();
                if ($stmtCheckDup->get_result()->num_rows > 0) { $errCount++; continue; }

                $ref = $refInput !== '' ? $refInput : ('IML-' . str_pad((string)rand(100, 9999), 3, '0', STR_PAD_LEFT));
                $stmtInsert->bind_param("ssssdssssdiiss", $ref, $titre, $tp, $tx, $prix, $periode, $wilaya, $commune, $quartier, $surface, $chambres, $sdb, $desc, $stat);
                $stmtInsert->execute();
                $imported++;
            }

            if ($imported > 0) {
                set_flash("$imported bien(s) importé(s) avec succès" . ($errCount > 0 ? " ($errCount ignoré(s))." : '.'));
            } else {
                set_flash("Aucun bien importé ($errCount ligne(s) invalide(s) ou doublon).", 'error');
            }
        } catch (Throwable $ex) {
            set_flash($ex->getMessage(), 'error');
        }
        header('Location: ' . $b . '/admin/biens.php');
        exit;
    }
}

// -------------------------------------------------------------------------
// 4. Requête MySQL filtrée des biens
// -------------------------------------------------------------------------
$where = ["1=1"];
$typesStr = "";
$params = [];

if ($transaction !== '') {
    $where[] = "`transaction` = ?";
    $typesStr .= "s";
    $params[] = $transaction;
}
if ($type !== '') {
    $where[] = "`type` = ?";
    $typesStr .= "s";
    $params[] = $type;
}
if ($status !== '') {
    $where[] = "`statut` = ?";
    $typesStr .= "s";
    $params[] = $status;
}
if ($search !== '') {
    $like = "%{$search}%";
    $where[] = "(`titre` LIKE ? OR `reference` LIKE ? OR `commune` LIKE ? OR `wilaya` LIKE ?)";
    $typesStr .= "ssss";
    array_push($params, $like, $like, $like, $like);
}

$orderClause = match ($sort) {
    'price-asc'    => 'ORDER BY `prix` ASC',
    'price-desc'   => 'ORDER BY `prix` DESC',
    'surface-asc'  => 'ORDER BY `surface` ASC',
    'surface-desc' => 'ORDER BY `surface` DESC',
    default        => 'ORDER BY `date_creation` DESC, `id` DESC',
};

$sql = "SELECT * FROM `properties` WHERE " . implode(' AND ', $where) . " " . $orderClause;
$stmtList = $conn->prepare($sql);
if (!empty($params)) {
    $stmtList->bind_param($typesStr, ...$params);
}
$stmtList->execute();
$resProps = $stmtList->get_result();
$properties = [];
while ($row = $resProps->fetch_assoc()) {
    $properties[] = $row;
}

// Export global filtré (GET ?export=xlsx|csv)
if (isset($_GET['export'])) {
    $fmt = $_GET['export'] === 'csv' ? 'csv' : 'xlsx';
    $expHeaders = ['ID', 'Référence', 'Titre', 'Type', 'Transaction', 'Prix (DA)', 'Période', 'Wilaya', 'Commune', 'Quartier', 'Surface (m²)', 'Chambres', 'Salles de bain', 'Statut', 'Date création'];
    $expRows = [];
    foreach ($properties as $r) {
        $expRows[] = [$r['id'], $r['reference'], $r['titre'], $r['type'], $r['transaction'], $r['prix'], $r['periode_prix'] ?? '', $r['wilaya'], $r['commune'], $r['quartier'] ?? '', $r['surface'], $r['chambres'] ?? '', $r['salles_de_bain'] ?? '', $r['statut'], $r['date_creation']];
    }
    $dateSuffix = date('Y-m-d');
    if ($fmt === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="biens_immolode_' . $dateSuffix . '.csv"');
        echo csv_build_string($expHeaders, $expRows);
    } else {
        $bin = xlsx_build_binary($expHeaders, $expRows, 'Biens immobiliers');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="biens_immolode_' . $dateSuffix . '.xlsx"');
        echo $bin;
    }
    exit;
}

$exportQs = http_build_query(array_filter([
    'search'      => $search,
    'transaction' => $transaction,
    'type'        => $type,
    'status'      => $status,
    'sort'        => $sort,
]));
$exportPrefix = $b . '/admin/biens.php?' . ($exportQs ? $exportQs . '&' : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Biens immobiliers — Administration Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="<?= $b ?>/admin/assets/css/admin.css">
  <style>
    .select-col { display: none; width: 44px; text-align: center; }
    .is-select-mode .select-col { display: table-cell; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-main-wrap">
      <?php include __DIR__ . '/includes/topbar.php'; ?>

      <main class="admin-content">
        <!-- Barre d'outils -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
          <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
            Catalogue complet des biens immobiliers, gestion des disponibilités et publication.
          </p>

          <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline btn-sm" id="btn-open-import">
              <?= admin_icon('upload', 14) ?> <span>Importer CSV / Excel</span>
            </button>
            <a href="<?= e($exportPrefix) ?>export=xlsx" class="btn btn-outline btn-sm">
              <?= admin_icon('download', 14) ?> <span>Export Excel</span>
            </a>
            <a href="<?= e($exportPrefix) ?>export=csv" class="btn btn-outline btn-sm">
              <?= admin_icon('download', 14) ?> <span>Export CSV</span>
            </a>
            <button type="button" class="btn btn-outline btn-sm" id="btn-toggle-select">
              <?= admin_icon('check', 14) ?> <span id="btn-select-label">Sélectionner</span>
            </button>
          </div>
        </div>

        <!-- Filtres PHP GET -->
        <form method="GET" action="<?= $b ?>/admin/biens.php" class="filters-bar" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px;">
          <div class="search-input-wrap">
            <span class="admin-icon"><?= admin_icon('search', 16) ?></span>
            <input type="text" name="search" placeholder="Rechercher par titre, référence, ville..." value="<?= e($search) ?>">
          </div>

          <div class="filters-group">
            <select class="form-select" name="transaction" onchange="this.form.submit()">
              <option value="">Toutes transactions</option>
              <option value="Acheter" <?= $transaction === 'Acheter' ? 'selected' : '' ?>>Vente</option>
              <option value="Louer" <?= $transaction === 'Louer' ? 'selected' : '' ?>>Location</option>
            </select>

            <select class="form-select" name="type" onchange="this.form.submit()">
              <option value="">Tous les types</option>
              <?php foreach (['Villa', 'Appartement', 'Terrain', 'Bureau', 'Local commercial', 'Duplex', 'Immeuble'] as $tOpt): ?>
                <option value="<?= e($tOpt) ?>" <?= $type === $tOpt ? 'selected' : '' ?>><?= e($tOpt) ?></option>
              <?php endforeach; ?>
            </select>

            <select class="form-select" name="status" onchange="this.form.submit()">
              <option value="">Tous les statuts</option>
              <?php foreach (['Disponible', 'Réservé', 'Vendu', 'Loué'] as $sOpt): ?>
                <option value="<?= e($sOpt) ?>" <?= $status === $sOpt ? 'selected' : '' ?>><?= e($sOpt) ?></option>
              <?php endforeach; ?>
            </select>

            <select class="form-select" name="sort" onchange="this.form.submit()">
              <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récents</option>
              <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Prix croissant</option>
              <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Prix décroissant</option>
              <option value="surface-asc" <?= $sort === 'surface-asc' ? 'selected' : '' ?>>Surface croissante</option>
              <option value="surface-desc" <?= $sort === 'surface-desc' ? 'selected' : '' ?>>Surface décroissante</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="<?= $b ?>/admin/biens.php" class="btn btn-outline btn-sm">Effacer</a>
          </div>
        </form>

        <!-- Résultats -->
        <?php if (empty($properties)): ?>
          <div class="card">
            <div class="empty-state">
              <div class="empty-state-icon"><?= admin_icon('building', 28) ?></div>
              <h3 class="empty-state-title">Aucun bien trouvé</h3>
              <p class="empty-state-text">Aucun bien ne correspond à vos critères de recherche actuels.</p>
              <a href="<?= $b ?>/admin/bien-edit.php" class="btn btn-primary btn-sm">Ajouter un nouveau bien</a>
            </div>
          </div>
        <?php else: ?>
          <div id="biens-table-wrap">
            <form method="POST" action="<?= $b ?>/admin/biens.php" id="bulk-form">
              <input type="hidden" name="action" id="bulk-action-input" value="">

              <div class="bulk-actions-bar" id="bulk-bar">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="bulk-badge" id="bulk-count">0</span>
                  <span style="font-weight: 600;">élément(s) sélectionné(s)</span>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                  <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-cancel">Annuler</button>
                  <button type="submit" class="btn btn-outline btn-sm" onclick="document.getElementById('bulk-action-input').value='bulk_export_csv'">
                    <?= admin_icon('download', 14) ?> <span>CSV</span>
                  </button>
                  <button type="submit" class="btn btn-outline btn-sm" onclick="document.getElementById('bulk-action-input').value='bulk_export_xlsx'">
                    <?= admin_icon('download', 14) ?> <span>Excel</span>
                  </button>
                  <button type="submit" class="btn btn-sm" style="background: var(--admin-danger); color: #fff; border: none;" onclick="document.getElementById('bulk-action-input').value='bulk_delete'; return confirm('Supprimer définitivement les biens sélectionnés ?');">
                    <?= admin_icon('trash', 14) ?> <span>Supprimer</span>
                  </button>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div>
                    <h2 class="card-title">Catalogue des propriétés</h2>
                    <p class="card-subtitle"><?= count($properties) ?> bien<?= count($properties) > 1 ? 's' : '' ?> correspondant<?= count($properties) > 1 ? 's' : '' ?></p>
                  </div>
                </div>
                <div class="card-body" style="padding: 0;">
                  <div class="table-responsive">
                    <table class="admin-table">
                      <thead>
                        <tr>
                          <th class="select-col"><input type="checkbox" id="check-all-rows" title="Tout sélectionner"></th>
                          <th>Bien &amp; Référence</th>
                          <th>Type</th>
                          <th>Localisation</th>
                          <th>Surface</th>
                          <th>Prix</th>
                          <th>Statut</th>
                          <th style="text-align: right; width: 120px;">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($properties as $p):
                          $imgs = json_decode($p['images'] ?? '[]', true) ?: [];
                          $thumb = !empty($imgs[0]) ? asset_url((string)$imgs[0]) : '';
                          $isHighlight = ($highlightId === (int)$p['id']);
                        ?>
                          <tr id="prop-row-<?= (int)$p['id'] ?>" class="<?= $isHighlight ? 'row-highlight' : '' ?>">
                            <td class="select-col">
                              <input type="checkbox" class="row-checkbox" name="ids[]" value="<?= (int)$p['id'] ?>">
                            </td>
                            <td>
                              <div class="table-property-cell">
                                <?php if ($thumb !== ''): ?>
                                  <img src="<?= e($thumb) ?>" alt="" class="table-thumb" loading="lazy">
                                <?php endif; ?>
                                <div>
                                  <a href="<?= $b ?>/admin/bien-edit.php?id=<?= (int)$p['id'] ?>" class="table-property-title" title="<?= e($p['titre']) ?>">
                                    <?= e($p['titre']) ?>
                                  </a>
                                  <span class="table-property-ref">
                                    <?= e($p['reference'] ?? '') ?>
                                    <?php if (!empty($p['a_la_une'])): ?> · <span style="color:var(--admin-gold);font-weight:600">À la une</span><?php endif; ?>
                                  </span>
                                </div>
                              </div>
                            </td>
                            <td>
                              <div style="display: flex; flex-direction: column; gap: 4px;">
                                <span><?= e($p['type']) ?></span>
                                <div><?= transaction_badge($p['transaction'] ?? 'Acheter') ?></div>
                              </div>
                            </td>
                            <td>
                              <strong style="color: var(--admin-text); display: block;"><?= e($p['commune'] ?: '—') ?></strong>
                              <span style="font-size: 0.78rem; color: var(--admin-text-subtle);"><?= e($p['wilaya'] ?? '') ?></span>
                            </td>
                            <td><?= format_surface($p['surface']) ?></td>
                            <td style="font-weight: 600; white-space: nowrap;"><?= format_price($p['prix'], $p['periode_prix'] ?? null) ?></td>
                            <td>
                              <select class="form-select" style="padding: 4px 8px; font-size: 0.78rem;" onchange="submitStatusChange(<?= (int)$p['id'] ?>, this.value)">
                                <?php foreach (['Disponible', 'Réservé', 'Vendu', 'Loué'] as $stOpt): ?>
                                  <option value="<?= e($stOpt) ?>" <?= ($p['statut'] ?? '') === $stOpt ? 'selected' : '' ?>><?= e($stOpt) ?></option>
                                <?php endforeach; ?>
                              </select>
                            </td>
                            <td style="text-align: right;">
                              <div class="table-actions" style="justify-content: flex-end;">
                                <a href="<?= $b ?>/client/bien.php?id=<?= (int)$p['id'] ?>" target="_blank" rel="noopener" class="btn-icon" title="Voir sur le site public">
                                  <?= admin_icon('external', 15) ?>
                                </a>
                                <a href="<?= $b ?>/admin/bien-edit.php?id=<?= (int)$p['id'] ?>" class="btn-icon" title="Modifier">
                                  <?= admin_icon('edit', 15) ?>
                                </a>
                                <button type="button" class="btn-icon btn-icon--danger" title="Supprimer ce bien" onclick="submitDeleteOne(<?= (int)$p['id'] ?>)">
                                  <?= admin_icon('trash', 15) ?>
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </form>

            <!-- Formulaires POST isolés pour changement rapide de statut / suppression unitaire -->
            <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>" id="single-status-form" style="display:none;">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="id" id="status-prop-id" value="">
              <input type="hidden" name="statut" id="status-prop-val" value="">
            </form>
            <form method="POST" action="<?= $b ?>/admin/biens.php" id="single-delete-form" style="display:none;">
              <input type="hidden" name="action" value="delete_one">
              <input type="hidden" name="id" id="delete-prop-id" value="">
            </form>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <?php
  $importModalTitle = 'Importer des biens immobiliers';
  $importActionUrl = $b . '/admin/biens.php';
  include __DIR__ . '/includes/import_modal.php';
  include __DIR__ . '/includes/footer_scripts.php';
  ?>

  <script>
    function submitStatusChange(id, val) {
      document.getElementById('status-prop-id').value = id;
      document.getElementById('status-prop-val').value = val;
      document.getElementById('single-status-form').submit();
    }
    function submitDeleteOne(id) {
      if (!confirm('Supprimer définitivement ce bien du catalogue et du site public ?')) return;
      document.getElementById('delete-prop-id').value = id;
      document.getElementById('single-delete-form').submit();
    }
    (function() {
      var wrap = document.getElementById('biens-table-wrap');
      var toggleBtn = document.getElementById('btn-toggle-select');
      var labelSpan = document.getElementById('btn-select-label');
      var bulkBar = document.getElementById('bulk-bar');
      var bulkCount = document.getElementById('bulk-count');
      var checkAll = document.getElementById('check-all-rows');
      var checkboxes = document.querySelectorAll('.row-checkbox');

      function updateBulkUI() {
        var count = 0;
        checkboxes.forEach(function(cb) {
          var tr = cb.closest('tr');
          if (cb.checked) { count++; tr?.classList.add('is-selected'); }
          else { tr?.classList.remove('is-selected'); }
        });
        if (bulkCount) bulkCount.textContent = String(count);
        if (bulkBar) bulkBar.classList.toggle('is-visible', count > 0 && wrap?.classList.contains('is-select-mode'));
      }

      toggleBtn?.addEventListener('click', function() {
        var active = wrap?.classList.toggle('is-select-mode');
        toggleBtn.className = 'btn ' + (active ? 'btn-primary' : 'btn-outline') + ' btn-sm';
        if (labelSpan) labelSpan.textContent = active ? 'Annuler la sélection' : 'Sélectionner';
        if (!active) {
          checkboxes.forEach(function(cb) { cb.checked = false; });
          if (checkAll) checkAll.checked = false;
        }
        updateBulkUI();
      });

      document.getElementById('btn-bulk-cancel')?.addEventListener('click', function() {
        checkboxes.forEach(function(cb) { cb.checked = false; });
        if (checkAll) checkAll.checked = false;
        updateBulkUI();
      });

      checkAll?.addEventListener('change', function() {
        checkboxes.forEach(function(cb) { cb.checked = checkAll.checked; });
        updateBulkUI();
      });
      checkboxes.forEach(function(cb) { cb.addEventListener('change', updateBulkUI); });

      var hl = document.querySelector('tr.row-highlight');
      if (hl) setTimeout(function() { hl.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 150);
    })();
  </script>
</body>
</html>
