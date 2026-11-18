<?php
/**
 * ============================================================================
 * client/index.php - Page d'accueil Immolode (Haute performance & rendu direct)
 * ============================================================================
 * Tout le contenu est généré directement côté serveur en PHP et connecté à MySQL
 * pour un chargement instantané sans écran blanc ni dépendance bloquante.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/includes/card.php';

$activePage = 'accueil';

// 1. Récupération des paramètres de l'agence depuis MySQL (CMS)
$settings = [];
$resParams = $conn->query("SELECT `cle`, `valeur` FROM `parametres`");
if ($resParams) {
    while ($r = $resParams->fetch_assoc()) {
        $settings[$r['cle']] = $r['valeur'];
    }
}
$agency_tel = $settings['agence_telephone'] ?? $settings['phone'] ?? '+213 555 12 34 56';
$agency_email = $settings['agence_email'] ?? $settings['email'] ?? 'contact@immolode.dz';
$agency_address = $settings['agence_adresse'] ?? $settings['address'] ?? '12 rue Didouche Mourad, Béjaïa 06000';

$hero_title = !empty($settings['hero_title']) ? $settings['hero_title'] : 'Votre prochain bien commence ici';
$hero_subtitle = !empty($settings['hero_subtitle']) ? $settings['hero_subtitle'] : 'Découvrez des propriétés sélectionnées avec soin pour vos projets d\'achat et de location en Algérie.';
$hero_image = !empty($settings['hero_image']) ? $settings['hero_image'] : 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=75';

$about_title = !empty($settings['about_title']) ? $settings['about_title'] : 'Une agence proche de ses clients';
$about_text = !empty($settings['about_text']) ? $settings['about_text'] : 'Imolode accompagne depuis plusieurs années les familles, les investisseurs et les entreprises dans leurs démarches immobilières. Notre rigueur sur le terrain et la sélection scrupuleuse des biens garantissent des transactions sereines et transparentes.';
$about_image = !empty($settings['about_image']) ? $settings['about_image'] : 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=75';

$service_achat_titre = !empty($settings['service_achat_titre']) ? $settings['service_achat_titre'] : 'Acquisition sur mesure';
$service_achat_desc = !empty($settings['service_achat_desc']) ? $settings['service_achat_desc'] : 'Trouvez la propriété qui correspond à vos critères et bénéficiez d\'un accompagnement juridique et notarié complet.';
$service_location_titre = !empty($settings['service_location_titre']) ? $settings['service_location_titre'] : 'Location premium';
$service_location_desc = !empty($settings['service_location_desc']) ? $settings['service_location_desc'] : 'Accédez à un catalogue varié de logements et locaux professionnels avec rédaction sécurisée du bail et état des lieux.';
$service_estimation_titre = !empty($settings['service_estimation_titre']) ? $settings['service_estimation_titre'] : 'Estimation de bien';
$service_estimation_desc = !empty($settings['service_estimation_desc']) ? $settings['service_estimation_desc'] : 'Obtenez un avis de valeur professionnel et objectif basé sur les transactions effectives de votre secteur géographique.';

// 2. Requête MySQL préparée pour récupérer les annonces "À la une"
$stmt = $conn->prepare("SELECT * FROM `properties` WHERE `a_la_une` = 1 AND `statut` = 'Disponible' ORDER BY `date_creation` DESC LIMIT 6");
$stmt->execute();
$featured_biens = $stmt->get_result();

// 3. Récupération des types de biens disponibles pour la barre de recherche
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
  <title>Immolode — Immobilier d'exception en Algérie</title>
  <meta name="description" content="Découvrez une sélection exclusive de villas, appartements et locaux commerciaux à Béjaïa, Alger et à travers l'Algérie.">
  <link rel="icon" type="image/svg+xml" href="/assets/images/logo.svg">

  <!-- Optimisation du chargement des polices : préconnexion et chargement asynchrone -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" media="print" onload="this.media='all'">
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  </noscript>

  <!-- Feuilles de styles officielles -->
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/component.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>

  <!-- En-tête de navigation PHP natif (Direct & Instantané) -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main>
    <!-- Section Hero avec image optimisée et moteur de recherche rapide -->
    <section class="hero">
      <div class="hero-media" aria-hidden="true">
        <img src="<?= e($hero_image) ?>" alt="" fetchpriority="high">
        <span class="hero-overlay"></span>
      </div>

      <div class="container hero-inner">
        <div class="hero-content">
          <p class="hero-eyebrow">Agence Immobilière Agréée</p>
          <h1 class="hero-title"><?= e($hero_title) ?></h1>
          <p class="hero-subtitle"><?= e($hero_subtitle) ?></p>
        </div>

        <!-- Moteur de recherche rapide direct en PHP (Amélioré visuellement Point 5) -->
        <div class="hero-search">
          <form class="search-bar" action="/client/biens.php" method="GET" role="search" aria-label="Rechercher un bien" style="background:#FFF; box-shadow:0 8px 30px rgba(0,0,0,0.18); border: 1px solid rgba(197,168,128,0.4); border-radius: var(--radius-md);">
            <div class="search-field">
              <label for="sb-trans" style="font-weight:700; color:var(--color-accent, #c5a880);">Transaction</label>
              <select id="sb-trans" name="transaction" style="cursor:pointer; font-weight:500;">
                <option value="Acheter">Acheter</option>
                <option value="Louer">Louer</option>
              </select>
            </div>

            <div class="search-field">
              <label for="sb-type" style="font-weight:700; color:var(--color-accent, #c5a880);">Type de bien</label>
              <select id="sb-type" name="type" style="cursor:pointer; font-weight:500;">
                <option value="">Tous les types</option>
                <?php foreach ($search_types as $st): ?>
                  <option value="<?= e($st) ?>"><?= e($st) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="search-field">
              <label for="sb-loc" style="font-weight:700; color:var(--color-accent, #c5a880);">Localisation</label>
              <input id="sb-loc" name="q" type="text" placeholder="Béjaïa, Alger, Oran..." style="cursor:text;">
            </div>

            <div class="search-field">
              <label for="sb-price" style="font-weight:700; color:var(--color-accent, #c5a880);">Budget max</label>
              <select id="sb-price" name="maxPrice" style="cursor:pointer; font-weight:500;">
                <option value="">Indifférent</option>
                <option value="5000000">Jusqu'à 5 000 000 DA</option>
                <option value="10000000">Jusqu'à 10 000 000 DA</option>
                <option value="20000000">Jusqu'à 20 000 000 DA</option>
                <option value="40000000">Jusqu'à 40 000 000 DA</option>
                <option value="80000000">Jusqu'à 80 000 000 DA</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary search-submit" aria-label="Lancer la recherche" style="background:var(--color-accent, #c5a880); color:#FFF; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(197,168,128,0.45); border-radius:var(--radius-sm); border:none; transition:transform 0.2s, box-shadow 0.2s;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
          </form>
        </div>

        <div class="hero-meta">
          <a href="/client/biens.php" class="hero-link">
            <span>Parcourir tous les biens disponibles</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
        </div>
      </div>
    </section>

    <!-- Section Biens À la une (Requête MySQL directe) -->
    <section class="section">
      <div class="container">
        <div class="section-head reveal">
          <div>
            <span class="section-eyebrow">Sélection exclusive</span>
            <h2 class="section-title">Biens à la une</h2>
          </div>
          <a class="section-link" href="/client/biens.php">
            <span>Voir tout le catalogue</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
        </div>

        <div class="properties-grid">
          <?php 
          if ($featured_biens && $featured_biens->num_rows > 0):
              while ($bien = $featured_biens->fetch_assoc()):
                  render_property_card($bien);
              endwhile;
          else:
          ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--color-muted);">
              <p>Aucun bien n'est actuellement mis en avant.</p>
              <a href="/client/biens.php" class="btn btn-outline" style="margin-top: 10px;">Explorer nos annonces</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Section Raccourci vers les Fiches client officielles -->
    <section class="section section--soft" style="background: linear-gradient(135deg, rgba(197, 168, 128, 0.08) 0%, rgba(255, 255, 255, 0.9) 100%); border-top: 1px solid var(--color-border-soft); border-bottom: 1px solid var(--color-border-soft);">
      <div class="container">
        <div style="max-width: 820px; margin: 0 auto; text-align: center;" class="reveal">
          <span class="section-eyebrow" style="color: var(--color-accent);">Démarche Officielle & Sécurisée</span>
          <h2 class="section-title" style="margin-top: 6px;">Constituez votre Fiche Client en ligne</h2>
          <p class="text-muted" style="margin: 12px 0 24px; font-size: 1.02rem; line-height: 1.6;">
            Gagnez du temps pour vos visites ou votre mise en mandat : transmettez votre pièce d'identité en toute sécurité et apposez votre signature électronique officielle directement depuis votre smartphone ou ordinateur.
          </p>
          <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
            <a href="/client/fiche-client.php" class="btn btn-primary" style="padding: 13px 28px; box-shadow: 0 4px 15px rgba(0,0,0,0.12);">
              <span>Remplir ma Fiche Client</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
            <a href="/client/estimation.php" class="btn btn-outline" style="padding: 13px 24px;">
              <span>Demander une estimation</span>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Nos Services & Accompagnement (Dynamique CMS Point 10) -->
    <section class="section">
      <div class="container">
        <div class="section-head reveal">
          <div>
            <span class="section-eyebrow">Notre savoir-faire</span>
            <h2 class="section-title">Un accompagnement sur mesure</h2>
          </div>
          <a class="section-link" href="/client/services.php">
            <span>Découvrir tous nos services</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
        </div>

        <div class="services-grid">
          <!-- Service 1 : Achat -->
          <article class="service-card">
            <div class="service-card-media">
              <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=75" alt="Service Achat" loading="lazy">
            </div>
            <div class="service-card-body">
              <span class="service-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              </span>
              <h3 class="service-card-title"><?= e($service_achat_titre) ?></h3>
              <p class="service-card-text"><?= e($service_achat_desc) ?></p>
              <a href="/client/biens.php?transaction=Acheter" class="service-card-link">
                <span>Découvrir les biens en vente</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </a>
            </div>
          </article>

          <!-- Service 2 : Location -->
          <article class="service-card">
            <div class="service-card-media">
              <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=600&q=75" alt="Service Location" loading="lazy">
            </div>
            <div class="service-card-body">
              <span class="service-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 5 4 4"/><path d="M13 7 8.7 11.3a2 2 0 0 0-.58 1.23l-.18 3.53a1 1 0 0 0 1.05 1.05l3.53-.18a2 2 0 0 0 1.23-.58L18 12"/><circle cx="7.5" cy="16.5" r="2.5"/></svg>
              </span>
              <h3 class="service-card-title"><?= e($service_location_titre) ?></h3>
              <p class="service-card-text"><?= e($service_location_desc) ?></p>
              <a href="/client/biens.php?transaction=Louer" class="service-card-link">
                <span>Découvrir les biens à louer</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </a>
            </div>
          </article>

          <!-- Service 3 : Estimation -->
          <article class="service-card">
            <div class="service-card-media">
              <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=600&q=75" alt="Service Estimation" loading="lazy">
            </div>
            <div class="service-card-body">
              <span class="service-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
              </span>
              <h3 class="service-card-title"><?= e($service_estimation_titre) ?></h3>
              <p class="service-card-text"><?= e($service_estimation_desc) ?></p>
              <a href="/client/estimation.php" class="service-card-link">
                <span>Estimer mon bien gratuitement</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Section À propos avec statistiques (Dynamique CMS Point 10) -->
    <section class="section section--soft">
      <div class="container">
        <div class="split">
          <div class="split-media reveal">
            <img src="<?= e($about_image) ?>" alt="Agence Immolode" loading="lazy">
          </div>
          <div class="split-body reveal">
            <span class="section-eyebrow">À propos</span>
            <h2 class="section-title" style="margin-top:8px"><?= e($about_title) ?></h2>
            <p class="text-muted">
              <?= nl2br(e($about_text)) ?>
            </p>
            
            <div class="stats-grid" style="margin:24px 0 32px">
              <div class="stat"><p class="stat-value">150+</p><p class="stat-label">Biens vendus</p></div>
              <div class="stat"><p class="stat-value">98%</p><p class="stat-label">Clients satisfaits</p></div>
              <div class="stat"><p class="stat-value">12 ans</p><p class="stat-label">D'expérience</p></div>
              <div class="stat"><p class="stat-value">24h</p><p class="stat-label">Délai de réponse</p></div>
            </div>

            <a class="btn btn-outline" href="/client/a-propos.php">En savoir plus sur l'agence</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Bannière d'appel à l'action (CTA) -->
    <section class="cta-section">
      <div class="cta-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=75" alt="" loading="lazy">
      </div>
      <div class="container cta-inner">
        <div class="cta-content reveal">
          <span class="cta-eyebrow">Passez à l'action</span>
          <h2 class="cta-title">Vous souhaitez vendre ou louer un bien d'exception ?</h2>
          <p class="cta-text">Faites confiance à des professionnels de terrain. Nous évaluons gratuitement votre bien au juste prix du marché.</p>
          <div class="cta-actions">
            <a class="btn btn-primary" href="/client/estimation.php">Demander une estimation</a>
            <a class="btn btn-outline-light" href="/client/contact.php">Contacter un conseiller</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Pied de page PHP natif -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
