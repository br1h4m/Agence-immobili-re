<?php
/**
 * admin/bien-edit.php - Création & modification d'un bien immobilier (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';

$currentUser = require_admin($conn);
$activePage = 'biens';
$b = BASE_URL;

$locations = algeria_locations();
$curatedGallery = [
    ['category' => 'Villas & Extérieurs',  'title' => 'Villa contemporaine avec piscine',          'url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Villas & Extérieurs',  'title' => "Façade d'architecte moderne",               'url' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Villas & Extérieurs',  'title' => "Villa d'exception avec jardin paysager",    'url' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Villas & Extérieurs',  'title' => 'Propriété de luxe avec terrasse',           'url' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Appartements & Salons','title' => 'Grand séjour lumineux et ouvert',           'url' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Appartements & Salons','title' => 'Salon moderne haut standing',               'url' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Appartements & Salons','title' => 'Appartement contemporain avec baie vitrée', 'url' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Cuisines & Suites',    'title' => 'Cuisine équipée moderne îlot central',      'url' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Cuisines & Suites',    'title' => 'Suite parentale spacieuse',                 'url' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Cuisines & Suites',    'title' => 'Salle de bain design marbre',               'url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Bureaux & Commerces',  'title' => 'Immeuble de bureaux contemporain',          'url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80'],
    ['category' => 'Bureaux & Commerces',  'title' => 'Plateau bureau moderne et lumineux',        'url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'],
];
$allFeatures = ['Jardin', 'Piscine', 'Terrasse', 'Garage', 'Climatisation', 'Chauffage central', 'Ascenseur', 'Vue mer', 'Cuisine équipée', 'Fibre optique', 'Gardiennage', 'Interphone', 'Balcon', 'Double vitrage', 'Suite parentale'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$property = null;

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM `properties` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();
    if (!$property) {
        set_flash('Bien introuvable.', 'error');
        header('Location: ' . $b . '/admin/biens.php');
        exit;
    }
}

$formError = '';

// Traitement PHP POST (Création ou Mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_property') {
    $editId     = (int)($_POST['id'] ?? $id);
    $titre      = trim((string)($_POST['titre'] ?? ''));
    $ref        = trim((string)($_POST['reference'] ?? ''));
    $type       = trim((string)($_POST['type'] ?? 'Appartement'));
    $trans      = trim((string)($_POST['transaction'] ?? 'Acheter'));
    $statut     = trim((string)($_POST['statut'] ?? 'Disponible'));
    $aLaUne     = isset($_POST['a_la_une']) ? 1 : 0;
    $nouveau    = isset($_POST['nouveau']) ? 1 : 0;
    $prix       = (float)($_POST['prix'] ?? 0);
    $periode    = trim((string)($_POST['periode_prix'] ?? ''));
    $periodeVal = ($periode !== '') ? $periode : null;

    $wilaya     = trim((string)($_POST['wilaya'] ?? 'Béjaïa'));
    $commune    = trim((string)($_POST['commune'] ?? 'Béjaïa'));
    $quartier   = trim((string)($_POST['quartier'] ?? ''));
    $latitude   = ($_POST['latitude']  ?? '') !== '' ? (float)$_POST['latitude']  : null;
    $longitude  = ($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null;

    $surface    = (float)($_POST['surface'] ?? 0);
    $surfTer    = ($_POST['surface_terrain'] ?? '') !== '' ? (float)$_POST['surface_terrain'] : null;
    $chambres   = ($_POST['chambres'] ?? '') !== '' ? (int)$_POST['chambres'] : null;
    $sdb        = ($_POST['salles_de_bain'] ?? '') !== '' ? (int)$_POST['salles_de_bain'] : null;
    $etage      = ($_POST['etage'] ?? '') !== '' ? (int)$_POST['etage'] : null;
    $etages     = ($_POST['etages'] ?? '') !== '' ? (int)$_POST['etages'] : null;
    $annee      = ($_POST['annee'] ?? '') !== '' ? (int)$_POST['annee'] : null;
    $garage     = isset($_POST['garage']) ? 1 : 0;

    $features   = array_values(array_map('strval', (array)($_POST['features'] ?? [])));
    if ($garage && !in_array('Garage', $features, true)) {
        $features[] = 'Garage';
    }
    $equipJson  = json_encode($features, JSON_UNESCAPED_UNICODE);
    $desc       = trim((string)($_POST['description'] ?? ''));

    // Collecte des images conservées + fichiers uploadés + URLs
    $imgs = array_values(array_filter(array_map('trim', (array)($_POST['images_list'] ?? []))));
    if (!empty($_FILES['upload_files']['name']) && is_array($_FILES['upload_files']['name'])) {
        $countFiles = count($_FILES['upload_files']['name']);
        for ($i = 0; $i < $countFiles; $i++) {
            if ($_FILES['upload_files']['error'][$i] === UPLOAD_ERR_OK) {
                $single = [
                    'name'     => $_FILES['upload_files']['name'][$i],
                    'type'     => $_FILES['upload_files']['type'][$i],
                    'tmp_name' => $_FILES['upload_files']['tmp_name'][$i],
                    'error'    => $_FILES['upload_files']['error'][$i],
                    'size'     => $_FILES['upload_files']['size'][$i],
                ];
                try {
                    $imgs[] = handle_file_upload($single, 'properties');
                } catch (Throwable $ex) {
                    $formError = $ex->getMessage();
                }
            }
        }
    }
    if (empty($imgs)) {
        $imgs[] = $curatedGallery[0]['url'];
    }
    $imagesJson = json_encode(array_values(array_unique($imgs)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($titre === '' || $prix <= 0) {
        $formError = "Le titre et un prix supérieur à 0 sont obligatoires.";
    }

    if ($formError === '') {
        if ($editId > 0) {
            if ($ref === '') {
                $ref = 'IML-' . str_pad((string)$editId, 3, '0', STR_PAD_LEFT);
            }
            $stmtUp = $conn->prepare("
                UPDATE `properties` SET
                    `reference` = ?, `titre` = ?, `type` = ?, `transaction` = ?, `prix` = ?, `periode_prix` = ?,
                    `wilaya` = ?, `commune` = ?, `quartier` = ?, `latitude` = ?, `longitude` = ?,
                    `surface` = ?, `surface_terrain` = ?,
                    `chambres` = ?, `salles_de_bain` = ?, `etage` = ?, `etages` = ?, `annee` = ?, `garage` = ?,
                    `equipements` = ?, `images` = ?, `description` = ?, `statut` = ?, `a_la_une` = ?, `nouveau` = ?
                WHERE `id` = ?
            ");
            $stmtUp->bind_param(
                'ssssdssssddddiiiiiissssiii',
                $ref, $titre, $type, $trans, $prix, $periodeVal,
                $wilaya, $commune, $quartier, $latitude, $longitude,
                $surface, $surfTer,
                $chambres, $sdb, $etage, $etages, $annee, $garage,
                $equipJson, $imagesJson, $desc, $statut, $aLaUne, $nouveau,
                $editId
            );
            $stmtUp->execute();
            set_flash('Bien mis à jour avec succès !');
        } else {
            if ($ref === '') {
                $ref = 'IML-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);
            }
            $stmtIn = $conn->prepare("
                INSERT INTO `properties` (
                    `reference`, `titre`, `type`, `transaction`, `prix`, `periode_prix`,
                    `wilaya`, `commune`, `quartier`, `latitude`, `longitude`,
                    `surface`, `surface_terrain`,
                    `chambres`, `salles_de_bain`, `etage`, `etages`, `annee`, `garage`,
                    `equipements`, `images`, `description`, `statut`, `a_la_une`, `nouveau`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtIn->bind_param(
                'ssssdssssddddiiiiiissssii',
                $ref, $titre, $type, $trans, $prix, $periodeVal,
                $wilaya, $commune, $quartier, $latitude, $longitude,
                $surface, $surfTer,
                $chambres, $sdb, $etage, $etages, $annee, $garage,
                $equipJson, $imagesJson, $desc, $statut, $aLaUne, $nouveau
            );
            $stmtIn->execute();
            set_flash('Nouveau bien créé avec succès !');
        }
        header('Location: ' . $b . '/admin/biens.php');
        exit;
    }
}

$pageHeading = $isEdit ? ('Modifier : ' . $property['titre']) : 'Ajouter un nouveau bien';
$breadcrumbs = [['label' => 'Biens', 'href' => $b . '/admin/biens.php'], ['label' => $isEdit ? 'Modifier' : 'Nouveau']];

$vTitle   = $_POST['titre'] ?? $property['titre'] ?? '';
$vRef     = $_POST['reference'] ?? $property['reference'] ?? '';
$vType    = $_POST['type'] ?? $property['type'] ?? 'Villa';
$vTrans   = $_POST['transaction'] ?? $property['transaction'] ?? 'Acheter';
$vStatus  = $_POST['statut'] ?? $property['statut'] ?? 'Disponible';
$vUne     = isset($_POST['action']) ? isset($_POST['a_la_une']) : !empty($property['a_la_une']);
$vNew     = isset($_POST['action']) ? isset($_POST['nouveau']) : !empty($property['nouveau']);
$vPrix    = $_POST['prix'] ?? $property['prix'] ?? '';
$vPeriode = $_POST['periode_prix'] ?? $property['periode_prix'] ?? '';
$vWilaya  = $_POST['wilaya'] ?? $property['wilaya'] ?? 'Béjaïa';
$vCity    = $_POST['commune'] ?? $property['commune'] ?? 'Béjaïa (Centre)';
$vQuartier= $_POST['quartier'] ?? $property['quartier'] ?? '';
$vLat     = $_POST['latitude']  ?? $property['latitude']  ?? '';
$vLng     = $_POST['longitude'] ?? $property['longitude'] ?? '';
$vSurface = $_POST['surface'] ?? $property['surface'] ?? '';
$vSurfTer = $_POST['surface_terrain'] ?? $property['surface_terrain'] ?? '';
$vChambres= $_POST['chambres'] ?? $property['chambres'] ?? '';
$vSdb     = $_POST['salles_de_bain'] ?? $property['salles_de_bain'] ?? '';
$vEtage   = $_POST['etage'] ?? $property['etage'] ?? '';
$vEtages  = $_POST['etages'] ?? $property['etages'] ?? '';
$vAnnee   = $_POST['annee'] ?? $property['annee'] ?? '';
$vGarage  = isset($_POST['action']) ? isset($_POST['garage']) : !empty($property['garage']);
$vFeatures= isset($_POST['action']) ? (array)($_POST['features'] ?? []) : (json_decode($property['equipements'] ?? '[]', true) ?: []);
$vDesc    = $_POST['description'] ?? $property['description'] ?? '';
$vImages  = isset($_POST['action'])
    ? array_values(array_filter((array)($_POST['images_list'] ?? [])))
    : (json_decode($property['images'] ?? '[]', true) ?: [$curatedGallery[0]['url']]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageHeading) ?> — Administration Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="<?= $b ?>/admin/assets/css/admin.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
</head>
<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-main-wrap">
      <?php include __DIR__ . '/includes/topbar.php'; ?>

      <main class="admin-content">
        <?php if ($formError !== ''): ?>
          <div style="padding:14px 18px;background:var(--admin-danger-soft);color:var(--admin-danger);border:1px solid #F0C4C1;border-radius:var(--radius-sm);margin-bottom:20px;font-weight:500;">
            <?= e($formError) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= $b ?>/admin/bien-edit.php<?= $isEdit ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_property">
          <input type="hidden" name="id" value="<?= $id ?>">

          <div class="card" style="margin-bottom: 28px;">
            <div class="card-header">
              <div>
                <h2 class="card-title"><?= $isEdit ? 'Modification de la fiche' : 'Création de la fiche' ?></h2>
                <p class="card-subtitle">Renseignez les détails du bien pour l'afficher sur le catalogue et le site public.</p>
              </div>
              <div style="display: flex; gap: 10px;">
                <a href="<?= $b ?>/admin/biens.php" class="btn btn-outline btn-sm">Annuler</a>
                <button type="submit" class="btn btn-primary btn-sm">
                  <?= admin_icon('check', 16) ?>
                  <span>Enregistrer le bien</span>
                </button>
              </div>
            </div>

            <div class="card-body">
              <!-- Section 1 : Informations Générales -->
              <div class="form-section">
                <h3 class="form-section-title"><?= admin_icon('building', 18) ?> <span>Informations principales</span></h3>
                <div class="form-grid">
                  <div class="form-col-8 form-group">
                    <label class="form-label" for="prop-title">Titre de l'annonce <span class="required">*</span></label>
                    <input type="text" id="prop-title" name="titre" class="form-input" required placeholder="ex. Villa contemporaine avec vue mer à Tichy" value="<?= e($vTitle) ?>">
                  </div>

                  <div class="form-col-4 form-group">
                    <label class="form-label" for="prop-ref">Référence de l'agence</label>
                    <input type="text" id="prop-ref" name="reference" class="form-input" placeholder="ex. IML-001 (auto si vide)" value="<?= e($vRef) ?>">
                    <span class="form-hint">Laisser vide pour générer automatiquement</span>
                  </div>

                  <div class="form-col-4 form-group">
                    <label class="form-label" for="prop-type">Type de bien <span class="required">*</span></label>
                    <select id="prop-type" name="type" class="form-select" required>
                      <?php foreach (['Villa', 'Appartement', 'Duplex', 'Terrain', 'Bureau', 'Local commercial', 'Immeuble'] as $tp): ?>
                        <option value="<?= e($tp) ?>" <?= $vType === $tp ? 'selected' : '' ?>><?= e($tp) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-col-4 form-group">
                    <label class="form-label" for="prop-transaction">Transaction <span class="required">*</span></label>
                    <select id="prop-transaction" name="transaction" class="form-select" required>
                      <option value="Acheter" <?= $vTrans === 'Acheter' ? 'selected' : '' ?>>Vente (Acheter)</option>
                      <option value="Louer" <?= $vTrans === 'Louer' ? 'selected' : '' ?>>Location (Louer)</option>
                    </select>
                  </div>

                  <div class="form-col-4 form-group">
                    <label class="form-label" for="prop-status">Statut de disponibilité</label>
                    <select id="prop-status" name="statut" class="form-select">
                      <?php foreach (['Disponible', 'Réservé', 'Vendu', 'Loué'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $vStatus === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="checkbox-label" style="margin-top: 10px;">
                      <input type="checkbox" name="a_la_une" <?= $vUne ? 'checked' : '' ?>>
                      <span>Mettre ce bien <strong>À la une</strong> (Coup de cœur sur l'accueil)</span>
                    </label>
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="checkbox-label" style="margin-top: 10px;">
                      <input type="checkbox" name="nouveau" <?= $vNew ? 'checked' : '' ?>>
                      <span>Badge <strong>Nouveauté</strong> sur la fiche</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Section 2 : Prix & Conditions -->
              <div class="form-section">
                <h3 class="form-section-title"><?= admin_icon('dashboard', 18) ?> <span>Prix &amp; Conditions</span></h3>
                <div class="form-grid">
                  <div class="form-col-6 form-group">
                    <label class="form-label" for="prop-price">Prix en Dinars Algériens (DA) <span class="required">*</span></label>
                    <input type="number" id="prop-price" name="prix" class="form-input" required min="1" step="any" placeholder="ex. 25000000" value="<?= e((string)$vPrix) ?>">
                    <span class="form-hint">Montant total pour une vente ou mensuel pour une location</span>
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="prop-priceperiod">Période du loyer (si location)</label>
                    <select id="prop-priceperiod" name="periode_prix" class="form-select">
                      <option value="" <?= $vPeriode === '' ? 'selected' : '' ?>>Prix net / global (Vente)</option>
                      <option value="mois" <?= $vPeriode === 'mois' ? 'selected' : '' ?>>Par mois (Location)</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Section 3 : Localisation -->
              <div class="form-section">
                <h3 class="form-section-title"><?= admin_icon('phone', 18) ?> <span>Localisation (Wilaya &amp; Commune liées)</span></h3>
                <div class="form-grid">
                  <div class="form-col-4 form-group">
                    <label class="form-label" for="prop-wilaya-select">Wilaya <span class="required">*</span></label>
                    <select id="prop-wilaya-select" name="wilaya" class="form-select" required>
                      <?php foreach (array_keys($locations) as $wName): ?>
                        <option value="<?= e($wName) ?>" <?= mb_strtolower($vWilaya, 'UTF-8') === mb_strtolower($wName, 'UTF-8') ? 'selected' : '' ?>><?= e($wName) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-col-4 form-group">
                    <label class="form-label" for="prop-city-select">Commune / Ville <span class="required">*</span></label>
                    <select id="prop-city-select" name="commune" class="form-select" required>
                      <?php
                      $initCities = $locations[$vWilaya] ?? [$vCity];
                      if (!in_array($vCity, $initCities, true) && $vCity !== '') array_unshift($initCities, $vCity);
                      foreach ($initCities as $cName):
                      ?>
                        <option value="<?= e($cName) ?>" <?= mb_strtolower($vCity, 'UTF-8') === mb_strtolower($cName, 'UTF-8') ? 'selected' : '' ?>><?= e($cName) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-col-4 form-group">
                    <label class="form-label" for="prop-district">Quartier / Adresse</label>
                    <input type="text" id="prop-district" name="quartier" class="form-input" placeholder="ex. Boulevard de la Soummam..." value="<?= e($vQuartier) ?>">
                  </div>
                </div>

                <!-- Widget Carte & Géolocalisation -->
                <div style="margin-top: 18px; border: 1px solid var(--admin-border); border-radius: var(--radius-sm); overflow: hidden; background: var(--admin-surface-alt);">
                  <div style="padding: 12px 16px; border-bottom: 1px solid var(--admin-border); display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 260px; position: relative;">
                      <input type="text" id="map-search-input" class="form-input" placeholder="🔍  Rechercher une adresse sur la carte..." autocomplete="off" style="padding-right: 40px;">
                      <div id="map-search-results" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:9999; background:var(--admin-surface); border:1px solid var(--admin-border); border-top:none; border-radius:0 0 var(--radius-xs) var(--radius-xs); max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,.12);"></div>
                    </div>
                    <button type="button" id="btn-map-search" class="btn btn-outline btn-sm">Rechercher</button>
                    <button type="button" id="btn-map-locate-me" class="btn btn-outline btn-sm" title="Utiliser ma position GPS">📍 Ma position</button>
                    <span id="map-coords-display" style="font-size:0.8rem; color:var(--admin-text-muted); margin-left:auto;"></span>
                  </div>
                  <div id="prop-map" style="height: 320px; width: 100%;"></div>
                  <div style="padding: 8px 16px; background: var(--admin-surface-alt); border-top: 1px solid var(--admin-border); font-size: 0.78rem; color: var(--admin-text-muted);">
                    <?= admin_icon('pin', 13) ?> Cliquez sur la carte pour placer le marqueur, ou cherchez une adresse ci-dessus.
                  </div>
                </div>

                <!-- Champs cachés latitude / longitude -->
                <input type="hidden" id="prop-latitude"  name="latitude"  value="<?= e((string)$vLat) ?>">
                <input type="hidden" id="prop-longitude" name="longitude" value="<?= e((string)$vLng) ?>">
              </div>

              <!-- Section 4 : Caractéristiques & Mesures -->
              <div class="form-section">
                <h3 class="form-section-title"><?= admin_icon('building', 18) ?> <span>Caractéristiques &amp; Mesures</span></h3>
                <div class="form-grid">
                  <div class="form-col-3 form-group">
                    <label class="form-label" for="prop-surface">Surface habitable (m²) <span class="required">*</span></label>
                    <input type="number" id="prop-surface" name="surface" class="form-input" required min="1" step="any" placeholder="ex. 180" value="<?= e((string)$vSurface) ?>">
                  </div>
                  <div class="form-col-3 form-group">
                    <label class="form-label" for="prop-landsurface">Surface du terrain (m²)</label>
                    <input type="number" id="prop-landsurface" name="surface_terrain" class="form-input" min="0" step="any" placeholder="Optionnel" value="<?= e((string)$vSurfTer) ?>">
                  </div>
                  <div class="form-col-3 form-group">
                    <label class="form-label" for="prop-bedrooms">Chambres</label>
                    <input type="number" id="prop-bedrooms" name="chambres" class="form-input" min="0" placeholder="ex. 4" value="<?= e((string)$vChambres) ?>">
                  </div>
                  <div class="form-col-3 form-group">
                    <label class="form-label" for="prop-bathrooms">Salles de bain</label>
                    <input type="number" id="prop-bathrooms" name="salles_de_bain" class="form-input" min="0" placeholder="ex. 2" value="<?= e((string)$vSdb) ?>">
                  </div>
                  <div class="form-col-3 form-group">
                    <label class="form-label" for="prop-floor">Étage</label>
                    <input type="number" id="prop-floor" name="etage" class="form-input" placeholder="ex. 0 (RDC), 2..." value="<?= e((string)$vEtage) ?>">
                  </div>
                  <div class="form-col-3 form-group">
                    <label class="form-label" for="prop-floors">Nombre total d'étages</label>
                    <input type="number" id="prop-floors" name="etages" class="form-input" min="1" placeholder="ex. 2" value="<?= e((string)$vEtages) ?>">
                  </div>
                  <div class="form-col-3 form-group">
                    <label class="form-label" for="prop-year">Année de construction</label>
                    <input type="number" id="prop-year" name="annee" class="form-input" min="1900" max="2035" placeholder="ex. 2021" value="<?= e((string)$vAnnee) ?>">
                  </div>
                  <div class="form-col-3 form-group">
                    <label class="checkbox-label" style="margin-top: 30px;">
                      <input type="checkbox" name="garage" <?= $vGarage ? 'checked' : '' ?>>
                      <span>Possède un garage</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Section 5 : Équipements -->
              <div class="form-section">
                <h3 class="form-section-title"><?= admin_icon('check', 18) ?> <span>Équipements &amp; Prestations</span></h3>
                <div class="form-checkbox-group">
                  <?php foreach ($allFeatures as $feat): ?>
                    <label class="checkbox-label">
                      <input type="checkbox" name="features[]" value="<?= e($feat) ?>" <?= in_array($feat, $vFeatures, true) ? 'checked' : '' ?>>
                      <span><?= e($feat) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Section 6 : Description -->
              <div class="form-section">
                <h3 class="form-section-title"><?= admin_icon('edit', 18) ?> <span>Description détaillée</span></h3>
                <div class="form-group">
                  <label class="form-label" for="prop-desc">Texte descriptif du bien <span class="required">*</span></label>
                  <textarea id="prop-desc" name="description" class="form-textarea" required rows="6" placeholder="Décrivez les atouts majeurs du bien..."><?= e($vDesc) ?></textarea>
                </div>
              </div>

              <!-- Section 7 : Galerie Photos -->
              <div class="form-section">
                <h3 class="form-section-title"><?= admin_icon('eye', 18) ?> <span>Galerie photos</span></h3>
                <p style="font-size: 0.82rem; color: var(--admin-text-muted); margin: 0 0 12px;">
                  Choisissez des photos depuis notre galerie officielle, téléversez des images depuis votre appareil ou ajoutez un lien URL direct.
                </p>

                <div class="image-add-box" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
                  <input type="url" id="new-image-url" class="form-input" style="flex: 1; min-width: 250px;" placeholder="Coller une URL d'image (https://...)">
                  <button type="button" class="btn btn-outline" id="btn-add-url-image">
                    <?= admin_icon('plus', 15) ?> <span>Ajouter l'URL</span>
                  </button>
                  <button type="button" class="btn btn-primary" id="btn-open-gallery-picker" style="background: var(--admin-gold); color: #fff; border: none;">
                    <?= admin_icon('eye', 15) ?> <span>Choisir depuis notre galerie</span>
                  </button>
                  <button type="button" class="btn btn-outline" id="btn-trigger-upload">
                    <?= admin_icon('upload', 15) ?> <span>Téléverser un fichier</span>
                  </button>
                  <input type="file" name="upload_files[]" id="upload-files-input" accept="image/*" multiple style="display: none;">
                </div>

                <div id="upload-queue-note" style="font-size:0.82rem;color:var(--admin-gold);margin-bottom:10px;display:none;"></div>

                <div class="images-gallery-editor" id="images-container">
                  <?php foreach ($vImages as $idx => $imgUrl): ?>
                    <div class="image-preview-card">
                      <input type="hidden" name="images_list[]" value="<?= e($imgUrl) ?>">
                      <img src="<?= e(asset_url($imgUrl)) ?>" alt="Photo <?= $idx + 1 ?>" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=400&q=80'">
                      <?php if ($idx === 0): ?>
                        <span style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 2px;">Couverture</span>
                      <?php endif; ?>
                      <button type="button" class="image-remove-btn" title="Supprimer cette photo" onclick="this.parentElement.remove()">
                        <?= admin_icon('close', 12) ?>
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="card-footer">
              <a href="<?= $b ?>/admin/biens.php" class="btn btn-outline">Annuler</a>
              <button type="submit" class="btn btn-primary">
                <?= admin_icon('check', 16) ?>
                <span><?= $isEdit ? 'Enregistrer les modifications' : 'Créer et publier le bien' ?></span>
              </button>
            </div>
          </div>
        </form>

        <!-- Modale statique Sélecteur de Galerie Officielle (HTML brut) -->
        <div id="gallery-picker-modal" class="admin-modal-overlay">
          <div class="admin-modal-card" style="max-width: 900px; width: 100%; max-height: 85vh; display: flex; flex-direction: column; padding: 0;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600;">Galerie d'images Imolode</h3>
                <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">Cochez les photos pour les ajouter à votre bien</p>
              </div>
              <button type="button" class="btn-icon" id="btn-close-gallery-modal"><?= admin_icon('close', 18) ?></button>
            </div>

            <div style="padding: 10px 20px; background: var(--admin-surface-alt); border-bottom: 1px solid var(--admin-border); display: flex; gap: 8px; flex-wrap: wrap;" id="gallery-cat-pills">
              <button type="button" class="btn btn-outline btn-sm is-active" data-cat="all">Toutes</button>
              <button type="button" class="btn btn-outline btn-sm" data-cat="Villas & Extérieurs">Villas &amp; Extérieurs</button>
              <button type="button" class="btn btn-outline btn-sm" data-cat="Appartements & Salons">Appartements &amp; Salons</button>
              <button type="button" class="btn btn-outline btn-sm" data-cat="Cuisines & Suites">Cuisines &amp; Suites</button>
              <button type="button" class="btn btn-outline btn-sm" data-cat="Bureaux & Commerces">Bureaux &amp; Commerces</button>
            </div>

            <div style="padding: 20px; overflow-y: auto; flex: 1;">
              <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px;" id="gallery-grid-items">
                <?php foreach ($curatedGallery as $gItem): ?>
                  <label class="gallery-picker-item" data-cat="<?= e($gItem['category']) ?>" style="border: 2px solid var(--admin-border); border-radius: var(--radius-xs); overflow: hidden; cursor: pointer; position: relative; background: var(--admin-surface-alt); display: block;">
                    <input type="checkbox" class="gallery-pick-cb" value="<?= e($gItem['url']) ?>" style="position:absolute; top:8px; right:8px; width:18px; height:18px; accent-color: var(--admin-gold);">
                    <img src="<?= e($gItem['url']) ?>" alt="<?= e($gItem['title']) ?>" style="width: 100%; height: 120px; object-fit: cover; display: block;">
                    <div style="padding: 6px 8px; font-size: 0.75rem; font-weight: 500; color: var(--admin-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                      <?= e($gItem['title']) ?>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div style="padding: 14px 20px; border-top: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; background: var(--admin-surface-alt);">
              <span id="gallery-selected-count" style="font-size: 0.85rem; color: var(--admin-text-muted);">0 photo sélectionnée</span>
              <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-outline btn-sm" id="btn-cancel-gallery-modal">Annuler</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-insert-gallery-photos">Insérer dans le bien</button>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer_scripts.php'; ?>

  <script>
    (function() {
      // 1. Liaison Wilaya -> Communes
      var locations = <?= json_encode($locations, JSON_UNESCAPED_UNICODE) ?>;
      var wSelect = document.getElementById('prop-wilaya-select');
      var cSelect = document.getElementById('prop-city-select');
      wSelect?.addEventListener('change', function() {
        var list = locations[wSelect.value] || [wSelect.value];
        cSelect.innerHTML = '';
        list.forEach(function(city) {
          var opt = document.createElement('option');
          opt.value = city;
          opt.textContent = city;
          cSelect.appendChild(opt);
        });
      });

      // 2. Ajout image par URL + Aperçu upload local
      var container = document.getElementById('images-container');
      function appendImageCard(url) {
        var card = document.createElement('div');
        card.className = 'image-preview-card';
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'images_list[]';
        hidden.value = url;
        var img = document.createElement('img');
        img.src = url;
        img.alt = 'Photo';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'image-remove-btn';
        btn.textContent = '×';
        btn.onclick = function() { card.remove(); };
        card.appendChild(hidden);
        card.appendChild(img);
        card.appendChild(btn);
        container?.appendChild(card);
      }

      document.getElementById('btn-add-url-image')?.addEventListener('click', function() {
        var inp = document.getElementById('new-image-url');
        var val = inp?.value.trim();
        if (val) {
          appendImageCard(val);
          inp.value = '';
        }
      });

      var fileInput = document.getElementById('upload-files-input');
      var queueNote = document.getElementById('upload-queue-note');
      document.getElementById('btn-trigger-upload')?.addEventListener('click', function() { fileInput?.click(); });
      fileInput?.addEventListener('change', function() {
        var count = fileInput.files ? fileInput.files.length : 0;
        if (queueNote) {
          queueNote.style.display = count > 0 ? 'block' : 'none';
          queueNote.textContent = count + ' fichier(s) local(aux) prêt(s) à être envoyé(s) lors de l\'enregistrement.';
        }
      });

      // 3. Modale galerie statique
      var modal = document.getElementById('gallery-picker-modal');
      var countSpan = document.getElementById('gallery-selected-count');
      var cbs = document.querySelectorAll('.gallery-pick-cb');

      function updateGalleryCount() {
        var n = 0;
        cbs.forEach(function(cb) {
          var item = cb.closest('.gallery-picker-item');
          if (cb.checked) { n++; if (item) item.style.borderColor = 'var(--admin-gold)'; }
          else { if (item) item.style.borderColor = 'var(--admin-border)'; }
        });
        if (countSpan) countSpan.textContent = n + ' photo' + (n > 1 ? 's' : '') + ' sélectionnée' + (n > 1 ? 's' : '');
      }

      cbs.forEach(function(cb) { cb.addEventListener('change', updateGalleryCount); });

      document.querySelectorAll('#gallery-cat-pills button').forEach(function(btn) {
        btn.addEventListener('click', function() {
          document.querySelectorAll('#gallery-cat-pills button').forEach(function(b) { b.classList.remove('is-active'); });
          btn.classList.add('is-active');
          var cat = btn.getAttribute('data-cat');
          document.querySelectorAll('#gallery-grid-items .gallery-picker-item').forEach(function(item) {
            item.style.display = (cat === 'all' || item.getAttribute('data-cat') === cat) ? 'block' : 'none';
          });
        });
      });

      document.getElementById('btn-open-gallery-picker')?.addEventListener('click', function() {
        cbs.forEach(function(cb) { cb.checked = false; });
        updateGalleryCount();
        modal?.classList.add('is-active');
      });
      function closeGModal() { modal?.classList.remove('is-active'); }
      document.getElementById('btn-close-gallery-modal')?.addEventListener('click', closeGModal);
      document.getElementById('btn-cancel-gallery-modal')?.addEventListener('click', closeGModal);
      modal?.addEventListener('click', function(e) { if (e.target === modal) closeGModal(); });

      document.getElementById('btn-insert-gallery-photos')?.addEventListener('click', function() {
        cbs.forEach(function(cb) { if (cb.checked) appendImageCard(cb.value); });
        closeGModal();
      });
    })();
  </script>

  <!-- Leaflet JS + Carte de localisation avec Nominatim -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
  (function() {
    var latInput  = document.getElementById('prop-latitude');
    var lngInput  = document.getElementById('prop-longitude');
    var coordsDisplay = document.getElementById('map-coords-display');
    var searchInput   = document.getElementById('map-search-input');
    var searchResults = document.getElementById('map-search-results');

    // Coordonnées initiales : valeur sauvée ou centre Algérie
    var initLat = parseFloat(latInput.value) || 36.7372;
    var initLng = parseFloat(lngInput.value) || 3.0865;
    var initZoom = (latInput.value && lngInput.value) ? 14 : 6;

    var map = L.map('prop-map', { zoomControl: true }).setView([initLat, initLng], initZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19
    }).addTo(map);

    // Marqueur personnalisé
    var markerIcon = L.divIcon({
      className: '',
      html: '<div style="width:28px;height:28px;background:var(--admin-gold,#C8A95B);border:3px solid #fff;border-radius:50% 50% 50% 0;transform:rotate(-45deg);box-shadow:0 2px 8px rgba(0,0,0,.35);"></div>',
      iconSize: [28, 28],
      iconAnchor: [14, 28],
      popupAnchor: [0, -30]
    });

    var marker = null;

    function updateCoords(lat, lng, label) {
      latInput.value  = lat.toFixed(7);
      lngInput.value  = lng.toFixed(7);
      var txt = lat.toFixed(5) + ', ' + lng.toFixed(5);
      if (label) txt = label + '  (' + txt + ')';
      if (coordsDisplay) coordsDisplay.textContent = '📌 ' + txt;
    }

    function placeMarker(lat, lng, label, zoom) {
      if (marker) {
        marker.setLatLng([lat, lng]);
      } else {
        marker = L.marker([lat, lng], { icon: markerIcon, draggable: true }).addTo(map);
        marker.on('dragend', function() {
          var p = marker.getLatLng();
          updateCoords(p.lat, p.lng, '');
          reverseGeocode(p.lat, p.lng);
        });
      }
      map.setView([lat, lng], zoom || map.getZoom());
      updateCoords(lat, lng, label);
    }

    // Clic carte → place marqueur
    map.on('click', function(e) {
      placeMarker(e.latlng.lat, e.latlng.lng, '', Math.max(map.getZoom(), 15));
      reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    // Marqueur initial si coordonnées déjà présentes
    if (latInput.value && lngInput.value) {
      placeMarker(initLat, initLng, '', initZoom);
    }

    // Reverse geocoding (affiche l'adresse trouvée)
    function reverseGeocode(lat, lng) {
      fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=fr')
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d && d.display_name) {
            if (coordsDisplay) coordsDisplay.textContent = '📌 ' + d.display_name.substring(0, 60) + '…';
          }
        }).catch(function(){});
    }

    // Recherche Nominatim
    var searchTimer = null;
    function doSearch(q) {
      if (!q || q.length < 3) { searchResults.style.display = 'none'; return; }
      fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&limit=6&accept-language=fr&countrycodes=dz')
        .then(function(r) { return r.json(); })
        .then(function(results) {
          searchResults.innerHTML = '';
          if (!results.length) {
            searchResults.innerHTML = '<div style="padding:10px 14px;color:var(--admin-text-muted);font-size:0.83rem;">Aucun résultat trouvé</div>';
            searchResults.style.display = 'block';
            return;
          }
          results.forEach(function(item) {
            var div = document.createElement('div');
            div.style.cssText = 'padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid var(--admin-border);';
            div.textContent = item.display_name;
            div.addEventListener('mouseenter', function() { div.style.background = 'var(--admin-surface-alt)'; });
            div.addEventListener('mouseleave', function() { div.style.background = ''; });
            div.addEventListener('click', function() {
              var lat = parseFloat(item.lat);
              var lng = parseFloat(item.lon);
              placeMarker(lat, lng, item.display_name.substring(0, 50), 15);
              searchInput.value = item.display_name;
              searchResults.style.display = 'none';
            });
            searchResults.appendChild(div);
          });
          searchResults.style.display = 'block';
        }).catch(function(){});
    }

    searchInput?.addEventListener('input', function() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function() { doSearch(searchInput.value.trim()); }, 350);
    });
    searchInput?.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); doSearch(searchInput.value.trim()); }
    });
    document.getElementById('btn-map-search')?.addEventListener('click', function() {
      doSearch(searchInput.value.trim());
    });

    // Bouton Ma Position
    document.getElementById('btn-map-locate-me')?.addEventListener('click', function() {
      if (!navigator.geolocation) { alert('Géolocalisation non supportée par ce navigateur.'); return; }
      navigator.geolocation.getCurrentPosition(function(pos) {
        placeMarker(pos.coords.latitude, pos.coords.longitude, 'Ma position', 16);
        reverseGeocode(pos.coords.latitude, pos.coords.longitude);
      }, function() { alert('Impossible d\'obtenir votre position.'); });
    });

    // Fermer la dropdown si clic ailleurs
    document.addEventListener('click', function(e) {
      if (!searchResults.contains(e.target) && e.target !== searchInput) {
        searchResults.style.display = 'none';
      }
    });

    // Recalibrer la carte quand on scrolle (Leaflet glitch)
    setTimeout(function() { map.invalidateSize(); }, 300);
  })();
  </script>
</body>
</html>
