<?php
/**
 * admin/fiches-client.php - Gestion des fiches client officielles (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/xlsx.php';

$currentUser = require_admin($conn);
$activePage = 'fiches-client';
$pageHeading = 'Fiches client & Dossiers';
$breadcrumbs = [['label' => 'Fiches client']];
$b = BASE_URL;
$topbarAction = ['label' => 'Nouveau dossier (public)', 'href' => $b . '/client/fiche-client.php', 'target' => '_blank', 'icon' => 'external'];

// -------------------------------------------------------------------------
// 1. TÃ©lÃ©chargement du modÃ¨le d'import CSV / Excel
// -------------------------------------------------------------------------
if (isset($_GET['template'])) {
    $fmt = $_GET['template'] === 'xlsx' ? 'xlsx' : 'csv';
    $headers = ['nom', 'prenom', 'telephone', 'email', 'transaction', 'type_bien'];
    $rows = [
        ['Mansouri', 'Nadir', '0555123456', 'nadir.mansouri@gmail.com', 'Demande', 'Appartement'],
        ['Brahimi', 'Sarah', '0770987654', 'sarah.brahimi@yahoo.fr', 'Offre', 'Villa']
    ];
    if ($fmt === 'xlsx') {
        $bin = xlsx_build_binary($headers, $rows, 'Modele Fiches Client');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="modele_fiches_client.xlsx"');
        echo $bin;
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modele_fiches_client.csv"');
        echo csv_build_string($headers, $rows);
    }
    exit;
}

$search      = trim((string)($_GET['search'] ?? ''));
$transaction = trim((string)($_GET['transaction'] ?? ''));
$typeBien    = trim((string)($_GET['typeBien'] ?? ''));
$status      = trim((string)($_GET['status'] ?? ''));
$openId      = (int)($_GET['id'] ?? 0);

$filterQs = http_build_query(array_filter(['search' => $search, 'transaction' => $transaction, 'typeBien' => $typeBien, 'status' => $status]));
$selfUrl = $b . '/admin/fiches-client.php' . ($filterQs ? '?' . $filterQs : '');

// -------------------------------------------------------------------------
// 2. Traitements POST
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || isset($_POST['ajax']);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $st = trim((string)($_POST['statut'] ?? ''));
        if ($id > 0 && $st !== '') {
            $stmt = $conn->prepare("UPDATE `fiches_client` SET `statut` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $st, $id);
            $stmt->execute();
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'statut' => $st, 'counts' => get_unread_counts($conn)]);
                exit;
            }
            set_flash("Statut du dossier mis Ã  jour : {$st}");
        }
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false]);
            exit;
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'toggle_read') {
        $id = (int)($_POST['id'] ?? 0);
        $nr = (int)($_POST['new_read'] ?? 1) ? 1 : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE `fiches_client` SET `is_read` = ? WHERE `id` = ?");
            $stmt->bind_param("ii", $nr, $id);
            $stmt->execute();
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'  => true,
                    'new_read' => $nr,
                    'id'       => $id,
                    'counts'   => get_unread_counts($conn)
                ]);
                exit;
            }
            set_flash($nr ? 'Fiche marquÃ©e comme lue.' : 'Fiche marquÃ©e comme non lue.');
        }
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false]);
            exit;
        }
        header('Location: ' . $selfUrl);
        exit;
    }

    if ($action === 'delete_one') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM `fiches_client` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            set_flash('Fiche client supprimÃ©e.');
        }
        header('Location: ' . $selfUrl);
        exit;
    }

    if (in_array($action, ['bulk_delete', 'bulk_mark_read', 'bulk_export_csv', 'bulk_export_xlsx'], true)) {
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])), fn($v) => $v > 0));
        if (!empty($ids)) {
            $in = implode(',', $ids);
            if ($action === 'bulk_delete') {
                $conn->query("DELETE FROM `fiches_client` WHERE `id` IN ($in)");
                set_flash(count($ids) . ' dossier(s) supprimÃ©(s).');
                header('Location: ' . $selfUrl);
                exit;
            }
            if ($action === 'bulk_mark_read') {
                $conn->query("UPDATE `fiches_client` SET `is_read` = 1 WHERE `id` IN ($in)");
                set_flash(count($ids) . ' dossier(s) marquÃ©(s) comme lu(s).');
                header('Location: ' . $selfUrl);
                exit;
            }
            $resEx = $conn->query("SELECT * FROM `fiches_client` WHERE `id` IN ($in) ORDER BY `date_creation` DESC");
            $expHeaders = ['ID', 'RÃ©fÃ©rence', 'Nom', 'PrÃ©nom', 'TÃ©lÃ©phone', 'Email', 'OpÃ©ration', 'Type de bien', 'Statut', 'Lu', 'Date crÃ©ation'];
            $expRows = [];
            while ($r = $resEx->fetch_assoc()) {
                $expRows[] = [$r['id'], $r['reference'], $r['nom'], $r['prenom'], $r['telephone'], $r['email'], $r['transaction'], $r['type_bien'], $r['statut'], $r['is_read'] ? 'Oui' : 'Non', $r['date_creation']];
            }
            $dateSuffix = date('Y-m-d');
            if ($action === 'bulk_export_csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="fiches_client_selection_' . $dateSuffix . '.csv"');
                echo csv_build_string($expHeaders, $expRows);
            } else {
                $bin = xlsx_build_binary($expHeaders, $expRows, 'Fiches sÃ©lection');
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="fiches_client_selection_' . $dateSuffix . '.xlsx"');
                echo $bin;
            }
            exit;
        }
        set_flash('Aucun dossier sÃ©lectionnÃ©.', 'error');
        header('Location: ' . $selfUrl);
        exit;
    }

    if ($action === 'import_file') {
        try {
            $parsedRows = spreadsheet_parse_upload($_FILES['import_file'] ?? []);
            if (count($parsedRows) < 2) throw new RuntimeException('Fichier vide ou sans donnÃ©es.');
            $rawHeaders = array_shift($parsedRows);
            $headers = array_map(fn($h) => normalize_import_header((string)$h), $rawHeaders);

            $stmtCheckRef = $conn->prepare("SELECT `id` FROM `fiches_client` WHERE `reference` = ? LIMIT 1");
            $stmtCheckDup = $conn->prepare("SELECT `id` FROM `fiches_client` WHERE `nom` = ? AND `telephone` = ? LIMIT 1");
            $stmtIns = $conn->prepare("INSERT INTO `fiches_client` (`reference`, `nom`, `prenom`, `telephone`, `email`, `transaction`, `type_bien`, `statut`, `is_read`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");

            $imported = 0; $errCount = 0;
            foreach ($parsedRows as $row) {
                $d = [];
                foreach ($headers as $i => $h) { if ($h !== '') $d[$h] = trim((string)($row[$i] ?? '')); }
                $nom = $d['nom'] ?? '';
                $prenom = $d['prenom'] ?? '';
                $tel = $d['telephone'] ?? $d['phone'] ?? '';
                $email = $d['email'] ?? '';
                $transRaw = strtolower($d['transaction'] ?? $d['operation'] ?? '');
                $tx = in_array($transRaw, ['offre', 'mandat'], true) ? 'Offre' : 'Demande';
                $tb = ($d['type_bien'] ?? $d['typebien'] ?? $d['type_de_bien'] ?? '') ?: 'Appartement';
                $st = ($d['statut'] ?? $d['status'] ?? '') ?: 'Nouveau';
                $refInput = $d['reference'] ?? '';

                if ($nom === '' || $tel === '') { $errCount++; continue; }
                if ($refInput !== '') {
                    $stmtCheckRef->bind_param("s", $refInput);
                    $stmtCheckRef->execute();
                    if ($stmtCheckRef->get_result()->num_rows > 0) { $errCount++; continue; }
                }
                $stmtCheckDup->bind_param("ss", $nom, $tel);
                $stmtCheckDup->execute();
                if ($stmtCheckDup->get_result()->num_rows > 0) { $errCount++; continue; }

                $ref = $refInput !== '' ? $refInput : ('FC-' . date('Ymd') . '-' . str_pad((string)rand(100, 9999), 3, '0', STR_PAD_LEFT));
                $stmtIns->bind_param("ssssssss", $ref, $nom, $prenom, $tel, $email, $tx, $tb, $st);
                $stmtIns->execute();
                $imported++;
            }
            set_flash($imported > 0 ? "$imported fiche(s) importÃ©e(s) avec succÃ¨s." : "Aucune fiche importÃ©e ($errCount ignorÃ©e(s)).", $imported > 0 ? 'success' : 'error');
        } catch (Throwable $ex) {
            set_flash($ex->getMessage(), 'error');
        }
        header('Location: ' . $b . '/admin/fiches-client.php');
        exit;
    }
}

// -------------------------------------------------------------------------
// 3. Auto-marquer lu si drawer ouvert + Stats KPI
// -------------------------------------------------------------------------
$drawerFiche = null;
if ($openId > 0) {
    $conn->query("UPDATE `fiches_client` SET `is_read` = 1 WHERE `id` = " . $openId);
    $stmtOne = $conn->prepare("SELECT * FROM `fiches_client` WHERE `id` = ? LIMIT 1");
    $stmtOne->bind_param("i", $openId);
    $stmtOne->execute();
    $drawerFiche = $stmtOne->get_result()->fetch_assoc();
}

$totalFiches  = (int)$conn->query("SELECT COUNT(*) c FROM `fiches_client`")->fetch_assoc()['c'];
$pendingCount = (int)$conn->query("SELECT COUNT(*) c FROM `fiches_client` WHERE `statut` IN ('Nouveau', 'En cours')")->fetch_assoc()['c'];
$demandeCount = (int)$conn->query("SELECT COUNT(*) c FROM `fiches_client` WHERE `transaction` = 'Demande'")->fetch_assoc()['c'];
$offreCount   = (int)$conn->query("SELECT COUNT(*) c FROM `fiches_client` WHERE `transaction` = 'Offre'")->fetch_assoc()['c'];

// -------------------------------------------------------------------------
// 4. Liste filtrÃ©e depuis MySQL
// -------------------------------------------------------------------------
$where = ["1=1"];
$typesStr = "";
$params = [];

if ($transaction !== '') {
    $where[] = "`transaction` = ?";
    $typesStr .= "s";
    $params[] = $transaction;
}
if ($typeBien !== '') {
    $where[] = "`type_bien` = ?";
    $typesStr .= "s";
    $params[] = $typeBien;
}
if ($status !== '') {
    if ($status === 'TraitÃ©') {
        $where[] = "`statut` IN ('TraitÃ©', 'Traite')";
    } else {
        $where[] = "`statut` = ?";
        $typesStr .= "s";
        $params[] = $status;
    }
}
if ($search !== '') {
    $like = "%{$search}%";
    $where[] = "(`reference` LIKE ? OR `nom` LIKE ? OR `prenom` LIKE ? OR `telephone` LIKE ? OR `email` LIKE ?)";
    $typesStr .= "sssss";
    array_push($params, $like, $like, $like, $like, $like);
}

$sql = "SELECT * FROM `fiches_client` WHERE " . implode(' AND ', $where) . " ORDER BY `date_creation` DESC, `id` DESC";
$stmtList = $conn->prepare($sql);
if (!empty($params)) {
    $stmtList->bind_param($typesStr, ...$params);
}
$stmtList->execute();
$res = $stmtList->get_result();
$fiches = [];
while ($r = $res->fetch_assoc()) {
    $fiches[] = $r;
}

if (isset($_GET['export'])) {
    $fmt = $_GET['export'] === 'csv' ? 'csv' : 'xlsx';
    $expHeaders = ['ID', 'RÃ©fÃ©rence', 'Nom', 'PrÃ©nom', 'TÃ©lÃ©phone', 'Email', 'OpÃ©ration', 'Type de bien', 'Statut', 'Lu', 'Date crÃ©ation'];
    $expRows = [];
    foreach ($fiches as $r) {
        $expRows[] = [$r['id'], $r['reference'], $r['nom'], $r['prenom'], $r['telephone'], $r['email'], $r['transaction'], $r['type_bien'], $r['statut'], $r['is_read'] ? 'Oui' : 'Non', $r['date_creation']];
    }
    $dateSuffix = date('Y-m-d');
    if ($fmt === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="fiches_client_immolode_' . $dateSuffix . '.csv"');
        echo csv_build_string($expHeaders, $expRows);
    } else {
        $bin = xlsx_build_binary($expHeaders, $expRows, 'Fiches client');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="fiches_client_immolode_' . $dateSuffix . '.xlsx"');
        echo $bin;
    }
    exit;
}

$exportPrefix = $b . '/admin/fiches-client.php?' . ($filterQs ? $filterQs . '&' : '');
$openLinkPrefix = $b . '/admin/fiches-client.php?' . ($filterQs ? $filterQs . '&' : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fiches client â€” Administration Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="<?= $b ?>/admin/assets/css/admin.css">
  <style>
    .select-col { display: none; width: 44px; text-align: center; }
    .is-select-mode .select-col { display: table-cell; }
    .doc-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; padding: 3px 8px; background: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-xs); color: var(--admin-text); }
  </style>
</head>
<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-main-wrap">
      <?php include __DIR__ . '/includes/topbar.php'; ?>

      <main class="admin-content">
        <!-- KPIs -->
        <div class="stats-grid" style="margin-bottom: 24px;">
          <div class="stat-card">
            <div class="stat-icon stat-icon--gold"><?= admin_icon('file-text', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $totalFiches ?></p>
              <p class="stat-label">Total dossiers constituÃ©s</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon <?= $pendingCount > 0 ? 'stat-icon--gold' : 'stat-icon--blue' ?>"><?= admin_icon('check', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $pendingCount ?></p>
              <p class="stat-label">Dossiers Ã  traiter / en cours</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon--blue"><?= admin_icon('search', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $demandeCount ?></p>
              <p class="stat-label">Dossiers Â« Demande Â»</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon--purple"><?= admin_icon('building', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $offreCount ?></p>
              <p class="stat-label">Dossiers Â« Offre Â»</p>
            </div>
          </div>
        </div>

        <!-- Outils -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
          <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
            Dossiers officiels de constitution client avec piÃ¨ces d'identitÃ© et signatures certifiÃ©es.
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
              <?= admin_icon('check', 14) ?> <span id="btn-select-label">SÃ©lectionner</span>
            </button>
          </div>
        </div>

        <!-- Filtres PHP GET -->
        <form method="GET" action="<?= $b ?>/admin/fiches-client.php" class="filters-bar" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px;">
          <div class="search-input-wrap">
            <span class="admin-icon"><?= admin_icon('search', 16) ?></span>
            <input type="text" name="search" placeholder="Rechercher par rÃ©fÃ©rence, nom, prÃ©nom, tÃ©lÃ©phone..." value="<?= e($search) ?>">
          </div>

          <div class="filters-group">
            <select class="form-select" name="transaction" onchange="this.form.submit()">
              <option value="">Toutes opÃ©rations</option>
              <option value="Demande" <?= $transaction === 'Demande' ? 'selected' : '' ?>>Demande (recherche)</option>
              <option value="Offre" <?= $transaction === 'Offre' ? 'selected' : '' ?>>Offre (mise en vente / loc)</option>
            </select>

            <select class="form-select" name="typeBien" onchange="this.form.submit()">
              <option value="">Tous les types de bien</option>
              <?php foreach (['Appartement', 'Villa', 'Commerce', 'Terrain', 'DÃ©pÃ´t'] as $tbOpt): ?>
                <option value="<?= e($tbOpt) ?>" <?= $typeBien === $tbOpt ? 'selected' : '' ?>><?= e($tbOpt) ?></option>
              <?php endforeach; ?>
            </select>

            <select class="form-select" name="status" onchange="this.form.submit()">
              <option value="">Tous les statuts</option>
              <?php foreach (['Nouveau', 'En cours', 'ValidÃ©', 'TraitÃ©', 'RejetÃ©'] as $stOpt): ?>
                <option value="<?= e($stOpt) ?>" <?= $status === $stOpt ? 'selected' : '' ?>><?= e($stOpt) ?></option>
              <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="<?= $b ?>/admin/fiches-client.php" class="btn btn-outline btn-sm">Effacer</a>
          </div>
        </form>

        <!-- RÃ©sultats -->
        <?php if (empty($fiches)): ?>
          <div class="card">
            <div class="empty-state">
              <div class="empty-state-icon"><?= admin_icon('file-text', 28) ?></div>
              <h3 class="empty-state-title">Aucune fiche client trouvÃ©e</h3>
              <p class="empty-state-text">Aucun dossier ne correspond Ã  vos filtres actuels.</p>
            </div>
          </div>
        <?php else: ?>
          <div id="fiches-table-wrap">
            <form method="POST" action="<?= e($selfUrl) ?>" id="bulk-form">
              <input type="hidden" name="action" id="bulk-action-input" value="">

              <div class="bulk-actions-bar" id="bulk-bar">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="bulk-badge" id="bulk-count">0</span>
                  <span style="font-weight: 600;">dossier(s) sÃ©lectionnÃ©(s)</span>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                  <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-cancel">Annuler</button>
                  <button type="submit" class="btn btn-outline btn-sm" onclick="document.getElementById('bulk-action-input').value='bulk_mark_read'">
                    <?= admin_icon('check', 14) ?> <span>Marquer lu</span>
                  </button>
                  <button type="submit" class="btn btn-outline btn-sm" onclick="document.getElementById('bulk-action-input').value='bulk_export_csv'">
                    <?= admin_icon('download', 14) ?> <span>CSV</span>
                  </button>
                  <button type="submit" class="btn btn-outline btn-sm" onclick="document.getElementById('bulk-action-input').value='bulk_export_xlsx'">
                    <?= admin_icon('download', 14) ?> <span>Excel</span>
                  </button>
                  <button type="submit" class="btn btn-sm" style="background: var(--admin-danger); color: #fff; border: none;" onclick="document.getElementById('bulk-action-input').value='bulk_delete'; return confirm('Supprimer dÃ©finitivement ces fiches client ?');">
                    <?= admin_icon('trash', 14) ?> <span>Supprimer</span>
                  </button>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h2 class="card-title">Fiches client enregistrÃ©es (<?= count($fiches) ?>)</h2>
                </div>
                <div class="table-responsive">
                  <table class="admin-table">
                    <thead>
                      <tr>
                        <th class="select-col"><input type="checkbox" id="check-all-rows" title="Tout sÃ©lectionner"></th>
                        <th>RÃ©fÃ©rence &amp; Date</th>
                        <th>Client</th>
                        <th>OpÃ©ration &amp; Bien</th>
                        <th>PiÃ¨ces &amp; Signature</th>
                        <th>Statut</th>
                        <th style="width: 120px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($fiches as $f):
                        $isUnread = ((int)($f['is_read'] ?? 0) === 0);
                        $isOffre = ($f['transaction'] === 'Offre');
                        $rowOpenUrl = $openLinkPrefix . 'id=' . (int)$f['id'];
                      ?>
                        <tr id="row-fiche-<?= (int)$f['id'] ?>" class="<?= $isUnread ? 'is-unread' : '' ?>" style="<?= $isUnread ? 'font-weight:600;' : '' ?>" onclick="if(!document.getElementById('fiches-table-wrap').classList.contains('is-select-mode')) window.location.href='<?= e($rowOpenUrl) ?>'">
                          <td class="select-col" onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-checkbox" name="ids[]" value="<?= (int)$f['id'] ?>">
                          </td>
                          <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                              <span class="unread-dot" style="<?= $isUnread ? '' : 'display:none;' ?>" title="Non lu"></span>
                              <div>
                                <div style="font-weight: 600; color: var(--admin-gold); font-family: monospace; font-size: 0.92rem;"><?= e($f['reference'] ?: ('IML-DOS-' . $f['id'])) ?></div>
                                <div style="font-size: 0.75rem; color: var(--admin-text-subtle);"><?= format_date($f['date_creation']) ?></div>
                              </div>
                            </div>
                          </td>
                          <td onclick="event.stopPropagation()">
                            <div style="font-weight: 600; color: var(--admin-text);"><?= e(trim(($f['nom'] ?? '') . ' ' . ($f['prenom'] ?? '')) ?: 'Client sans nom') ?></div>
                            <div style="font-size: 0.78rem; color: var(--admin-text-muted); display: flex; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
                              <?php if (!empty($f['email'])): ?><a href="mailto:<?= e($f['email']) ?>" style="color: var(--admin-gold);"><?= e($f['email']) ?></a><?php endif; ?>
                              <?php if (!empty($f['telephone'])): ?><span>â€¢</span><a href="tel:<?= e($f['telephone']) ?>"><?= e($f['telephone']) ?></a><?php endif; ?>
                            </div>
                          </td>
                          <td>
                            <span class="badge <?= $isOffre ? 'badge--sale' : 'badge--info' ?>">
                              <?= $isOffre ? admin_icon('building', 12) . ' Offre (Vente/Loc)' : admin_icon('search', 12) . ' Demande' ?>
                            </span>
                            <div style="font-size: 0.8rem; color: var(--admin-text-subtle); margin-top: 4px;"><?= e($f['type_bien'] ?: 'Appartement') ?></div>
                          </td>
                          <td>
                            <div style="display: flex; gap: 4px; flex-direction: column;">
                              <?php if (!empty($f['piece_identite'])): ?><span class="doc-badge"><?= admin_icon('file-text', 11) ?> CNI</span><?php endif; ?>
                              <?php if (!empty($f['signature'])): ?><span class="badge badge--success" style="font-size:0.7rem;padding:2px 6px;"><?= admin_icon('check', 10) ?> SignÃ©</span><?php endif; ?>
                              <?php if (empty($f['piece_identite']) && empty($f['signature'])): ?><span style="font-size:0.75rem;color:var(--admin-text-muted);">Sans piÃ¨ce</span><?php endif; ?>
                            </div>
                          </td>
                          <td onclick="event.stopPropagation()">
                            <select class="form-select" style="padding: 4px 8px; font-size: 0.8rem;" onchange="submitFicheStatus(<?= (int)$f['id'] ?>, this.value, this)">
                              <?php foreach (['Nouveau', 'En cours', 'ValidÃ©', 'TraitÃ©', 'RejetÃ©'] as $stOpt): ?>
                                <option value="<?= e($stOpt) ?>" <?= ($f['statut'] === $stOpt || ($stOpt === 'TraitÃ©' && $f['statut'] === 'Traite')) ? 'selected' : '' ?>><?= e($stOpt) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </td>
                          <td onclick="event.stopPropagation()">
                            <div class="table-actions">
                              <button type="button" class="btn-icon" title="<?= $isUnread ? 'Marquer comme lu' : 'Marquer comme non lu' ?>" onclick="submitToggleRead(<?= (int)$f['id'] ?>, <?= $isUnread ? 1 : 0 ?>, this)">
                                <?= admin_icon($isUnread ? 'check' : 'mail', 15) ?>
                              </button>
                              <a href="<?= e($rowOpenUrl) ?>" class="btn-icon" title="Consulter la fiche client">
                                <?= admin_icon('eye', 16) ?>
                              </a>
                              <button type="button" class="btn-icon btn-icon--danger" title="Supprimer la fiche" onclick="submitDeleteFiche(<?= (int)$f['id'] ?>)">
                                <?= admin_icon('trash', 16) ?>
                              </button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </form>

            <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>" id="single-status-form" style="display:none;">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="id" id="st-id" value="">
              <input type="hidden" name="statut" id="st-val" value="">
            </form>
            <form method="POST" action="<?= e($selfUrl) ?>" id="single-read-form" style="display:none;">
              <input type="hidden" name="action" value="toggle_read">
              <input type="hidden" name="id" id="rd-id" value="">
              <input type="hidden" name="new_read" id="rd-val" value="">
            </form>
            <form method="POST" action="<?= e($selfUrl) ?>" id="single-delete-form" style="display:none;">
              <input type="hidden" name="action" value="delete_one">
              <input type="hidden" name="id" id="dl-id" value="">
            </form>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <!-- Volet latÃ©ral (Drawer) Fiche Client rendu en PHP + HTML brut -->
  <?php if ($drawerFiche):
    $fName = trim(($drawerFiche['nom'] ?? '') . ' ' . ($drawerFiche['prenom'] ?? '')) ?: 'Client';
    $pieceUrl = !empty($drawerFiche['piece_identite']) ? asset_url((string)$drawerFiche['piece_identite']) : '';
    $pieceIsPdf = ($pieceUrl !== '' && str_ends_with(strtolower($pieceUrl), '.pdf'));
    $sigUrl = !empty($drawerFiche['signature']) ? asset_url((string)$drawerFiche['signature']) : '';
  ?>
    <a href="<?= e($selfUrl) ?>" class="sidebar-backdrop is-open" style="z-index: 140;" aria-label="Fermer"></a>
    <div class="admin-drawer is-open" id="fiche-drawer">
      <div class="drawer-header">
        <div>
          <h3 class="drawer-title">Fiche client : <?= e($drawerFiche['reference'] ?: ('IML-DOS-' . $drawerFiche['id'])) ?></h3>
          <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">ConstituÃ©e le <?= format_datetime($drawerFiche['date_creation']) ?></p>
        </div>
        <a href="<?= e($selfUrl) ?>" class="btn-icon" aria-label="Fermer"><?= admin_icon('close', 18) ?></a>
      </div>

      <div class="drawer-body">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid var(--admin-border-subtle);">
          <div class="user-avatar" style="width: 52px; height: 52px; font-size: 1.2rem; background-color: var(--admin-gold); color: #FFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
            <?= e(mb_strtoupper(mb_substr($drawerFiche['nom'] ?: 'C', 0, 1, 'UTF-8'), 'UTF-8')) ?>
          </div>
          <div style="flex: 1;">
            <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--admin-text);"><?= e($fName) ?></h4>
            <div style="display: flex; gap: 12px; margin-top: 4px; font-size: 0.85rem; flex-wrap: wrap;">
              <?php if (!empty($drawerFiche['email'])): ?><a href="mailto:<?= e($drawerFiche['email']) ?>" style="color: var(--admin-gold); font-weight: 500;"><?= e($drawerFiche['email']) ?></a><?php endif; ?>
              <?php if (!empty($drawerFiche['telephone'])): ?><span>â€¢</span><a href="tel:<?= e($drawerFiche['telephone']) ?>" style="color: var(--admin-text-muted); font-weight: 500;"><?= e($drawerFiche['telephone']) ?></a><?php endif; ?>
            </div>
          </div>
        </div>

        <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--admin-surface-alt); border-radius: var(--radius-sm);">
          <span style="font-weight: 600; font-size: 0.86rem;">Statut du dossier :</span>
          <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>" style="margin:0;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="<?= (int)$drawerFiche['id'] ?>">
            <select name="statut" class="form-select" style="padding: 6px 12px; font-weight: 600;" onchange="this.form.submit()">
              <?php foreach (['Nouveau', 'En cours', 'ValidÃ©', 'TraitÃ©', 'RejetÃ©'] as $sv): ?>
                <option value="<?= e($sv) ?>" <?= ($drawerFiche['statut'] === $sv || ($sv === 'TraitÃ©' && $drawerFiche['statut'] === 'Traite')) ? 'selected' : '' ?>><?= e($sv) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <div style="margin-bottom: 24px; padding: 18px; background-color: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
          <h4 style="margin: 0 0 14px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <?= admin_icon('building', 16) ?> Objet de la dÃ©marche
          </h4>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.88rem;">
            <div><span style="color: var(--admin-text-muted);">Nature de l'opÃ©ration :</span> <strong><?= e($drawerFiche['transaction'] ?: 'Demande') ?></strong></div>
            <div><span style="color: var(--admin-text-muted);">Type de bien :</span> <strong><?= e($drawerFiche['type_bien'] ?: 'Appartement') ?></strong></div>
            <div><span style="color: var(--admin-text-muted);">NumÃ©ro de dossier :</span> <strong><?= e($drawerFiche['reference'] ?: 'â€”') ?></strong></div>
            <div><span style="color: var(--admin-text-muted);">Date de constitution :</span> <strong><?= format_date($drawerFiche['date_creation']) ?></strong></div>
          </div>
        </div>

        <div style="margin-bottom: 24px;">
          <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <?= admin_icon('file-text', 16) ?> PiÃ¨ce d'identitÃ© officielle
          </h4>
          <?php if ($pieceUrl !== ''): ?>
            <div class="doc-inspect-card">
              <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <?= admin_icon('file-text', 24) ?>
                  <strong style="font-size: 0.9rem;"><?= e(basename($pieceUrl)) ?></strong>
                </div>
                <?php if (!$pieceIsPdf): ?>
                  <button type="button" class="btn btn-outline btn-sm btn-zoom-doc" data-img="<?= e($pieceUrl) ?>" data-caption="PiÃ¨ce d'identitÃ© â€” <?= e($fName) ?>">
                    <?= admin_icon('eye', 14) ?> Agrandir
                  </button>
                <?php else: ?>
                  <a href="<?= e($pieceUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                    <?= admin_icon('download', 14) ?> TÃ©lÃ©charger
                  </a>
                <?php endif; ?>
              </div>
              <?php if (!$pieceIsPdf): ?>
                <div class="doc-inspect-preview btn-zoom-doc" data-img="<?= e($pieceUrl) ?>" data-caption="PiÃ¨ce d'identitÃ© â€” <?= e($fName) ?>">
                  <img src="<?= e($pieceUrl) ?>" alt="PiÃ¨ce d'identitÃ©" class="doc-inspect-img">
                  <div class="doc-inspect-overlay"><?= admin_icon('search', 18) ?> <span>Cliquer pour inspecter en plein Ã©cran</span></div>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Aucune piÃ¨ce d'identitÃ© enregistrÃ©e.</p>
          <?php endif; ?>
        </div>

        <div style="margin-bottom: 24px;">
          <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <?= admin_icon('check', 16) ?> Engagement officiel &amp; Signature Ã©lectronique
          </h4>
          <div style="padding: 12px 14px; background-color: rgba(181, 154, 106, 0.08); border-left: 3px solid var(--admin-gold); border-radius: var(--radius-xs); font-size: 0.84rem; line-height: 1.5; margin-bottom: 10px;">
            Â« Ceci constitue un engagement officiel. En cas de transaction, le client est tenu d'honorer les frais de l'agence conformÃ©ment aux dispositions rÃ©glementaires en vigueur. Â»
          </div>
          <div style="background: #FFFFFF; border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
            <?php if ($sigUrl !== ''): ?>
              <img src="<?= e($sigUrl) ?>" alt="Signature Ã©lectronique" style="max-height: 110px; max-width: 100%; display: inline-block; object-fit: contain;">
              <p style="margin: 8px 0 0; font-size: 0.75rem; color: var(--admin-text-muted);">Signature Ã©lectronique manuscrite certifiÃ©e â€” Recueillie le <?= format_datetime($drawerFiche['date_creation']) ?></p>
            <?php else: ?>
              <p style="margin: 0; font-size: 0.85rem; color: var(--admin-text-muted);">Signature non disponible.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="drawer-footer">
        <button type="button" class="btn btn-danger-outline btn-sm" onclick="submitDeleteFiche(<?= (int)$drawerFiche['id'] ?>)">
          <?= admin_icon('trash', 14) ?> Supprimer
        </button>

        <div style="display: flex; gap: 8px;">
          <a href="<?= $b ?>/admin/imprimer.php?source=fiche&id=<?= (int)$drawerFiche['id'] ?>" target="_blank" class="btn btn-outline btn-sm">
            <?= admin_icon('printer', 14) ?> Imprimer
          </a>
          <?php if (!empty($drawerFiche['telephone'])): ?>
            <a href="tel:<?= e($drawerFiche['telephone']) ?>" class="btn btn-outline btn-sm"><?= admin_icon('phone', 14) ?> Appeler</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php
  $importModalTitle = 'Importer des fiches client';
  $importActionUrl = $b . '/admin/fiches-client.php';
  include __DIR__ . '/includes/import_modal.php';
  include __DIR__ . '/includes/footer_scripts.php';
  ?>

  <script>
    function submitFicheStatus(id, val, selectEl) {
      var formData = new FormData();
      formData.append('action', 'update_status');
      formData.append('id', id);
      formData.append('statut', val);
      formData.append('ajax', '1');

      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data && data.success) {
          if (window.showAdminToast) window.showAdminToast('Statut du dossier mis Ã  jour : ' + val);
          if (data.counts) {
            window.updateSidebarBadge('demandes', (data.counts.demandes || 0) + (data.counts.fiches || 0));
            window.updateSidebarBadge('fiches-client', data.counts.fiches);
          }
        }
      })
      .catch(function() {
        document.getElementById('st-id').value = id;
        document.getElementById('st-val').value = val;
        document.getElementById('single-status-form').submit();
      });
    }

    function submitToggleRead(id, nr, btn) {
      var formData = new FormData();
      formData.append('action', 'toggle_read');
      formData.append('id', id);
      formData.append('new_read', nr);
      formData.append('ajax', '1');

      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data && data.success) {
          var row = document.getElementById('row-fiche-' + id) || btn?.closest('tr');
          if (row) {
            var isNowRead = (data.new_read === 1);
            row.classList.toggle('is-unread', !isNowRead);
            row.style.fontWeight = isNowRead ? 'normal' : '600';
            var dot = row.querySelector('.unread-dot');
            if (dot) dot.style.display = isNowRead ? 'none' : 'inline-block';
            if (btn) {
              btn.setAttribute('title', isNowRead ? 'Marquer comme non lu' : 'Marquer comme lu');
              btn.innerHTML = isNowRead
                ? '<svg class="admin-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>'
                : '<svg class="admin-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
              btn.setAttribute('onclick', "submitToggleRead(" + id + ", " + (isNowRead ? 0 : 1) + ", this)");
            }
          }
          if (data.counts) {
            window.updateSidebarBadge('demandes', (data.counts.demandes || 0) + (data.counts.fiches || 0));
            window.updateSidebarBadge('fiches-client', data.counts.fiches);
          }
          if (window.showAdminToast) {
            window.showAdminToast(data.new_read === 1 ? 'Fiche client marquÃ©e comme lue' : 'Fiche client marquÃ©e comme non lue');
          }
        }
      })
      .catch(function() {
        document.getElementById('rd-id').value = id;
        document.getElementById('rd-val').value = nr;
        document.getElementById('single-read-form').submit();
      });
    }

    function submitDeleteFiche(id) {
      if (!confirm('Supprimer dÃ©finitivement cette fiche client ?')) return;
      document.getElementById('dl-id').value = id;
      document.getElementById('single-delete-form').submit();
    }
    (function() {
      var wrap = document.getElementById('fiches-table-wrap');
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
        if (labelSpan) labelSpan.textContent = active ? 'Annuler la sÃ©lection' : 'SÃ©lectionner';
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
    })();
  </script>
</body>
</html>

