<?php
/**
 * client/fiche-client.php - Constitution de dossier / Fiche client (HTML brut + PHP MySQLi)
 * Soumission directe en PHP POST (upload fichier pièce d'identité + signature canvas PNG).
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/admin_helpers.php';

$activePage = 'fiche-client';
$b = BASE_URL;

$errors = [];
$createdFiche = null;

// Si redirection après succès
if (isset($_GET['success']) && $_GET['success'] === '1' && !empty($_GET['ref'])) {
    $refGet = trim((string)$_GET['ref']);
    $stmtGet = $conn->prepare("SELECT * FROM `fiches_client` WHERE `reference` = ? LIMIT 1");
    $stmtGet->bind_param("s", $refGet);
    $stmtGet->execute();
    $createdFiche = $stmtGet->get_result()->fetch_assoc();
}

// Traitement PHP POST du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_fiche') {
    $nom         = trim((string)($_POST['nom'] ?? ''));
    $prenom      = trim((string)($_POST['prenom'] ?? ''));
    $telephone   = trim((string)($_POST['telephone'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));
    $transaction = trim((string)($_POST['type_transaction'] ?? ''));
    $typeBien    = trim((string)($_POST['type_bien'] ?? ''));
    $sigData     = trim((string)($_POST['signature_data'] ?? ''));

    if ($nom === '') {
        $errors['nom'] = 'Veuillez renseigner votre nom.';
    }
    if ($prenom === '') {
        $errors['prenom'] = 'Veuillez renseigner votre prénom.';
    }
    $cleanTel = preg_replace('/[^0-9+]/', '', $telephone);
    if ($telephone === '' || strlen($cleanTel) < 8) {
        $errors['telephone'] = 'Veuillez renseigner un numéro de téléphone valide.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Veuillez renseigner une adresse e-mail valide.';
    }
    if (!in_array($transaction, ['Demande', 'Offre'], true)) {
        $errors['transaction'] = 'Veuillez sélectionner un type de transaction (Demande ou Offre).';
    }
    if ($typeBien === '') {
        $errors['type_bien'] = 'Veuillez sélectionner le type de bien immobilier concerné.';
    }

    $pieceUrl = null;
    if (!isset($_FILES['piece_identite']) || $_FILES['piece_identite']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['file'] = "Veuillez joindre une copie de votre pièce d'identité (image ou PDF).";
    } elseif (empty($errors)) {
        try {
            $pieceUrl = handle_file_upload($_FILES['piece_identite'], 'documents');
        } catch (Throwable $ex) {
            $errors['file'] = $ex->getMessage();
        }
    }

    if ($sigData === '' || !str_starts_with($sigData, 'data:image/')) {
        $errors['signature'] = 'Veuillez apposer votre signature dans la zone prévue avant de valider.';
    }

    if (empty($errors)) {
        $savedSig = save_base64_file($sigData, 'signatures', 'sig') ?: $sigData;
        $ref = 'IML-DOS-' . date('Y') . '-' . rand(1000, 9999);

        $stmtIns = $conn->prepare("
            INSERT INTO `fiches_client` (
                `reference`, `nom`, `prenom`, `telephone`, `email`,
                `transaction`, `type_bien`, `piece_identite`, `signature`, `statut`, `is_read`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Nouveau', 0)
        ");
        $stmtIns->bind_param(
            "sssssssss",
            $ref, $nom, $prenom, $telephone, $email,
            $transaction, $typeBien, $pieceUrl, $savedSig
        );
        $stmtIns->execute();

        header('Location: ' . $b . '/client/fiche-client.php?success=1&ref=' . urlencode($ref));
        exit;
    }
}

$valNom         = $_POST['nom'] ?? '';
$valPrenom      = $_POST['prenom'] ?? '';
$valTelephone   = $_POST['telephone'] ?? '';
$valEmail       = $_POST['email'] ?? '';
$valTransaction = $_POST['type_transaction'] ?? '';
$valTypeBien    = $_POST['type_bien'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fiche client — Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">

  <link rel="stylesheet" href="<?= $b ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/component.css">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/responsive.css">

  <style>
    .fiche-wrap { max-width: 840px; margin: 0 auto; }
    .fiche-section { background: var(--color-surface); border: 1px solid var(--color-border-soft); border-radius: var(--radius-md); padding: var(--space-6); margin-bottom: var(--space-5); box-shadow: var(--shadow-xs); transition: border-color var(--transition-fast); }
    .fiche-section:focus-within { border-color: var(--color-border); }
    .fiche-section-header { display: flex; align-items: flex-start; gap: var(--space-4); margin-bottom: var(--space-5); padding-bottom: var(--space-4); border-bottom: 1px solid var(--color-border-soft); }
    .fiche-section-num { width: 36px; height: 36px; border-radius: 50%; background: var(--color-accent-soft); color: var(--color-accent-dark); font-weight: 700; font-size: 0.88rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; letter-spacing: -0.02em; }
    .fiche-section-title-wrap { flex: 1; }
    .fiche-section-title { margin: 0 0 4px; font-family: var(--font-serif); font-size: 1.35rem; font-weight: 500; color: var(--color-text); }
    .fiche-section-desc { margin: 0; font-size: 0.88rem; color: var(--color-muted); }
    .field-required { color: var(--color-danger); margin-left: 2px; font-weight: 600; }
    .field-error { color: var(--color-danger); font-size: 0.8rem; margin-top: 4px; display: none; line-height: 1.4; }
    .form-field.has-error input, .form-field.has-error select, .form-field.has-error textarea { border-color: var(--color-danger); background-color: #FFF9F8; }
    .form-field.has-error .field-error { display: block; }
    .section-error { color: var(--color-danger); font-size: 0.82rem; margin-top: var(--space-3); display: none; font-weight: 500; }
    .section-error.is-visible { display: block; }
    .transaction-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); }
    .transaction-card { position: relative; display: flex; flex-direction: column; gap: 6px; padding: var(--space-5); border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: var(--color-surface); cursor: pointer; transition: all var(--transition-fast); }
    .transaction-card input { position: absolute; opacity: 0; pointer-events: none; }
    .transaction-card:hover { border-color: var(--color-accent); transform: translateY(-1px); }
    .transaction-card.is-checked { border-color: var(--color-accent); background: var(--color-accent-soft); }
    .transaction-card-head { display: flex; align-items: center; justify-content: space-between; }
    .transaction-card-title { font-size: 1.05rem; font-weight: 600; color: var(--color-text); margin: 0; }
    .transaction-card-radio { width: 18px; height: 18px; border: 2px solid var(--color-border); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: all var(--transition-fast); }
    .transaction-card.is-checked .transaction-card-radio { border-color: var(--color-accent); background: var(--color-accent); }
    .transaction-card.is-checked .transaction-card-radio::after { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #FFFFFF; }
    .transaction-card-desc { font-size: 0.84rem; color: var(--color-muted); margin: 0; line-height: 1.5; }
    .property-type-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: var(--space-3); }
    .property-type-card { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: var(--space-4) var(--space-2); border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: var(--color-surface); cursor: pointer; transition: all var(--transition-fast); gap: 8px; min-height: 96px; }
    .property-type-card input { position: absolute; opacity: 0; pointer-events: none; }
    .property-type-card:hover { border-color: var(--color-accent); transform: translateY(-1px); }
    .property-type-card.is-checked { border-color: var(--color-accent); background: var(--color-accent-soft); font-weight: 600; color: var(--color-text); }
    .property-type-card .icon-slot { color: var(--color-muted); transition: color var(--transition-fast); display: inline-flex; }
    .property-type-card:hover .icon-slot, .property-type-card.is-checked .icon-slot { color: var(--color-accent-dark); }
    .property-type-name { font-size: 0.88rem; }
    .upload-dropzone { position: relative; border: 2px dashed var(--color-border); border-radius: var(--radius-sm); padding: var(--space-6) var(--space-4); text-align: center; background: var(--color-bg); cursor: pointer; transition: all var(--transition-fast); outline: none; }
    .upload-dropzone:hover, .upload-dropzone.is-dragover { border-color: var(--color-accent); background: var(--color-accent-soft); }
    .upload-dropzone.has-error { border-color: var(--color-danger); background: #FFF9F8; }
    .upload-icon { display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: var(--color-surface); color: var(--color-accent-dark); margin-bottom: var(--space-3); box-shadow: var(--shadow-xs); }
    .upload-title { font-size: 0.96rem; font-weight: 600; color: var(--color-text); margin: 0 0 4px; }
    .upload-subtitle { font-size: 0.82rem; color: var(--color-muted); margin: 0; }
    .upload-preview { display: none; align-items: center; justify-content: space-between; gap: var(--space-4); padding: var(--space-4); border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: var(--color-bg); margin-top: var(--space-3); }
    .upload-preview.is-visible { display: flex; }
    .upload-preview-left { display: flex; align-items: center; gap: var(--space-3); min-width: 0; }
    .upload-thumbnail { width: 54px; height: 54px; border-radius: var(--radius-xs); object-fit: cover; border: 1px solid var(--color-border); background: #FFFFFF; flex-shrink: 0; }
    .upload-pdf-icon { width: 54px; height: 54px; border-radius: var(--radius-xs); background: var(--color-surface); border: 1px solid var(--color-border); display: inline-flex; flex-direction: column; align-items: center; justify-content: center; color: var(--color-accent-dark); flex-shrink: 0; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.05em; }
    .upload-file-info { min-width: 0; }
    .upload-file-name { font-size: 0.9rem; font-weight: 600; color: var(--color-text); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .upload-file-meta { font-size: 0.78rem; color: var(--color-muted); margin: 0; }
    .engagement-callout { padding: var(--space-4) var(--space-5); background: var(--color-accent-soft); border-left: 3px solid var(--color-accent); border-radius: var(--radius-xs); margin-bottom: var(--space-5); }
    .engagement-callout p { margin: 0; font-size: 0.92rem; line-height: 1.6; color: var(--color-text); font-weight: 500; }
    .signature-card { border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: #FFFFFF; position: relative; transition: border-color var(--transition-fast); }
    .signature-card.has-error { border-color: var(--color-danger); background: #FFF9F8; }
    .signature-canvas-wrap { position: relative; width: 100%; height: 190px; background: #FFFFFF; border-radius: var(--radius-sm) var(--radius-sm) 0 0; overflow: hidden; }
    .signature-canvas { display: block; width: 100%; height: 100%; cursor: crosshair; touch-action: none; }
    .signature-watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; color: rgba(28, 28, 28, 0.16); font-size: 0.95rem; font-weight: 500; letter-spacing: 0.05em; text-transform: uppercase; user-select: none; transition: opacity var(--transition-fast); }
    .signature-watermark.is-hidden { opacity: 0; }
    .signature-baseline { position: absolute; bottom: 35px; left: 30px; right: 30px; height: 1px; background: repeating-linear-gradient(90deg, var(--color-border), var(--color-border) 4px, transparent 4px, transparent 8px); pointer-events: none; }
    .signature-footer { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); background: var(--color-bg); border-top: 1px solid var(--color-border); border-radius: 0 0 var(--radius-sm) var(--radius-sm); }
    .signature-hint { font-size: 0.82rem; color: var(--color-muted); margin: 0; }
    .fiche-actions { margin-top: var(--space-6); display: flex; flex-direction: column; gap: var(--space-3); }
    .fiche-note { font-size: 0.8rem; color: var(--color-muted); text-align: center; margin: 0; }
    .fiche-success { background: var(--color-surface); border: 1px solid var(--color-border-soft); border-radius: var(--radius-md); padding: var(--space-7); box-shadow: var(--shadow-sm); text-align: center; }
    .success-badge { display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: var(--color-accent-soft); color: var(--color-accent-dark); margin-bottom: var(--space-4); }
    .success-title { font-family: var(--font-serif); font-size: 1.8rem; font-weight: 500; margin: 0 0 var(--space-2); color: var(--color-text); }
    .success-lead { color: var(--color-muted); font-size: 0.98rem; margin: 0 0 var(--space-5); }
    .recap-card { text-align: left; border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: var(--color-bg); padding: var(--space-5); margin: var(--space-6) 0; }
    .recap-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding-bottom: var(--space-3); margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2); }
    .recap-title { font-size: 0.92rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin: 0; color: var(--color-text); }
    .recap-ref { font-family: monospace; font-size: 0.88rem; padding: 3px 8px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xs); color: var(--color-accent-dark); font-weight: 600; }
    .recap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); }
    .recap-row { display: flex; flex-direction: column; gap: 2px; }
    .recap-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-muted); }
    .recap-val { font-size: 0.92rem; font-weight: 600; color: var(--color-text); word-break: break-word; }
    .recap-signature-thumb { max-height: 50px; border: 1px solid var(--color-border); border-radius: var(--radius-xs); background: #FFFFFF; padding: 2px 6px; display: inline-block; margin-top: 4px; }
    .success-actions { display: flex; gap: var(--space-3); justify-content: center; flex-wrap: wrap; margin-top: var(--space-6); }
    @media (max-width: 768px) {
      .property-type-grid { grid-template-columns: repeat(3, 1fr); }
      .transaction-grid, .recap-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 520px) {
      .property-type-grid { grid-template-columns: repeat(2, 1fr); }
      .fiche-section { padding: var(--space-5) var(--space-4); }
      .signature-footer { flex-direction: column; align-items: stretch; text-align: center; }
      .signature-footer .btn { width: 100%; }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/header.php'; ?>

  <main>
    <section class="page-hero">
      <div class="container">
        <span class="section-eyebrow">Dossier client</span>
        <h1 class="section-title">Fiche client</h1>
        <p class="section-lead">
          Constitution officielle de votre dossier pour toute démarche de demande ou d'offre immobilière auprès de l'agence Imolode.
        </p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="fiche-wrap">

          <?php if ($createdFiche): ?>
            <!-- Écran de confirmation de succès généré en PHP depuis MySQL -->
            <div class="fiche-success" id="fiche-success">
              <div class="success-badge">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              </div>
              <h2 class="success-title">Fiche client enregistrée avec succès</h2>
              <p class="success-lead">Votre dossier de constitution a bien été réceptionné par notre équipe.</p>

              <div class="recap-card">
                <div class="recap-head">
                  <span class="recap-title">Récapitulatif du dossier client</span>
                  <span class="recap-ref">#<?= e($createdFiche['reference']) ?></span>
                </div>

                <div class="recap-grid">
                  <div class="recap-row">
                    <span class="recap-label">Nom et Prénom</span>
                    <span class="recap-val"><?= e($createdFiche['nom'] . ' ' . $createdFiche['prenom']) ?></span>
                  </div>
                  <div class="recap-row">
                    <span class="recap-label">Numéro de téléphone</span>
                    <span class="recap-val"><?= e($createdFiche['telephone']) ?></span>
                  </div>
                  <div class="recap-row">
                    <span class="recap-label">Adresse e-mail</span>
                    <span class="recap-val"><?= e($createdFiche['email']) ?></span>
                  </div>
                  <div class="recap-row">
                    <span class="recap-label">Nature de l'opération</span>
                    <span class="recap-val"><?= e($createdFiche['transaction']) ?></span>
                  </div>
                  <div class="recap-row">
                    <span class="recap-label">Type de bien</span>
                    <span class="recap-val"><?= e($createdFiche['type_bien']) ?></span>
                  </div>
                  <div class="recap-row">
                    <span class="recap-label">Pièce d'identité jointe</span>
                    <span class="recap-val"><?= e(basename((string)($createdFiche['piece_identite'] ?? 'Fournie'))) ?></span>
                  </div>
                  <div class="recap-row" style="grid-column: 1 / -1;">
                    <span class="recap-label">Signature électronique enregistrée</span>
                    <div>
                      <?php if (!empty($createdFiche['signature'])): ?>
                        <img src="<?= e(asset_url($createdFiche['signature'])) ?>" alt="Signature du client" class="recap-signature-thumb">
                      <?php else: ?>
                        <span>Signature électronique validée</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <p style="font-size:0.9rem; color:var(--color-muted); margin:0 0 var(--space-6);">
                Un conseiller de l'agence Imolode prendra contact avec vous dans les plus brefs délais pour donner suite à votre dossier.
              </p>

              <div class="success-actions">
                <a href="<?= $b ?>/client/biens.php" class="btn btn-primary">Parcourir nos biens</a>
                <a href="<?= $b ?>/client/index.php" class="btn btn-outline">Retour à l'accueil</a>
                <a href="<?= $b ?>/client/fiche-client.php" class="btn btn-ghost">Constituer un autre dossier</a>
              </div>
            </div>

          <?php else: ?>

            <?php if (!empty($errors)): ?>
              <div style="padding:16px;background:#FDE8E8;color:#9B1C1C;border:1px solid #F8B4B4;border-radius:8px;font-size:0.9rem;margin-bottom:20px;">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul style="margin:6px 0 0 18px;padding:0;">
                  <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form id="fiche-client-form" method="POST" action="<?= $b ?>/client/fiche-client.php" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="action" value="save_fiche">
              <input type="hidden" name="signature_data" id="signature-data" value="">

              <!-- SECTION 1 : Informations personnelles -->
              <fieldset class="fiche-section" id="section-personnelle">
                <legend class="sr-only">Informations personnelles</legend>
                <div class="fiche-section-header">
                  <span class="fiche-section-num" aria-hidden="true">01</span>
                  <div class="fiche-section-title-wrap">
                    <h2 class="fiche-section-title">Informations personnelles</h2>
                    <p class="fiche-section-desc">Renseignez vos coordonnées exactes pour le suivi officiel du dossier.</p>
                  </div>
                </div>

                <div class="form-grid">
                  <label class="form-field <?= isset($errors['nom']) ? 'has-error' : '' ?>" id="field-nom">
                    <span>Nom <span class="field-required">*</span></span>
                    <input type="text" name="nom" id="input-nom" placeholder="Ex. Bouraoui" autocomplete="family-name" required value="<?= e($valNom) ?>">
                    <span class="field-error" id="error-nom"><?= e($errors['nom'] ?? 'Veuillez renseigner votre nom.') ?></span>
                  </label>

                  <label class="form-field <?= isset($errors['prenom']) ? 'has-error' : '' ?>" id="field-prenom">
                    <span>Prénom <span class="field-required">*</span></span>
                    <input type="text" name="prenom" id="input-prenom" placeholder="Ex. Youcef" autocomplete="given-name" required value="<?= e($valPrenom) ?>">
                    <span class="field-error" id="error-prenom"><?= e($errors['prenom'] ?? 'Veuillez renseigner votre prénom.') ?></span>
                  </label>
                </div>

                <div class="form-grid" style="margin-top: var(--space-4);">
                  <label class="form-field <?= isset($errors['telephone']) ? 'has-error' : '' ?>" id="field-telephone">
                    <span>Numéro de téléphone <span class="field-required">*</span></span>
                    <input type="tel" name="telephone" id="input-telephone" placeholder="Ex. 0656 54 84 88" autocomplete="tel" required value="<?= e($valTelephone) ?>">
                    <span class="field-error" id="error-telephone"><?= e($errors['telephone'] ?? 'Veuillez renseigner un numéro de téléphone valide.') ?></span>
                  </label>

                  <label class="form-field <?= isset($errors['email']) ? 'has-error' : '' ?>" id="field-email">
                    <span>Adresse e-mail <span class="field-required">*</span></span>
                    <input type="email" name="email" id="input-email" placeholder="Ex. contact@exemple.com" autocomplete="email" required value="<?= e($valEmail) ?>">
                    <span class="field-error" id="error-email"><?= e($errors['email'] ?? 'Veuillez renseigner une adresse e-mail valide.') ?></span>
                  </label>
                </div>
              </fieldset>

              <!-- SECTION 2 : Type de transaction -->
              <fieldset class="fiche-section" id="section-transaction">
                <legend class="sr-only">Type de transaction</legend>
                <div class="fiche-section-header">
                  <span class="fiche-section-num" aria-hidden="true">02</span>
                  <div class="fiche-section-title-wrap">
                    <h2 class="fiche-section-title">Type de transaction</h2>
                    <p class="fiche-section-desc">Précisez si vous formulez une recherche ou si vous proposez un bien.</p>
                  </div>
                </div>

                <div class="transaction-grid">
                  <label class="transaction-card <?= $valTransaction === 'Demande' ? 'is-checked' : '' ?>" id="card-demande">
                    <input type="radio" name="type_transaction" value="Demande" required <?= $valTransaction === 'Demande' ? 'checked' : '' ?>>
                    <div class="transaction-card-head">
                      <span class="transaction-card-title">Demande</span>
                      <span class="transaction-card-radio" aria-hidden="true"></span>
                    </div>
                    <p class="transaction-card-desc">Vous recherchez un bien immobilier à acquérir, louer ou pour un investissement.</p>
                  </label>

                  <label class="transaction-card <?= $valTransaction === 'Offre' ? 'is-checked' : '' ?>" id="card-offre">
                    <input type="radio" name="type_transaction" value="Offre" required <?= $valTransaction === 'Offre' ? 'checked' : '' ?>>
                    <div class="transaction-card-head">
                      <span class="transaction-card-title">Offre</span>
                      <span class="transaction-card-radio" aria-hidden="true"></span>
                    </div>
                    <p class="transaction-card-desc">Vous proposez un bien immobilier à la vente ou à la mise en location.</p>
                  </label>
                </div>
                <span class="section-error <?= isset($errors['transaction']) ? 'is-visible' : '' ?>" id="error-transaction"><?= e($errors['transaction'] ?? 'Veuillez sélectionner un type de transaction (Demande ou Offre).') ?></span>
              </fieldset>

              <!-- SECTION 3 : Type de bien immobilier -->
              <fieldset class="fiche-section" id="section-bien">
                <legend class="sr-only">Type de bien immobilier</legend>
                <div class="fiche-section-header">
                  <span class="fiche-section-num" aria-hidden="true">03</span>
                  <div class="fiche-section-title-wrap">
                    <h2 class="fiche-section-title">Type de bien immobilier</h2>
                    <p class="fiche-section-desc">Sélectionnez la catégorie de propriété concernée par votre dossier.</p>
                  </div>
                </div>

                <div class="property-type-grid" id="property-type-grid">
                  <?php
                  $bienChoices = [
                      'Appartement' => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M12 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/>',
                      'Villa'       => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
                      'Commerce'    => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
                      'Terrain'     => '<path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/>',
                      'Dépôt'       => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
                  ];
                  foreach ($bienChoices as $label => $svgBody):
                  ?>
                    <label class="property-type-card <?= $valTypeBien === $label ? 'is-checked' : '' ?>">
                      <input type="radio" name="type_bien" value="<?= e($label) ?>" required <?= $valTypeBien === $label ? 'checked' : '' ?>>
                      <span class="icon-slot">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $svgBody ?></svg>
                      </span>
                      <span class="property-type-name"><?= e($label) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <span class="section-error <?= isset($errors['type_bien']) ? 'is-visible' : '' ?>" id="error-bien"><?= e($errors['type_bien'] ?? 'Veuillez sélectionner le type de bien immobilier concerné.') ?></span>
              </fieldset>

              <!-- SECTION 4 : Copie d'identité -->
              <fieldset class="fiche-section" id="section-identite">
                <legend class="sr-only">Copie d'identité</legend>
                <div class="fiche-section-header">
                  <span class="fiche-section-num" aria-hidden="true">04</span>
                  <div class="fiche-section-title-wrap">
                    <h2 class="fiche-section-title">Copie d'identité</h2>
                    <p class="fiche-section-desc">Téléversez une pièce d'identité officielle en cours de validité.</p>
                  </div>
                </div>

                <input type="file" id="input-file" name="piece_identite" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" style="display:none;" required>

                <div class="upload-dropzone <?= isset($errors['file']) ? 'has-error' : '' ?>" id="upload-dropzone" tabindex="0" role="button" aria-label="Sélectionner une pièce d'identité">
                  <div class="upload-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                  </div>
                  <p class="upload-title">Cliquez pour sélectionner un document ou glissez-le ici</p>
                  <p class="upload-subtitle">Formats acceptés : Images (JPG, PNG, WEBP) ou PDF — 15 Mo maximum</p>
                </div>

                <div class="upload-preview" id="upload-preview">
                  <div class="upload-preview-left">
                    <div id="preview-media-slot"></div>
                    <div class="upload-file-info">
                      <p class="upload-file-name" id="preview-file-name"></p>
                      <p class="upload-file-meta" id="preview-file-meta"></p>
                    </div>
                  </div>
                  <button type="button" class="btn btn-outline btn-sm" id="btn-remove-file">Retirer</button>
                </div>

                <span class="section-error <?= isset($errors['file']) ? 'is-visible' : '' ?>" id="error-file"><?= e($errors['file'] ?? "Veuillez joindre une copie de votre pièce d'identité (image ou PDF).") ?></span>
              </fieldset>

              <!-- SECTION 5 : Engagement et signature électronique -->
              <fieldset class="fiche-section" id="section-signature">
                <legend class="sr-only">Engagement et signature électronique</legend>
                <div class="fiche-section-header">
                  <span class="fiche-section-num" aria-hidden="true">05</span>
                  <div class="fiche-section-title-wrap">
                    <h2 class="fiche-section-title">Engagement et signature électronique</h2>
                    <p class="fiche-section-desc">Validez la conformité légale de votre démarche par signature manuscrite.</p>
                  </div>
                </div>

                <div class="engagement-callout">
                  <p>Ceci constitue un engagement officiel. En cas de transaction, le client est tenu d'honorer les frais de l'agence conformément aux dispositions réglementaires en vigueur.</p>
                </div>

                <div class="signature-card <?= isset($errors['signature']) ? 'has-error' : '' ?>" id="signature-card">
                  <div class="signature-canvas-wrap">
                    <canvas id="signature-canvas" class="signature-canvas" aria-label="Zone de signature électronique"></canvas>
                    <div class="signature-baseline" aria-hidden="true"></div>
                    <span class="signature-watermark" id="signature-watermark" aria-hidden="true">Signer ici</span>
                  </div>
                  <div class="signature-footer">
                    <p class="signature-hint">Le client signe avec son doigt ou sa souris</p>
                    <button type="button" class="btn btn-ghost btn-sm" id="btn-clear-signature">Effacer</button>
                  </div>
                </div>

                <span class="section-error <?= isset($errors['signature']) ? 'is-visible' : '' ?>" id="error-signature"><?= e($errors['signature'] ?? 'Veuillez apposer votre signature dans la zone prévue avant de valider.') ?></span>
              </fieldset>

              <div class="fiche-actions">
                <button type="submit" class="btn btn-primary btn-lg btn-block" id="btn-submit-fiche">
                  Valider et enregistrer ma fiche client
                </button>
                <p class="fiche-note">
                  Dossier confidentiel réservé aux démarches immobilières de l'Agence Immobilière Agréée Imolode.
                </p>
              </div>
            </form>
          <?php endif; ?>

        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <?php if (!$createdFiche): ?>
  <script>
    (function() {
      // 1. Pad de signature Canvas natif (souris, tactile, stylet)
      var canvas = document.getElementById('signature-canvas');
      var watermark = document.getElementById('signature-watermark');
      var signatureCard = document.getElementById('signature-card');
      var errorSignature = document.getElementById('error-signature');
      var sigHiddenInput = document.getElementById('signature-data');
      var ctx = canvas.getContext('2d');
      var dpr = window.devicePixelRatio || 1;
      var isDrawing = false;
      var hasSignature = false;
      var strokes = [];
      var currentStroke = null;
      var lastPoint = null;

      function initCanvas() {
        var rect = canvas.getBoundingClientRect();
        var w = rect.width || 400;
        var h = rect.height || 180;
        dpr = window.devicePixelRatio || 1;
        canvas.width = Math.round(w * dpr);
        canvas.height = Math.round(h * dpr);
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#1C1C1C';
        ctx.fillStyle = '#1C1C1C';
        ctx.lineWidth = 2.2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
      }

      function getCoords(e) {
        var rect = canvas.getBoundingClientRect();
        var cx = (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
        var cy = (e.touches && e.touches[0]) ? e.touches[0].clientY : e.clientY;
        return { x: cx - rect.left, y: cy - rect.top };
      }

      function startStroke(coords) {
        isDrawing = true;
        hasSignature = true;
        lastPoint = coords;
        currentStroke = [coords];
        ctx.beginPath();
        ctx.arc(coords.x, coords.y, 1.1, 0, Math.PI * 2);
        ctx.fill();
        watermark.classList.add('is-hidden');
        signatureCard.classList.remove('has-error');
        errorSignature.classList.remove('is-visible');
      }

      function moveStroke(coords) {
        if (!isDrawing || !lastPoint) return;
        ctx.beginPath();
        ctx.moveTo(lastPoint.x, lastPoint.y);
        ctx.lineTo(coords.x, coords.y);
        ctx.stroke();
        lastPoint = coords;
        if (currentStroke) currentStroke.push(coords);
      }

      function endStroke() {
        if (!isDrawing) return;
        isDrawing = false;
        lastPoint = null;
        if (currentStroke && currentStroke.length) strokes.push(currentStroke);
        currentStroke = null;
      }

      initCanvas();

      if (window.PointerEvent) {
        canvas.addEventListener('pointerdown', function(e) { if (e.button === undefined || e.button === 0) { e.preventDefault(); startStroke(getCoords(e)); } }, { passive: false });
        window.addEventListener('pointermove', function(e) { if (isDrawing) { e.preventDefault(); moveStroke(getCoords(e)); } }, { passive: false });
        window.addEventListener('pointerup', endStroke);
        window.addEventListener('pointercancel', endStroke);
      } else {
        canvas.addEventListener('mousedown', function(e) { e.preventDefault(); startStroke(getCoords(e)); });
        window.addEventListener('mousemove', function(e) { if (isDrawing) { e.preventDefault(); moveStroke(getCoords(e)); } });
        window.addEventListener('mouseup', endStroke);
        canvas.addEventListener('touchstart', function(e) { e.preventDefault(); startStroke(getCoords(e)); }, { passive: false });
        window.addEventListener('touchmove', function(e) { if (isDrawing) { e.preventDefault(); moveStroke(getCoords(e)); } }, { passive: false });
        window.addEventListener('touchend', endStroke);
      }

      document.getElementById('btn-clear-signature').addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        strokes = [];
        hasSignature = false;
        if (sigHiddenInput) sigHiddenInput.value = '';
        watermark.classList.remove('is-hidden');
      });

      // 2. Cartes radio Transaction & Type de bien
      var transactionCards = document.querySelectorAll('.transaction-card');
      transactionCards.forEach(function(card) {
        var radio = card.querySelector('input[type="radio"]');
        card.addEventListener('click', function() {
          transactionCards.forEach(function(c) { c.classList.remove('is-checked'); });
          card.classList.add('is-checked');
          radio.checked = true;
          document.getElementById('error-transaction').classList.remove('is-visible');
        });
      });

      var propertyTypeCards = document.querySelectorAll('.property-type-card');
      propertyTypeCards.forEach(function(card) {
        var radio = card.querySelector('input[type="radio"]');
        card.addEventListener('click', function() {
          propertyTypeCards.forEach(function(c) { c.classList.remove('is-checked'); });
          card.classList.add('is-checked');
          radio.checked = true;
          document.getElementById('error-bien').classList.remove('is-visible');
        });
      });

      // 3. Dropzone et prévisualisation du fichier d'identité
      var fileInput = document.getElementById('input-file');
      var dropzone = document.getElementById('upload-dropzone');
      var previewWrap = document.getElementById('upload-preview');
      var mediaSlot = document.getElementById('preview-media-slot');
      var fileNameEl = document.getElementById('preview-file-name');
      var fileMetaEl = document.getElementById('preview-file-meta');
      var btnRemoveFile = document.getElementById('btn-remove-file');
      var errorFile = document.getElementById('error-file');
      var currentFile = null;

      function formatSize(bytes) {
        if (!bytes) return '0 Ko';
        return bytes < 1048576 ? (bytes / 1024).toFixed(1) + ' Ko' : (bytes / 1048576).toFixed(2) + ' Mo';
      }

      function previewFile(file) {
        if (!file) return;
        var isImg = file.type.indexOf('image/') === 0;
        var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
        if (!isImg && !isPdf) {
          errorFile.textContent = 'Format non supporté (JPG, PNG, WEBP ou PDF uniquement).';
          errorFile.classList.add('is-visible');
          dropzone.classList.add('has-error');
          return;
        }
        if (file.size > 15 * 1024 * 1024) {
          errorFile.textContent = 'Le fichier dépasse la taille maximale autorisée (15 Mo).';
          errorFile.classList.add('is-visible');
          dropzone.classList.add('has-error');
          return;
        }
        currentFile = file;
        errorFile.classList.remove('is-visible');
        dropzone.classList.remove('has-error');
        fileNameEl.textContent = file.name;
        fileMetaEl.textContent = formatSize(file.size) + ' · ' + (isPdf ? 'Document PDF' : 'Image');
        if (isImg) {
          var reader = new FileReader();
          reader.onload = function(ev) {
            mediaSlot.innerHTML = '<img src="' + ev.target.result + '" alt="Aperçu" class="upload-thumbnail">';
          };
          reader.readAsDataURL(file);
        } else {
          mediaSlot.innerHTML = '<div class="upload-pdf-icon"><span>PDF</span></div>';
        }
        dropzone.style.display = 'none';
        previewWrap.classList.add('is-visible');
      }

      dropzone.addEventListener('click', function() { fileInput.click(); });
      dropzone.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); } });
      ['dragenter', 'dragover'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.add('is-dragover'); });
      });
      ['dragleave', 'drop'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('is-dragover'); });
      });
      dropzone.addEventListener('drop', function(e) {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
          fileInput.files = e.dataTransfer.files;
          previewFile(e.dataTransfer.files[0]);
        }
      });
      fileInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) previewFile(e.target.files[0]);
      });
      btnRemoveFile.addEventListener('click', function() {
        currentFile = null;
        fileInput.value = '';
        mediaSlot.innerHTML = '';
        previewWrap.classList.remove('is-visible');
        dropzone.style.display = 'block';
      });

      // 4. Validation finale et export DataURL avant soumission PHP POST
      var form = document.getElementById('fiche-client-form');
      form.addEventListener('submit', function(e) {
        var hasError = false;
        var firstErr = null;

        function markErr(el, errSpan) {
          hasError = true;
          if (el) el.classList.add('has-error');
          if (errSpan) errSpan.classList.add('is-visible');
          if (!firstErr && el) firstErr = el;
        }

        var nomInput = document.getElementById('input-nom');
        var fieldNom = document.getElementById('field-nom');
        if (!nomInput.value.trim()) markErr(fieldNom); else fieldNom.classList.remove('has-error');

        var prenomInput = document.getElementById('input-prenom');
        var fieldPrenom = document.getElementById('field-prenom');
        if (!prenomInput.value.trim()) markErr(fieldPrenom); else fieldPrenom.classList.remove('has-error');

        var telInput = document.getElementById('input-telephone');
        var fieldTel = document.getElementById('field-telephone');
        if (!telInput.value.trim() || telInput.value.replace(/[^0-9+]/g, '').length < 8) markErr(fieldTel); else fieldTel.classList.remove('has-error');

        var emailInput = document.getElementById('input-email');
        var fieldEmail = document.getElementById('field-email');
        if (!emailInput.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) markErr(fieldEmail); else fieldEmail.classList.remove('has-error');

        if (!form.querySelector('input[name="type_transaction"]:checked')) {
          markErr(document.getElementById('section-transaction'), document.getElementById('error-transaction'));
        }
        if (!form.querySelector('input[name="type_bien"]:checked')) {
          markErr(document.getElementById('section-bien'), document.getElementById('error-bien'));
        }
        if (!fileInput.files || !fileInput.files.length) {
          markErr(dropzone, errorFile);
        }
        if (!hasSignature || strokes.length === 0) {
          markErr(signatureCard, errorSignature);
        } else {
          sigHiddenInput.value = canvas.toDataURL('image/png');
        }

        if (hasError) {
          e.preventDefault();
          if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    })();
  </script>
  <?php endif; ?>
</body>
</html>
