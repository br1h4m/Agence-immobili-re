<?php
/**
 * admin/demandes.php - Demandes, Estimations & Fiches client unifiÃ©es (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/xlsx.php';

$currentUser = require_admin($conn);
$activePage = 'demandes';
$pageHeading = 'Demandes & Estimations';
$breadcrumbs = [['label' => 'Demandes']];
$b = BASE_URL;

// -------------------------------------------------------------------------
// 1. TÃ©lÃ©chargement du modÃ¨le d'import CSV / Excel
// -------------------------------------------------------------------------
if (isset($_GET['template'])) {
    $fmt = $_GET['template'] === 'xlsx' ? 'xlsx' : 'csv';
    $headers = ['type', 'nom', 'email', 'telephone', 'sujet', 'message', 'statut'];
    $rows = [
        ['Contact', 'Salim Haddad', 'salim.haddad@gmail.com', '0550112233', 'Demande de visite appartement F4', 'Souhaite visiter le bien cette semaine.', 'Nouveau'],
        ['Estimation', 'Kamel Ouali', 'kamel.ouali@yahoo.fr', '0661445566', 'Estimation villa Ã  BÃ©jaÃ¯a', 'Villa R+2 de 240 mÂ² Ã  Tichy.', 'En cours']
    ];
    if ($fmt === 'xlsx') {
        $bin = xlsx_build_binary($headers, $rows, 'Modele Demandes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="modele_demandes.xlsx"');
        echo $bin;
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modele_demandes.csv"');
        echo csv_build_string($headers, $rows);
    }
    exit;
}

// ParamÃ¨tres de filtre conservÃ©s dans les liens
$search = trim((string)($_GET['search'] ?? ''));
$type   = trim((string)($_GET['type'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$openId = (int)($_GET['id'] ?? 0);
$openType = trim((string)($_GET['open_type'] ?? ''));

$baseFilterQs = http_build_query(array_filter(['search' => $search, 'type' => $type, 'status' => $status]));
$selfUrl = $b . '/admin/demandes.php' . ($baseFilterQs ? '?' . $baseFilterQs : '');

// -------------------------------------------------------------------------
// 2. Traitements POST
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || isset($_POST['ajax']);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $src = (string)($_POST['source'] ?? 'demande');
        $id = (int)($_POST['id'] ?? 0);
        $newStatut = trim((string)($_POST['statut'] ?? ''));
        if ($id > 0 && $newStatut !== '') {
            $tbl = ($src === 'fiche') ? 'fiches_client' : 'demandes';
            $stmt = $conn->prepare("UPDATE `$tbl` SET `statut` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $newStatut, $id);
            $stmt->execute();
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'statut' => $newStatut, 'counts' => get_unread_counts($conn)]);
                exit;
            }
            set_flash("Statut mis Ã  jour : {$newStatut}");
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
        $src = (string)($_POST['source'] ?? 'demande');
        $id = (int)($_POST['id'] ?? 0);
        $newRead = (int)($_POST['new_read'] ?? 1) ? 1 : 0;
        if ($id > 0) {
            $tbl = ($src === 'fiche') ? 'fiches_client' : 'demandes';
            $stmt = $conn->prepare("UPDATE `$tbl` SET `is_read` = ? WHERE `id` = ?");
            $stmt->bind_param("ii", $newRead, $id);
            $stmt->execute();
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'  => true,
                    'new_read' => $newRead,
                    'id'       => $id,
                    'source'   => $src,
                    'counts'   => get_unread_counts($conn)
                ]);
                exit;
            }
            set_flash($newRead ? 'MarquÃ© comme lu.' : 'MarquÃ© comme non lu.');
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
        $src = (string)($_POST['source'] ?? 'demande');
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $tbl = ($src === 'fiche') ? 'fiches_client' : 'demandes';
            $stmt = $conn->prepare("DELETE FROM `$tbl` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            set_flash('Demande supprimÃ©e avec succÃ¨s.');
        }
        header('Location: ' . $selfUrl);
        exit;
    }

    if (in_array($action, ['bulk_delete', 'bulk_mark_read', 'bulk_export_csv', 'bulk_export_xlsx'], true)) {
        $uids = (array)($_POST['uids'] ?? []);
        $demIds = [];
        $ficIds = [];
        foreach ($uids as $uid) {
            if (str_starts_with((string)$uid, 'fiche:')) {
                $val = (int)substr((string)$uid, 6);
                if ($val > 0) $ficIds[] = $val;
            } elseif (str_starts_with((string)$uid, 'demande:')) {
                $val = (int)substr((string)$uid, 8);
                if ($val > 0) $demIds[] = $val;
            }
        }

        if ($action === 'bulk_delete') {
            if (!empty($demIds)) $conn->query("DELETE FROM `demandes` WHERE `id` IN (" . implode(',', $demIds) . ")");
            if (!empty($ficIds)) $conn->query("DELETE FROM `fiches_client` WHERE `id` IN (" . implode(',', $ficIds) . ")");
            $c = count($demIds) + count($ficIds);
            set_flash("$c Ã©lÃ©ment(s) supprimÃ©(s).");
            header('Location: ' . $selfUrl);
            exit;
        }

        if ($action === 'bulk_mark_read') {
            if (!empty($demIds)) $conn->query("UPDATE `demandes` SET `is_read` = 1 WHERE `id` IN (" . implode(',', $demIds) . ")");
            if (!empty($ficIds)) $conn->query("UPDATE `fiches_client` SET `is_read` = 1 WHERE `id` IN (" . implode(',', $ficIds) . ")");
            $c = count($demIds) + count($ficIds);
            set_flash("$c Ã©lÃ©ment(s) marquÃ©(s) comme lu(s).");
            header('Location: ' . $selfUrl);
            exit;
        }

        // Export sÃ©lection
        $expHeaders = ['Source', 'ID / RÃ©f', 'Type', 'Client', 'Email', 'TÃ©lÃ©phone', 'Sujet', 'Message', 'Statut', 'Lu', 'Date'];
        $expRows = [];
        if (!empty($demIds)) {
            $resD = $conn->query("SELECT * FROM `demandes` WHERE `id` IN (" . implode(',', $demIds) . ")");
            while ($r = $resD->fetch_assoc()) {
                $expRows[] = ['Demande', $r['id'], $r['type'], $r['nom'], $r['email'], $r['telephone'], $r['sujet'], $r['message'], $r['statut'], $r['is_read'] ? 'Oui' : 'Non', $r['date_creation']];
            }
        }
        if (!empty($ficIds)) {
            $resF = $conn->query("SELECT * FROM `fiches_client` WHERE `id` IN (" . implode(',', $ficIds) . ")");
            while ($r = $resF->fetch_assoc()) {
                $expRows[] = ['Fiche client', $r['reference'], 'Fiche client', trim($r['nom'] . ' ' . $r['prenom']), $r['email'], $r['telephone'], $r['transaction'] . ' - ' . $r['type_bien'], '', $r['statut'], $r['is_read'] ? 'Oui' : 'Non', $r['date_creation']];
            }
        }
        $dateSuffix = date('Y-m-d');
        if ($action === 'bulk_export_csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="demandes_selection_' . $dateSuffix . '.csv"');
            echo csv_build_string($expHeaders, $expRows);
        } else {
            $bin = xlsx_build_binary($expHeaders, $expRows, 'Demandes sÃ©lection');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="demandes_selection_' . $dateSuffix . '.xlsx"');
            echo $bin;
        }
        exit;
    }

    if ($action === 'import_file') {
        try {
            $parsedRows = spreadsheet_parse_upload($_FILES['import_file'] ?? []);
            if (count($parsedRows) < 2) throw new RuntimeException('Le fichier est vide ou sans donnÃ©es.');
            $rawHeaders = array_shift($parsedRows);
            $headers = array_map(fn($h) => normalize_import_header((string)$h), $rawHeaders);

            $stmtCheck = $conn->prepare("SELECT `id` FROM `demandes` WHERE `nom` = ? AND (`email` = ? OR `telephone` = ?) AND COALESCE(`sujet`, '') = ? LIMIT 1");
            $stmtIns = $conn->prepare("INSERT INTO `demandes` (`type`, `nom`, `email`, `telephone`, `sujet`, `message`, `statut`, `is_read`) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");

            $imported = 0; $errCount = 0;
            foreach ($parsedRows as $row) {
                $d = [];
                foreach ($headers as $i => $h) { if ($h !== '') $d[$h] = trim((string)($row[$i] ?? '')); }
                $tpRaw = strtolower($d['type'] ?? 'contact');
                $tp = str_contains($tpRaw, 'estim') ? 'Estimation' : 'Contact';
                $nom = $d['nom'] ?? $d['client'] ?? '';
                $email = $d['email'] ?? '';
                $tel = $d['telephone'] ?? $d['phone'] ?? $d['tel'] ?? '';
                $sujet = ($d['sujet'] ?? $d['subject'] ?? '') ?: 'Demande importÃ©e';
                $msg = $d['message'] ?? '';
                $st = ($d['statut'] ?? $d['status'] ?? '') ?: 'Nouveau';
                if ($nom === '' || ($email === '' && $tel === '')) { $errCount++; continue; }
                $stmtCheck->bind_param("ssss", $nom, $email, $tel, $sujet);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) { $errCount++; continue; }
                $stmtIns->bind_param("sssssss", $tp, $nom, $email, $tel, $sujet, $msg, $st);
                $stmtIns->execute();
                $imported++;
            }
            set_flash($imported > 0 ? "$imported demande(s) importÃ©e(s) avec succÃ¨s." : "Aucune demande importÃ©e ($errCount ignorÃ©e(s)).", $imported > 0 ? 'success' : 'error');
        } catch (Throwable $ex) {
            set_flash($ex->getMessage(), 'error');
        }
        header('Location: ' . $b . '/admin/demandes.php');
        exit;
    }
}

// -------------------------------------------------------------------------
// 3. Auto-marquer comme lu si tiroir ouvert via GET ?open_type=...&id=...
// -------------------------------------------------------------------------
$drawerItem = null;
if ($openId > 0) {
    if ($openType === 'fiche') {
        $conn->query("UPDATE `fiches_client` SET `is_read` = 1 WHERE `id` = " . $openId);
        $r = $conn->query("SELECT * FROM `fiches_client` WHERE `id` = " . $openId . " LIMIT 1")->fetch_assoc();
        if ($r) {
            $drawerItem = $r;
            $drawerItem['_source'] = 'fiche';
        }
    } else {
        $conn->query("UPDATE `demandes` SET `is_read` = 1 WHERE `id` = " . $openId);
        $r = $conn->query("SELECT * FROM `demandes` WHERE `id` = " . $openId . " LIMIT 1")->fetch_assoc();
        if ($r) {
            $drawerItem = $r;
            $drawerItem['_source'] = 'demande';
        }
    }
}

// -------------------------------------------------------------------------
// 4. Chargement & fusion des demandes + fiches_client
// -------------------------------------------------------------------------
$items = [];

if ($type !== 'Fiche client') {
    $resD = $conn->query("SELECT * FROM `demandes` ORDER BY `date_creation` DESC, `id` DESC");
    while ($r = $resD->fetch_assoc()) {
        $items[] = [
            'uid'           => 'demande:' . $r['id'],
            'source'        => 'demande',
            'id'            => (int)$r['id'],
            'type'          => $r['type'] ?: 'Contact',
            'reference'     => null,
            'nom'           => $r['nom'] ?: 'Anonyme',
            'email'         => $r['email'] ?? '',
            'telephone'     => $r['telephone'] ?? '',
            'sujet'         => $r['sujet'] ?: ($r['message'] ?: 'â€”'),
            'message'       => $r['message'] ?? '',
            'statut'        => $r['statut'] ?: 'Nouveau',
            'is_read'       => (int)($r['is_read'] ?? 0),
            'date_creation' => $r['date_creation'],
            'piece_identite'=> null,
            'signature'     => null,
        ];
    }
}

if ($type === '' || $type === 'Fiche client') {
    $resF = $conn->query("SELECT * FROM `fiches_client` ORDER BY `date_creation` DESC, `id` DESC");
    while ($r = $resF->fetch_assoc()) {
        $fullName = trim(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? '')) ?: 'Client';
        $items[] = [
            'uid'           => 'fiche:' . $r['id'],
            'source'        => 'fiche',
            'id'            => (int)$r['id'],
            'type'          => 'Fiche client',
            'reference'     => $r['reference'],
            'transaction'   => $r['transaction'] ?: 'Demande',
            'type_bien'     => $r['type_bien'] ?: 'Bien',
            'nom'           => $fullName,
            'email'         => $r['email'] ?? '',
            'telephone'     => $r['telephone'] ?? '',
            'sujet'         => 'Dossier ' . ($r['reference'] ?? '') . ' : ' . ($r['transaction'] ?: 'Demande') . ' (' . ($r['type_bien'] ?: 'Bien') . ')',
            'message'       => 'Dossier officiel â€” OpÃ©ration : ' . ($r['transaction'] ?: 'Demande') . ' | Bien : ' . ($r['type_bien'] ?: 'Non spÃ©cifiÃ©'),
            'statut'        => $r['statut'] ?: 'Nouveau',
            'is_read'       => (int)($r['is_read'] ?? 0),
            'date_creation' => $r['date_creation'],
            'piece_identite'=> $r['piece_identite'] ?? null,
            'signature'     => $r['signature'] ?? null,
        ];
    }
}

// Filtrage PHP
$filtered = array_filter($items, function ($item) use ($type, $status, $search) {
    if ($type !== '' && $item['type'] !== $type) return false;
    if ($status !== '') {
        if ($status === 'Traite') {
            if (!in_array($item['statut'], ['Traite', 'TraitÃ©'], true)) return false;
        } elseif ($item['statut'] !== $status) {
            return false;
        }
    }
    if ($search !== '') {
        $q = mb_strtolower($search, 'UTF-8');
        $hay = mb_strtolower(implode(' ', [$item['nom'], $item['email'], $item['telephone'], $item['sujet'], $item['reference'] ?? '', $item['type_bien'] ?? '']), 'UTF-8');
        if (!str_contains($hay, $q)) return false;
    }
    return true;
});

usort($filtered, fn($a, $b) => strtotime((string)$b['date_creation']) <=> strtotime((string)$a['date_creation']));
$filtered = array_values($filtered);

// Export global (GET ?export=xlsx|csv)
if (isset($_GET['export'])) {
    $fmt = $_GET['export'] === 'csv' ? 'csv' : 'xlsx';
    $expHeaders = ['Type', 'RÃ©f / ID', 'Client', 'Email', 'TÃ©lÃ©phone', 'Sujet', 'Message', 'Statut', 'Lu', 'Date crÃ©ation'];
    $expRows = [];
    foreach ($filtered as $it) {
        $expRows[] = [$it['type'], $it['reference'] ?: $it['id'], $it['nom'], $it['email'], $it['telephone'], $it['sujet'], $it['message'], $it['statut'], $it['is_read'] ? 'Oui' : 'Non', $it['date_creation']];
    }
    $dateSuffix = date('Y-m-d');
    if ($fmt === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="demandes_immolode_' . $dateSuffix . '.csv"');
        echo csv_build_string($expHeaders, $expRows);
    } else {
        $bin = xlsx_build_binary($expHeaders, $expRows, 'Demandes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="demandes_immolode_' . $dateSuffix . '.xlsx"');
        echo $bin;
    }
    exit;
}

$exportPrefix = $b . '/admin/demandes.php?' . ($baseFilterQs ? $baseFilterQs . '&' : '');
$openLinkPrefix = $b . '/admin/demandes.php?' . ($baseFilterQs ? $baseFilterQs . '&' : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Demandes &amp; Estimations â€” Administration Imolode</title>
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
        <!-- Encart d'accÃ¨s direct + Outils -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; background: rgba(181, 154, 106, 0.08); border-left: 3px solid var(--admin-gold); border-radius: var(--radius-xs); margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
          <div style="font-size: 0.88rem; color: var(--admin-text);">
            <strong>Dossiers officiels :</strong> Consultez ici toutes les prises de contact, estimations et dossiers clients.
          </div>
          <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline btn-sm" id="btn-open-import">
              <?= admin_icon('upload', 14) ?> <span>Importer CSV / Excel</span>
            </button>
            <a href="<?= e($exportPrefix) ?>export=xlsx" class="btn btn-outline btn-sm">
              <?= admin_icon('download', 14) ?> <span>Export Excel</span>
            </a>
            <a href="<?= e($exportPrefix) ?>export=csv" class="btn btn-outline btn-sm">
              <?= admin_icon('download', 14) ?> <span>Export CSV</span>
            </a>
            <a href="<?= $b ?>/admin/fiches-client.php" class="btn btn-primary btn-sm">
              <?= admin_icon('file-text', 14) ?> <span>Consulter les fiches client</span>
            </a>
            <button type="button" class="btn btn-outline btn-sm" id="btn-toggle-select">
              <?= admin_icon('check', 14) ?> <span id="btn-select-label">SÃ©lectionner</span>
            </button>
          </div>
        </div>

        <!-- Filtres PHP GET -->
        <form method="GET" action="<?= $b ?>/admin/demandes.php" class="filters-bar" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px;">
          <div class="search-input-wrap">
            <span class="admin-icon"><?= admin_icon('search', 16) ?></span>
            <input type="text" name="search" placeholder="Rechercher par nom, email, tÃ©lÃ©phone, sujet..." value="<?= e($search) ?>">
          </div>

          <div class="filters-group">
            <select class="form-select" name="type" onchange="this.form.submit()">
              <option value="">Tous les types</option>
              <option value="Fiche client" <?= $type === 'Fiche client' ? 'selected' : '' ?>>Fiches client (avec CNI &amp; Signature)</option>
              <option value="Estimation" <?= $type === 'Estimation' ? 'selected' : '' ?>>Demandes d'estimation</option>
              <option value="Contact" <?= $type === 'Contact' ? 'selected' : '' ?>>Messages de contact</option>
            </select>

            <select class="form-select" name="status" onchange="this.form.submit()">
              <option value="">Tous les statuts</option>
              <?php foreach (['Nouveau' => 'Nouveau', 'En cours' => 'En cours', 'ValidÃ©' => 'ValidÃ©', 'Traite' => 'TraitÃ©', 'RejetÃ©' => 'RejetÃ©'] as $stVal => $stLbl): ?>
                <option value="<?= e($stVal) ?>" <?= $status === $stVal ? 'selected' : '' ?>><?= e($stLbl) ?></option>
              <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="<?= $b ?>/admin/demandes.php" class="btn btn-outline btn-sm">Effacer</a>
          </div>
        </form>

        <!-- RÃ©sultats -->
        <?php if (empty($filtered)): ?>
          <div class="card">
            <div class="empty-state">
              <div class="empty-state-icon"><?= admin_icon('mail', 28) ?></div>
              <h3 class="empty-state-title">Aucune demande trouvÃ©e</h3>
              <p class="empty-state-text">Aucun message ne correspond Ã  vos critÃ¨res de recherche actuels.</p>
            </div>
          </div>
        <?php else: ?>
          <div id="demandes-table-wrap">
            <form method="POST" action="<?= e($selfUrl) ?>" id="bulk-form">
              <input type="hidden" name="action" id="bulk-action-input" value="">

              <div class="bulk-actions-bar" id="bulk-bar">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="bulk-badge" id="bulk-count">0</span>
                  <span style="font-weight: 600;">demande(s) sÃ©lectionnÃ©e(s)</span>
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
                  <button type="submit" class="btn btn-sm" style="background: var(--admin-danger); color: #fff; border: none;" onclick="document.getElementById('bulk-action-input').value='bulk_delete'; return confirm('Supprimer dÃ©finitivement la sÃ©lection ?');">
                    <?= admin_icon('trash', 14) ?> <span>Supprimer</span>
                  </button>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h2 class="card-title">Demandes &amp; Dossiers enregistrÃ©s (<?= count($filtered) ?>)</h2>
                </div>
                <div class="table-responsive">
                  <table class="admin-table">
                    <thead>
                      <tr>
                        <th class="select-col"><input type="checkbox" id="check-all-rows" title="Tout sÃ©lectionner"></th>
                        <th>Date &amp; RÃ©f</th>
                        <th>Type de demande</th>
                        <th>Client</th>
                        <th>Sujet &amp; Message</th>
                        <th>Statut</th>
                        <th style="width: 120px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($filtered as $item):
                        $isUnread = ((int)$item['is_read'] === 0);
                        $rowOpenUrl = $openLinkPrefix . 'open_type=' . urlencode($item['source']) . '&id=' . $item['id'];
                      ?>
                        <tr id="row-<?= e($item['uid']) ?>" class="<?= $isUnread ? 'is-unread' : '' ?>" style="<?= $isUnread ? 'font-weight:600;' : '' ?>" onclick="if(!document.getElementById('demandes-table-wrap').classList.contains('is-select-mode')) window.location.href='<?= e($rowOpenUrl) ?>'">
                          <td class="select-col" onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-checkbox" name="uids[]" value="<?= e($item['uid']) ?>">
                          </td>
                          <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                              <span class="unread-dot" style="<?= $isUnread ? '' : 'display:none;' ?>" title="Non lu"></span>
                              <div>
                                <div style="font-weight: 600; color: var(--admin-text); font-size: 0.85rem;"><?= format_date($item['date_creation']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--admin-text-subtle);">
                                  <?php if (!empty($item['reference'])): ?>
                                    <span style="font-family: monospace; color: var(--admin-gold); font-weight: 600;"><?= e($item['reference']) ?></span>
                                  <?php else: ?>
                                    <?= date('H:i', strtotime((string)$item['date_creation'])) ?>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </div>
                          </td>
                          <td>
                            <?php if ($item['type'] === 'Fiche client'): ?>
                              <span class="badge <?= ($item['transaction'] === 'Offre') ? 'badge--sale' : 'badge--info' ?>"><?= admin_icon('file-text', 12) ?> Dossier client (<?= e($item['transaction']) ?>)</span>
                              <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;">
                                <?php if (!empty($item['piece_identite'])): ?><span class="doc-badge"><?= admin_icon('file-text', 11) ?> CNI</span><?php endif; ?>
                                <?php if (!empty($item['signature'])): ?><span class="badge badge--success" style="font-size:0.7rem;padding:2px 6px;"><?= admin_icon('check', 10) ?> SignÃ©</span><?php endif; ?>
                              </div>
                            <?php elseif ($item['type'] === 'Estimation'): ?>
                              <span class="badge badge--sale"><?= admin_icon('building', 12) ?> Estimation</span>
                            <?php else: ?>
                              <span class="badge badge--info"><?= admin_icon('mail', 12) ?> Contact</span>
                            <?php endif; ?>
                          </td>
                          <td onclick="event.stopPropagation()">
                            <div style="font-weight: 600; color: var(--admin-text);"><?= e($item['nom']) ?></div>
                            <div style="font-size: 0.78rem; color: var(--admin-text-muted); display: flex; gap: 6px; flex-wrap: wrap; margin-top: 2px;">
                              <?php if ($item['email'] !== ''): ?><a href="mailto:<?= e($item['email']) ?>" style="color: var(--admin-gold);"><?= e($item['email']) ?></a><?php endif; ?>
                              <?php if ($item['telephone'] !== ''): ?><span>â€¢</span><a href="tel:<?= e($item['telephone']) ?>"><?= e($item['telephone']) ?></a><?php endif; ?>
                            </div>
                          </td>
                          <td>
                            <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500;"><?= e($item['sujet']) ?></div>
                            <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.78rem; color: var(--admin-text-subtle);"><?= e($item['message']) ?></div>
                          </td>
                          <td onclick="event.stopPropagation()">
                            <select class="form-select" style="padding: 4px 8px; font-size: 0.8rem;" onchange="submitDemandeStatus('<?= e($item['source']) ?>', <?= (int)$item['id'] ?>, this.value, this)">
                              <?php foreach (['Nouveau' => 'Nouveau', 'En cours' => 'En cours', 'ValidÃ©' => 'ValidÃ©', 'Traite' => 'TraitÃ©', 'RejetÃ©' => 'RejetÃ©'] as $stVal => $stLbl): ?>
                                <option value="<?= e($stVal) ?>" <?= ($item['statut'] === $stVal || ($stVal === 'Traite' && $item['statut'] === 'TraitÃ©')) ? 'selected' : '' ?>><?= e($stLbl) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </td>
                          <td onclick="event.stopPropagation()">
                            <div class="table-actions">
                              <button type="button" class="btn-icon btn-toggle-read" title="<?= $isUnread ? 'Marquer comme lu' : 'Marquer comme non lu' ?>" onclick="submitToggleRead('<?= e($item['source']) ?>', <?= (int)$item['id'] ?>, <?= $isUnread ? 1 : 0 ?>, this)">
                                <?= admin_icon($isUnread ? 'check' : 'mail', 15) ?>
                              </button>
                              <a href="<?= e($rowOpenUrl) ?>" class="btn-icon" title="Consulter les dÃ©tails">
                                <?= admin_icon('eye', 16) ?>
                              </a>
                              <button type="button" class="btn-icon btn-icon--danger" title="Supprimer" onclick="submitDeleteDemande('<?= e($item['source']) ?>', <?= (int)$item['id'] ?>)">
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

            <!-- Formulaires POST isolÃ©s -->
            <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>" id="single-status-form" style="display:none;">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="source" id="st-src" value="">
              <input type="hidden" name="id" id="st-id" value="">
              <input type="hidden" name="statut" id="st-val" value="">
            </form>
            <form method="POST" action="<?= e($selfUrl) ?>" id="single-read-form" style="display:none;">
              <input type="hidden" name="action" value="toggle_read">
              <input type="hidden" name="source" id="rd-src" value="">
              <input type="hidden" name="id" id="rd-id" value="">
              <input type="hidden" name="new_read" id="rd-val" value="">
            </form>
            <form method="POST" action="<?= e($selfUrl) ?>" id="single-delete-form" style="display:none;">
              <input type="hidden" name="action" value="delete_one">
              <input type="hidden" name="source" id="dl-src" value="">
              <input type="hidden" name="id" id="dl-id" value="">
            </form>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <!-- Volet latÃ©ral (Drawer) rendu en HTML + PHP quand une demande est ouverte via GET ?id=... -->
  <?php if ($drawerItem):
    $dSrc = $drawerItem['_source'];
    $isFiche = ($dSrc === 'fiche');
    $isEst = (!$isFiche && ($drawerItem['type'] ?? '') === 'Estimation');
    $dName = $isFiche ? trim(($drawerItem['nom'] ?? '') . ' ' . ($drawerItem['prenom'] ?? '')) : ($drawerItem['nom'] ?? 'Anonyme');
    $dEmail = $drawerItem['email'] ?? '';
    $dTel = $drawerItem['telephone'] ?? '';
    $dStatus = $drawerItem['statut'] ?? 'Nouveau';
    $dTitle = $isFiche ? ('Dossier ' . ($drawerItem['reference'] ?? '') . ' : ' . ($drawerItem['transaction'] ?? 'Demande')) : ($drawerItem['sujet'] ?? 'Demande');
    $estDetails = $isEst ? (json_decode($drawerItem['details_bien'] ?? '{}', true) ?: []) : [];
    $pieceUrl = ($isFiche && !empty($drawerItem['piece_identite'])) ? asset_url((string)$drawerItem['piece_identite']) : '';
    $pieceIsPdf = ($pieceUrl !== '' && str_ends_with(strtolower($pieceUrl), '.pdf'));
    $sigUrl = ($isFiche && !empty($drawerItem['signature'])) ? asset_url((string)$drawerItem['signature']) : '';
  ?>
    <a href="<?= e($selfUrl) ?>" class="sidebar-backdrop is-open" style="z-index: 140;" aria-label="Fermer le volet"></a>
    <div class="admin-drawer is-open" id="demande-drawer">
      <div class="drawer-header">
        <div>
          <h3 class="drawer-title"><?= e($dTitle) ?></h3>
          <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">
            ReÃ§ue le <?= format_datetime($drawerItem['date_creation']) ?>
          </p>
        </div>
        <a href="<?= e($selfUrl) ?>" class="btn-icon" aria-label="Fermer"><?= admin_icon('close', 18) ?></a>
      </div>

      <div class="drawer-body">
        <!-- Client -->
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid var(--admin-border-subtle);">
          <div class="user-avatar" style="width: 48px; height: 48px; font-size: 1.1rem; background-color: var(--admin-gold); color: #FFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
            <?= e(mb_strtoupper(mb_substr($dName ?: '?', 0, 1, 'UTF-8'), 'UTF-8')) ?>
          </div>
          <div style="flex: 1;">
            <h4 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--admin-text);"><?= e($dName) ?></h4>
            <div style="display: flex; gap: 12px; margin-top: 4px; font-size: 0.84rem; flex-wrap: wrap;">
              <?php if ($dEmail !== ''): ?><a href="mailto:<?= e($dEmail) ?>" style="color: var(--admin-gold); font-weight: 500;"><?= e($dEmail) ?></a><?php endif; ?>
              <?php if ($dTel !== ''): ?><span>â€¢</span><a href="tel:<?= e($dTel) ?>" style="color: var(--admin-text-muted); font-weight: 500;"><?= e($dTel) ?></a><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Statut -->
        <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--admin-surface-alt); border-radius: var(--radius-sm);">
          <span style="font-weight: 600; font-size: 0.86rem; color: var(--admin-text);">Statut actuel :</span>
          <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>" style="margin:0;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="source" value="<?= e($dSrc) ?>">
            <input type="hidden" name="id" value="<?= (int)$drawerItem['id'] ?>">
            <select name="statut" class="form-select" style="padding: 6px 12px; font-weight: 600;" onchange="this.form.submit()">
              <?php foreach (['Nouveau' => 'Nouveau', 'En cours' => 'En cours', 'ValidÃ©' => 'ValidÃ©', 'Traite' => 'TraitÃ©', 'RejetÃ©' => 'RejetÃ©'] as $sv => $sl): ?>
                <option value="<?= e($sv) ?>" <?= ($dStatus === $sv || ($sv === 'Traite' && $dStatus === 'TraitÃ©')) ? 'selected' : '' ?>><?= e($sl) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <?php if ($isEst): ?>
          <div style="margin-bottom: 24px; padding: 18px; background-color: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
            <h4 style="margin: 0 0 14px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
              <?= admin_icon('building', 16) ?> DÃ©tails du bien Ã  estimer
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.86rem;">
              <div><span style="color: var(--admin-text-muted);">Type de bien :</span> <strong><?= e($estDetails['type'] ?? 'Non spÃ©cifiÃ©') ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Wilaya :</span> <strong><?= e($estDetails['wilaya'] ?? 'â€”') ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Commune :</span> <strong><?= e($estDetails['commune'] ?? 'â€”') ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Quartier :</span> <strong><?= e($estDetails['quartier'] ?? 'â€”') ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Surface habitable :</span> <strong><?= !empty($estDetails['surface']) ? e((string)$estDetails['surface']) . ' mÂ²' : 'â€”' ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Surface terrain :</span> <strong><?= !empty($estDetails['land']) ? e((string)$estDetails['land']) . ' mÂ²' : 'â€”' ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Chambres :</span> <strong><?= e((string)($estDetails['bedrooms'] ?? 'â€”')) ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Salles de bain :</span> <strong><?= e((string)($estDetails['bathrooms'] ?? 'â€”')) ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Garage :</span> <strong><?= e($estDetails['garage'] ?? 'â€”') ?></strong></div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($isFiche): ?>
          <div style="margin-bottom: 24px; padding: 18px; background-color: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
            <h4 style="margin: 0 0 14px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
              <?= admin_icon('building', 16) ?> DÃ©tails de la constitution de dossier
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.88rem;">
              <div><span style="color: var(--admin-text-muted);">OpÃ©ration :</span> <strong><?= e($drawerItem['transaction'] ?: 'Demande') ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Type de bien :</span> <strong><?= e($drawerItem['type_bien'] ?: 'Appartement') ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">RÃ©fÃ©rence :</span> <strong><?= e($drawerItem['reference'] ?: 'â€”') ?></strong></div>
              <div><span style="color: var(--admin-text-muted);">Date :</span> <strong><?= format_date($drawerItem['date_creation']) ?></strong></div>
            </div>
          </div>

          <div style="margin-bottom: 24px;">
            <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
              <?= admin_icon('file-text', 16) ?> Justificatif d'identitÃ©
            </h4>
            <?php if ($pieceUrl !== ''): ?>
              <div class="doc-inspect-card">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <?= admin_icon('file-text', 22) ?>
                    <strong style="font-size:0.9rem;"><?= e(basename($pieceUrl)) ?></strong>
                  </div>
                  <?php if (!$pieceIsPdf): ?>
                    <button type="button" class="btn btn-outline btn-sm btn-zoom-doc" data-img="<?= e($pieceUrl) ?>" data-caption="PiÃ¨ce d'identitÃ© â€” <?= e($dName) ?>">
                      <?= admin_icon('eye', 14) ?> Inspecter en grand
                    </button>
                  <?php else: ?>
                    <a href="<?= e($pieceUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?= admin_icon('download', 14) ?> TÃ©lÃ©charger PDF</a>
                  <?php endif; ?>
                </div>
                <?php if (!$pieceIsPdf): ?>
                  <div class="doc-inspect-preview btn-zoom-doc" data-img="<?= e($pieceUrl) ?>" data-caption="PiÃ¨ce d'identitÃ© â€” <?= e($dName) ?>">
                    <img src="<?= e($pieceUrl) ?>" alt="PiÃ¨ce d'identitÃ©" class="doc-inspect-img">
                    <div class="doc-inspect-overlay"><?= admin_icon('search', 18) ?> <span>Cliquer pour agrandir</span></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <p style="font-size:0.88rem;color:var(--admin-text-muted);">Aucune piÃ¨ce d'identitÃ© jointe.</p>
            <?php endif; ?>
          </div>

          <div style="margin-bottom: 24px;">
            <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
              <?= admin_icon('check', 16) ?> Engagement officiel &amp; Signature Ã©lectronique
            </h4>
            <div style="padding: 12px 14px; background-color: rgba(181, 154, 106, 0.08); border-left: 3px solid var(--admin-gold); border-radius: var(--radius-xs); font-size: 0.84rem; margin-bottom: 10px;">
              Â« Ceci constitue un engagement officiel. En cas de transaction, le client est tenu d'honorer les frais de l'agence conformÃ©ment aux dispositions rÃ©glementaires en vigueur. Â»
            </div>
            <div style="background: #FFFFFF; border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
              <?php if ($sigUrl !== ''): ?>
                <img src="<?= e($sigUrl) ?>" alt="Signature" style="max-height: 110px; max-width: 100%; display: inline-block; object-fit: contain;">
              <?php else: ?>
                <p style="margin: 0; font-size: 0.85rem; color: var(--admin-text-muted);">Signature non enregistrÃ©e.</p>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <div style="margin-bottom: 24px;">
            <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600;">Message ou description</h4>
            <div style="padding: 16px; background-color: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">
              <?= e($drawerItem['message'] ?: 'Aucun message textuel fourni.') ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="drawer-footer">
        <button type="button" class="btn btn-danger-outline btn-sm" onclick="submitDeleteDemande('<?= e($dSrc) ?>', <?= (int)$drawerItem['id'] ?>)">
          <?= admin_icon('trash', 14) ?> Supprimer
        </button>

        <div style="display: flex; gap: 8px;">
          <a href="<?= $b ?>/admin/imprimer.php?source=<?= e($dSrc) ?>&id=<?= (int)$drawerItem['id'] ?>" target="_blank" class="btn btn-outline btn-sm">
            <?= admin_icon('printer', 14) ?> Imprimer
          </a>
          <?php if ($dTel !== ''): ?>
            <a href="tel:<?= e($dTel) ?>" class="btn btn-outline btn-sm"><?= admin_icon('phone', 14) ?> Appeler</a>
          <?php endif; ?>
         
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php
  $importModalTitle = 'Importer des demandes';
  $importActionUrl = $b . '/admin/demandes.php';
  include __DIR__ . '/includes/import_modal.php';
  include __DIR__ . '/includes/footer_scripts.php';
  ?>

  <script>
    function submitDemandeStatus(src, id, val, selectEl) {
      var formData = new FormData();
      formData.append('action', 'update_status');
      formData.append('source', src);
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
          if (window.showAdminToast) window.showAdminToast('Statut mis Ã  jour : ' + val);
          if (data.counts) {
            window.updateSidebarBadge('demandes', (data.counts.demandes || 0) + (data.counts.fiches || 0));
            window.updateSidebarBadge('fiches-client', data.counts.fiches);
          }
        }
      })
      .catch(function() {
        document.getElementById('st-src').value = src;
        document.getElementById('st-id').value = id;
        document.getElementById('st-val').value = val;
        document.getElementById('single-status-form').submit();
      });
    }

    function submitToggleRead(src, id, newRead, btn) {
      var formData = new FormData();
      formData.append('action', 'toggle_read');
      formData.append('source', src);
      formData.append('id', id);
      formData.append('new_read', newRead);
      formData.append('ajax', '1');

      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data && data.success) {
          var uid = (src === 'fiche' ? 'fiche:' : 'demande:') + id;
          var row = document.getElementById('row-' + uid) || btn?.closest('tr');
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
              btn.setAttribute('onclick', "submitToggleRead('" + src + "', " + id + ", " + (isNowRead ? 0 : 1) + ", this)");
            }
          }
          if (data.counts) {
            window.updateSidebarBadge('demandes', (data.counts.demandes || 0) + (data.counts.fiches || 0));
            window.updateSidebarBadge('fiches-client', data.counts.fiches);
          }
          if (window.showAdminToast) {
            window.showAdminToast(data.new_read === 1 ? 'Demande marquÃ©e comme lue' : 'Demande marquÃ©e comme non lue');
          }
        }
      })
      .catch(function() {
        document.getElementById('rd-src').value = src;
        document.getElementById('rd-id').value = id;
        document.getElementById('rd-val').value = newRead;
        document.getElementById('single-read-form').submit();
      });
    }

    function submitDeleteDemande(src, id) {
      if (!confirm('Supprimer dÃ©finitivement cette demande ?')) return;
      document.getElementById('dl-src').value = src;
      document.getElementById('dl-id').value = id;
      document.getElementById('single-delete-form').submit();
    }
    (function() {
      var wrap = document.getElementById('demandes-table-wrap');
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

