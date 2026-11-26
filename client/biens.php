<?php
/**
 * client/biens.php - Catalogue et recherche des biens immobiliers (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/card.php';

$activePage = 'biens';
$b = BASE_URL;

// 1. Récupération des filtres GET
$q           = trim($_GET['q'] ?? '');
$transaction = trim($_GET['transaction'] ?? '');
$type        = trim($_GET['type'] ?? '');
$wilaya      = trim($_GET['wilaya'] ?? '');
$city        = trim($_GET['city'] ?? '');
$minPrice    = isset($_GET['minPrice']) && $_GET['minPrice'] !== '' ? (float)$_GET['minPrice'] : null;
$maxPrice    = isset($_GET['maxPrice']) && $_GET['maxPrice'] !== '' ? (float)$_GET['maxPrice'] : null;
$minSurface  = isset($_GET['minSurface']) && $_GET['minSurface'] !== '' ? (float)$_GET['minSurface'] : null;
$maxSurface  = isset($_GET['maxSurface']) && $_GET['maxSurface'] !== '' ? (float)$_GET['maxSurface'] : null;
$bedrooms    = isset($_GET['bedrooms']) && $_GET['bedrooms'] !== '' ? (int)$_GET['bedrooms'] : null;
$bathrooms   = isset($_GET['bathrooms']) && $_GET['bathrooms'] !== '' ? (int)$_GET['bathrooms'] : null;
$sort        = trim($_GET['sort'] ?? 'recent');
$hasSecondaryFilters = ($bedrooms !== null || $bathrooms !== null);

// 2. Requête filtrée MySQLi pour le rendu initial PHP
$conditions = ["`statut` != 'Archivé'"];
$types = "";
$params = [];

if ($transaction !== '' && in_array($transaction, ['Acheter', 'Louer'], true)) {
    $conditions[] = "`transaction` = ?";
    $types .= "s";
    $params[] = $transaction;
}
if ($type !== '') {
    $conditions[] = "`type` = ?";
    $types .= "s";
    $params[] = $type;
}
if ($wilaya !== '') {
    $conditions[] = "`wilaya` = ?";
    $types .= "s";
    $params[] = $wilaya;
}
if ($city !== '') {
    $conditions[] = "(`commune` LIKE ? OR `quartier` LIKE ?)";
    $types .= "ss";
    $cityWildcard = "%{$city}%";
    $params[] = $cityWildcard;
    $params[] = $cityWildcard;
}
if ($q !== '') {
    $conditions[] = "(`titre` LIKE ? OR `description` LIKE ? OR `commune` LIKE ? OR `quartier` LIKE ? OR `reference` LIKE ?)";
    $types .= "sssss";
    $qWildcard = "%{$q}%";
    $params[] = $qWildcard;
    $params[] = $qWildcard;
    $params[] = $qWildcard;
    $params[] = $qWildcard;
    $params[] = $qWildcard;
}
if ($minPrice !== null && $minPrice > 0) {
    $conditions[] = "`prix` >= ?";
    $types .= "d";
    $params[] = $minPrice;
}
if ($maxPrice !== null && $maxPrice > 0) {
    $conditions[] = "`prix` <= ?";
    $types .= "d";
    $params[] = $maxPrice;
}
if ($minSurface !== null && $minSurface > 0) {
    $conditions[] = "`surface` >= ?";
    $types .= "d";
    $params[] = $minSurface;
}
if ($maxSurface !== null && $maxSurface > 0) {
    $conditions[] = "`surface` <= ?";
    $types .= "d";
    $params[] = $maxSurface;
}
if ($bedrooms !== null && $bedrooms > 0) {
    $conditions[] = "`chambres` >= ?";
    $types .= "i";
    $params[] = $bedrooms;
}
if ($bathrooms !== null && $bathrooms > 0) {
    $conditions[] = "`salles_de_bain` >= ?";
    $types .= "i";
    $params[] = $bathrooms;
}

$orderClause = match ($sort) {
    'price-asc'    => "ORDER BY `prix` ASC",
    'price-desc'   => "ORDER BY `prix` DESC",
    'surface-asc'  => "ORDER BY `surface` ASC",
    'surface-desc' => "ORDER BY `surface` DESC",
    default        => "ORDER BY `date_creation` DESC",
};

$sql = "SELECT * FROM `properties` WHERE " . implode(' AND ', $conditions) . " " . $orderClause;
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$biens = [];
while ($row = $result->fetch_assoc()) {
    $biens[] = $row;
}
$total_count = count($biens);

// 3. Récupération de l'ensemble des biens pour le filtrage temps réel JavaScript
$all_res = $conn->query("SELECT * FROM `properties` WHERE `statut` != 'Archivé' ORDER BY `date_creation` DESC");
$all_properties_data = [];
if ($all_res) {
    while ($p = $all_res->fetch_assoc()) {
        $images = json_decode($p['images'] ?? '[]', true) ?: [];
        $rawCover = !empty($images[0]) ? asset_url((string)$images[0]) : 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=75';
        if (str_contains($rawCover, 'images.unsplash.com')) {
            $cover = preg_replace('/w=\d+/', 'w=600', $rawCover);
            $cover = preg_replace('/q=\d+/', 'q=75', (string)$cover);
        } else {
            $cover = $rawCover;
        }

        $all_properties_data[] = [
            'id'             => (int)$p['id'],
            'reference'      => $p['reference'] ?? '',
            'titre'          => $p['titre'] ?? '',
            'type'           => $p['type'] ?? '',
            'transaction'    => $p['transaction'] ?? 'Acheter',
            'prix'           => (float)$p['prix'],
            'periode_prix'   => $p['periode_prix'] ?? null,
            'surface'        => !empty($p['surface']) ? (float)$p['surface'] : null,
            'chambres'       => !empty($p['chambres']) ? (int)$p['chambres'] : null,
            'salles_de_bain' => !empty($p['salles_de_bain']) ? (int)$p['salles_de_bain'] : null,
            'wilaya'         => $p['wilaya'] ?? '',
            'commune'        => $p['commune'] ?? '',
            'quartier'       => $p['quartier'] ?? '',
            'description'    => $p['description'] ?? '',
            'statut'         => $p['statut'] ?? '',
            'nouveau'        => !empty($p['nouveau']),
            'date_creation'  => $p['date_creation'] ?? '',
            'cover'          => $cover,
            'features'       => json_decode($p['caracteristiques'] ?? '[]', true) ?: []
        ];
    }
}

// 4. Listes déroulantes dynamiques
$wilayas_res = $conn->query("SELECT DISTINCT `wilaya` FROM `properties` WHERE `wilaya` IS NOT NULL AND `wilaya` != '' ORDER BY `wilaya` ASC");
$available_wilayas = [];
if ($wilayas_res) {
    while ($w = $wilayas_res->fetch_assoc()) {
        $available_wilayas[] = $w['wilaya'];
    }
}
if (empty($available_wilayas)) {
    $available_wilayas = ['Béjaïa', 'Alger', 'Oran', 'Constantine', 'Sétif'];
}

$communes_by_wilaya = [
    'Béjaïa'      => ['Béjaïa', 'Tichy', 'Aokas', 'Souk El Ténine', 'Akbou', 'El Kseur', 'Amizour', 'Sidi Aïch', 'Tazmalt', 'Kherrata'],
    'Alger'       => ['Alger Centre', 'Hydra', 'El Biar', 'Ben Aknoun', 'Chéraga', 'Dely Ibrahim', 'Zéralda', 'Staoueli', 'Kouba', 'Bir Mourad Raïs'],
    'Oran'        => ['Oran', 'Bir El Djir', 'Es Senia', 'Ain El Turk', 'Arzew', 'Mers El Kebir', 'Canastel'],
    'Constantine' => ['Constantine', 'Ali Mendjeli', 'El Khroub', 'Ain Smara'],
    'Sétif'       => ['Sétif', 'El Eulma', 'Ain Arnat', 'Ain Oulmene']
];
$pairs_res = $conn->query("SELECT DISTINCT `wilaya`, `commune` FROM `properties` WHERE `wilaya` IS NOT NULL AND `commune` IS NOT NULL AND `commune` != '' ORDER BY `commune` ASC");
if ($pairs_res) {
    while ($row = $pairs_res->fetch_assoc()) {
        $w = $row['wilaya'];
        $c = $row['commune'];
        if (!isset($communes_by_wilaya[$w])) $communes_by_wilaya[$w] = [];
        if (!in_array($c, $communes_by_wilaya[$w], true)) $communes_by_wilaya[$w][] = $c;
    }
}

$types_res = $conn->query("SELECT DISTINCT `type` FROM `properties` WHERE `type` IS NOT NULL AND `type` != '' ORDER BY `type` ASC");
$available_types = [];
if ($types_res) {
    while ($t = $types_res->fetch_assoc()) {
        $available_types[] = $t['type'];
    }
}
if (empty($available_types)) {
    $available_types = ['Appartement', 'Villa', 'Duplex', 'Bureau', 'Terrain'];
}

if ($wilaya !== '' && isset($communes_by_wilaya[$wilaya])) {
    $available_communes = $communes_by_wilaya[$wilaya];
} else {
    $all_communes = [];
    foreach ($communes_by_wilaya as $cList) {
        foreach ($cList as $c) {
            if (!in_array($c, $all_communes, true)) $all_communes[] = $c;
        }
    }
    sort($all_communes);
    $available_communes = $all_communes;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nos biens — Imolode</title>
  <meta name="description" content="Catalogue complet des biens immobiliers disponibles à la vente et à la location.">
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
    <!-- Hero avec barre de recherche rapide -->
    <section class="page-hero">
      <div class="container">
        <h1 class="section-title">Nos biens</h1>
        <p class="section-lead">Découvrez notre sélection de propriétés disponibles à la vente et à la location.</p>

        <div style="margin: 32px auto 0; max-width: 920px; text-align: left;">
          <form class="search-bar search-bar--compact" action="<?= $b ?>/client/biens.php" method="GET" role="search" aria-label="Recherche rapide" data-search-bar>
            <div class="search-field">
              <label for="sb-transaction">Transaction</label>
              <select id="sb-transaction" name="transaction">
                <option value="Acheter" <?= $transaction === 'Acheter' ? 'selected' : '' ?>>Acheter</option>
                <option value="Louer" <?= $transaction === 'Louer' ? 'selected' : '' ?>>Louer</option>
              </select>
            </div>

            <div class="search-field">
              <label for="sb-type">Type de bien</label>
              <select id="sb-type" name="type">
                <option value="">Tous les types</option>
                <?php foreach ($available_types as $t): ?>
                  <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="search-field">
              <label for="sb-location">Recherche</label>
              <input id="sb-location" name="q" type="text" placeholder="Ville, quartier, mot-clé..." autocomplete="address-level2" value="<?= e($q) ?>">
            </div>

            <div class="search-field">
              <label for="sb-price">Budget</label>
              <select id="sb-price" name="maxPrice">
                <option value="">Indifférent</option>
                <option value="5000000" <?= $maxPrice === 5000000.0 ? 'selected' : '' ?>>Jusqu'à 5 000 000 DA</option>
                <option value="10000000" <?= $maxPrice === 10000000.0 ? 'selected' : '' ?>>Jusqu'à 10 000 000 DA</option>
                <option value="20000000" <?= $maxPrice === 20000000.0 ? 'selected' : '' ?>>Jusqu'à 20 000 000 DA</option>
                <option value="40000000" <?= $maxPrice === 40000000.0 ? 'selected' : '' ?>>Jusqu'à 40 000 000 DA</option>
                <option value="80000000" <?= $maxPrice === 80000000.0 ? 'selected' : '' ?>>Jusqu'à 80 000 000 DA</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary search-submit" aria-label="Rechercher">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              <span>Rechercher</span>
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- Section principale : Filtres latéraux + Grille des biens -->
    <section class="section">
      <div class="container">
        <div id="biens-layout" style="display:grid;grid-template-columns:280px minmax(0,1fr);gap:40px;align-items:start">
          
          <!-- Volet latéral de filtres -->
          <aside class="filters" id="filters-aside" data-filters aria-label="Filtres">
            <div class="filters-head">
              <h2 class="filters-title">Filtres</h2>
              <button type="button" class="filters-close" id="filters-close-btn" aria-label="Fermer les filtres">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
              </button>
            </div>

            <form class="filters-form" id="filters-form" data-filters-form method="GET" action="<?= $b ?>/client/biens.php">
              <!-- Recherche rapide dans les filtres -->
              <fieldset class="filter-group">
                <legend>Recherche rapide</legend>
                <label class="filter-field">
                  <span>Mot-clé ou référence</span>
                  <input type="text" name="q" id="aside-search-input" placeholder="Ex: vue mer, meublé, villa..." value="<?= e($q) ?>">
                </label>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Type de transaction</legend>
                <div class="filter-toggle" role="radiogroup" aria-label="Type de transaction">
                  <label>
                    <input type="radio" name="transaction" value="" <?= empty($transaction) ? 'checked' : '' ?>>
                    <span>Tous</span>
                  </label>
                  <label>
                    <input type="radio" name="transaction" value="Acheter" <?= $transaction === 'Acheter' ? 'checked' : '' ?>>
                    <span>Acheter</span>
                  </label>
                  <label>
                    <input type="radio" name="transaction" value="Louer" <?= $transaction === 'Louer' ? 'checked' : '' ?>>
                    <span>Louer</span>
                  </label>
                </div>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Type de bien</legend>
                <select name="type" id="filter-type">
                  <option value="">Tous les types</option>
                  <?php foreach ($available_types as $t): ?>
                    <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Wilaya &amp; Quartier</legend>
                <label class="filter-field">
                  <span>Wilaya</span>
                  <select name="wilaya" id="aside-wilaya-select">
                    <option value="">Toutes les wilayas</option>
                    <?php foreach ($available_wilayas as $w): ?>
                      <option value="<?= e($w) ?>" <?= $wilaya === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>

                <label class="filter-field" style="margin-top:8px">
                  <span>Commune / Quartier</span>
                  <select name="city" id="aside-city-select">
                    <option value="">Toutes les communes</option>
                    <?php foreach ($available_communes as $c): ?>
                      <option value="<?= e($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Fourchette de prix (DA)</legend>
                <div class="filter-range">
                  <input type="number" name="minPrice" min="0" placeholder="Prix min" value="<?= $minPrice !== null ? $minPrice : '' ?>">
                  <input type="number" name="maxPrice" min="0" placeholder="Prix max" value="<?= $maxPrice !== null ? $maxPrice : '' ?>">
                </div>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Surface (m²)</legend>
                <div class="filter-range">
                  <input type="number" name="minSurface" min="0" placeholder="Min (m²)" value="<?= $minSurface !== null ? $minSurface : '' ?>">
                  <input type="number" name="maxSurface" min="0" placeholder="Max (m²)" value="<?= $maxSurface !== null ? $maxSurface : '' ?>">
                </div>
              </fieldset>

              <div class="filter-group">
                <button type="button" class="filter-more-toggle" id="btn-more-filters" aria-expanded="<?= $hasSecondaryFilters ? 'true' : 'false' ?>" aria-controls="filter-more-panel">
                  <span>Plus de critères (chambres...)</span>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="filter-more-panel" id="filter-more-panel" <?= $hasSecondaryFilters ? '' : 'hidden' ?>>
                  <fieldset class="filter-group">
                    <legend>Chambres minimum</legend>
                    <select name="bedrooms">
                      <option value="">Indifférent</option>
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $bedrooms === $i ? 'selected' : '' ?>><?= $i ?>+ chambre<?= $i > 1 ? 's' : '' ?></option>
                      <?php endfor; ?>
                    </select>
                  </fieldset>

                  <fieldset class="filter-group">
                    <legend>Salles de bain minimum</legend>
                    <select name="bathrooms">
                      <option value="">Indifférent</option>
                      <?php for ($i = 1; $i <= 3; $i++): ?>
                        <option value="<?= $i ?>" <?= $bathrooms === $i ? 'selected' : '' ?>><?= $i ?>+ salle<?= $i > 1 ? 's' : '' ?> de bain</option>
                      <?php endfor; ?>
                    </select>
                  </fieldset>
                </div>
              </div>

              <div class="filters-actions" style="display: flex; gap: 8px;">
                <a href="<?= $b ?>/client/biens.php" class="btn btn-outline" id="btn-reset-filters" style="text-align:center; flex: 1; border-color: #A09A8F; color: var(--color-text);">Réinitialiser</a>
                <button type="submit" class="btn btn-primary" style="flex: 1; font-weight: 600; box-shadow: 0 2px 8px rgba(181, 154, 106, 0.4); cursor: pointer;">Filtrer</button>
              </div>
            </form>
          </aside>

          <!-- Colonne droite : Barre de recherche principale, compteur, tri et grille -->
          <div>
            <!-- Grande barre de recherche principale en tête du catalogue -->
          

            <!-- Barre d'outils : Compteur et tri -->
            <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
              <p id="properties-count" style="margin:0;color:var(--color-muted);font-size:0.95rem">
                <strong><?= $total_count ?></strong> bien<?= $total_count > 1 ? 's' : '' ?> disponible<?= $total_count > 1 ? 's' : '' ?>
              </p>

              <div style="display:inline-flex;align-items:center;gap:8px;font-size:.88rem">
                <label for="properties-sort" class="text-muted" style="margin:0;">Trier par :</label>
                <select id="properties-sort" name="sort" style="width:auto;padding:8px 36px 8px 12px;cursor:pointer;">
                  <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récents</option>
                  <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Prix croissant</option>
                  <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Prix décroissant</option>
                  <option value="surface-asc" <?= $sort === 'surface-asc' ? 'selected' : '' ?>>Surface croissante</option>
                  <option value="surface-desc" <?= $sort === 'surface-desc' ? 'selected' : '' ?>>Surface décroissante</option>
                </select>
              </div>
            </div>

            <div id="properties-results">
              <div class="property-grid" id="properties-grid">
                <?php if (empty($biens)): ?>
                  <div class="property-grid-empty" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border-soft);">
                    <p style="font-size: 1.15rem; font-weight: 500; margin-bottom: 8px;">Aucun bien ne correspond à vos critères de recherche.</p>
                    <p class="text-muted" style="margin-bottom: 24px;">Essayez d'élargir vos critères ou de réinitialiser les filtres.</p>
                    <a href="<?= $b ?>/client/biens.php" class="btn btn-outline" id="btn-empty-reset">Réinitialiser les filtres</a>
                  </div>
                <?php else: ?>
                  <?php foreach ($biens as $bien): ?>
                    <?php render_property_card($bien); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div style="text-align:center;margin-top:40px">
              <button id="properties-load-more" class="btn btn-outline" style="display:none">Charger plus de biens</button>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-primary filters-open-btn" id="filters-open-btn">Filtres</button>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Script d'interaction temps réel (JavaScript brut côté client) -->
  <script>
    (function() {
      var BASE_URL = <?= json_encode($b) ?>;
      var ALL_PROPERTIES = <?= json_encode($all_properties_data, JSON_UNESCAPED_UNICODE) ?>;
      var COMMUNES_DATA = <?= json_encode($communes_by_wilaya, JSON_UNESCAPED_UNICODE) ?>;

      // Layout responsive pour filtres
      function adjustLayout() {
        var layout = document.getElementById('biens-layout');
        if (layout) layout.style.gridTemplateColumns = window.innerWidth <= 768 ? '1fr' : '280px minmax(0,1fr)';
      }
      adjustLayout();
      window.addEventListener('resize', adjustLayout, { passive: true });

      // Tiroir mobile
      var aside = document.getElementById('filters-aside');
      document.getElementById('filters-open-btn')?.addEventListener('click', function() { aside?.classList.add('is-open'); });
      document.getElementById('filters-close-btn')?.addEventListener('click', function() { aside?.classList.remove('is-open'); });

      // Plus de critères
      var moreBtn = document.getElementById('btn-more-filters');
      var morePanel = document.getElementById('filter-more-panel');
      if (moreBtn && morePanel) {
        moreBtn.addEventListener('click', function() {
          var isHidden = morePanel.hidden;
          morePanel.hidden = !isHidden;
          moreBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
      }

      // Dynamique Wilaya -> Commune
      var wilayaSelect = document.getElementById('aside-wilaya-select');
      var citySelect = document.getElementById('aside-city-select');
      function updateCommunesList(chosenWilaya, keepVal) {
        if (!citySelect) return;
        var currentVal = keepVal || citySelect.value;
        while (citySelect.options.length > 0) citySelect.remove(0);

        var defOpt = document.createElement('option');
        defOpt.value = '';
        defOpt.textContent = 'Toutes les communes';
        citySelect.appendChild(defOpt);

        var list = chosenWilaya && COMMUNES_DATA[chosenWilaya] ? COMMUNES_DATA[chosenWilaya] : [];
        if (!list.length && !chosenWilaya) {
          var all = [];
          Object.keys(COMMUNES_DATA).forEach(function(k) {
            COMMUNES_DATA[k].forEach(function(c) { if (all.indexOf(c) === -1) all.push(c); });
          });
          list = all.sort();
        }
        list.forEach(function(c) {
          var opt = document.createElement('option');
          opt.value = c;
          opt.textContent = c;
          if (c === currentVal) opt.selected = true;
          citySelect.appendChild(opt);
        });
      }

      if (wilayaSelect) {
        wilayaSelect.addEventListener('change', function() {
          updateCommunesList(this.value, '');
          triggerFilter();
        });
      }

      // --- ÉLÉMENTS DE RECHERCHE ET FILTRES ---
      var searchBar = document.querySelector('[data-search-bar]');
      var filterForm = document.getElementById('filters-form');
      var catalogSearchInput = document.getElementById('catalog-search-input');
      var catalogSearchBtn = document.getElementById('catalog-search-btn');
      var catalogSearchClear = document.getElementById('catalog-search-clear');
      var asideSearchInput = document.getElementById('aside-search-input');
      var heroSearchInput = document.getElementById('sb-location');

      var sortSelect = document.getElementById('properties-sort');
      var gridSlot = document.getElementById('properties-grid');
      var countSlot = document.getElementById('properties-count');
      var loadMoreBtn = document.getElementById('properties-load-more');

      var perPage = 9;
      var currentPage = 1;
      var currentFiltered = [];

      function normalize(str) {
        return String(str || '')
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase()
          .trim();
      }

      function escapeHtml(str) {
        if (!str) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
      }

      function formatPrice(price, period) {
        var formatted = new Intl.NumberFormat('fr-DZ', { maximumFractionDigits: 0 }).format(price).replace(/\s/g, ' ') + ' DA';
        if (period) formatted += ' / ' + period;
        return formatted;
      }

      function formatSurface(surface) {
        return new Intl.NumberFormat('fr-DZ', { maximumFractionDigits: 0 }).format(surface) + ' m²';
      }

      function renderCard(p) {
        var url = BASE_URL + '/client/bien.php?id=' + p.id;
        var trans = p.transaction === 'Louer' ? 'À louer' : 'À vendre';
        var loc = escapeHtml(p.commune || '');
        if (p.quartier) loc += ' · ' + escapeHtml(p.quartier);
        if (p.wilaya && p.wilaya !== p.commune) loc += ' (' + escapeHtml(p.wilaya) + ')';

        var badges = '<span class="badge badge-transaction">' + trans + '</span>';
        if (p.nouveau) badges += '<span class="badge badge-new">Nouveau</span>';
        if (p.statut && p.statut !== 'Disponible' && p.statut !== 'Nouveau') {
          badges += '<span class="badge badge-status">' + escapeHtml(p.statut) + '</span>';
        }

        var specs = [];
        if (p.surface) specs.push(formatSurface(p.surface));
        if (p.chambres) specs.push(p.chambres + ' ch.');
        if (p.salles_de_bain) specs.push(p.salles_de_bain + ' sdb');

        var specsHtml = '';
        if (specs.length) {
          specsHtml = '<ul class="property-card-specs">' + specs.map(function(s) {
            return '<li>' + escapeHtml(s) + '</li>';
          }).join('') + '</ul>';
        }

        return '<article class="property-card" data-property-id="' + p.id + '">' +
          '<a href="' + url + '" class="property-card-media" aria-label="' + escapeHtml(p.titre) + '">' +
            '<img src="' + escapeHtml(p.cover) + '" alt="' + escapeHtml(p.titre) + '" loading="lazy" decoding="async">' +
            '<div class="property-card-badges">' + badges + '</div>' +
          '</a>' +
          '<div class="property-card-body">' +
            '<h3 class="property-card-title"><a href="' + url + '">' + escapeHtml(p.titre) + '</a></h3>' +
            '<p class="property-card-location">' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>' +
              '<span>' + loc + '</span>' +
            '</p>' +
            '<p class="property-card-price">' + formatPrice(p.prix, p.periode_prix) + '</p>' +
            specsHtml +
          '</div>' +
          '<a href="' + url + '" class="property-card-link">' +
            '<span>Voir le bien</span>' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>' +
          '</a>' +
        '</article>';
      }

      function getFilterState() {
        var state = {
          transaction: '',
          type: '',
          wilaya: '',
          city: '',
          minPrice: null,
          maxPrice: null,
          minSurface: null,
          maxSurface: null,
          bedrooms: null,
          bathrooms: null,
          q: '',
          sort: sortSelect ? sortSelect.value : 'recent'
        };

        if (filterForm) {
          var fd = new FormData(filterForm);
          state.transaction = fd.get('transaction') || '';
          state.type = fd.get('type') || '';
          state.wilaya = fd.get('wilaya') || '';
          state.city = fd.get('city') || '';
          state.minPrice = fd.get('minPrice') ? parseFloat(fd.get('minPrice')) : null;
          state.maxPrice = fd.get('maxPrice') ? parseFloat(fd.get('maxPrice')) : null;
          state.minSurface = fd.get('minSurface') ? parseFloat(fd.get('minSurface')) : null;
          state.maxSurface = fd.get('maxSurface') ? parseFloat(fd.get('maxSurface')) : null;
          state.bedrooms = fd.get('bedrooms') ? parseInt(fd.get('bedrooms'), 10) : null;
          state.bathrooms = fd.get('bathrooms') ? parseInt(fd.get('bathrooms'), 10) : null;
        }

        // Le terme de recherche peut venir de la barre principale, du volet latéral ou du Hero
        var currentQ = (catalogSearchInput ? catalogSearchInput.value : '') ||
                       (asideSearchInput ? asideSearchInput.value : '') ||
                       (heroSearchInput ? heroSearchInput.value : '');
        state.q = String(currentQ || '').trim();

        return state;
      }

      function applyFiltering() {
        var state = getFilterState();

        // Mettre à jour l'affichage de la croix d'effacement
        if (catalogSearchClear) {
          catalogSearchClear.style.display = state.q ? 'block' : 'none';
        }

        var filtered = ALL_PROPERTIES.filter(function(p) {
          if (state.transaction && p.transaction !== state.transaction) return false;
          if (state.type && p.type !== state.type) return false;
          if (state.wilaya && p.wilaya !== state.wilaya) return false;
          if (state.city) {
            var needle = normalize(state.city);
            var hayCommune = normalize(p.commune || '');
            var hayQuartier = normalize(p.quartier || '');
            if (!hayCommune.includes(needle) && !hayQuartier.includes(needle)) return false;
          }
          if (state.minPrice !== null && p.prix < state.minPrice) return false;
          if (state.maxPrice !== null && p.prix > state.maxPrice) return false;
          if (state.minSurface !== null && (p.surface === null || p.surface < state.minSurface)) return false;
          if (state.maxSurface !== null && (p.surface === null || p.surface > state.maxSurface)) return false;
          if (state.bedrooms !== null && (p.chambres === null || p.chambres < state.bedrooms)) return false;
          if (state.bathrooms !== null && (p.salles_de_bain === null || p.salles_de_bain < state.bathrooms)) return false;

          if (state.q) {
            var qClean = normalize(state.q);
            var haystack = normalize([
              p.reference,
              p.titre,
              p.type,
              p.description,
              p.commune,
              p.quartier,
              p.wilaya,
              (p.features || []).join(' ')
            ].join(' '));
            if (!haystack.includes(qClean)) return false;
          }

          return true;
        });

        // Tri
        var s = state.sort;
        filtered.sort(function(a, b) {
          if (s === 'price-asc') return a.prix - b.prix;
          if (s === 'price-desc') return b.prix - a.prix;
          if (s === 'surface-asc') return (a.surface || 0) - (b.surface || 0);
          if (s === 'surface-desc') return (b.surface || 0) - (a.surface || 0);
          return (b.date_creation || '').localeCompare(a.date_creation || '');
        });

        currentFiltered = filtered;
        renderResults();
        updateURL(state);
      }

      function renderResults() {
        if (!gridSlot) return;

        var total = currentFiltered.length;
        if (countSlot) {
          countSlot.innerHTML = '<strong>' + total + '</strong> bien' + (total > 1 ? 's' : '') + ' disponible' + (total > 1 ? 's' : '');
        }

        if (total === 0) {
          gridSlot.innerHTML = '<div class="property-grid-empty" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border-soft);">' +
            '<p style="font-size: 1.15rem; font-weight: 500; margin-bottom: 8px;">Aucun bien ne correspond à vos critères de recherche.</p>' +
            '<p class="text-muted" style="margin-bottom: 24px;">Essayez d\'élargir vos critères ou de réinitialiser les filtres.</p>' +
            '<a href="' + BASE_URL + '/client/biens.php" class="btn btn-outline" id="btn-empty-reset-js">Réinitialiser les filtres</a>' +
          '</div>';
          if (loadMoreBtn) loadMoreBtn.style.display = 'none';
          document.getElementById('btn-empty-reset-js')?.addEventListener('click', function(e) {
            e.preventDefault();
            resetFilters();
          });
          return;
        }

        var visibleItems = currentFiltered.slice(0, currentPage * perPage);
        gridSlot.innerHTML = visibleItems.map(renderCard).join('');

        if (loadMoreBtn) {
          loadMoreBtn.style.display = visibleItems.length < total ? 'inline-block' : 'none';
        }
      }

      function updateURL(state) {
        var params = new URLSearchParams();
        if (state.transaction) params.set('transaction', state.transaction);
        if (state.type) params.set('type', state.type);
        if (state.wilaya) params.set('wilaya', state.wilaya);
        if (state.city) params.set('city', state.city);
        if (state.minPrice !== null && !isNaN(state.minPrice)) params.set('minPrice', state.minPrice);
        if (state.maxPrice !== null && !isNaN(state.maxPrice)) params.set('maxPrice', state.maxPrice);
        if (state.minSurface !== null && !isNaN(state.minSurface)) params.set('minSurface', state.minSurface);
        if (state.maxSurface !== null && !isNaN(state.maxSurface)) params.set('maxSurface', state.maxSurface);
        if (state.bedrooms !== null && !isNaN(state.bedrooms)) params.set('bedrooms', state.bedrooms);
        if (state.bathrooms !== null && !isNaN(state.bathrooms)) params.set('bathrooms', state.bathrooms);
        if (state.q) params.set('q', state.q);
        if (state.sort && state.sort !== 'recent') params.set('sort', state.sort);

        var qs = params.toString();
        var newUrl = window.location.pathname + (qs ? '?' + qs : '');
        window.history.replaceState({}, '', newUrl);
      }

      // Synchronisation du terme de recherche à travers tous les champs de saisie
      function syncSearchQuery(newQ) {
        if (catalogSearchInput && catalogSearchInput.value !== newQ) catalogSearchInput.value = newQ;
        if (asideSearchInput && asideSearchInput.value !== newQ) asideSearchInput.value = newQ;
        if (heroSearchInput && heroSearchInput.value !== newQ) heroSearchInput.value = newQ;
        if (catalogSearchClear) catalogSearchClear.style.display = newQ ? 'block' : 'none';
      }

      function syncSearchBarToFilters() {
        if (!searchBar || !filterForm) return;
        var sbTrans = searchBar.querySelector('[name="transaction"]');
        var sbType = searchBar.querySelector('[name="type"]');
        var sbQ = searchBar.querySelector('[name="q"]');
        var sbPrice = searchBar.querySelector('[name="maxPrice"]');

        if (sbTrans && sbTrans.value) {
          var radio = filterForm.querySelector('input[name="transaction"][value="' + sbTrans.value + '"]');
          if (radio) radio.checked = true;
        }
        if (sbType) {
          var fType = filterForm.querySelector('select[name="type"]');
          if (fType) fType.value = sbType.value;
        }
        if (sbQ) {
          syncSearchQuery(sbQ.value);
        }
        if (sbPrice) {
          var fPrice = filterForm.querySelector('input[name="maxPrice"]');
          if (fPrice) fPrice.value = sbPrice.value;
        }
      }

      function syncFiltersToSearchBar() {
        if (!searchBar || !filterForm) return;
        var sbTrans = searchBar.querySelector('[name="transaction"]');
        var sbType = searchBar.querySelector('[name="type"]');
        var sbPrice = searchBar.querySelector('[name="maxPrice"]');

        var checkedRadio = filterForm.querySelector('input[name="transaction"]:checked');
        if (sbTrans && checkedRadio && checkedRadio.value) {
          sbTrans.value = checkedRadio.value;
        }
        var fType = filterForm.querySelector('select[name="type"]');
        if (sbType && fType) {
          sbType.value = fType.value;
        }
        var fMaxPrice = filterForm.querySelector('input[name="maxPrice"]');
        if (sbPrice && fMaxPrice) {
          sbPrice.value = fMaxPrice.value || '';
        }
      }

      var filterTimer = null;
      function triggerFilter(resetPaging) {
        if (resetPaging !== false) currentPage = 1;
        clearTimeout(filterTimer);
        filterTimer = setTimeout(function() {
          applyFiltering();
        }, 150);
      }

      // --- ÉCOUTEURS POUR LA GRANDE BARRE DE RECHERCHE PRINCIPALE ---
      if (catalogSearchInput) {
        var catalogSearchTimer = null;
        catalogSearchInput.addEventListener('input', function() {
          var val = this.value;
          syncSearchQuery(val);
          clearTimeout(catalogSearchTimer);
          catalogSearchTimer = setTimeout(function() {
            triggerFilter();
          }, 200);
        });

        catalogSearchInput.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            triggerFilter();
          }
        });
      }

      if (catalogSearchBtn) {
        catalogSearchBtn.addEventListener('click', function(e) {
          e.preventDefault();
          triggerFilter();
        });
      }

      if (catalogSearchClear) {
        catalogSearchClear.addEventListener('click', function() {
          syncSearchQuery('');
          triggerFilter();
          catalogSearchInput?.focus();
        });
      }

      // --- ÉCOUTEURS DU CHAMP DE RECHERCHE DANS LE VOLET LATÉRAL ---
      if (asideSearchInput) {
        var asideSearchTimer = null;
        asideSearchInput.addEventListener('input', function() {
          var val = this.value;
          syncSearchQuery(val);
          clearTimeout(asideSearchTimer);
          asideSearchTimer = setTimeout(function() {
            triggerFilter();
          }, 200);
        });
      }

      // --- ÉCOUTEURS DE LA BARRE DU HERO ---
      if (searchBar) {
        searchBar.addEventListener('submit', function(e) {
          e.preventDefault();
          syncSearchBarToFilters();
          triggerFilter();
        });

        searchBar.querySelectorAll('select').forEach(function(sel) {
          sel.addEventListener('change', function() {
            syncSearchBarToFilters();
            triggerFilter();
          });
        });

        if (heroSearchInput) {
          var heroQTimer = null;
          heroSearchInput.addEventListener('input', function() {
            var val = this.value;
            syncSearchQuery(val);
            clearTimeout(heroQTimer);
            heroQTimer = setTimeout(function() {
              syncSearchBarToFilters();
              triggerFilter();
            }, 250);
          });
        }
      }

      // --- ÉCOUTEURS DU FORMULAIRE DE FILTRES LATÉRAL ---
      if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
          e.preventDefault();
          syncFiltersToSearchBar();
          triggerFilter();
          aside?.classList.remove('is-open');
        });

        filterForm.querySelectorAll('select, input[type="radio"]').forEach(function(elem) {
          elem.addEventListener('change', function() {
            syncFiltersToSearchBar();
            triggerFilter();
          });
        });

        filterForm.querySelectorAll('input[type="number"]').forEach(function(elem) {
          var numTimer = null;
          elem.addEventListener('input', function() {
            clearTimeout(numTimer);
            numTimer = setTimeout(function() {
              syncFiltersToSearchBar();
              triggerFilter();
            }, 250);
          });
        });
      }

      // --- TRI ---
      if (sortSelect) {
        sortSelect.addEventListener('change', function() {
          triggerFilter();
        });
      }

      // --- CHARGER PLUS ---
      if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
          currentPage += 1;
          renderResults();
        });
      }

      // --- RÉINITIALISATION ---
      function resetFilters() {
        if (filterForm) filterForm.reset();
        if (searchBar) searchBar.reset();
        syncSearchQuery('');
        if (sortSelect) sortSelect.value = 'recent';
        updateCommunesList('', '');
        currentPage = 1;
        currentFiltered = ALL_PROPERTIES.slice();
        renderResults();
        window.history.replaceState({}, '', window.location.pathname);
      }

      document.getElementById('btn-reset-filters')?.addEventListener('click', function(e) {
        e.preventDefault();
        resetFilters();
      });

      // Initialisation
      applyFiltering();
    })();
  </script>
</body>
</html>
