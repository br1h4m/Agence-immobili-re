<?php
/**
 * admin/parametres.php - Paramètres généraux & CMS du site (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';

$currentUser = require_admin($conn);
$activePage = 'parametres';
$pageHeading = 'Paramètres généraux & Contenu du site';
$breadcrumbs = [['label' => 'Paramètres']];
$b = BASE_URL;

// Traitement PHP POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {

    // --- Gestion des uploads d'images CMS ---
    $uploadDir = __DIR__ . '/../uploads/cms/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $cmsUploads = [
        'cms_hero_file'     => 'cms_hero_image',
        'cms_about_file'    => 'cms_about_image',
        'cms_services_file' => 'cms_services_image',
    ];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    foreach ($cmsUploads as $fileKey => $postKey) {
        if (!empty($_FILES[$fileKey]['tmp_name']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $tmpPath  = $_FILES[$fileKey]['tmp_name'];
            $origName = $_FILES[$fileKey]['name'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $mime     = mime_content_type($tmpPath);
            if (in_array($ext, $allowedExts, true) && in_array($mime, $allowedMimes, true)) {
                // Supprimer l'ancienne image locale si elle existe
                $oldVal = trim((string)($_POST[$postKey] ?? ''));
                if ($oldVal && !str_starts_with($oldVal, 'http') && file_exists(__DIR__ . '/../' . $oldVal)) {
                    @unlink(__DIR__ . '/../' . $oldVal);
                }
                $slug     = str_replace(['cms_hero_image','cms_about_image','cms_services_image'], ['hero','about','services'], $postKey);
                $filename = $slug . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmpPath, $uploadDir . $filename)) {
                    $_POST[$postKey] = 'uploads/cms/' . $filename;
                }
            }
        }
    }
    // --- Fin gestion uploads ---

    $pairs = [
        'agency_name'        => trim((string)($_POST['agencyName'] ?? 'Imolode')),
        'tagline'            => trim((string)($_POST['tagline'] ?? '')),
        'description'        => trim((string)($_POST['description'] ?? '')),
        'phone'              => trim((string)($_POST['phone'] ?? '')),
        'agence_telephone'   => trim((string)($_POST['phone'] ?? '')),
        'whatsapp'           => trim((string)($_POST['whatsapp'] ?? '')),
        'agence_whatsapp'    => trim((string)($_POST['whatsapp'] ?? '')),
        'email'              => trim((string)($_POST['email'] ?? '')),
        'agence_email'       => trim((string)($_POST['email'] ?? '')),
        'hours'              => trim((string)($_POST['hours'] ?? '')),
        'agence_horaires'    => trim((string)($_POST['hours'] ?? '')),
        'address'            => trim((string)($_POST['address'] ?? '')),
        'agence_adresse'     => trim((string)($_POST['address'] ?? '')),
        'facebook'           => trim((string)($_POST['facebook'] ?? '')),
        'instagram'          => trim((string)($_POST['instagram'] ?? '')),

        'cms_hero_title'     => trim((string)($_POST['cms_hero_title'] ?? '')),
        'hero_title'         => trim((string)($_POST['cms_hero_title'] ?? '')),
        'cms_hero_subtitle'  => trim((string)($_POST['cms_hero_subtitle'] ?? '')),
        'hero_subtitle'      => trim((string)($_POST['cms_hero_subtitle'] ?? '')),
        'cms_hero_image'     => trim((string)($_POST['cms_hero_image'] ?? '')),
        'hero_image'         => trim((string)($_POST['cms_hero_image'] ?? '')),

        'cms_about_title'    => trim((string)($_POST['cms_about_title'] ?? '')),
        'about_title'        => trim((string)($_POST['cms_about_title'] ?? '')),
        'cms_about_text'     => trim((string)($_POST['cms_about_text'] ?? '')),
        'about_text'         => trim((string)($_POST['cms_about_text'] ?? '')),
        'cms_about_mission'  => trim((string)($_POST['cms_about_mission'] ?? '')),
        'cms_about_vision'   => trim((string)($_POST['cms_about_vision'] ?? '')),
        'cms_about_image'    => trim((string)($_POST['cms_about_image'] ?? '')),
        'about_image'        => trim((string)($_POST['cms_about_image'] ?? '')),

        'cms_services_intro' => trim((string)($_POST['cms_services_intro'] ?? '')),
        'cms_services_image' => trim((string)($_POST['cms_services_image'] ?? '')),
    ];

    $stmt = $conn->prepare("INSERT INTO `parametres` (`cle`, `valeur`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`)");
    foreach ($pairs as $k => $v) {
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
    }

    $tab = trim((string)($_POST['active_tab'] ?? 'identity'));
    set_flash('Paramètres et contenus mis à jour avec succès !');
    header('Location: ' . $b . '/admin/parametres.php?tab=' . urlencode($tab));
    exit;
}

$s = load_site_settings($conn);
$activeTab = trim((string)($_GET['tab'] ?? 'identity'));
if (!in_array($activeTab, ['identity', 'home', 'about', 'services'], true)) {
    $activeTab = 'identity';
}

$vAgencyName   = $s['agency_name'] ?? $s['agence_nom'] ?? 'Imolode';
$vTagline      = $s['tagline'] ?? $s['agence_slogan'] ?? 'L\'immobilier d\'exception à Béjaïa et en Algérie';
$vDescription  = $s['description'] ?? $s['agence_description'] ?? 'Agence immobilière agréée, spécialisée dans l\'accompagnement sur mesure.';
$vPhone        = $s['agence_telephone'] ?? $s['phone'] ?? '+213 555 12 34 56';
$vWhatsapp     = $s['whatsapp'] ?? $s['agence_whatsapp'] ?? '213555123456';
$vEmail        = $s['agence_email'] ?? $s['email'] ?? 'contact@immolode.dz';
$vHours        = $s['hours'] ?? $s['agence_horaires'] ?? 'Dimanche - Jeudi : 9h00 - 17h30 · Samedi : 9h00 - 13h00';
$vAddress      = $s['agence_adresse'] ?? $s['address'] ?? '12 rue Didouche Mourad, Béjaïa 06000, Algérie';
$vFacebook     = $s['facebook'] ?? 'https://facebook.com/imolode';
$vInstagram    = $s['instagram'] ?? 'https://instagram.com/immolode';

$vHeroTitle    = $s['cms_hero_title'] ?? $s['hero_title'] ?? 'L\'adresse de vos plus beaux projets';
$vHeroSubtitle = $s['cms_hero_subtitle'] ?? $s['hero_subtitle'] ?? 'Découvrez une sélection exclusive de biens immobiliers d\'exception en Algérie.';
$vHeroImage    = $s['cms_hero_image'] ?? $s['hero_image'] ?? 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1920&q=80';

$vAboutTitle   = $s['cms_about_title'] ?? $s['about_title'] ?? 'Une agence immobilière d\'exception au cœur de Béjaïa';
$vAboutText    = $s['cms_about_text'] ?? $s['about_text'] ?? 'Fondée avec la volonté de redéfinir les standards de l\'immobilier en Algérie, l\'agence Imolode vous garantit discrétion, expertise juridique et rigueur professionnelle.';
$vAboutMission = $s['cms_about_mission'] ?? 'Offrir un accompagnement transparent, sécurisé et personnalisé pour chaque acquéreur, vendeur ou locataire.';
$vAboutVision  = $s['cms_about_vision'] ?? 'Devenir la référence incontournable de l\'immobilier premium et de la gestion de patrimoine en Algérie.';
$vAboutImage   = $s['cms_about_image'] ?? $s['about_image'] ?? 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80';

$vServicesIntro = $s['cms_services_intro'] ?? 'De l\'estimation rigoureuse de votre bien jusqu\'à la finalisation notariée, découvrez une gamme complète de services immobiliers sur mesure.';
$vServicesImage = $s['cms_services_image'] ?? 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Paramètres &amp; CMS — Administration Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="<?= $b ?>/admin/assets/css/admin.css">
</head>
<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-main-wrap">
      <?php include __DIR__ . '/includes/topbar.php'; ?>

      <main class="admin-content">
        <form method="POST" action="<?= $b ?>/admin/parametres.php" enctype="multipart/form-data" style="max-width: 1040px;">
          <input type="hidden" name="action" value="save_settings">
          <input type="hidden" name="active_tab" id="active-tab-input" value="<?= e($activeTab) ?>">

          <!-- Entête & Sauvegarde -->
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
              <h2 style="margin: 0; font-family: var(--font-serif); font-size: 1.4rem; font-weight: 600;">Gestion du site &amp; CMS</h2>
              <p style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.88rem;">
                Modifiez directement les coordonnées, les bannières, les photos et les textes des pages sans passer par le code.
              </p>
            </div>
            <button type="submit" class="btn btn-primary">
              <?= admin_icon('check', 16) ?>
              <span>Enregistrer les modifications</span>
            </button>
          </div>

          <!-- Onglets -->
          <div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid var(--admin-border); padding-bottom: 8px; overflow-x: auto;" id="cms-tabs-nav">
            <button type="button" class="btn btn-outline btn-sm <?= $activeTab === 'identity' ? 'is-active' : '' ?>" data-tab="identity">
              <?= admin_icon('settings', 14) ?> Identité &amp; Coordonnées
            </button>
            <button type="button" class="btn btn-outline btn-sm <?= $activeTab === 'home' ? 'is-active' : '' ?>" data-tab="home">
              <?= admin_icon('dashboard', 14) ?> Page d'accueil &amp; Bannières
            </button>
            <button type="button" class="btn btn-outline btn-sm <?= $activeTab === 'about' ? 'is-active' : '' ?>" data-tab="about">
              <?= admin_icon('users', 14) ?> Page « À propos »
            </button>
            <button type="button" class="btn btn-outline btn-sm <?= $activeTab === 'services' ? 'is-active' : '' ?>" data-tab="services">
              <?= admin_icon('building', 14) ?> Page « Services »
            </button>
          </div>

          <!-- ONGLET 1 : Identité & Contact -->
          <div class="cms-tab-pane" id="pane-identity" style="display: <?= $activeTab === 'identity' ? 'block' : 'none' ?>;">
            <div class="card" style="margin-bottom: 24px;">
              <div class="card-header">
                <h3 class="card-title">Identité de l'agence</h3>
                <p class="card-subtitle">Nom officiel, slogan et description de présentation</p>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-agencyName">Nom de l'agence <span class="required">*</span></label>
                    <input type="text" class="form-input" id="setting-agencyName" name="agencyName" required value="<?= e($vAgencyName) ?>">
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-tagline">Slogan commercial</label>
                    <input type="text" class="form-input" id="setting-tagline" name="tagline" value="<?= e($vTagline) ?>">
                  </div>

                  <div class="form-col-12 form-group">
                    <label class="form-label" for="setting-description">Présentation générale de l'agence</label>
                    <textarea class="form-textarea" id="setting-description" name="description" rows="3"><?= e($vDescription) ?></textarea>
                  </div>
                </div>
              </div>
            </div>

            <div class="card" style="margin-bottom: 24px;">
              <div class="card-header">
                <h3 class="card-title">Coordonnées officielles &amp; Horaires</h3>
                <p class="card-subtitle">Affichées sur le bandeau, le pied de page et la page contact</p>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-phone">Téléphone direct <span class="required">*</span></label>
                    <input type="text" class="form-input" id="setting-phone" name="phone" required value="<?= e($vPhone) ?>">
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-whatsapp">Numéro WhatsApp (sans le +)</label>
                    <input type="text" class="form-input" id="setting-whatsapp" name="whatsapp" value="<?= e($vWhatsapp) ?>">
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-email">Email officiel <span class="required">*</span></label>
                    <input type="email" class="form-input" id="setting-email" name="email" required value="<?= e($vEmail) ?>">
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-hours">Horaires d'ouverture</label>
                    <input type="text" class="form-input" id="setting-hours" name="hours" value="<?= e($vHours) ?>">
                  </div>

                  <div class="form-col-12 form-group">
                    <label class="form-label" for="setting-address">Adresse physique du siège</label>
                    <input type="text" class="form-input" id="setting-address" name="address" value="<?= e($vAddress) ?>">
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-facebook">Lien page Facebook</label>
                    <input type="url" class="form-input" id="setting-facebook" name="facebook" value="<?= e($vFacebook) ?>">
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-instagram">Lien page Instagram</label>
                    <input type="url" class="form-input" id="setting-instagram" name="instagram" value="<?= e($vInstagram) ?>">
                  </div>
                </div>
              </div>
            </div>

            <div class="card" style="margin-bottom: 24px;">
              <div class="card-header">
                <h3 class="card-title">Apparence de l'administration</h3>
                <p class="card-subtitle">Préférence d'affichage de l'interface (sauvegardée sur cet appareil)</p>
              </div>
              <div class="card-body">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                  <div>
                    <strong style="display: block; font-size: 0.92rem;">Thème visuel</strong>
                    <span style="font-size: 0.82rem; color: var(--admin-text-muted);" id="settings-theme-status">Mode actuel : Clair</span>
                  </div>
                  <button type="button" class="btn btn-outline btn-sm" id="btn-settings-theme-toggle">
                    <span class="theme-icon-light"><?= admin_icon('moon', 15) ?></span>
                    <span class="theme-icon-dark" style="display:none;"><?= admin_icon('sun', 15) ?></span>
                    <span id="settings-theme-btn-label">Basculer le thème</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ONGLET 2 : Page d'Accueil & Bannières -->
          <div class="cms-tab-pane" id="pane-home" style="display: <?= $activeTab === 'home' ? 'block' : 'none' ?>;">
            <div class="card" style="margin-bottom: 24px;">
              <div class="card-header">
                <h3 class="card-title">Bannière principale (Hero Banner)</h3>
                <p class="card-subtitle">Titres et visuel affichés en haut de la page d'accueil</p>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-col-12 form-group">
                    <label class="form-label" for="setting-cms-hero-title">Titre principal de l'accueil</label>
                    <input type="text" class="form-input" id="setting-cms-hero-title" name="cms_hero_title" value="<?= e($vHeroTitle) ?>">
                  </div>

                  <div class="form-col-12 form-group">
                    <label class="form-label" for="setting-cms-hero-subtitle">Sous-titre explicatif</label>
                    <textarea class="form-textarea" id="setting-cms-hero-subtitle" name="cms_hero_subtitle" rows="2"><?= e($vHeroSubtitle) ?></textarea>
                  </div>

                  <div class="form-col-12 form-group">
                    <label class="form-label">Image d'arrière-plan de la bannière</label>
                    <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:10px;">
                      <div style="flex:1; min-width:220px;">
                        <label class="form-label" for="setting-cms-hero-image" style="font-size:0.8rem; color:var(--admin-text-muted); margin-bottom:4px; display:block;">🔗 Via URL</label>
                        <input type="url" class="form-input" id="setting-cms-hero-image" name="cms_hero_image" value="<?= e($vHeroImage) ?>" placeholder="https://…">
                      </div>
                      <div style="min-width:200px;">
                        <label class="form-label" for="upload-cms-hero" style="font-size:0.8rem; color:var(--admin-text-muted); margin-bottom:4px; display:block;">📁 Ou depuis votre galerie</label>
                        <label for="upload-cms-hero" class="cms-upload-btn">
                          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                          <span id="upload-cms-hero-label">Choisir un fichier…</span>
                        </label>
                        <input type="file" class="cms-file-input" id="upload-cms-hero" name="cms_hero_file" accept="image/*">
                      </div>
                    </div>
                    <div style="height: 140px; border-radius: var(--radius-xs); overflow: hidden; border: 1px solid var(--admin-border); background:#111;">
                      <img id="preview-hero-image" src="<?= e(asset_url($vHeroImage)) ?>" alt="Aperçu Bannière" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ONGLET 3 : Page À propos -->
          <div class="cms-tab-pane" id="pane-about" style="display: <?= $activeTab === 'about' ? 'block' : 'none' ?>;">
            <div class="card" style="margin-bottom: 24px;">
              <div class="card-header">
                <h3 class="card-title">Contenu de la page « À propos »</h3>
                <p class="card-subtitle">Histoire, valeurs et illustration de l'agence</p>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-col-12 form-group">
                    <label class="form-label" for="setting-cms-about-title">Titre de la section À propos</label>
                    <input type="text" class="form-input" id="setting-cms-about-title" name="cms_about_title" value="<?= e($vAboutTitle) ?>">
                  </div>

                  <div class="form-col-12 form-group">
                    <label class="form-label" for="setting-cms-about-text">Texte de présentation / Notre histoire</label>
                    <textarea class="form-textarea" id="setting-cms-about-text" name="cms_about_text" rows="5"><?= e($vAboutText) ?></textarea>
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-cms-about-mission">Notre Mission</label>
                    <textarea class="form-textarea" id="setting-cms-about-mission" name="cms_about_mission" rows="3"><?= e($vAboutMission) ?></textarea>
                  </div>

                  <div class="form-col-6 form-group">
                    <label class="form-label" for="setting-cms-about-vision">Notre Vision</label>
                    <textarea class="form-textarea" id="setting-cms-about-vision" name="cms_about_vision" rows="3"><?= e($vAboutVision) ?></textarea>
                  </div>

                  <div class="form-col-12 form-group">
                    <label class="form-label">Image d'illustration de l'agence</label>
                    <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:10px;">
                      <div style="flex:1; min-width:220px;">
                        <label class="form-label" for="setting-cms-about-image" style="font-size:0.8rem; color:var(--admin-text-muted); margin-bottom:4px; display:block;">🔗 Via URL</label>
                        <input type="url" class="form-input" id="setting-cms-about-image" name="cms_about_image" value="<?= e($vAboutImage) ?>" placeholder="https://…">
                      </div>
                      <div style="min-width:200px;">
                        <label class="form-label" for="upload-cms-about" style="font-size:0.8rem; color:var(--admin-text-muted); margin-bottom:4px; display:block;">📁 Ou depuis votre galerie</label>
                        <label for="upload-cms-about" class="cms-upload-btn">
                          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                          <span id="upload-cms-about-label">Choisir un fichier…</span>
                        </label>
                        <input type="file" class="cms-file-input" id="upload-cms-about" name="cms_about_file" accept="image/*">
                      </div>
                    </div>
                    <div style="height: 140px; border-radius: var(--radius-xs); overflow: hidden; border: 1px solid var(--admin-border); max-width: 380px; background:#111;">
                      <img id="preview-about-image" src="<?= e(asset_url($vAboutImage)) ?>" alt="Aperçu Agence" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ONGLET 4 : Page Services -->
          <div class="cms-tab-pane" id="pane-services" style="display: <?= $activeTab === 'services' ? 'block' : 'none' ?>;">
            <div class="card" style="margin-bottom: 24px;">
              <div class="card-header">
                <h3 class="card-title">Contenu de la page « Services »</h3>
                <p class="card-subtitle">Présentation globale de l'offre de services</p>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-col-12 form-group">
                    <label class="form-label" for="setting-cms-services-intro">Texte d'introduction de la page Services</label>
                    <textarea class="form-textarea" id="setting-cms-services-intro" name="cms_services_intro" rows="4"><?= e($vServicesIntro) ?></textarea>
                  </div>

                  <div class="form-col-12 form-group">
                    <label class="form-label">Image d'en-tête Services</label>
                    <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:10px;">
                      <div style="flex:1; min-width:220px;">
                        <label class="form-label" for="setting-cms-services-image" style="font-size:0.8rem; color:var(--admin-text-muted); margin-bottom:4px; display:block;">🔗 Via URL</label>
                        <input type="url" class="form-input" id="setting-cms-services-image" name="cms_services_image" value="<?= e($vServicesImage) ?>" placeholder="https://…">
                      </div>
                      <div style="min-width:200px;">
                        <label class="form-label" for="upload-cms-services" style="font-size:0.8rem; color:var(--admin-text-muted); margin-bottom:4px; display:block;">📁 Ou depuis votre galerie</label>
                        <label for="upload-cms-services" class="cms-upload-btn">
                          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                          <span id="upload-cms-services-label">Choisir un fichier…</span>
                        </label>
                        <input type="file" class="cms-file-input" id="upload-cms-services" name="cms_services_file" accept="image/*">
                      </div>
                    </div>
                    <div style="height: 140px; border-radius: var(--radius-xs); overflow: hidden; border: 1px solid var(--admin-border); background:#111;">
                      <img id="preview-services-image" src="<?= e(asset_url($vServicesImage)) ?>" alt="Aperçu Services" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
            <button type="submit" class="btn btn-primary btn-lg">
              <?= admin_icon('check', 16) ?>
              <span>Enregistrer les paramètres du site</span>
            </button>
          </div>
        </form>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer_scripts.php'; ?>

  <style>
    .cms-file-input { display: none; }
    .cms-upload-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 14px;
      border: 1.5px dashed var(--admin-border);
      border-radius: var(--radius-xs);
      cursor: pointer;
      font-size: 0.84rem;
      font-family: var(--font-sans);
      color: var(--admin-text-muted);
      background: var(--admin-bg);
      transition: border-color .2s, color .2s, background .2s;
      white-space: nowrap;
      user-select: none;
    }
    .cms-upload-btn:hover {
      border-color: var(--gold);
      color: var(--gold);
      background: rgba(var(--gold-rgb, 180,140,60), .07);
    }
    .cms-upload-btn.has-file {
      border-color: #22c55e;
      color: #22c55e;
      border-style: solid;
    }
  </style>

  <script>
    (function() {
      var tabBtns = document.querySelectorAll('#cms-tabs-nav button');
      var activeInput = document.getElementById('active-tab-input');
      tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
          tabBtns.forEach(function(b) { b.classList.remove('is-active'); });
          btn.classList.add('is-active');
          var t = btn.getAttribute('data-tab');
          if (activeInput) activeInput.value = t;
          document.querySelectorAll('.cms-tab-pane').forEach(function(p) { p.style.display = 'none'; });
          var target = document.getElementById('pane-' + t);
          if (target) target.style.display = 'block';
        });
      });

      // ---- URL input → live preview ----
      document.getElementById('setting-cms-hero-image')?.addEventListener('input', function(e) {
        var img = document.getElementById('preview-hero-image');
        if (img && e.target.value.trim()) img.src = e.target.value.trim();
      });
      document.getElementById('setting-cms-about-image')?.addEventListener('input', function(e) {
        var img = document.getElementById('preview-about-image');
        if (img && e.target.value.trim()) img.src = e.target.value.trim();
      });
      document.getElementById('setting-cms-services-image')?.addEventListener('input', function(e) {
        var img = document.getElementById('preview-services-image');
        if (img && e.target.value.trim()) img.src = e.target.value.trim();
      });

      // ---- File input → FileReader preview ----
      function bindFilePreview(inputId, previewId, labelId, labelBtnId) {
        var fileInput  = document.getElementById(inputId);
        var preview    = document.getElementById(previewId);
        var labelSpan  = document.getElementById(labelId);
        var labelBtn   = document.querySelector('label[for="' + inputId + '"]');
        if (!fileInput) return;
        fileInput.addEventListener('change', function() {
          var file = this.files && this.files[0];
          if (!file) return;
          if (labelSpan) labelSpan.textContent = file.name.length > 24 ? file.name.substring(0,22)+'…' : file.name;
          if (labelBtn)  labelBtn.classList.add('has-file');
          if (preview) {
            var reader = new FileReader();
            reader.onload = function(ev) { preview.src = ev.target.result; };
            reader.readAsDataURL(file);
          }
        });
      }

      bindFilePreview('upload-cms-hero',     'preview-hero-image',     'upload-cms-hero-label');
      bindFilePreview('upload-cms-about',    'preview-about-image',    'upload-cms-about-label');
      bindFilePreview('upload-cms-services', 'preview-services-image', 'upload-cms-services-label');
    })();
  </script>
</body>
</html>
