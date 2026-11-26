<?php
/**
 * config/admin_helpers.php - Authentification, icônes SVG, badges, uploads et profil admin
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function set_flash(string $message, string $type = 'success'): void {
    $_SESSION['admin_flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array {
    if (!empty($_SESSION['admin_flash'])) {
        $f = $_SESSION['admin_flash'];
        unset($_SESSION['admin_flash']);
        return $f;
    }
    return null;
}

function require_admin(mysqli $conn): array {
    if (empty($_SESSION['admin_user']['id'])) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? (BASE_URL . '/admin/index.php'));
        header('Location: ' . BASE_URL . '/admin/login.php?redirect=' . $redirect);
        exit;
    }

    // Rafraîchir les infos utilisateur depuis MySQL pour être toujours à jour
    $uid = (int)$_SESSION['admin_user']['id'];
    $stmt = $conn->prepare("SELECT `id`, `nom`, `email`, `role`, `poste`, `telephone`, `avatar`, `actif` FROM `utilisateurs` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $fresh = $stmt->get_result()->fetch_assoc();

    if (!$fresh || !(int)$fresh['actif']) {
        unset($_SESSION['admin_user']);
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }

    $_SESSION['admin_user'] = $fresh;
    handle_own_profile_post($conn, $fresh);
    return $fresh;
}

function get_unread_counts(mysqli $conn): array {
    $dCount = 0;
    $fCount = 0;
    $r1 = $conn->query("SELECT COUNT(*) AS c FROM `demandes` WHERE `is_read` = 0");
    if ($r1 && ($row = $r1->fetch_assoc())) {
        $dCount = (int)$row['c'];
    }
    $r2 = $conn->query("SELECT COUNT(*) AS c FROM `fiches_client` WHERE `is_read` = 0");
    if ($r2 && ($row = $r2->fetch_assoc())) {
        $fCount = (int)$row['c'];
    }
    return ['demandes' => $dCount, 'fiches' => $fCount];
}

function admin_icon(string $name, int $size = 18, string $className = ''): string {
    $icons = [
        'dashboard'     => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'building'      => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M12 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/>',
        'mail'          => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'users'         => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'settings'      => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'external'      => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/>',
        'logout'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'plus'          => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'edit'          => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash'         => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'eye'           => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'search'        => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'check'         => '<path d="M20 6 9 17l-5-5"/>',
        'close'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'filter'        => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'menu'          => '<line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/>',
        'arrow-left'    => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'user'          => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'file-text'     => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'printer'       => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'download'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
        'upload'        => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
        'sun'           => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
        'moon'          => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'lock'          => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'camera'        => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
        'chevrons-left' => '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',
    ];
    $body = $icons[$name] ?? $icons['check'];
    $classes = 'admin-icon' . ($className !== '' ? ' ' . $className : '');
    return '<svg class="' . e($classes) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

function status_badge(string $status): string {
    $badgeClass = 'badge--neutral';
    if (in_array($status, ['Disponible', 'Traite', 'Traité', 'Validé'], true)) {
        $badgeClass = 'badge--success';
    } elseif (in_array($status, ['Reserve', 'Réservé', 'En cours'], true)) {
        $badgeClass = 'badge--warning';
    } elseif ($status === 'Nouveau') {
        $badgeClass = 'badge--info';
    } elseif (in_array($status, ['Vendu', 'Loue', 'Loué', 'Rejeté'], true)) {
        $badgeClass = 'badge--muted';
    }
    return '<span class="badge ' . $badgeClass . '">' . e($status) . '</span>';
}

function transaction_badge(string $transaction): string {
    $isRent = ($transaction === 'Louer');
    return '<span class="badge ' . ($isRent ? 'badge--rent' : 'badge--sale') . '">' . ($isRent ? 'Location' : 'Vente') . '</span>';
}

function algeria_locations(): array {
    return [
        "Béjaïa"      => ["Béjaïa (Centre)", "Tichy", "Aokas", "Souk El Ténine", "Akbou", "El Kseur", "Amizour", "Sidi Aïch", "Tazmalt", "Kherrata", "Adekar", "Ouzellaguen", "Darguina", "Barbacha", "Chemini", "Ighram", "Beni Maouche", "Tifra"],
        "Alger"       => ["Alger Centre", "Hydra", "El Biar", "Ben Aknoun", "Chéraga", "Dely Ibrahim", "Zéralda", "Staoueli", "Kouba", "Bir Mourad Raïs", "Hussein Dey", "Bab El Oued", "Bouzaréah", "Bab Ezzouar", "Bordj El Kiffan", "Rouïba", "Dar El Beïda", "Saoula", "Draria", "Birtouta", "Ain Benian", "Bologhine"],
        "Oran"        => ["Oran (Centre)", "Bir El Djir", "Es Senia", "Ain El Turk", "Arzew", "Mers El Kebir", "Bethioua", "Canastel", "Akid Lotfi", "Gdyel", "El Kerma"],
        "Constantine" => ["Constantine (Centre)", "Ali Mendjeli", "El Khroub", "Ain Smara", "Hamma Bouziane", "Zighoud Youcef", "Didouche Mourad"],
        "Sétif"       => ["Sétif (Centre)", "El Eulma", "Ain Arnat", "Ain Oulmene", "Bouandas", "Beni Aziz"],
        "Blida"       => ["Blida (Centre)", "Boufarik", "Ouled Yaich", "Mouzaia", "Larbaa", "Bougara", "El Affroun"],
        "Tizi Ouzou"  => ["Tizi Ouzou (Centre)", "Azazga", "Tigzirt", "Draa Ben Khedda", "Boghni", "Larbaa Nath Irathen", "Azeffoun", "Ouadhia"],
        "Boumerdès"   => ["Boumerdès (Centre)", "Zemmouri", "Dellys", "Bordj Menaiel", "Corso", "Thénia", "Khemis El Khechna", "Boudouaou"],
        "Tipaza"      => ["Tipaza (Centre)", "Cherchell", "Kolea", "Bou Ismail", "Fouka", "Hadjout", "Douaouda", "Bousmail"],
        "Annaba"      => ["Annaba (Centre)", "El Bouni", "Sidi Amar", "Berrahal", "El Hadjar", "Seraïdi"],
        "Tlemcen"     => ["Tlemcen (Centre)", "Mansourah", "Chetouane", "Maghnia", "Remchi", "Ghazaouet"],
        "Mostaganem"  => ["Mostaganem (Centre)", "Ain Tedeles", "Hassi Mameche", "Sidi Lakhdar", "Mazagran"],
        "Jijel"       => ["Jijel (Centre)", "Taher", "El Milia", "Ziama Mansouriah", "Texenna"]
    ];
}

function handle_file_upload(array $fileInfo, string $subfolder = 'properties'): string {
    $allowedFolders = ['properties', 'avatars', 'cms', 'documents', 'signatures'];
    $safeFolder = in_array($subfolder, $allowedFolders, true) ? $subfolder : 'properties';

    if (!isset($fileInfo['error']) || $fileInfo['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Aucun fichier sélectionné.');
    }
    if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur lors du téléversement (code ' . $fileInfo['error'] . ').');
    }
    if ((int)$fileInfo['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('Le fichier dépasse 15 Mo.');
    }

    $ext = strtolower(pathinfo((string)$fileInfo['name'], PATHINFO_EXTENSION));
    $allowed = ($safeFolder === 'documents') ? ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'] : ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Extension non autorisée : .' . $ext);
    }

    $tmpName = (string)$fileInfo['tmp_name'];
    if ($ext !== 'pdf' && @getimagesize($tmpName) === false) {
        throw new RuntimeException('Le fichier sélectionné n\'est pas une image valide.');
    }

    $targetDir = ROOT_PATH . '/uploads/' . $safeFolder;
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }

    $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $dest = $targetDir . '/' . $safeName;

    $ok = is_uploaded_file($tmpName) ? @move_uploaded_file($tmpName, $dest) : @rename($tmpName, $dest);
    if (!$ok) {
        throw new RuntimeException('Impossible d\'enregistrer le fichier sur le serveur.');
    }

    return '/uploads/' . $safeFolder . '/' . $safeName;
}

function save_base64_file(string $base64Data, string $subfolder = 'signatures', string $prefix = 'sig'): ?string {
    if ($base64Data === '') return null;
    if (str_starts_with($base64Data, 'http://') || str_starts_with($base64Data, 'https://') || str_starts_with($base64Data, '/uploads/')) {
        return $base64Data;
    }

    $ext = 'png';
    if (preg_match('/^data:(?:image|application)\/([a-zA-Z0-9.+-]+);base64,/', $base64Data, $m)) {
        $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        $raw = strtolower($m[1]);
        $ext = match ($raw) {
            'jpeg' => 'jpg',
            'svg+xml' => 'svg',
            'pdf' => 'pdf',
            default => preg_replace('/[^a-z0-9]/', '', $raw) ?: 'png'
        };
    }

    $decoded = base64_decode($base64Data, true);
    if ($decoded === false || strlen($decoded) === 0) return null;

    $allowedFolders = ['properties', 'avatars', 'cms', 'documents', 'signatures'];
    $safeFolder = in_array($subfolder, $allowedFolders, true) ? $subfolder : 'signatures';
    $targetDir = ROOT_PATH . '/uploads/' . $safeFolder;
    if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);

    $fileName = (preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'file') . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    if (file_put_contents($targetDir . '/' . $fileName, $decoded) === false) return null;

    return '/uploads/' . $safeFolder . '/' . $fileName;
}

function validate_algerian_user_fields(string $nom, string $email, string $tel): ?string {
    if ($nom === '' || $email === '') {
        return 'Le nom et l\'adresse e-mail sont obligatoires.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Format d\'adresse e-mail invalide.';
    }
    if ($tel !== '') {
        $cleaned = preg_replace('/[\s.\-()]/', '', $tel);
        if (!preg_match('/^(?:0[5-7]\d{8}|(?:\+213|00213)[5-7]\d{8}|0[2-4]\d{7})$/', $cleaned)) {
            return 'Numéro de téléphone algérien invalide (ex: 0550123456 ou +213550123456).';
        }
    }
    return null;
}

/**
 * Traite la modification de son propre profil depuis le modal du topbar sur n'importe quelle page admin
 */
function handle_own_profile_post(mysqli $conn, array $currentUser): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['global_action'] ?? '') !== 'update_own_profile') {
        return;
    }

    $id = (int)$currentUser['id'];
    $nom = trim((string)($_POST['profile_nom'] ?? ''));
    $email = strtolower(trim((string)($_POST['profile_email'] ?? '')));
    $tel = trim((string)($_POST['profile_telephone'] ?? ''));
    $password = (string)($_POST['profile_password'] ?? '');
    $avatar = trim((string)($_POST['profile_avatar_url'] ?? ''));

    if (!empty($_POST['remove_avatar'])) {
        $avatar = '';
    }

    if (isset($_FILES['profile_avatar_file']) && $_FILES['profile_avatar_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $avatar = handle_file_upload($_FILES['profile_avatar_file'], 'avatars');
        } catch (Throwable $ex) {
            set_flash($ex->getMessage(), 'error');
            header('Location: ' . ($_SERVER['REQUEST_URI'] ?? BASE_URL . '/admin/index.php'));
            exit;
        }
    }

    if ($err = validate_algerian_user_fields($nom, $email, $tel)) {
        set_flash($err, 'error');
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? BASE_URL . '/admin/index.php'));
        exit;
    }

    $avatarVal = ($avatar !== '') ? $avatar : null;
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `telephone` = ?, `avatar` = ?, `mot_de_passe` = ? WHERE `id` = ?");
        $stmt->bind_param("sssssi", $nom, $email, $tel, $avatarVal, $hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE `utilisateurs` SET `nom` = ?, `email` = ?, `telephone` = ?, `avatar` = ? WHERE `id` = ?");
        $stmt->bind_param("ssssi", $nom, $email, $tel, $avatarVal, $id);
    }
    $stmt->execute();

    set_flash('Profil mis à jour avec succès !');
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? BASE_URL . '/admin/index.php'));
    exit;
}
