<?php
/**
 * admin/utilisateurs.php - Gestion des utilisateurs & conseillers (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/xlsx.php';

$currentUser = require_admin($conn);
$activePage = 'utilisateurs';
$pageHeading = 'Utilisateurs & Équipe';
$breadcrumbs = [['label' => 'Utilisateurs']];
$b = BASE_URL;
$topbarAction = ['label' => 'Ajouter un membre', 'href' => $b . '/admin/utilisateurs.php?drawer=new', 'icon' => 'plus'];
$currentUserId = (int)$currentUser['id'];

// -------------------------------------------------------------------------
// 1. Modèle d'import CSV / Excel
// -------------------------------------------------------------------------
if (isset($_GET['template'])) {
    $fmt = $_GET['template'] === 'xlsx' ? 'xlsx' : 'csv';
    $headers = ['nom', 'email', 'role', 'poste', 'telephone', 'mot_de_passe'];
    $rows = [
        ['Karim Benali', 'karim.benali@immolode.dz', 'Agent commercial', 'Conseiller senior', '0550123456', 'agent123'],
        ['Amel Dahmani', 'amel.dahmani@immolode.dz', 'Agent commercial', 'Conseillère location', '0661223344', 'agent123']
    ];
    if ($fmt === 'xlsx') {
        $bin = xlsx_build_binary($headers, $rows, 'Modele Utilisateurs');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="modele_users.xlsx"');
        echo $bin;
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modele_users.csv"');
        echo csv_build_string($headers, $rows);
    }
    exit;
}

$drawerMode = (string)($_GET['drawer'] ?? ''); // 'new' ou 'edit'
$editId     = (int)($_GET['id'] ?? 0);
$drawerError = '';

// -------------------------------------------------------------------------
// 2. Traitements POST
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_user') {
        $uid    = (int)($_POST['id'] ?? 0);
        $nom    = trim((string)($_POST['nom'] ?? ''));
        $poste  = trim((string)($_POST['poste'] ?? 'Conseiller'));
        $email  = strtolower(trim((string)($_POST['email'] ?? '')));
        $tel    = trim((string)($_POST['telephone'] ?? ''));
        $role   = trim((string)($_POST['role'] ?? 'Agent commercial'));
        $actif  = isset($_POST['actif']) ? 1 : 0;
        $pwd    = (string)($_POST['password'] ?? '');
        $avatar = trim((string)($_POST['avatar_url'] ?? ''));

        if ($uid > 0 && $uid === $currentUserId) {
            set_flash('Utilisez la fenêtre « Mon Profil » en haut à droite pour modifier votre propre compte.', 'error');
            header('Location: ' . $b . '/admin/utilisateurs.php');
            exit;
        }

        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $avatar = handle_file_upload($_FILES['avatar_file'], 'avatars');
            } catch (Throwable $ex) {
                $drawerError = $ex->getMessage();
            }
        }

        if ($drawerError === '') {
            if ($err = validate_algerian_user_fields($nom, $email, $tel)) {
                $drawerError = $err;
            }
        }

        if ($drawerError === '') {
            $avatarVal = $avatar !== '' ? $avatar : null;
            if ($uid > 0) {
                if ($pwd !== '') {
                    $hash = password_hash($pwd, PASSWORD_BCRYPT);
                    $stmtUp = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `mot_de_passe` = ?, `role` = ?, `poste` = ?, `telephone` = ?, `actif` = ?, `avatar` = ? WHERE `id` = ?");
                    $stmtUp->bind_param("ssssssisi", $nom, $email, $hash, $role, $poste, $tel, $actif, $avatarVal, $uid);
                } else {
                    $stmtUp = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `role` = ?, `poste` = ?, `telephone` = ?, `actif` = ?, `avatar` = ? WHERE `id` = ?");
                    $stmtUp->bind_param("sssssisi", $nom, $email, $role, $poste, $tel, $actif, $avatarVal, $uid);
                }
                $stmtUp->execute();
                set_flash('Modifications enregistrées.');
                header('Location: ' . $b . '/admin/utilisateurs.php');
                exit;
            } else {
                $chk = $conn->prepare("SELECT `id` FROM `utilisateurs` WHERE LOWER(`email`) = ? LIMIT 1");
                $chk->bind_param("s", $email);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $drawerError = 'Un compte existe déjà avec cette adresse e-mail.';
                } else {
                    $hash = password_hash($pwd !== '' ? $pwd : 'admin123', PASSWORD_BCRYPT);
                    $stmtIn = $conn->prepare("INSERT INTO `utilisateurs` (`nom`, `email`, `mot_de_passe`, `role`, `poste`, `telephone`, `avatar`, `actif`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtIn->bind_param("sssssssi", $nom, $email, $hash, $role, $poste, $tel, $avatarVal, $actif);
                    $stmtIn->execute();
                    set_flash('Nouveau collaborateur ajouté.');
                    header('Location: ' . $b . '/admin/utilisateurs.php');
                    exit;
                }
            }
        }
        $drawerMode = $uid > 0 ? 'edit' : 'new';
        $editId = $uid;
    }

    if ($action === 'delete_one') {
        $uid = (int)($_POST['id'] ?? 0);
        if ($uid === 1 || $uid === $currentUserId) {
            set_flash('Impossible de supprimer ce compte protégé.', 'error');
        } elseif ($uid > 0) {
            $stmt = $conn->prepare("DELETE FROM `utilisateurs` WHERE `id` = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            set_flash('Utilisateur supprimé.');
        }
        header('Location: ' . $b . '/admin/utilisateurs.php');
        exit;
    }

    if (in_array($action, ['bulk_delete', 'bulk_export_csv', 'bulk_export_xlsx'], true)) {
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])), fn($v) => $v > 0));
        if ($action === 'bulk_delete') {
            $safeIds = array_values(array_filter($ids, fn($v) => $v !== 1 && $v !== $currentUserId));
            if (!empty($safeIds)) {
                $conn->query("DELETE FROM `utilisateurs` WHERE `id` IN (" . implode(',', $safeIds) . ")");
                set_flash(count($safeIds) . ' membre(s) supprimé(s).');
            } else {
                set_flash('Aucun membre supprimable sélectionné.', 'error');
            }
            header('Location: ' . $b . '/admin/utilisateurs.php');
            exit;
        }
        if (!empty($ids)) {
            $resEx = $conn->query("SELECT `id`, `nom`, `poste`, `email`, `telephone`, `role`, `actif` FROM `utilisateurs` WHERE `id` IN (" . implode(',', $ids) . ") ORDER BY `id` ASC");
            $expHeaders = ['ID', 'Nom', 'Poste', 'Email', 'Téléphone', 'Rôle', 'Actif'];
            $expRows = [];
            while ($r = $resEx->fetch_assoc()) {
                $expRows[] = [$r['id'], $r['nom'], $r['poste'] ?? '', $r['email'], $r['telephone'] ?? '', $r['role'] ?? '', !empty($r['actif']) ? 'Oui' : 'Non'];
            }
            $dateSuffix = date('Y-m-d');
            if ($action === 'bulk_export_csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="utilisateurs_selection_' . $dateSuffix . '.csv"');
                echo csv_build_string($expHeaders, $expRows);
            } else {
                $bin = xlsx_build_binary($expHeaders, $expRows, 'Utilisateurs sélection');
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="utilisateurs_selection_' . $dateSuffix . '.xlsx"');
                echo $bin;
            }
            exit;
        }
    }

    if ($action === 'import_file') {
        try {
            $parsedRows = spreadsheet_parse_upload($_FILES['import_file'] ?? []);
            if (count($parsedRows) < 2) throw new RuntimeException('Fichier vide ou sans données.');
            $rawHeaders = array_shift($parsedRows);
            $headers = array_map(fn($h) => normalize_import_header((string)$h), $rawHeaders);

            $stmtCheck = $conn->prepare("SELECT `id` FROM `utilisateurs` WHERE LOWER(`email`) = ? LIMIT 1");
            $stmtIns = $conn->prepare("INSERT INTO `utilisateurs` (`nom`, `email`, `role`, `poste`, `telephone`, `mot_de_passe`, `actif`) VALUES (?, ?, ?, ?, ?, ?, 1)");

            $imported = 0; $errCount = 0;
            foreach ($parsedRows as $row) {
                $d = [];
                foreach ($headers as $i => $h) { if ($h !== '') $d[$h] = trim((string)($row[$i] ?? '')); }
                $nom = $d['nom'] ?? $d['name'] ?? '';
                $email = strtolower($d['email'] ?? '');
                $roleRaw = strtolower($d['role'] ?? '');
                $role = in_array($roleRaw, ['administrateur', 'admin'], true) ? 'Administrateur'
                    : ($roleRaw === 'gestionnaire' ? 'Gestionnaire' : 'Agent commercial');
                $poste = ($d['poste'] ?? '') ?: 'Conseiller';
                $tel = $d['telephone'] ?? $d['phone'] ?? '';
                $pwd = ($d['mot_de_passe'] ?? $d['password'] ?? '') ?: 'agent123';

                if ($nom === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errCount++; continue; }
                $stmtCheck->bind_param("s", $email);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) { $errCount++; continue; }

                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $stmtIns->bind_param("ssssss", $nom, $email, $role, $poste, $tel, $hash);
                $stmtIns->execute();
                $imported++;
            }
            set_flash($imported > 0 ? "$imported utilisateur(s) importé(s)." : "Aucun utilisateur importé ($errCount ignoré(s)).", $imported > 0 ? 'success' : 'error');
        } catch (Throwable $ex) {
            set_flash($ex->getMessage(), 'error');
        }
        header('Location: ' . $b . '/admin/utilisateurs.php');
        exit;
    }
}

// -------------------------------------------------------------------------
// 3. Liste des utilisateurs + export GET
// -------------------------------------------------------------------------
$res = $conn->query("SELECT `id`, `nom`, `email`, `role`, `poste`, `telephone`, `actif`, `avatar`, `date_creation` FROM `utilisateurs` ORDER BY `id` ASC");
$users = [];
while ($r = $res->fetch_assoc()) {
    $users[] = $r;
}

if (isset($_GET['export'])) {
    $fmt = $_GET['export'] === 'csv' ? 'csv' : 'xlsx';
    $expHeaders = ['ID', 'Nom', 'Poste', 'Email', 'Téléphone', 'Rôle', 'Actif'];
    $expRows = [];
    foreach ($users as $r) {
        $expRows[] = [$r['id'], $r['nom'], $r['poste'] ?? '', $r['email'], $r['telephone'] ?? '', $r['role'] ?? '', !empty($r['actif']) ? 'Oui' : 'Non'];
    }
    $dateSuffix = date('Y-m-d');
    if ($fmt === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="membres_immolode_' . $dateSuffix . '.csv"');
        echo csv_build_string($expHeaders, $expRows);
    } else {
        $bin = xlsx_build_binary($expHeaders, $expRows, 'Utilisateurs');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="membres_immolode_' . $dateSuffix . '.xlsx"');
        echo $bin;
    }
    exit;
}

$drawerUser = null;
if ($drawerMode === 'edit' && $editId > 0) {
    foreach ($users as $uItem) {
        if ((int)$uItem['id'] === $editId) {
            $drawerUser = $uItem;
            break;
        }
    }
}
$showDrawer = ($drawerMode === 'new' || ($drawerMode === 'edit' && $drawerUser !== null));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Utilisateurs &amp; Équipe — Administration Imolode</title>
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
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
          <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
            Gérez les comptes d'accès à l'interface d'administration ainsi que les conseillers affichés sur le site public.
          </p>

          <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline btn-sm" id="btn-open-import">
              <?= admin_icon('upload', 14) ?> <span>Importer CSV / Excel</span>
            </button>
            <a href="<?= $b ?>/admin/utilisateurs.php?export=xlsx" class="btn btn-outline btn-sm">
              <?= admin_icon('download', 14) ?> <span>Export Excel</span>
            </a>
            <a href="<?= $b ?>/admin/utilisateurs.php?export=csv" class="btn btn-outline btn-sm">
              <?= admin_icon('download', 14) ?> <span>Export CSV</span>
            </a>
            <button type="button" class="btn btn-outline btn-sm" id="btn-toggle-select">
              <?= admin_icon('check', 14) ?> <span id="btn-select-label">S&#233lectionner</span>
            </button>
          </div>
        </div>

        <?php if (empty($users)): ?>
          <div class="card">
            <div class="empty-state">
              <div class="empty-state-icon"><?= admin_icon('users', 28) ?></div>
              <h3 class="empty-state-title">Aucun utilisateur enregistré</h3>
              <p class="empty-state-text">Cliquez sur « Ajouter un membre » pour créer un nouveau compte.</p>
              <a href="<?= $b ?>/admin/utilisateurs.php?drawer=new" class="btn btn-primary"><?= admin_icon('plus', 16) ?> Ajouter un membre</a>
            </div>
          </div>
        <?php else: ?>
          <div id="users-table-wrap">
            <form method="POST" action="<?= $b ?>/admin/utilisateurs.php" id="bulk-form">
              <input type="hidden" name="action" id="bulk-action-input" value="">

              <div class="bulk-actions-bar" id="bulk-bar">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="bulk-badge" id="bulk-count">0</span>
                  <span style="font-weight: 600;">membre(s) sélectionné(s)</span>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                  <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-cancel">Annuler</button>
                  <button type="submit" class="btn btn-outline btn-sm" onclick="document.getElementById('bulk-action-input').value='bulk_export_csv'">
                    <?= admin_icon('download', 14) ?> <span>CSV</span>
                  </button>
                  <button type="submit" class="btn btn-outline btn-sm" onclick="document.getElementById('bulk-action-input').value='bulk_export_xlsx'">
                    <?= admin_icon('download', 14) ?> <span>Excel</span>
                  </button>
                  <button type="submit" class="btn btn-sm" style="background: var(--admin-danger); color: #fff; border: none;" onclick="document.getElementById('bulk-action-input').value='bulk_delete'; return confirm('Supprimer définitivement ces membres ?');">
                    <?= admin_icon('trash', 14) ?> <span>Supprimer</span>
                  </button>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div>
                    <h2 class="card-title">Membres de l'équipe (<?= count($users) ?>)</h2>
                    <p class="card-subtitle">Administrateurs et agents autorisés</p>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="admin-table">
                    <thead>
                      <tr>
                        <th class="select-col"><input type="checkbox" id="check-all-rows" title="Tout sélectionner"></th>
                        <th>Utilisateur / Conseiller</th>
                        <th>Coordonnées</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th style="width: 150px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($users as $u):
                        $isSelf = ((int)$u['id'] === $currentUserId);
                        $avatarUrl = !empty($u['avatar']) ? asset_url((string)$u['avatar']) : '';
                        $isAdminRole = in_array($u['role'], ['Administrateur', 'Gérant'], true);
                      ?>
                        <tr>
                          <td class="select-col">
                            <?php if ($isSelf || (int)$u['id'] === 1): ?>
                              <input type="checkbox" disabled title="Compte protégé" style="opacity:0.35;cursor:not-allowed;">
                            <?php else: ?>
                              <input type="checkbox" class="row-checkbox" name="ids[]" value="<?= (int)$u['id'] ?>">
                            <?php endif; ?>
                          </td>
                          <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                              <?php if ($avatarUrl !== ''): ?>
                                <img src="<?= e($avatarUrl) ?>" alt="" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;">
                              <?php else: ?>
                                <div class="user-avatar" style="width: 38px; height: 38px; border-radius: 50%; background-color: var(--admin-gold); color: #FFF; font-weight: 700; display: flex; align-items: center; justify-content: center;">
                                  <?= e(mb_strtoupper(mb_substr($u['nom'] ?: 'U', 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                </div>
                              <?php endif; ?>
                              <div>
                                <div style="font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 6px;">
                                  <?= e($u['nom']) ?>
                                  <?php if ($isSelf): ?><span class="badge" style="background: var(--admin-gold-soft); color: var(--admin-gold); font-size:0.68rem;">Vous</span><?php endif; ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--admin-text-subtle);"><?= e($u['poste'] ?: 'Conseiller') ?></div>
                              </div>
                            </div>
                          </td>
                          <td>
                            <div><a href="mailto:<?= e($u['email']) ?>" style="color: var(--admin-gold);"><?= e($u['email']) ?></a></div>
                            <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 2px;"><?= e($u['telephone'] ?: '—') ?></div>
                          </td>
                          <td>
                            <span class="badge <?= $isAdminRole ? 'badge--success' : 'badge--info' ?>"><?= e($u['role'] ?: 'Agent commercial') ?></span>
                          </td>
                          <td>
                            <?= !empty($u['actif']) ? '<span class="badge badge--success">Actif</span>' : '<span class="badge badge--muted">Inactif</span>' ?>
                          </td>
                          <td>
                            <?php if ($isSelf): ?>
                              <button type="button" onclick="document.getElementById('admin-profile-modal')?.classList.add('is-active')" style="font-size: 0.76rem; font-weight: 600; color: var(--admin-gold); background: rgba(181, 154, 106, 0.12); padding: 4px 8px; border:0; border-radius: var(--radius-xs); cursor:pointer;">
                                Votre compte (Mon Profil)
                              </button>
                            <?php else: ?>
                              <div class="table-actions">
                                <a href="<?= $b ?>/admin/utilisateurs.php?drawer=edit&id=<?= (int)$u['id'] ?>" class="btn-icon" title="Modifier">
                                  <?= admin_icon('edit', 16) ?>
                                </a>
                                <?php if ((int)$u['id'] !== 1): ?>
                                  <button type="button" class="btn-icon btn-icon--danger" title="Supprimer" onclick="submitDeleteUser(<?= (int)$u['id'] ?>)">
                                    <?= admin_icon('trash', 16) ?>
                                  </button>
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </form>

            <form method="POST" action="<?= $b ?>/admin/utilisateurs.php" id="single-delete-form" style="display:none;">
              <input type="hidden" name="action" value="delete_one">
              <input type="hidden" name="id" id="dl-id" value="">
            </form>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <!-- Volet latéral (Drawer) Création / Édition Utilisateur -->
  <?php if ($showDrawer):
    $isNew = ($drawerUser === null);
    $duNom    = $_POST['nom'] ?? $drawerUser['nom'] ?? '';
    $duPoste  = $_POST['poste'] ?? $drawerUser['poste'] ?? '';
    $duEmail  = $_POST['email'] ?? $drawerUser['email'] ?? '';
    $duTel    = $_POST['telephone'] ?? $drawerUser['telephone'] ?? '';
    $duRole   = $_POST['role'] ?? $drawerUser['role'] ?? 'Agent commercial';
    $duActif  = isset($_POST['action']) ? isset($_POST['actif']) : ($isNew ? true : !empty($drawerUser['actif']));
    $duAvatar = $_POST['avatar_url'] ?? $drawerUser['avatar'] ?? '';
    $duAvatarSrc = $duAvatar !== '' ? asset_url((string)$duAvatar) : '';
  ?>
    <a href="<?= $b ?>/admin/utilisateurs.php" class="sidebar-backdrop is-open" style="z-index: 140;" aria-label="Fermer"></a>
    <div class="admin-drawer is-open" id="user-drawer">
      <div class="drawer-header">
        <div>
          <h3 class="drawer-title"><?= $isNew ? 'Nouveau collaborateur' : 'Modifier le profil' ?></h3>
          <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">
            <?= $isNew ? 'Renseignez les coordonnées et le rôle' : e($duNom) ?>
          </p>
        </div>
        <a href="<?= $b ?>/admin/utilisateurs.php" class="btn-icon" aria-label="Fermer"><?= admin_icon('close', 18) ?></a>
      </div>

      <form method="POST" action="<?= $b ?>/admin/utilisateurs.php?drawer=<?= $isNew ? 'new' : 'edit&id=' . (int)$editId ?>" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
        <input type="hidden" name="action" value="save_user">
        <input type="hidden" name="id" value="<?= $isNew ? 0 : (int)$drawerUser['id'] ?>">

        <div class="drawer-body">
          <?php if ($drawerError !== ''): ?>
            <div style="padding:12px 14px;background:var(--admin-danger-soft);color:var(--admin-danger);border:1px solid #F0C4C1;border-radius:var(--radius-xs);font-size:0.86rem;margin-bottom:16px;">
              <?= e($drawerError) ?>
            </div>
          <?php endif; ?>

          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="user-avatar-url">Photo de profil</label>
            <div style="display: flex; gap: 12px; align-items: center;">
              <div id="user-avatar-preview" style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; background: var(--admin-gold); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <?php if ($duAvatarSrc !== ''): ?>
                  <img src="<?= e($duAvatarSrc) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                  <?= e(mb_strtoupper(mb_substr($duNom ?: 'U', 0, 1, 'UTF-8'), 'UTF-8')) ?>
                <?php endif; ?>
              </div>
              <div style="flex: 1;">
                <input type="text" class="form-input" name="avatar_url" id="user-avatar-url" value="<?= e($duAvatar) ?>" placeholder="URL de l'image ou téléverser ci-dessous" style="margin-bottom: 6px; padding:6px 10px; font-size:0.84rem;">
                <input type="file" name="avatar_file" id="user-avatar-file" accept="image/*" style="display:none;">
                <button type="button" class="btn btn-outline btn-sm" id="btn-upload-user-avatar">
                  <?= admin_icon('camera', 14) ?> <span>Téléverser</span>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-nom">Nom complet <span class="required">*</span></label>
            <input type="text" class="form-input" name="nom" id="user-nom" required value="<?= e($duNom) ?>" placeholder="Ex: G. Hocine">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-poste">Poste / Fonction <span class="required">*</span></label>
            <input type="text" class="form-input" name="poste" id="user-poste" required value="<?= e($duPoste) ?>" placeholder="Ex: Conseiller immobilier senior">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-email">Adresse e-mail <span class="required">*</span></label>
            <input type="email" class="form-input" name="email" id="user-email" required value="<?= e($duEmail) ?>" placeholder="conseiller@immolode.dz">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-phone">Numéro de téléphone</label>
            <input type="text" class="form-input" name="telephone" id="user-phone" value="<?= e($duTel) ?>" placeholder="0550 12 34 56">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-role">Rôle d'administration</label>
            <select class="form-select" name="role" id="user-role" style="width: 100%;">
              <?php foreach (['Administrateur' => 'Administrateur (accès complet)', 'Agent commercial' => 'Agent commercial (biens & demandes)', 'Gestionnaire' => 'Gestionnaire de biens'] as $rVal => $rLbl): ?>
                <option value="<?= e($rVal) ?>" <?= $duRole === $rVal ? 'selected' : '' ?>><?= e($rLbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-pwd">Mot de passe <?= $isNew ? '<span style="font-weight:400;color:var(--admin-text-subtle);">(par défaut : admin123)</span>' : '<span style="font-weight:400;color:var(--admin-text-subtle);">(laisser vide pour conserver)</span>' ?></label>
            <input type="password" class="form-input" name="password" id="user-pwd" placeholder="••••••••">
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="actif" <?= $duActif ? 'checked' : '' ?>>
              <span>Compte actif (autorisé à se connecter)</span>
            </label>
          </div>
        </div>

        <div class="drawer-footer">
          <a href="<?= $b ?>/admin/utilisateurs.php" class="btn btn-outline btn-sm">Annuler</a>
          <button type="submit" class="btn btn-primary btn-sm">
            <?= admin_icon('check', 14) ?> <?= $isNew ? 'Créer le membre' : 'Enregistrer' ?>
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <?php
  $importModalTitle = 'Importer des utilisateurs';
  $importActionUrl = $b . '/admin/utilisateurs.php';
  include __DIR__ . '/includes/import_modal.php';
  include __DIR__ . '/includes/footer_scripts.php';
  ?>

  <script>
    function submitDeleteUser(id) {
      if (!confirm('Supprimer définitivement ce collaborateur ?')) return;
      document.getElementById('dl-id').value = id;
      document.getElementById('single-delete-form').submit();
    }
    (function() {
      var wrap = document.getElementById('users-table-wrap');
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

      var uFile = document.getElementById('user-avatar-file');
      var uPrev = document.getElementById('user-avatar-preview');
      document.getElementById('btn-upload-user-avatar')?.addEventListener('click', function() { uFile?.click(); });
      uFile?.addEventListener('change', function(e) {
        var f = e.target.files && e.target.files[0];
        if (!f || !uPrev) return;
        var r = new FileReader();
        r.onload = function(ev) { uPrev.innerHTML = '<img src="' + ev.target.result + '" style="width:100%;height:100%;object-fit:cover;">'; };
        r.readAsDataURL(f);
      });
    })();
  </script>
</body>
</html>
