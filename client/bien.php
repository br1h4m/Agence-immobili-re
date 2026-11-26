<?php
/**
 * client/bien.php - Détail d'un bien immobilier (HTML brut + PHP MySQLi)
 * Formulaire de contact traité directement en PHP POST dans ce fichier + panneau fixe (position: static).
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/card.php';

$activePage = 'biens';
$b = BASE_URL;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$contactSuccess = isset($_GET['sent']) && $_GET['sent'] === '1';
$contactError = '';

// Traitement PHP direct du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_contact') {
    $postBienId = (int)($_POST['bien_id'] ?? $id);
    $nom = trim((string)($_POST['firstname'] ?? '') . ' ' . (string)($_POST['lastname'] ?? ''));
    if ($nom === '') $nom = 'Visiteur';
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $sujet   = trim((string)($_POST['subject'] ?? "Demande d'information"));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($phone === '' && $email === '') {
        $contactError = 'Veuillez renseigner un numéro de téléphone ou une adresse e-mail.';
    } else {
        $stmtIns = $conn->prepare("
            INSERT INTO `demandes` (`type`, `nom`, `email`, `telephone`, `sujet`, `message`, `bien_id`, `statut`, `is_read`)
            VALUES ('Contact', ?, ?, ?, ?, ?, ?, 'Nouveau', 0)
        ");
        $bienIdParam = $postBienId > 0 ? $postBienId : null;
        $stmtIns->bind_param("sssssi", $nom, $email, $phone, $sujet, $message, $bienIdParam);
        $stmtIns->execute();

        header('Location: ' . $b . '/client/bien.php?id=' . $postBienId . '&sent=1#contact-panel-box');
        exit;
    }
}

$property = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM `properties` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();
}

$settings = load_site_settings($conn);
$agency_phone = $settings['agence_telephone'] ?? $settings['phone'] ?? '+213 555 12 34 56';
$agency_wa    = preg_replace('/[^0-9]/', '', $agency_phone) ?: '213555123456';
$agency_address = $settings['agence_adresse'] ?? $settings['address'] ?? '12 rue Didouche Mourad, Béjaïa 06000, Algérie';

if ($property) {
    $pageTitle = e($property['titre']) . ' — Imolode';
    $titre     = $property['titre'];
    $trans     = ($property['transaction'] === 'Louer') ? 'À louer' : 'À vendre';
    $statut    = $property['statut'] ?? 'Disponible';
    $prix      = format_price($property['prix'], $property['periode_prix'] ?? null);

    $rawImages = json_decode($property['images'] ?? '[]', true) ?: [];
    if (empty($rawImages)) {
        $rawImages = ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=75'];
    }
    $images = array_map(function ($url) {
        $resolved = asset_url((string)$url);
        if (str_contains($resolved, 'images.unsplash.com')) {
            $resolved = preg_replace('/w=\d+/', 'w=1200', $resolved);
            $resolved = preg_replace('/q=\d+/', 'q=75', (string)$resolved);
        }
        return $resolved;
    }, $rawImages);

    $equipements = json_decode($property['equipements'] ?? '[]', true) ?: [];
    $loc_parts = array_filter([$property['quartier'], $property['commune'], $property['wilaya']]);
    $full_location = implode(', ', $loc_parts);

    $specs = [];
    if (!empty($property['surface'])) $specs[] = ['Surface habitable', number_format((float)$property['surface'], 0, ',', ' ') . ' m²'];
    if (!empty($property['surface_terrain'])) $specs[] = ['Surface du terrain', number_format((float)$property['surface_terrain'], 0, ',', ' ') . ' m²'];
    if (!empty($property['chambres'])) $specs[] = ['Chambres', (int)$property['chambres']];
    if (!empty($property['salles_de_bain'])) $specs[] = ['Salles de bain', (int)$property['salles_de_bain']];
    if ($property['garage'] !== null) $specs[] = ['Garage', $property['garage'] ? 'Oui' : 'Non'];
    if ($property['etage'] !== null && $property['etage'] !== '') $specs[] = ['Étage', (int)$property['etage'] === 0 ? 'Rez-de-chaussée' : $property['etage'] . 'e étage'];
    if (!empty($property['etages'])) $specs[] = ["Nombre d'étages", (int)$property['etages']];
    if (!empty($property['annee'])) $specs[] = ['Année de construction', (int)$property['annee']];
    if (!empty($property['reference'])) $specs[] = ['Référence annonce', $property['reference']];

    $sim_stmt = $conn->prepare("SELECT * FROM `properties` WHERE `id` != ? AND `statut` != 'Archivé' AND (`transaction` = ? OR `type` = ?) ORDER BY `date_creation` DESC LIMIT 3");
    $sim_stmt->bind_param("iss", $id, $property['transaction'], $property['type']);
    $sim_stmt->execute();
    $similar_result = $sim_stmt->get_result();

    $wa_msg = urlencode("Bonjour Imolode, je vous contacte au sujet du bien : {$titre} (Réf: {$property['reference']}) affiché à {$prix}. Est-il toujours disponible ?");
} else {
    $pageTitle = 'Bien introuvable — Imolode';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $pageTitle ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">

  <link rel="stylesheet" href="<?= $b ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/component.css?v=20261126">
  <link rel="stylesheet" href="<?= $b ?>/assets/css/responsive.css">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

  <?php include __DIR__ . '/includes/header.php'; ?>

  <main class="property-detail">
    <div class="container">
      <?php if (!$property): ?>
        <div style="padding:120px 0;text-align:center">
          <h1 class="section-title">Bien introuvable</h1>
          <p class="text-muted" style="margin-bottom: 24px;">Ce bien n'existe pas ou n'est plus disponible à la vente ou à la location.</p>
          <a class="btn btn-primary" href="<?= $b ?>/client/biens.php">Retour au catalogue des biens</a>
        </div>
      <?php else: ?>
        <nav class="property-breadcrumb" aria-label="Fil d'Ariane">
          <a href="<?= $b ?>/client/index.php">Accueil</a><span>/</span>
          <a href="<?= $b ?>/client/biens.php">Nos biens</a><span>/</span>
          <span><?= e($titre) ?></span>
        </nav>

        <header class="property-hero">
          <div class="property-hero-main">
            <h1 class="property-title"><?= e($titre) ?></h1>
            <p class="property-location">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              <span><?= e($full_location) ?></span>
            </p>
          </div>
          <div class="property-hero-side">
            <p class="property-transaction"><?= $trans ?> · <span class="badge"><?= e($statut) ?></span></p>
            <p class="property-price"><?= $prix ?></p>
          </div>
        </header>

        <div class="property-layout">
          <div class="property-content">
            <!-- Galerie -->
            <div class="gallery" data-gallery>
              <div class="gallery-stage">
                <img class="gallery-main" data-gallery-main src="<?= e($images[0]) ?>" alt="<?= e($titre) ?>" data-index="0">

                <button type="button" class="gallery-nav gallery-nav--prev" data-gallery-prev aria-label="Photo précédente">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" class="gallery-nav gallery-nav--next" data-gallery-next aria-label="Photo suivante">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <button type="button" class="gallery-expand" data-gallery-open aria-label="Agrandir l'image" title="Agrandir l'image">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                </button>
                <span class="gallery-counter" data-gallery-counter>1 / <?= count($images) ?></span>
              </div>

              <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs" data-gallery-thumbs>
                  <?php foreach ($images as $index => $imgUrl): ?>
                    <button type="button" class="gallery-thumb <?= $index === 0 ? 'is-active' : '' ?>" data-gallery-thumb data-index="<?= $index ?>" aria-label="Photo <?= $index + 1 ?>">
                      <img src="<?= e($imgUrl) ?>" alt="Photo <?= $index + 1 ?>">
                    </button>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="lightbox" data-lightbox hidden>
                <button type="button" class="lightbox-close" data-lightbox-close aria-label="Fermer">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
                <button type="button" class="lightbox-nav lightbox-nav--prev" data-gallery-prev aria-label="Photo précédente">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <img class="lightbox-image" data-lightbox-image src="" alt="<?= e($titre) ?>">
                <button type="button" class="lightbox-nav lightbox-nav--next" data-gallery-next aria-label="Photo suivante">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </button>
              </div>
            </div>

            <!-- Caractéristiques -->
            <section style="margin-top: 40px;">
              <h2 class="section-title" style="font-size:1.35rem">Caractéristiques</h2>
              <div class="property-specs">
                <?php foreach ($specs as [$lbl, $val]): ?>
                  <div class="property-spec">
                    <span class="property-spec-label"><?= e($lbl) ?></span>
                    <span class="property-spec-value"><?= e((string)$val) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

            <!-- Description -->
            <section style="margin-top: 40px;">
              <h2 class="section-title" style="font-size:1.35rem">Description</h2>
              <div class="property-description is-clamped" id="property-description">
                <?= nl2br(e($property['description'] ?? 'Aucune description fournie.')) ?>
              </div>
              <button type="button" class="btn btn-ghost property-description-toggle" id="toggle-description" style="margin-top: 12px;">Afficher plus</button>
            </section>

            <!-- Équipements -->
            <?php if (!empty($equipements)): ?>
              <section class="property-features" style="margin-top: 40px;">
                <h2 class="section-title" style="font-size:1.35rem">Équipements &amp; Prestations</h2>
                <ul class="features-list">
                  <?php foreach ($equipements as $equip): ?>
                    <li class="feature-item">
                      <span class="feature-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                      </span>
                      <span class="feature-label"><?= e($equip) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </section>
            <?php endif; ?>

            <!-- Carte Interactive Leaflet — zone approximative uniquement -->
            <section style="margin-top: 40px;">
              <h2 class="section-title" style="font-size:1.35rem">Localisation</h2>
              <p class="text-muted" style="margin-bottom:4px"><?= e($full_location) ?></p>
              <p style="font-size:0.8rem; color:var(--color-text-muted, #888); margin-bottom:16px; display:flex; align-items:center; gap:5px;">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                L'adresse exacte est communiquée lors de la prise de contact avec l'agence.
              </p>
              <div id="property-map" style="height: 320px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--color-border-soft); z-index: 1; overflow:hidden;"></div>
            </section>

            <!-- Biens similaires -->
            <?php if ($similar_result && $similar_result->num_rows > 0): ?>
              <section style="margin-top: 50px;">
                <h2 class="section-title" style="font-size:1.35rem; margin-bottom: 24px;">Biens similaires</h2>
                <div class="property-grid">
                  <?php while ($sim = $similar_result->fetch_assoc()): ?>
                    <?php render_property_card($sim); ?>
                  <?php endwhile; ?>
                </div>
              </section>
            <?php endif; ?>
          </div>

          <!-- Panneau de contact fixe dans la page (position: static, ne descend pas au scroll) -->
          <aside class="contact-panel" id="contact-panel-box">
            <h3 style="margin-bottom: 4px;">Contacter l'agence</h3>
            <p style="margin-bottom: 12px; font-size: 0.84rem;">Nos conseillers sont à votre disposition pour organiser une visite.</p>

            <div class="contact-actions" style="margin-bottom: 14px; gap: 6px;">
              <a href="tel:<?= e($agency_phone) ?>" class="btn btn-outline btn-sm btn-block">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <span>Appeler l'agence</span>
              </a>

              <a href="https://wa.me/<?= e($agency_wa) ?>?text=<?= $wa_msg ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm btn-block">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                <span>Discuter sur WhatsApp</span>
              </a>
            </div>

            <hr class="divider" style="margin: 12px 0; border: 0; border-top: 1px solid var(--color-border-soft);">

            <?php if ($contactSuccess): ?>
              <div style="padding:14px;background:#EBF7EE;color:#186A3B;border:1px solid #C3E6CB;border-radius:6px;font-size:0.9rem;margin-bottom:14px;">
                Merci, votre demande a bien été enregistrée. Un conseiller vous contactera dans les plus brefs délais.
              </div>
            <?php endif; ?>
            <?php if ($contactError !== ''): ?>
              <div style="padding:12px;background:#FDE8E8;color:#9B1C1C;border:1px solid #F8B4B4;border-radius:6px;font-size:0.88rem;margin-bottom:14px;">
                <?= e($contactError) ?>
              </div>
            <?php endif; ?>

            <form class="contact-form contact-form--compact" method="POST" action="<?= $b ?>/client/bien.php?id=<?= $id ?>" style="gap: 10px;">
              <input type="hidden" name="action" value="send_contact">
              <input type="hidden" name="bien_id" value="<?= $id ?>">
              <input type="hidden" name="subject" value="Demande d'information — <?= e($titre) ?> (Réf: <?= e($property['reference']) ?>)">

              <div class="form-head" style="margin-bottom: 2px;">
                <h3 class="form-title" style="font-size: 0.98rem;">Envoyer une demande</h3>
                <p class="form-context" style="margin: 0; font-size: 0.78rem;">Réf : <?= e($property['reference']) ?></p>
              </div>

              <div class="form-grid" style="gap: 8px;">
                <label class="form-field">
                  <span>Nom</span>
                  <input name="lastname" type="text" required placeholder="Votre nom" style="padding: 8px 10px;" value="<?= e($_POST['lastname'] ?? '') ?>">
                </label>
                <label class="form-field">
                  <span>Prénom</span>
                  <input name="firstname" type="text" required placeholder="Votre prénom" style="padding: 8px 10px;" value="<?= e($_POST['firstname'] ?? '') ?>">
                </label>
              </div>

              <div class="form-grid" style="gap: 8px;">
                <label class="form-field">
                  <span>Téléphone</span>
                  <input name="phone" type="tel" required placeholder="05 XX XX XX XX" style="padding: 8px 10px;" value="<?= e($_POST['phone'] ?? '') ?>">
                </label>
                <label class="form-field">
                  <span>Email</span>
                  <input name="email" type="email" placeholder="votre@email.com" style="padding: 8px 10px;" value="<?= e($_POST['email'] ?? '') ?>">
                </label>
              </div>

              <label class="form-field">
                <span>Message</span>
                <textarea name="message" rows="3" required style="min-height: 76px; padding: 8px 10px;" placeholder="Je souhaite avoir plus de renseignements..."><?= e($_POST['message'] ?? "Bonjour, je souhaite avoir plus de précisions sur ce bien ({$titre} - Réf: {$property['reference']}) et éventuellement convenir d'une visite. Merci.") ?></textarea>
              </label>

              <button type="submit" class="btn btn-primary btn-block">
                <span>Envoyer ma demande</span>
              </button>

              <p class="form-note">Vos données restent strictement confidentielles et ne sont transmises à aucun tiers.</p>
            </form>
          </aside>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    (function() {
      // Galerie interactive + Lightbox
      var images = <?= json_encode($images ?? []) ?>;
      if (images.length > 0) {
        images.forEach(function(src) { var i = new Image(); i.src = src; });
        var root = document.querySelector('[data-gallery]');
        if (root) {
          var main = root.querySelector('[data-gallery-main]');
          var thumbs = root.querySelectorAll('[data-gallery-thumb]');
          var counter = root.querySelector('[data-gallery-counter]');
          var lightbox = root.querySelector('[data-lightbox]');
          var lightboxImage = root.querySelector('[data-lightbox-image]');
          var index = 0;

          function update(newIdx) {
            index = (newIdx + images.length) % images.length;
            if (main) main.src = images[index];
            if (lightboxImage && !lightbox.hidden) lightboxImage.src = images[index];
            if (counter) counter.textContent = (index + 1) + ' / ' + images.length;
            thumbs.forEach(function(t, i) { t.classList.toggle('is-active', i === index); });
          }

          root.querySelectorAll('[data-gallery-prev]').forEach(function(b) { b.addEventListener('click', function() { update(index - 1); }); });
          root.querySelectorAll('[data-gallery-next]').forEach(function(b) { b.addEventListener('click', function() { update(index + 1); }); });
          thumbs.forEach(function(t, i) { t.addEventListener('click', function() { update(i); }); });

          var openBtn = root.querySelector('[data-gallery-open]');
          if (openBtn && lightbox) {
            openBtn.addEventListener('click', function() {
              if (lightboxImage) lightboxImage.src = images[index];
              lightbox.hidden = false;
              document.body.classList.add('no-scroll');
            });
          }
          if (main && lightbox) {
            main.style.cursor = 'zoom-in';
            main.addEventListener('click', function() {
              if (lightboxImage) lightboxImage.src = images[index];
              lightbox.hidden = false;
              document.body.classList.add('no-scroll');
            });
          }
          function closeLightbox() {
            if (lightbox) lightbox.hidden = true;
            document.body.classList.remove('no-scroll');
          }
          root.querySelector('[data-lightbox-close]')?.addEventListener('click', closeLightbox);
          if (lightbox) {
            lightbox.addEventListener('click', function(e) { if (e.target === lightbox) closeLightbox(); });
            document.addEventListener('keydown', function(e) {
              if (lightbox.hidden) return;
              if (e.key === 'Escape') closeLightbox();
              else if (e.key === 'ArrowLeft') update(index - 1);
              else if (e.key === 'ArrowRight') update(index + 1);
            });
          }
        }
      }

      // Afficher plus / moins
      var toggle = document.getElementById('toggle-description');
      var desc = document.getElementById('property-description');
      if (toggle && desc) {
        toggle.addEventListener('click', function() {
          var clamped = desc.classList.toggle('is-clamped');
          toggle.textContent = clamped ? 'Afficher plus' : 'Afficher moins';
        });
      }

      // Carte Leaflet — zone approximative du bien (sans adresse exacte)
      function initInteractiveMap() {
        var mapEl = document.getElementById('property-map');
        if (!mapEl || typeof L === 'undefined') {
          setTimeout(initInteractiveMap, 100);
          return;
        }

        // Coordonnées des wilayas algériennes (fallback si Nominatim échoue)
        var WILAYA_COORDS = {
          'adrar': [27.8744, -0.2914], 'chlef': [36.1647, 1.3317], 'laghouat': [33.8000, 2.8653],
          'oum el bouaghi': [35.8778, 7.1133], 'batna': [35.5553, 6.1744], 'béjaïa': [36.7511, 5.0567],
          'bejaia': [36.7511, 5.0567], 'biskra': [34.8500, 5.7333], 'béchar': [31.6097, -2.2161],
          'bechar': [31.6097, -2.2161], 'blida': [36.4703, 2.8277], 'bouira': [36.3700, 3.9008],
          'tamanrasset': [22.7851, 5.5228], 'tébessa': [35.4044, 8.1247], 'tebessa': [35.4044, 8.1247],
          'tlemcen': [34.8886, -1.3144], 'tiaret': [35.3706, 1.3272], 'tizi ouzou': [36.7117, 4.0453],
          'alger': [36.7538, 3.0588], 'algiers': [36.7538, 3.0588], 'djelfa': [34.6753, 3.2631],
          'jijel': [36.8206, 5.7667], 'sétif': [36.1908, 5.4097], 'setif': [36.1908, 5.4097],
          'saïda': [34.8311, 0.1511], 'saida': [34.8311, 0.1511], 'skikda': [36.8761, 6.9067],
          'sidi bel abbès': [35.1833, -0.6333], 'sidi bel abbes': [35.1833, -0.6333],
          'annaba': [36.9000, 7.7667], 'guelma': [36.4639, 7.4333], 'constantine': [36.3650, 6.6147],
          'médéa': [36.2644, 2.7536], 'medea': [36.2644, 2.7536], 'mostaganem': [35.9333, 0.0833],
          "m'sila": [35.7069, 4.5406], 'mascara': [35.3956, 0.1406], 'ouargla': [31.9497, 5.3246],
          'oran': [35.6971, -0.6308], 'el bayadh': [33.6836, 1.0106], 'illizi': [26.5069, 8.4761],
          'bordj bou arréridj': [36.0731, 4.7639], 'boumerdès': [36.7667, 3.4667],
          'boumerdes': [36.7667, 3.4667], 'el tarf': [36.7678, 8.3131], 'tindouf': [27.6736, -8.1472],
          'tissemsilt': [35.6072, 1.8117], 'el oued': [33.3675, 6.8631], 'khenchela': [35.4356, 7.1436],
          'souk ahras': [36.2869, 7.9514], 'tipaza': [36.5894, 2.4475],
          'mila': [36.4486, 6.2639], 'aïn defla': [36.2644, 1.9664], 'ain defla': [36.2644, 1.9664],
          'naâma': [33.2669, -0.3131], 'naama': [33.2669, -0.3131],
          'aïn témouchent': [35.2983, -1.1403], 'ain temouchent': [35.2983, -1.1403],
          'ghardaïa': [32.4903, 3.6736], 'ghardaia': [32.4903, 3.6736],
          'relizane': [35.7372, 0.5561]
        };

        // On géocode uniquement commune + wilaya (jamais quartier/rue)
        var commune = <?= json_encode(trim((string)($property['commune'] ?? ''))) ?>;
        var wilaya  = <?= json_encode(trim((string)($property['wilaya']  ?? ''))) ?>;
        var searchZone = [commune, wilaya, 'Algérie'].filter(Boolean).join(', ');

        // Fallback : chercher dans la table par wilaya (insensible à la casse)
        var wilayaKey = wilaya.toLowerCase().replace(/\s*\(.*\)/, '').trim();
        var communeKey = commune.toLowerCase().replace(/\s*\(.*\)/, '').trim();
        var fallback = WILAYA_COORDS[wilayaKey] || WILAYA_COORDS[communeKey] || [36.7511, 5.0567];
        var fallbackLat = fallback[0], fallbackLng = fallback[1];

        var map = L.map('property-map', {
          scrollWheelZoom: false,
          zoomControl: true
        }).setView([fallbackLat, fallbackLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
          maxZoom: 19
        }).addTo(map);

        // Cercle de zone — rayon ~700m, sans marqueur précis
        var circle = L.circle([fallbackLat, fallbackLng], {
          radius: 700,
          color: '#B8970A',
          weight: 2,
          opacity: 0.7,
          fillColor: '#D4AF37',
          fillOpacity: 0.18
        }).addTo(map);
        circle.bindPopup('<strong><?= e(addslashes($full_location)) ?></strong><br><em style="font-size:0.82rem;color:#888">Zone approximative — adresse communiquée sur demande</em>');

        map.touchZoom.disable();
        map.doubleClickZoom.disable();

        // Géocodage Nominatim sur commune + wilaya pour affiner si réseau disponible
        try {
          fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(searchZone))
            .then(function(r) { return r.json(); })
            .then(function(res) {
              if (res && res[0] && res[0].lat && res[0].lon) {
                var lat = parseFloat(res[0].lat), lng = parseFloat(res[0].lon);
                map.setView([lat, lng], 13);
                circle.setLatLng([lat, lng]);
              }
            }).catch(function() {});
        } catch (_) {}
      }
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initInteractiveMap);
      else initInteractiveMap();
    })();
  </script>
</body>
</html>
