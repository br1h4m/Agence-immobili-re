<?php
/**
 * admin/imprimer.php - Impression A4 isolée d'une demande ou d'une fiche client (HTML brut + PHP)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';

$currentUser = require_admin($conn);
$b = BASE_URL;

$source = trim((string)($_GET['source'] ?? 'demande'));
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    die('Identifiant de document manquant.');
}

$settings = load_site_settings($conn);
$agencyAddr = $settings['agence_adresse'] ?? $settings['address'] ?? '04 Boulevard des Frères Bouaouina, Béjaïa 06000';
$agencyTel  = $settings['agence_telephone'] ?? $settings['phone'] ?? '+213 555 12 34 56';
$agencyMail = $settings['agence_email'] ?? $settings['email'] ?? 'contact@immolode.dz';

$docTitle = 'Dossier Imolode';
$docType  = 'Contact';
$docRef   = '';
$docDate  = '';
$docStatus= '';
$client   = ['nom' => '', 'tel' => '', 'email' => ''];
$details  = [];
$message  = '';
$sigUrl   = '';
$pieceUrl = '';

if ($source === 'fiche') {
    $stmt = $conn->prepare("SELECT * FROM `fiches_client` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) die('Fiche client introuvable.');

    $docTitle  = 'Dossier officiel de constitution client';
    $docType   = 'Fiche client (' . ($row['transaction'] ?: 'Demande') . ')';
    $docRef    = $row['reference'] ?: ('IML-DOS-' . $row['id']);
    $docDate   = format_datetime($row['date_creation']);
    $docStatus = $row['statut'] ?: 'Nouveau';
    $client    = [
        'nom'   => trim(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? '')),
        'tel'   => $row['telephone'] ?? '',
        'email' => $row['email'] ?? '',
    ];
    $details = [
        ['Numéro de dossier', $docRef],
        ['Nature de l\'opération', $row['transaction'] ?: 'Demande'],
        ['Type de bien recherché', $row['type_bien'] ?: 'Appartement'],
        ['Engagement d\'honoraires', 'Accepté et certifié électroniquement'],
        ['Date de constitution', $docDate],
    ];
    $message = "Le client certifie sur l'honneur l'exactitude des informations transmises pour la constitution de son dossier immobilier.";
    $sigUrl = !empty($row['signature']) ? asset_url((string)$row['signature']) : '';
    $pieceUrl = !empty($row['piece_identite']) ? asset_url((string)$row['piece_identite']) : '';
} else {
    $stmt = $conn->prepare("SELECT * FROM `demandes` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) die('Demande introuvable.');

    $isEst = ($row['type'] === 'Estimation');
    $docTitle  = $isEst ? "Fiche d'estimation immobilière" : 'Fiche de prise de contact client';
    $docType   = $row['type'] ?: 'Contact';
    $docRef    = 'DEM-' . $row['id'];
    $docDate   = format_datetime($row['date_creation']);
    $docStatus = $row['statut'] ?: 'Nouveau';
    $client    = [
        'nom'   => $row['nom'] ?? '',
        'tel'   => $row['telephone'] ?? '',
        'email' => $row['email'] ?? '',
    ];
    if ($isEst) {
        $d = json_decode($row['details_bien'] ?? '{}', true) ?: [];
        if (!empty($d['type'])) $details[] = ['Type de bien', $d['type']];
        if (!empty($d['wilaya'])) $details[] = ['Wilaya', $d['wilaya']];
        if (!empty($d['commune'])) $details[] = ['Commune', $d['commune']];
        if (!empty($d['quartier'])) $details[] = ['Quartier', $d['quartier']];
        if (!empty($d['surface'])) $details[] = ['Surface habitable', $d['surface'] . ' m²'];
        if (!empty($d['land'])) $details[] = ['Surface terrain', $d['land'] . ' m²'];
        if (!empty($d['bedrooms'])) $details[] = ['Chambres', $d['bedrooms']];
        if (!empty($d['bathrooms'])) $details[] = ['Salles de bain', $d['bathrooms']];
        if (!empty($d['garage'])) $details[] = ['Garage / Parking', $d['garage']];
    }
    $message = (string)($row['message'] ?? '');
}

$pieceIsPdf = $pieceUrl !== '' && str_ends_with(strtolower($pieceUrl), '.pdf');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?= e($docTitle) ?> — Imolode</title>
  <style>
    @page { size: A4 portrait; margin: 15mm 20mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #1A1A18; background: #F5F4F0; font-size: 10pt; line-height: 1.5; }
    .toolbar { background: #141414; color: #FFF; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; }
    .toolbar-btn { background: #B59A6A; color: #FFF; border: 0; padding: 8px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
    .toolbar-back { color: #DDD; text-decoration: none; font-size: 0.9rem; }
    .sheet { max-width: 210mm; margin: 20px auto; background: #FFF; padding: 20mm; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .print-header { display: flex; justify-content: space-between; border-bottom: 2px solid #8C7355; padding-bottom: 12px; margin-bottom: 18px; }
    .brand-name { font-size: 18pt; font-weight: 700; color: #1A1A18; }
    .brand-sub { font-size: 8.5pt; color: #8C7355; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
    .doc-meta { text-align: right; font-size: 9pt; color: #555; }
    .doc-ref { font-size: 11pt; font-weight: 700; color: #1A1A18; }
    .print-title-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .print-doc-title { font-size: 13.5pt; font-weight: 700; color: #1A1A18; }
    .print-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 8.5pt; font-weight: 600; background: #EFECE6; color: #1A1A18; border: 1px solid #D6D2CA; }
    .print-section { margin-bottom: 16px; }
    .print-section-title { font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #8C7355; border-bottom: 1px solid #EFECE6; padding-bottom: 4px; margin-bottom: 8px; }
    .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .print-field { font-size: 9pt; }
    .print-label { color: #666; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
    .print-value { font-weight: 600; color: #1A1A18; }
    .print-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 4px; }
    .print-table th { text-align: left; padding: 6px 10px; background: #F8F7F4; color: #555; font-weight: 600; width: 40%; border-bottom: 1px solid #EFECE6; }
    .print-table td { padding: 6px 10px; color: #1A1A18; border-bottom: 1px solid #EFECE6; }
    .print-box { background: #FAF9F6; border: 1px solid #EFECE6; border-radius: 4px; padding: 10px 14px; font-size: 9pt; white-space: pre-wrap; line-height: 1.6; }
    .print-footer { margin-top: 24px; border-top: 1px solid #D6D2CA; padding-top: 10px; font-size: 8pt; color: #888; text-align: center; }
    @media print {
      body { background: #FFF; }
      .toolbar { display: none !important; }
      .sheet { margin: 0; padding: 0; box-shadow: none; max-width: 100%; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <a href="javascript:window.close()" class="toolbar-back">&larr; Fermer</a>
    <button type="button" class="toolbar-btn" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
  </div>

  <div class="sheet">
    <header class="print-header">
      <div>
        <div class="brand-name">Imolode</div>
        <div class="brand-sub">Agence Immobilière Agréée</div>
      </div>
      <div class="doc-meta">
        <div class="doc-ref">Réf : <?= e($docRef) ?></div>
        <div>Date : <?= e($docDate) ?></div>
        <div>Édité depuis l'administration Imolode</div>
      </div>
    </header>

    <div class="print-title-bar">
      <h1 class="print-doc-title"><?= e($docTitle) ?></h1>
      <span class="print-badge"><?= e($docStatus) ?></span>
    </div>

    <div class="print-section">
      <h3 class="print-section-title">Coordonnées du client</h3>
      <div class="print-grid">
        <div class="print-field">
          <span class="print-label">Nom &amp; Prénom</span>
          <span class="print-value"><?= e($client['nom'] ?: 'Non renseigné') ?></span>
        </div>
        <div class="print-field">
          <span class="print-label">Téléphone</span>
          <span class="print-value"><?= e($client['tel'] ?: 'Non renseigné') ?></span>
        </div>
        <div class="print-field">
          <span class="print-label">Adresse E-mail</span>
          <span class="print-value"><?= e($client['email'] ?: 'Non renseigné') ?></span>
        </div>
        <div class="print-field">
          <span class="print-label">Type de dossier</span>
          <span class="print-value"><?= e($docType) ?></span>
        </div>
      </div>
    </div>

    <?php if (!empty($details)): ?>
      <div class="print-section">
        <h3 class="print-section-title">Caractéristiques &amp; Détails</h3>
        <table class="print-table">
          <tbody>
            <?php foreach ($details as [$k, $v]): ?>
              <tr><th><?= e($k) ?></th><td><?= e((string)$v) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
      <div class="print-section">
        <h3 class="print-section-title">Message / Engagement</h3>
        <div class="print-box"><?= e($message) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($pieceUrl !== ''): ?>
      <div class="print-section" style="page-break-inside: avoid;">
        <h3 class="print-section-title">Justificatif d'identité</h3>
        <p style="font-size: 9.5pt; color: #555; margin: 4px 0;">Fichier joint : <strong><?= e(basename($pieceUrl)) ?></strong></p>
        <?php if (!$pieceIsPdf): ?>
          <div style="margin-top: 6px; border: 1px solid #D6D2CA; padding: 4px; display: inline-block;">
            <img src="<?= e($pieceUrl) ?>" alt="Pièce jointe" style="max-height: 160px; max-width: 300px; display: block;">
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($sigUrl !== ''): ?>
      <div class="print-section" style="margin-top: 24px; page-break-inside: avoid;">
        <h3 class="print-section-title">Signature client recueillie</h3>
        <div style="border: 1px solid #D6D2CA; border-radius: 4px; padding: 10px; display: inline-block; background: #FFF;">
          <img src="<?= e($sigUrl) ?>" alt="Signature client" style="max-height: 75px; max-width: 250px; display: block;">
        </div>
      </div>
    <?php endif; ?>

    <footer class="print-footer">
      Imolode Immobilier — <?= e($agencyAddr) ?> — Tél : <?= e($agencyTel) ?> — <?= e($agencyMail) ?>
    </footer>
  </div>

  <script>
    window.addEventListener('load', function() { setTimeout(function() { window.print(); }, 250); });
  </script>
</body>
</html>
