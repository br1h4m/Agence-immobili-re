<?php
/**
 * ============================================================================
 * client/bien.php - Page de détail d'un bien immobilier (Haute performance)
 * ============================================================================
 * Récupère les informations complètes d'un bien par son identifiant (ID)
 * à l'aide d'une requête MySQL préparée avec MySQLi (sans PDO).
 * Rendu direct côté serveur pour un affichage instantané.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/includes/card.php';

$activePage = 'biens';

// ----------------------------------------------------------------------------
// 1. Récupération de l'identifiant du bien et requête préparée MySQLi
// ----------------------------------------------------------------------------
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$property = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM `properties` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $property = $result->fetch_assoc();
}

// Configuration de la page selon l'existence du bien
if ($property) {
    $pageTitle = htmlspecialchars($property['titre']) . ' — Imolode';
    $titre     = $property['titre'];
    $trans     = ($property['transaction'] === 'Louer') ? 'À louer' : 'À vendre';
    $statut    = $property['statut'] ?? 'Disponible';
    $prix      = format_price($property['prix'], $property['periode_prix'] ?? null);

    // Décodage des images et équipements depuis le JSON
    $rawImages = json_decode($property['images'] ?? '[]', true) ?: [];
    if (empty($rawImages)) {
        $rawImages = ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=75'];
    }

    // Optimisation automatique de la taille des images pour un chargement rapide
    $images = array_map(function($url) {
        if (str_contains($url, 'images.unsplash.com')) {
            $url = preg_replace('/w=\d+/', 'w=1200', $url);
            $url = preg_replace('/q=\d+/', 'q=75', $url);
        }
        return $url;
    }, $rawImages);

    $equipements = json_decode($property['equipements'] ?? '[]', true) ?: [];

    // Localisation complète
    $loc_parts = array_filter([$property['quartier'], $property['commune'], $property['wilaya']]);
    $full_location = implode(', ', $loc_parts);

    // Caractéristiques techniques (specs)
    $specs = [];
    if (!empty($property['surface'])) {
        $specs[] = ['Surface habitable', number_format((float)$property['surface'], 0, ',', ' ') . ' m²'];
    }
    if (!empty($property['surface_terrain'])) {
        $specs[] = ['Surface du terrain', number_format((float)$property['surface_terrain'], 0, ',', ' ') . ' m²'];
    }
    if (!empty($property['chambres'])) {
        $specs[] = ['Chambres', (int)$property['chambres']];
    }
    if (!empty($property['salles_de_bain'])) {
        $specs[] = ['Salles de bain', (int)$property['salles_de_bain']];
    }
    if ($property['garage'] !== null) {
        $specs[] = ['Garage', $property['garage'] ? 'Oui' : 'Non'];
    }
    if ($property['etage'] !== null && $property['etage'] !== '') {
        $specs[] = ['Étage', (int)$property['etage'] === 0 ? 'Rez-de-chaussée' : $property['etage'] . 'e étage'];
    }
    if (!empty($property['etages'])) {
        $specs[] = ["Nombre d'étages", (int)$property['etages']];
    }
    if (!empty($property['annee'])) {
        $specs[] = ['Année de construction', (int)$property['annee']];
    }
    if (!empty($property['reference'])) {
        $specs[] = ['Référence annonce', $property['reference']];
    }

    // Requête préparée pour les biens similaires
    $sim_stmt = $conn->prepare("SELECT * FROM `properties` WHERE `id` != ? AND `statut` != 'Archivé' AND (`transaction` = ? OR `type` = ?) ORDER BY `date_creation` DESC LIMIT 3");
    $sim_stmt->bind_param("iss", $id, $property['transaction'], $property['type']);
    $sim_stmt->execute();
    $similar_result = $sim_stmt->get_result();

    // Coordonnées de contact agence
    $agency_phone = "+213 555 12 34 56";
    $agency_wa    = "213555123456";
    $agency_address = "12 rue Didouche Mourad, Béjaïa 06000, Algérie";
    $param_res = $conn->query("SELECT `cle`, `valeur` FROM `parametres` WHERE `cle` IN ('agence_telephone', 'phone', 'address', 'agence_adresse')");
    if ($param_res) {
        while ($p_row = $param_res->fetch_assoc()) {
            if (($p_row['cle'] === 'agence_telephone' || $p_row['cle'] === 'phone') && !empty($p_row['valeur'])) {
                $agency_phone = $p_row['valeur'];
                $agency_wa = preg_replace('/[^0-9]/', '', $agency_phone);
            }
            if (($p_row['cle'] === 'address' || $p_row['cle'] === 'agence_adresse') && !empty($p_row['valeur'])) {
                $agency_address = $p_row['valeur'];
            }
        }
    }

    $wa_msg = urlencode("Bonjour Immolode, je vous contacte au sujet du bien : {$titre} (Réf: {$property['reference']}) affiché à {$prix}. Est-il toujours disponible ?");
} else {
    $pageTitle = 'Bien introuvable — Imolode';
    $agency_address = "12 rue Didouche Mourad, Béjaïa 06000, Algérie";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $pageTitle ?></title>
  <link rel="icon" type="image/svg+xml" href="/assets/images/logo.svg">

  <!-- Polices non bloquantes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" media="print" onload="this.media='all'">
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  </noscript>

  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/component.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">

  <!-- Leaflet Carte Interactive (OpenStreetMap) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

  <!-- En-tête PHP natif -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main class="property-detail">
    <div class="container">
      <?php if (!$property): ?>
        <!-- Cas où le bien n'existe pas en base de données -->
        <div style="padding:120px 0;text-align:center">
          <h1 class="section-title">Bien introuvable</h1>
          <p class="text-muted" style="margin-bottom: 24px;">Ce bien n'existe pas ou n'est plus disponible à la vente ou à la location.</p>
          <a class="btn btn-primary" href="/client/biens.php">Retour au catalogue des biens</a>
        </div>
      <?php else: ?>
        <!-- Fil d'Ariane -->
        <nav class="property-breadcrumb" aria-label="Fil d'Ariane">
          <a href="/client/index.php">Accueil</a><span>/</span>
          <a href="/client/biens.php">Nos biens</a><span>/</span>
          <span><?= e($titre) ?></span>
        </nav>

        <!-- En-tête du bien (Titre, localisation, prix, statut) -->
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

        <!-- Corps principal de la fiche bien -->
        <div class="property-layout">
          
          <!-- Colonne gauche : Galerie, Caractéristiques, Description, Équipements, Biens similaires -->
          <div class="property-content">
            
            <!-- Galerie photo interactive -->
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

              <!-- Miniatures cliquables -->
              <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs" data-gallery-thumbs>
                  <?php foreach ($images as $index => $imgUrl): ?>
                    <button type="button" class="gallery-thumb <?= $index === 0 ? 'is-active' : '' ?>" data-gallery-thumb data-index="<?= $index ?>" aria-label="Photo <?= $index + 1 ?>">
                      <img src="<?= e($imgUrl) ?>" alt="Photo <?= $index + 1 ?>">
                    </button>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <!-- Fenêtre plein écran (Lightbox) -->
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

            <!-- Caractéristiques techniques -->
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

            <!-- Description détaillée -->
            <section style="margin-top: 40px;">
              <h2 class="section-title" style="font-size:1.35rem">Description</h2>
              <div class="property-description is-clamped" id="property-description">
                <?= nl2br(e($property['description'] ?? 'Aucune description fournie.')) ?>
              </div>
              <button type="button" class="btn btn-ghost property-description-toggle" id="toggle-description" style="margin-top: 12px;">
                Afficher plus
              </button>
            </section>

            <!-- Équipements et commodités -->
            <?php if (!empty($equipements)): ?>
              <section class="property-features" style="margin-top: 40px;">
                <h2 class="section-title" style="font-size:1.35rem">Équipements & Prestations</h2>
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

            <!-- Localisation avec Carte Interactive Leaflet -->
            <section style="margin-top: 40px;">
              <h2 class="section-title" style="font-size:1.35rem">Localisation</h2>
              <p class="text-muted" style="margin-bottom:16px"><?= e($full_location) ?></p>
              <div id="property-map" style="height: 320px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--color-border-soft); z-index: 1;"></div>
            </section>

            <!-- Biens similaires recommandés -->
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

          <!-- Colonne droite : Panneau de contact fixe à sa place dans la page -->
          <aside class="contact-panel">
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

            <!-- Formulaire de demande d'information -->
            <form class="contact-form contact-form--compact" id="bien-contact-form" style="gap: 10px;" novalidate>
              <div class="form-head" style="margin-bottom: 2px;">
                <h3 class="form-title" style="font-size: 0.98rem;">Envoyer une demande</h3>
                <p class="form-context" style="margin: 0; font-size: 0.78rem;">Réf : <?= e($property['reference']) ?></p>
              </div>

              <input type="hidden" name="bien_id" value="<?= $id ?>">
              <input type="hidden" name="subject" value="Demande d'information — <?= e($titre) ?> (Réf: <?= e($property['reference']) ?>)">

              <div class="form-grid" style="gap: 8px;">
                <label class="form-field">
                  <span>Nom</span>
                  <input name="lastname" type="text" required placeholder="Votre nom" style="padding: 8px 10px;">
                </label>
                <label class="form-field">
                  <span>Prénom</span>
                  <input name="firstname" type="text" required placeholder="Votre prénom" style="padding: 8px 10px;">
                </label>
              </div>

              <div class="form-grid" style="gap: 8px;">
                <label class="form-field">
                  <span>Téléphone</span>
                  <input name="phone" type="tel" required placeholder="05 XX XX XX XX" style="padding: 8px 10px;">
                </label>
                <label class="form-field">
                  <span>Email</span>
                  <input name="email" type="email" placeholder="votre@email.com" style="padding: 8px 10px;">
                </label>
              </div>

              <label class="form-field">
                <span>Message</span>
                <textarea name="message" rows="3" required style="min-height: 76px; padding: 8px 10px;" placeholder="Je souhaite avoir plus de renseignements sur ce bien ou organiser une visite...">Bonjour, je souhaite avoir plus de précisions sur ce bien (<?= e($titre) ?> - Réf: <?= e($property['reference']) ?>) et éventuellement convenir d'une visite. Merci.</textarea>
              </label>

              <button type="submit" class="btn btn-primary btn-block" id="btn-submit-contact">
                <span>Envoyer ma demande</span>
              </button>

              <p class="form-note">Vos données restent strictement confidentielles et ne sont transmises à aucun tiers.</p>

              <div class="form-feedback" id="contact-feedback" hidden style="padding:12px;background:#EBF7EE;color:#186A3B;border-radius:6px;font-size:0.9rem;margin-top:10px;">
                Merci, votre demande a bien été enregistrée. Un conseiller vous contactera dans les plus brefs délais.
              </div>
            </form>

          </aside>

        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- Pied de page PHP natif -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Scripts légers et performants pour la galerie et le formulaire -->
  <script>
    (function() {
      // 1. Galerie photo interactive instantanée (avec préchargement immédiat)
      var images = <?= json_encode($images ?? []) ?>;
      if (images.length > 0) {
        // Précharger toutes les photos en arrière-plan dès l'ouverture de la page
        var preloadedImgs = images.map(function(src) {
          var img = new Image();
          img.src = src;
          return img;
        });

        var root = document.querySelector('[data-gallery]');
        if (root) {
          var main = root.querySelector('[data-gallery-main]');
          var thumbs = root.querySelectorAll('[data-gallery-thumb]');
          var counter = root.querySelector('[data-gallery-counter]');
          var lightbox = root.querySelector('[data-lightbox]');
          var lightboxImage = root.querySelector('[data-lightbox-image]');
          var index = 0;

          function update(next) {
            index = (next + images.length) % images.length;
            var targetSrc = images[index];
            if (main && main.src !== targetSrc) main.src = targetSrc;
            if (lightboxImage && !lightbox.hidden) lightboxImage.src = targetSrc;
            if (counter) counter.textContent = (index + 1) + ' / ' + images.length;
            thumbs.forEach(function(t, i) { t.classList.toggle('is-active', i === index); });
          }

          thumbs.forEach(function(btn, i) {
            btn.addEventListener('click', function() { update(i); });
          });

          root.querySelectorAll('[data-gallery-prev]').forEach(function(b) {
            b.addEventListener('click', function(e) { e.stopPropagation(); update(index - 1); });
          });
          root.querySelectorAll('[data-gallery-next]').forEach(function(b) {
            b.addEventListener('click', function(e) { e.stopPropagation(); update(index + 1); });
          });

          var closeLightbox = function() {
            if (!lightbox) return;
            lightbox.hidden = true;
            document.documentElement.classList.remove('no-scroll');
          };

          var openBtn = root.querySelector('[data-gallery-open]');
          if (openBtn && lightbox) {
            openBtn.addEventListener('click', function() {
              lightbox.hidden = false;
              lightboxImage.src = images[index];
              document.documentElement.classList.add('no-scroll');
            });
          }

          var closeBtn = root.querySelector('[data-lightbox-close]');
          if (closeBtn && lightbox) {
            closeBtn.addEventListener('click', closeLightbox);
          }
          if (lightbox) {
            lightbox.addEventListener('click', function(e) {
              if (e.target === lightbox) closeLightbox();
            });
          }
          document.addEventListener('keydown', function(e) {
            if (!lightbox || lightbox.hidden) return;
            if (e.key === 'Escape') closeLightbox();
            else if (e.key === 'ArrowLeft') update(index - 1);
            else if (e.key === 'ArrowRight') update(index + 1);
          });
        }
      }

      // 2. Bouton "Afficher plus" pour la description
      var desc = document.getElementById('property-description');
      var toggle = document.getElementById('toggle-description');
      if (desc && toggle) {
        toggle.addEventListener('click', function() {
          var clamped = desc.classList.toggle('is-clamped');
          toggle.textContent = clamped ? 'Afficher plus' : 'Afficher moins';
        });
      }

      // 3. Initialisation de la carte interactive Leaflet — Référence carte de visite (Footer)
      function initInteractiveMap() {
        var mapEl = document.getElementById('property-map');
        if (!mapEl || typeof L === 'undefined') {
          setTimeout(initInteractiveMap, 100);
          return;
        }

        // Récupérer l'adresse exacte depuis la carte de visite du footer
        var footerAddrEl = document.querySelector('.footer-contact li:last-child span') || document.querySelector('.footer-contact span:last-child');
        var fullAddress = (footerAddrEl ? footerAddrEl.textContent.trim() : <?= json_encode($agency_address ?? '12 rue Didouche Mourad, Béjaïa 06000, Algérie') ?>) || '12 rue Didouche Mourad, Béjaïa 06000, Algérie';

        // Coordonnées de repli fiables basées sur l'adresse réelle du footer
        var defaultLat = 36.7511;
        var defaultLng = 5.0567; // Béjaïa centre (Didouche Mourad)
        var lowerAddr = fullAddress.toLowerCase();
        if (lowerAddr.indexOf('mouradia') !== -1 || lowerAddr.indexOf('alger') !== -1 || lowerAddr.indexOf('hydra') !== -1) {
          defaultLat = 36.7538;
          defaultLng = 3.0588; // El Mouradia / Alger
        } else if (lowerAddr.indexOf('oran') !== -1) {
          defaultLat = 35.6971;
          defaultLng = -0.6308;
        }

        var map = L.map('property-map', { scrollWheelZoom: false }).setView([defaultLat, defaultLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
          maxZoom: 19
        }).addTo(map);

        var marker = L.marker([defaultLat, defaultLng]).addTo(map);
        marker.bindPopup('<strong>Imolode — Agence Immobilière</strong><br>' + fullAddress).openPopup();

        // Géocodage dynamique via Nominatim OpenStreetMap avec l'adresse du footer
        try {
          var cleanQuery = fullAddress.replace(/,\s*Algérie/i, '').trim();
          fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(cleanQuery), {
            headers: { 'Accept': 'application/json' }
          })
          .then(function(res) { return res.json(); })
          .then(function(results) {
            if (results && results.length > 0 && results[0].lat && results[0].lon) {
              var newLat = parseFloat(results[0].lat);
              var newLng = parseFloat(results[0].lon);
              map.setView([newLat, newLng], 15);
              marker.setLatLng([newLat, newLng]);
            }
          })
          .catch(function() {});
        } catch (_) {}
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInteractiveMap);
      } else {
        initInteractiveMap();
      }

      // 4. Soumission directe AJAX du formulaire de contact
      var form = document.getElementById('bien-contact-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          var btn = document.getElementById('btn-submit-contact');
          var feedback = document.getElementById('contact-feedback');

          if (btn) { btn.disabled = true; btn.textContent = 'Envoi en cours...'; }

          var data = new FormData(form);
          var payload = {
            type: 'Contact',
            nom: (data.get('firstname') || '') + ' ' + (data.get('lastname') || ''),
            email: data.get('email') || '',
            phone: data.get('phone') || '',
            sujet: data.get('subject') || 'Demande d\'information',
            message: data.get('message') || '',
            bien_id: data.get('bien_id') || null
          };

          fetch('/api/demandes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          }).then(function(res) {
            return res.json();
          }).then(function(result) {
            form.reset();
            if (feedback) feedback.hidden = false;
            if (btn) { btn.disabled = false; btn.textContent = 'Envoyé avec succès !'; }
          }).catch(function(err) {
            if (feedback) {
              feedback.hidden = false;
              feedback.textContent = 'Votre message a bien été transmis.';
            }
            if (btn) { btn.disabled = false; btn.textContent = 'Envoyer ma demande'; }
          });
        });
      }
    })();
  </script>
</body>
</html>
