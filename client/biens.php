<?php
/**
 * ============================================================================
 * client/biens.php - Catalogue et recherche des biens immobiliers
 * ============================================================================
 * Requêtes MySQL préparées simples avec MySQLi (sans PDO).
 * Rendu direct côté serveur pour un affichage instantané et haute performance.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/includes/card.php';

$activePage = 'biens';

// ----------------------------------------------------------------------------
// 1. Récupération et assainissement des paramètres GET
// ----------------------------------------------------------------------------
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
$sort        = trim($_GET['sort'] ?? 'recent');

// ----------------------------------------------------------------------------
// 2. Construction de la requête SQL préparée (MySQLi)
// ----------------------------------------------------------------------------
$conditions = ["`statut` != 'Archivé'"];
$types = "";
$params = [];

// Filtre transaction (Acheter / Louer)
if ($transaction !== '' && in_array($transaction, ['Acheter', 'Louer'])) {
    $conditions[] = "`transaction` = ?";
    $types .= "s";
    $params[] = $transaction;
}

// Filtre type de bien
if ($type !== '') {
    $conditions[] = "`type` = ?";
    $types .= "s";
    $params[] = $type;
}

// Filtre wilaya
if ($wilaya !== '') {
    $conditions[] = "`wilaya` = ?";
    $types .= "s";
    $params[] = $wilaya;
}

// Filtre ville / commune / quartier
if ($city !== '') {
    $conditions[] = "(`commune` LIKE ? OR `quartier` LIKE ?)";
    $types .= "ss";
    $cityWildcard = "%{$city}%";
    $params[] = $cityWildcard;
    $params[] = $cityWildcard;
}

// Recherche globale par mot-clé (q)
if ($q !== '') {
    $conditions[] = "(`titre` LIKE ? OR `description` LIKE ? OR `commune` LIKE ? OR `quartier` LIKE ?)";
    $types .= "ssss";
    $qWildcard = "%{$q}%";
    $params[] = $qWildcard;
    $params[] = $qWildcard;
    $params[] = $qWildcard;
    $params[] = $qWildcard;
}

// Filtre prix min et max
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

// Filtre surface min et max
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

// Filtre nombre de chambres
if ($bedrooms !== null && $bedrooms > 0) {
    $conditions[] = "`chambres` >= ?";
    $types .= "i";
    $params[] = $bedrooms;
}

// Ordre de tri
$orderClause = "ORDER BY `date_creation` DESC";
if ($sort === 'price-asc') {
    $orderClause = "ORDER BY `prix` ASC";
} elseif ($sort === 'price-desc') {
    $orderClause = "ORDER BY `prix` DESC";
} elseif ($sort === 'surface-asc') {
    $orderClause = "ORDER BY `surface` ASC";
} elseif ($sort === 'surface-desc') {
    $orderClause = "ORDER BY `surface` DESC";
}

// Assemblage final de la requête SQL
$sql = "SELECT * FROM `properties` WHERE " . implode(' AND ', $conditions) . " " . $orderClause;

// Exécution avec requête préparée
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

// ----------------------------------------------------------------------------
// 3. Récupération des options pour les listes déroulantes de filtre
// ----------------------------------------------------------------------------
$wilayas_res = $conn->query("SELECT DISTINCT `wilaya` FROM `properties` WHERE `wilaya` IS NOT NULL AND `wilaya` != '' ORDER BY `wilaya` ASC");
$types_res   = $conn->query("SELECT DISTINCT `type` FROM `properties` WHERE `type` IS NOT NULL AND `type` != '' ORDER BY `type` ASC");
$communes_res= $conn->query("SELECT DISTINCT `commune` FROM `properties` WHERE `commune` IS NOT NULL AND `commune` != '' ORDER BY `commune` ASC");

$available_wilayas = [];
if ($wilayas_res) {
    while ($w = $wilayas_res->fetch_assoc()) {
        $available_wilayas[] = $w['wilaya'];
    }
}

$available_types = [];
if ($types_res) {
    while ($t = $types_res->fetch_assoc()) {
        $available_types[] = $t['type'];
    }
}
if (empty($available_types)) {
    $available_types = ['Appartement', 'Villa', 'Duplex', 'Bureau', 'Terrain'];
}

$available_communes = [];
if ($communes_res) {
    while ($c = $communes_res->fetch_assoc()) {
        $available_communes[] = $c['commune'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nos biens — Imolode</title>
  <meta name="description" content="Catalogue complet des biens immobiliers disponibles à la vente et à la location.">
  <link rel="icon" type="image/svg+xml" href="/assets/images/logo.svg">

  <!-- Préconnexions et polices non bloquantes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" media="print" onload="this.media='all'">
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  </noscript>

  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/component.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>

  <!-- En-tête PHP natif -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main>
    <!-- Bannière Hero avec barre de recherche rapide -->
    <section class="page-hero">
      <div class="container">
        <h1 class="section-title">Nos biens</h1>
        <p class="section-lead">Découvrez notre sélection de propriétés disponibles à la vente et à la location.</p>
        
        <div style="margin: 32px auto 0; max-width: 920px; text-align: left;">
          <form class="search-bar search-bar--compact" action="/client/biens.php" method="GET" role="search" aria-label="Recherche rapide">
            <div class="search-field">
              <label for="sb-transaction">Transaction</label>
              <select id="sb-transaction" name="transaction">
                <option value="" <?= empty($transaction) ? 'selected' : '' ?>>Toutes</option>
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
              <label for="sb-location">Localisation / Mot-clé</label>
              <input id="sb-location" name="q" type="text" placeholder="Ville, quartier..." value="<?= e($q) ?>">
            </div>

            <div class="search-field">
              <label for="sb-price">Budget max</label>
              <select id="sb-price" name="maxPrice">
                <option value="">Indifférent</option>
                <option value="5000000" <?= $maxPrice === 5000000.0 ? 'selected' : '' ?>>Jusqu'à 5 000 000 DA</option>
                <option value="10000000" <?= $maxPrice === 10000000.0 ? 'selected' : '' ?>>Jusqu'à 10 000 000 DA</option>
                <option value="20000000" <?= $maxPrice === 20000000.0 ? 'selected' : '' ?>>Jusqu'à 20 000 000 DA</option>
                <option value="40000000" <?= $maxPrice === 40000000.0 ? 'selected' : '' ?>>Jusqu'à 40 000 000 DA</option>
                <option value="80000000" <?= $maxPrice === 80000000.0 ? 'selected' : '' ?>>Jusqu'à 80 000 000 DA</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary" aria-label="Rechercher">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- Section principale : Filtres à gauche, Grille de biens à droite -->
    <section class="section">
      <div class="container">
        <div id="biens-layout" style="display:grid;grid-template-columns:280px minmax(0,1fr);gap:40px;align-items:start">
          
          <!-- Volet latéral de filtres complets -->
          <aside class="filters" id="filters-aside" aria-label="Filtres">
            <div class="filters-head">
              <h2 class="filters-title">Filtres</h2>
              <button type="button" class="filters-close" id="filters-close-btn" aria-label="Fermer les filtres">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
              </button>
            </div>

            <form class="filters-form" method="GET" action="/client/biens.php">
              <fieldset class="filter-group">
                <legend>Recherche rapide</legend>
                <label class="filter-field">
                  <span>Mot-clé ou quartier</span>
                  <input type="text" name="q" placeholder="Ex: vue mer, meublé..." value="<?= e($q) ?>">
                </label>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Transaction</legend>
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
                <select name="type">
                  <option value="">Tous les types</option>
                  <?php foreach ($available_types as $t): ?>
                    <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Localisation</legend>
                <label class="filter-field">
                  <span>Wilaya</span>
                  <select name="wilaya">
                    <option value="">Toutes les wilayas</option>
                    <?php foreach ($available_wilayas as $w): ?>
                      <option value="<?= e($w) ?>" <?= $wilaya === $w ? 'selected' : '' ?>><?= e($w) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>

                <label class="filter-field" style="margin-top:8px">
                  <span>Ville / Commune</span>
                  <select name="city">
                    <option value="">Toutes les villes</option>
                    <?php foreach ($available_communes as $c): ?>
                      <option value="<?= e($c) ?>" <?= $city === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Prix (DA)</legend>
                <div class="filter-range">
                  <input type="number" name="minPrice" min="0" placeholder="Min" value="<?= $minPrice !== null ? $minPrice : '' ?>">
                  <input type="number" name="maxPrice" min="0" placeholder="Max" value="<?= $maxPrice !== null ? $maxPrice : '' ?>">
                </div>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Surface (m²)</legend>
                <div class="filter-range">
                  <input type="number" name="minSurface" min="0" placeholder="Min" value="<?= $minSurface !== null ? $minSurface : '' ?>">
                  <input type="number" name="maxSurface" min="0" placeholder="Max" value="<?= $maxSurface !== null ? $maxSurface : '' ?>">
                </div>
              </fieldset>

              <fieldset class="filter-group">
                <legend>Chambres minimum</legend>
                <select name="bedrooms">
                  <option value="">Toutes</option>
                  <option value="1" <?= $bedrooms === 1 ? 'selected' : '' ?>>1+</option>
                  <option value="2" <?= $bedrooms === 2 ? 'selected' : '' ?>>2+</option>
                  <option value="3" <?= $bedrooms === 3 ? 'selected' : '' ?>>3+</option>
                  <option value="4" <?= $bedrooms === 4 ? 'selected' : '' ?>>4+</option>
                  <option value="5" <?= $bedrooms === 5 ? 'selected' : '' ?>>5+</option>
                </select>
              </fieldset>

              <div class="filters-actions">
                <a href="/client/biens.php" class="btn btn-outline" style="text-align:center;">Réinitialiser</a>
                <button type="submit" class="btn btn-primary">Filtrer</button>
              </div>
            </form>
          </aside>

          <!-- Colonne droite : Compteur, tri et liste des annonces -->
          <div>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
              <p style="margin:0;color:var(--color-muted);font-size:0.95rem">
                <strong><?= $total_count ?></strong> bien<?= $total_count > 1 ? 's' : '' ?> disponible<?= $total_count > 1 ? 's' : '' ?>
              </p>

              <!-- Sélecteur de tri avec formulaire inline -->
              <form method="GET" action="/client/biens.php" style="display:inline-flex;align-items:center;gap:8px;font-size:.88rem">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <input type="hidden" name="transaction" value="<?= e($transaction) ?>">
                <input type="hidden" name="type" value="<?= e($type) ?>">
                <input type="hidden" name="wilaya" value="<?= e($wilaya) ?>">
                <input type="hidden" name="city" value="<?= e($city) ?>">
                <?php if ($minPrice): ?><input type="hidden" name="minPrice" value="<?= $minPrice ?>"><?php endif; ?>
                <?php if ($maxPrice): ?><input type="hidden" name="maxPrice" value="<?= $maxPrice ?>"><?php endif; ?>
                <?php if ($bedrooms): ?><input type="hidden" name="bedrooms" value="<?= $bedrooms ?>"><?php endif; ?>

                <label for="properties-sort" class="text-muted" style="margin:0;">Trier par :</label>
                <select id="properties-sort" name="sort" style="width:auto;padding:6px 12px;" onchange="this.form.submit()">
                  <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récents</option>
                  <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Prix croissant</option>
                  <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Prix décroissant</option>
                  <option value="surface-asc" <?= $sort === 'surface-asc' ? 'selected' : '' ?>>Surface croissante</option>
                  <option value="surface-desc" <?= $sort === 'surface-desc' ? 'selected' : '' ?>>Surface décroissante</option>
                </select>
              </form>
            </div>

            <!-- Grille des annonces immobilières générée directement en PHP -->
            <div class="property-grid">
              <?php if (empty($biens)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border-soft);">
                  <p style="font-size: 1.15rem; font-weight: 500; margin-bottom: 8px;">Aucun bien ne correspond à vos critères de recherche.</p>
                  <p class="text-muted" style="margin-bottom: 24px;">Essayez d'élargir vos critères ou de réinitialiser les filtres.</p>
                  <a href="/client/biens.php" class="btn btn-outline">Réinitialiser les filtres</a>
                </div>
              <?php else: ?>
                <?php foreach ($biens as $bien): ?>
                  <?php render_property_card($bien); ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Bouton flottant pour ouvrir les filtres sur mobile -->
        <button type="button" class="btn btn-primary filters-open-btn" id="filters-open-btn">
          Filtres
        </button>
      </div>
    </section>
  </main>

  <!-- Pied de page PHP natif -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Scripts légers pour l'interaction mobile et layout -->
  <script>
    (function() {
      // Ajustement responsive fluide
      function adjustLayout() {
        var layout = document.getElementById('biens-layout');
        if (layout) {
          layout.style.gridTemplateColumns = window.innerWidth <= 768 ? '1fr' : '280px minmax(0,1fr)';
        }
      }
      adjustLayout();
      window.addEventListener('resize', adjustLayout, { passive: true });

      // Tiroir mobile des filtres
      var aside = document.getElementById('filters-aside');
      var openBtn = document.getElementById('filters-open-btn');
      var closeBtn = document.getElementById('filters-close-btn');

      if (openBtn && aside) {
        openBtn.addEventListener('click', function() { aside.classList.add('is-open'); });
      }
      if (closeBtn && aside) {
        closeBtn.addEventListener('click', function() { aside.classList.remove('is-open'); });
      }
    })();
  </script>
</body>
</html>
