<?php
/**
 * 404.php - Page d'erreur 404 Imolode (HTML brut + PHP)
 */
declare(strict_types=1);

http_response_code(404);
require_once __DIR__ . '/config/db.php';

$activePage = '';
$b = BASE_URL;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page introuvable — Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/component.css">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/responsive.css">
</head>
<body>
  <?php include __DIR__ . '/client/includes/header.php'; ?>

  <main style="padding:160px 0;text-align:center">
    <div class="container">
      <span class="section-eyebrow">Erreur 404</span>
      <h1 class="section-title" style="font-family:var(--font-serif);font-weight:500;margin-top:8px">Page introuvable</h1>
      <p class="section-lead">La page que vous cherchez n'existe pas ou a été déplacée.</p>
      <a class="btn btn-primary" href="<?= $b ?>/client/index.php" style="margin-top:24px">Retour à l'accueil</a>
    </div>
  </main>

  <?php include __DIR__ . '/client/includes/footer.php'; ?>
</body>
</html>
