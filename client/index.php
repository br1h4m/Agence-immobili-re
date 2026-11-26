<?php
/**
 * client/index.php - Page d'accueil Imolode (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/card.php';

$activePage = 'accueil';
$b = BASE_URL;

// 1. Paramètres de l'agence depuis MySQL
$settings = load_site_settings($conn);
$agency_tel     = $settings['agence_telephone'] ?? $settings['phone'] ?? '+213 555 12 34 56';
$agency_email   = $settings['agence_email'] ?? $settings['email'] ?? 'contact@immolode.dz';
$agency_address = $settings['agence_adresse'] ?? $settings['address'] ?? '12 rue Didouche Mourad, Béjaïa 06000';

$hero_image    = !empty($settings['cms_hero_image']) ? asset_url($settings['cms_hero_image']) : (!empty($settings['hero_image']) ? asset_url($settings['hero_image']) : 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=75');
$hero_title    = !empty($settings['cms_hero_title']) ? $settings['cms_hero_title'] : (!empty($settings['hero_title']) ? $settings['hero_title'] : 'Votre prochain bien commence ici');
$hero_subtitle = !empty($settings['cms_hero_subtitle']) ? $settings['cms_hero_subtitle'] : (!empty($settings['hero_subtitle']) ? $settings['hero_subtitle'] : 'Découvrez des propriétés sélectionnées avec soin pour vos projets d\'achat et de location en Algérie.');

// 2. Annonces "À la une"
$stmt = $conn->prepare("SELECT * FROM `properties` WHERE `a_la_une` = 1 AND `statut` = 'Disponible' ORDER BY `date_creation` DESC LIMIT 6");
$stmt->execute();
$featured_biens = $stmt->get_result();

// 3. Types de biens disponibles pour la barre de recherche
$types_res = $conn->query("SELECT DISTINCT `type` FROM `properties` WHERE `type` IS NOT NULL AND `type` != '' ORDER BY `type` ASC");
$search_types = [];
if ($types_res) {
    while ($t = $types_res->fetch_assoc()) {
        $search_types[] = $t['type'];
    }
}
if (empty($search_types)) {
    $search_types = ['Appartement', 'Villa', 'Duplex', 'Bureau', 'Terrain'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Imolode — Agence Immobilière Agréée</title>
  <meta name="description" content="Découvrez une sélection exclusive de biens immobiliers à la vente et à la location en Algérie avec l'agence Imolode.">
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
    <!-- Section Hero avec moteur de recherche rapide -->
    <section class="hero">
      <div class="hero-media" aria-hidden="true">
        <img src="<?= e($hero_image) ?>" alt="Imolode Immobilier" fetchpriority="high">
        <span class="hero-overlay"></span>
      </div>

      <div class="container hero-inner">
        <div class="hero-content">
          <p class="hero-eyebrow">Agence Immobilière Agréée</p>
          <h1 class="hero-title"><?= e($hero_title) ?></h1>
          <p class="hero-subtitle"><?= e($hero_subtitle) ?></p>
        </div>

        <div class="hero-search">
          <form class="search-bar" action="<?= $b ?>/client/biens.php" method="GET" role="search" aria-label="Rechercher un bien">
            <div class="search-field">
              <label for="sb-trans">Transaction</label>
              <select id="sb-trans" name="transaction">
                <option value="Acheter">Acheter</option>
                <option value="Louer">Louer</option>
              </select>
            </div>

            <div class="search-field">
              <label for="sb-type">Type de bien</label>
              <select id="sb-type" name="type">
                <option value="">Tous les types</option>
                <?php foreach ($search_types as $st): ?>
                  <option value="<?= e($st) ?>"><?= e($st) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="search-field">
              <label for="sb-loc">Localisation</label>
              <input id="sb-loc" name="q" type="text" placeholder="Béjaïa, Alger, Oran...">
            </div>

            <div class="search-field">
              <label for="sb-price">Budget max</label>
              <select id="sb-price" name="maxPrice">
                <option value="">Indifférent</option>
                <option value="5000000">Jusqu'à 5 000 000 DA</option>
                <option value="10000000">Jusqu'à 10 000 000 DA</option>
                <option value="20000000">Jusqu'à 20 000 000 DA</option>
                <option value="40000000">Jusqu'à 40 000 000 DA</option>
                <option value="80000000">Jusqu'à 80 000 000 DA</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary" aria-label="Lancer la recherche">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
          </form>
        </div>

        <div class="hero-meta">
          <a href="<?= $b ?>/client/biens.php" class="hero-link">
            <span>Parcourir tous les biens disponibles</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
        </div>
      </div>
    </section>

    <!-- Biens à la une -->
    <section class="section">
      <div class="container">
        <header class="section-head reveal">
          <span class="section-eyebrow">Sélection</span>
          <h2 class="section-title">Biens à la une</h2>
          <p class="section-lead">Une sélection de propriétés récemment mises en ligne par nos conseillers.</p>
        </header>

        <div class="property-grid">
          <?php if ($featured_biens->num_rows === 0): ?>
            <p style="grid-column: 1 / -1; text-align: center; color: var(--color-muted); padding: 40px 0;">
              Aucun bien à la une disponible pour le moment.
            </p>
          <?php else: ?>
            <?php while ($bien = $featured_biens->fetch_assoc()): ?>
              <?php render_property_card($bien); ?>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>

        <div style="text-align:center;margin-top:40px">
          <a class="btn btn-outline" href="<?= $b ?>/client/biens.php">Voir tous les biens</a>
        </div>
      </div>
    </section>

    <!-- Pourquoi nous choisir -->
    <section class="section section--soft">
      <div class="container">
        <header class="section-head section-head--center reveal">
          <span class="section-eyebrow">Notre approche</span>
          <h2 class="section-title">Pourquoi nous choisir</h2>
        </header>

        <div class="reasons-grid reveal">
          <article class="reason">
            <span class="reason-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
            </span>
            <h3 class="reason-title">Expertise locale</h3>
            <p class="reason-text">Une connaissance fine du marché, des prix réels et des quartiers sur lesquels nous opérons.</p>
          </article>

          <article class="reason">
            <span class="reason-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </span>
            <h3 class="reason-title">Biens vérifiés</h3>
            <p class="reason-text">Chaque propriété est rigoureusement visitée, évaluée et contrôlée avant sa publication.</p>
          </article>

          <article class="reason">
            <span class="reason-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <h3 class="reason-title">Accompagnement personnalisé</h3>
            <p class="reason-text">Un conseiller dédié vous accompagne du premier contact jusqu'à la signature finale chez le notaire.</p>
          </article>

          <article class="reason">
            <span class="reason-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <h3 class="reason-title">Cadre légal sécurisé</h3>
            <p class="reason-text">Agence agréée par l'État, respectant scrupuleusement les barèmes réglementaires (Art. 34).</p>
          </article>
        </div>
      </div>
    </section>

    <!-- Services -->
    <section class="section">
      <div class="container">
        <header class="section-head reveal">
          <span class="section-eyebrow">Services</span>
          <h2 class="section-title">Ce que nous faisons</h2>
          <p class="section-lead">Un accompagnement complet pour vos projets d'achat, de vente, de location et d'estimation.</p>
        </header>

        <div class="property-grid">
          <article class="service-card">
            <div class="service-card-media">
              <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=75" alt="Service Achat" loading="lazy">
            </div>
            <div class="service-card-body">
              <span class="service-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              </span>
              <h3 class="service-card-title">Achat</h3>
              <p class="service-card-text">Trouvez la propriété qui correspond à vos critères et bénéficiez d'un accompagnement juridique et notarié complet.</p>
              <a href="<?= $b ?>/client/biens.php?transaction=Acheter" class="service-card-link">
                <span>Découvrir les biens en vente</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </a>
            </div>
          </article>

          <article class="service-card">
            <div class="service-card-media">
              <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=600&q=75" alt="Service Location" loading="lazy">
            </div>
            <div class="service-card-body">
              <span class="service-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 5 4 4"/><path d="M13 7 8.7 11.3a2 2 0 0 0-.58 1.23l-.18 3.53a1 1 0 0 0 1.05 1.05l3.53-.18a2 2 0 0 0 1.23-.58L18 12"/><circle cx="7.5" cy="16.5" r="2.5"/></svg>
              </span>
              <h3 class="service-card-title">Location</h3>
              <p class="service-card-text">Accédez à un catalogue varié de logements et locaux professionnels avec rédaction sécurisée du bail et état des lieux.</p>
              <a href="<?= $b ?>/client/biens.php?transaction=Louer" class="service-card-link">
                <span>Découvrir les biens à louer</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </a>
            </div>
          </article>

          <article class="service-card">
            <div class="service-card-media">
              <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=600&q=75" alt="Service Estimation" loading="lazy">
            </div>
            <div class="service-card-body">
              <span class="service-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
              </span>
              <h3 class="service-card-title">Estimation de bien</h3>
              <p class="service-card-text">Obtenez un avis de valeur professionnel et objectif basé sur les transactions effectives de votre secteur géographique.</p>
              <a href="<?= $b ?>/client/estimation.php" class="service-card-link">
                <span>Estimer mon bien gratuitement</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </a>
            </div>
          </article>
        </div>

        <div style="text-align:center;margin-top:40px">
          <a class="btn btn-outline" href="<?= $b ?>/client/services.php">Découvrir tous nos services</a>
        </div>
      </div>
    </section>

    <!-- À propos -->
    <section class="section section--soft">
      <div class="container">
        <div class="split">
          <div class="split-media reveal">
            <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=75" alt="Agence Imolode" loading="lazy">
          </div>
          <div class="split-body reveal">
            <span class="section-eyebrow">À propos</span>
            <h2 class="section-title" style="margin-top:8px">Une agence proche de ses clients</h2>
            <p class="text-muted">
              Imolode accompagne depuis plusieurs années les familles, les investisseurs et les entreprises dans leurs démarches immobilières. Notre rigueur sur le terrain et la sélection scrupuleuse des biens garantissent des transactions sereines et transparentes.
            </p>

            <div class="stats-grid" style="margin:24px 0 32px">
              <div class="stat"><p class="stat-value">150+</p><p class="stat-label">Biens vendus</p></div>
              <div class="stat"><p class="stat-value">98%</p><p class="stat-label">Clients satisfaits</p></div>
              <div class="stat"><p class="stat-value">12 ans</p><p class="stat-label">D'expérience</p></div>
              <div class="stat"><p class="stat-value">24h</p><p class="stat-label">Délai de réponse</p></div>
            </div>

            <a class="btn btn-outline" href="<?= $b ?>/client/a-propos.php">En savoir plus sur l'agence</a>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
      <div class="cta-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=75" alt="" loading="lazy">
      </div>
      <div class="container cta-inner">
        <h2 class="cta-title">Vous avez un projet immobilier ?</h2>
        <p class="cta-subtitle">Notre équipe est à votre disposition pour vous conseiller et vous accompagner à chaque étape.</p>
        <div class="cta-actions">
          <a href="<?= $b ?>/client/contact.php" class="btn btn-primary btn-lg">
            <span>Nous contacter</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
          <a href="<?= $b ?>/client/estimation.php" class="btn btn-outline-light btn-lg">Estimer mon bien</a>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
