<?php
/**
 * client/estimation.php - Assistant d'estimation de bien immobilier (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$activePage = 'estimation';
$b = BASE_URL;

$estimSuccess = isset($_GET['sent']) && $_GET['sent'] === '1';
$estimError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_estimation') {
    $typeBien  = trim((string)($_POST['type'] ?? 'Appartement'));
    $wilaya    = trim((string)($_POST['wilaya'] ?? ''));
    $commune   = trim((string)($_POST['commune'] ?? ''));
    $quartier  = trim((string)($_POST['quartier'] ?? ''));
    $surface   = trim((string)($_POST['surface'] ?? ''));
    $land      = trim((string)($_POST['land'] ?? ''));
    $bedrooms  = trim((string)($_POST['bedrooms'] ?? ''));
    $bathrooms = trim((string)($_POST['bathrooms'] ?? ''));
    $garage    = isset($_POST['garage']) ? 'Oui' : 'Non';

    $firstname = trim((string)($_POST['firstname'] ?? ''));
    $lastname  = trim((string)($_POST['lastname'] ?? ''));
    $nom       = trim($firstname . ' ' . $lastname) ?: 'Visiteur';
    $email     = trim((string)($_POST['email'] ?? ''));
    $phone     = trim((string)($_POST['phone'] ?? ''));
    $message   = trim((string)($_POST['message'] ?? ''));

    if ($phone === '' && $email === '') {
        $estimError = 'Veuillez renseigner un numéro de téléphone ou une adresse e-mail.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $estimError = 'Format d\'adresse e-mail invalide.';
    } else {
        $sujet = "Demande d'estimation : {$typeBien}" . ($wilaya !== '' ? " à {$wilaya}" : '');
        $details = json_encode([
            'type'      => $typeBien,
            'wilaya'    => $wilaya,
            'commune'   => $commune,
            'quartier'  => $quartier,
            'surface'   => $surface,
            'land'      => $land,
            'bedrooms'  => $bedrooms,
            'bathrooms' => $bathrooms,
            'garage'    => $garage
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("
            INSERT INTO `demandes` (`type`, `nom`, `email`, `telephone`, `sujet`, `message`, `details_bien`, `statut`, `is_read`)
            VALUES ('Estimation', ?, ?, ?, ?, ?, ?, 'Nouveau', 0)
        ");
        $stmt->bind_param("ssssss", $nom, $email, $phone, $sujet, $message, $details);
        $stmt->execute();

        header('Location: ' . $b . '/client/estimation.php?sent=1#estimation-zone');
        exit;
    }
}

$settings = load_site_settings($conn);
$agency_tel = $settings['agence_telephone'] ?? $settings['phone'] ?? '+213 555 12 34 56';
$agency_tel_raw = preg_replace('/[^0-9+]/', '', $agency_tel) ?: '0555123456';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Estimer mon bien — Imolode</title>
  <meta name="description" content="Obtenez une première estimation gratuite de votre bien immobilier en Algérie en quelques minutes.">
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">

  <link rel="stylesheet" href="<?= $b ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/component.css">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/responsive.css">
</head>
<body>

  <?php include __DIR__ . '/includes/header.php'; ?>

  <main>
    <section class="page-hero">
      <div class="container">
        <h1 class="section-title">Estimer mon bien</h1>
        <p class="section-lead">Obtenez un avis de valeur professionnel et gratuit en quelques minutes.</p>
      </div>
    </section>

    <section class="section" id="estimation-zone">
      <div class="container">
        <div class="stepper" data-estimation>
          <div class="stepper-progress">
            <div class="stepper-step <?= $estimSuccess ? 'is-done' : 'is-active' ?>"><span></span></div>
            <div class="stepper-step <?= $estimSuccess ? 'is-done' : '' ?>"><span></span></div>
            <div class="stepper-step <?= $estimSuccess ? 'is-done' : '' ?>"><span></span></div>
            <div class="stepper-step <?= $estimSuccess ? 'is-done' : '' ?>"><span></span></div>
          </div>

          <?php if ($estimSuccess): ?>
            <div class="stepper-card stepper-success" id="estimation-success" style="text-align:center;padding:60px 24px;">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#27AE60" stroke-width="2" style="margin-bottom:16px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <h2 style="font-size:1.6rem;margin-bottom:12px;">Merci pour votre demande</h2>
              <p class="text-muted" style="max-width:540px;margin:0 auto 24px;">Un conseiller Imolode va étudier les caractéristiques de votre bien et vous recontactera sous 24 à 48 heures avec un dossier d'estimation détaillé.</p>
              <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a class="btn btn-primary" href="<?= $b ?>/client/biens.php">Découvrir nos biens en vente</a>
                <a class="btn btn-outline" href="<?= $b ?>/client/estimation.php">Estimer un autre bien</a>
              </div>
            </div>
          <?php else: ?>

            <?php if ($estimError !== ''): ?>
              <div style="padding:14px;background:#FDE8E8;color:#9B1C1C;border:1px solid #F8B4B4;border-radius:6px;font-size:0.9rem;margin-bottom:20px;">
                <?= e($estimError) ?>
              </div>
            <?php endif; ?>

            <form class="stepper-card" id="estimation-form" method="POST" action="<?= $b ?>/client/estimation.php">
              <input type="hidden" name="action" value="send_estimation">

              <!-- Étape 1 : Type de bien -->
              <div class="stepper-pane is-active">
                <h2 class="stepper-title">Type de bien</h2>
                <p class="stepper-subtitle">Sélectionnez la nature du bien immobilier à estimer.</p>
                <div class="stepper-choices">
                  <?php
                  $estimTypes = ['Appartement' => 'Appartement', 'Villa' => 'Villa', 'Duplex' => 'Duplex', 'Terrain' => 'Terrain', 'Local' => 'Local commercial', 'Bureau' => 'Bureau'];
                  $selectedType = $_POST['type'] ?? 'Appartement';
                  foreach ($estimTypes as $val => $lbl):
                  ?>
                    <label class="choice">
                      <input type="radio" name="type" value="<?= e($val) ?>" <?= $selectedType === $val ? 'checked' : '' ?>>
                      <span><?= e($lbl) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Étape 2 : Localisation -->
              <div class="stepper-pane">
                <h2 class="stepper-title">Localisation</h2>
                <p class="stepper-subtitle">Indiquez l'emplacement géographique du bien.</p>
                <div class="form-grid">
                  <label class="form-field"><span>Wilaya</span><input type="text" name="wilaya" placeholder="Ex: Béjaïa" required value="<?= e($_POST['wilaya'] ?? '') ?>"></label>
                  <label class="form-field"><span>Commune</span><input type="text" name="commune" placeholder="Ex: Béjaïa ville" required value="<?= e($_POST['commune'] ?? '') ?>"></label>
                </div>
                <label class="form-field" style="margin-top:12px"><span>Quartier / Adresse</span><input type="text" name="quartier" placeholder="Ex: Tichy, Centre-ville, etc." value="<?= e($_POST['quartier'] ?? '') ?>"></label>
              </div>

              <!-- Étape 3 : Caractéristiques -->
              <div class="stepper-pane">
                <h2 class="stepper-title">Caractéristiques</h2>
                <p class="stepper-subtitle">Précisez les dimensions et volumes du bien.</p>
                <div class="form-grid">
                  <label class="form-field"><span>Surface habitable (m²)</span><input type="number" name="surface" min="1" placeholder="120" required value="<?= e($_POST['surface'] ?? '') ?>"></label>
                  <label class="form-field"><span>Surface terrain (m²)</span><input type="number" name="land" min="0" placeholder="Optionnel" value="<?= e($_POST['land'] ?? '') ?>"></label>
                  <label class="form-field"><span>Chambres</span><input type="number" name="bedrooms" min="0" placeholder="3" value="<?= e($_POST['bedrooms'] ?? '') ?>"></label>
                  <label class="form-field"><span>Salles de bain</span><input type="number" name="bathrooms" min="0" placeholder="1" value="<?= e($_POST['bathrooms'] ?? '') ?>"></label>
                </div>
                <label class="choice" style="margin-top:16px"><input type="checkbox" name="garage" <?= isset($_POST['garage']) ? 'checked' : '' ?>><span>Garage ou place de stationnement</span></label>
              </div>

              <!-- Étape 4 : Coordonnées -->
              <div class="stepper-pane">
                <h2 class="stepper-title">Vos coordonnées</h2>
                <p class="stepper-subtitle">Pour recevoir votre étude de valeur personnalisée.</p>
                <div class="form-grid">
                  <label class="form-field"><span>Prénom</span><input type="text" name="firstname" required placeholder="Votre prénom" value="<?= e($_POST['firstname'] ?? '') ?>"></label>
                  <label class="form-field"><span>Nom</span><input type="text" name="lastname" required placeholder="Votre nom" value="<?= e($_POST['lastname'] ?? '') ?>"></label>
                  <label class="form-field"><span>Email</span><input type="email" name="email" required placeholder="votre@email.com" value="<?= e($_POST['email'] ?? '') ?>"></label>
                  <label class="form-field"><span>Téléphone</span><input type="tel" name="phone" required placeholder="05 XX XX XX XX" value="<?= e($_POST['phone'] ?? '') ?>"></label>
                </div>
                <label class="form-field" style="margin-top:12px"><span>Remarques ou spécificités</span><textarea name="message" rows="3" placeholder="État du bien, vue mer, terrasse, ascenseur..."><?= e($_POST['message'] ?? '') ?></textarea></label>
              </div>

              <div class="stepper-actions">
                <button type="button" class="btn btn-outline" data-step-prev style="display:none;">Précédent</button>
                <div style="margin-left:auto; display:flex; gap:10px;">
                  <button type="button" class="btn btn-primary" data-step-next>Suivant</button>
                  <button type="submit" class="btn btn-primary" id="btn-submit-estim" style="display:none;">Valider l'estimation</button>
                </div>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Bannière CTA -->
    <section class="cta-section">
      <div class="cta-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=75" alt="" loading="lazy">
      </div>
      <div class="container cta-inner">
        <h2 class="cta-title">Une question avant d'estimer ?</h2>
        <p class="cta-subtitle">Nos conseillers sont disponibles pour échanger directement par téléphone ou à l'agence.</p>
        <div class="cta-actions">
          <a href="<?= $b ?>/client/contact.php" class="btn btn-primary btn-lg">Nous contacter</a>
          <a href="tel:<?= e($agency_tel_raw) ?>" class="btn btn-outline-light btn-lg">Appeler un conseiller</a>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    (function() {
      var form = document.getElementById('estimation-form');
      if (!form) return;

      var root = document.querySelector('[data-estimation]');
      var steps = root.querySelectorAll('.stepper-step');
      var panes = root.querySelectorAll('.stepper-pane');
      var prevBtn = root.querySelector('[data-step-prev]');
      var nextBtn = root.querySelector('[data-step-next]');
      var submitBtn = document.getElementById('btn-submit-estim');
      var current = <?= $estimError !== '' ? 3 : 0 ?>;

      function render() {
        panes.forEach(function(p, i) { p.classList.toggle('is-active', i === current); });
        steps.forEach(function(s, i) {
          s.classList.toggle('is-active', i === current);
          s.classList.toggle('is-done', i < current);
        });
        if (prevBtn) prevBtn.style.display = current > 0 ? 'inline-flex' : 'none';
        if (nextBtn) nextBtn.style.display = current < panes.length - 1 ? 'inline-flex' : 'none';
        if (submitBtn) submitBtn.style.display = current === panes.length - 1 ? 'inline-flex' : 'none';
      }

      function validatePane(pane) {
        var valid = true;
        pane.querySelectorAll('input[required]').forEach(function(f) {
          var ok = f.value.trim() !== '';
          f.style.borderColor = ok ? '' : 'var(--color-danger, #E74C3C)';
          if (!ok) valid = false;
        });
        return valid;
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', function() {
          if (!validatePane(panes[current])) return;
          if (current < panes.length - 1) {
            current += 1;
            render();
          }
        });
      }

      if (prevBtn) {
        prevBtn.addEventListener('click', function() {
          if (current > 0) {
            current -= 1;
            render();
          }
        });
      }

      form.addEventListener('submit', function(e) {
        if (!validatePane(panes[current])) {
          e.preventDefault();
        }
      });

      render();
    })();
  </script>
</body>
</html>
