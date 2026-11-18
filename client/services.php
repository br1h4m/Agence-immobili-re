<?php
/**
 * ============================================================================
 * client/services.php - Services et honoraires réglementaires
 * ============================================================================
 * Rendu direct côté serveur en PHP pour un affichage ultra-rapide.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

$activePage = 'services';

// Récupération des paramètres CMS dynamiques
$settings = [];
$res_p = $conn->query("SELECT `cle`, `valeur` FROM `parametres`");
if ($res_p) {
    while ($row = $res_p->fetch_assoc()) {
        $settings[$row['cle']] = $row['valeur'];
    }
}

$service_achat_titre = !empty($settings['service_achat_titre']) ? $settings['service_achat_titre'] : 'Achat';
$service_achat_desc = !empty($settings['service_achat_desc']) ? $settings['service_achat_desc'] : "Trouvez la propriété qui correspond à vos critères et vos exigences. Nous sélectionnons des biens vérifiés et vous accompagnons de la première visite jusqu'à la signature de l'acte authentique chez le notaire.";

$service_location_titre = !empty($settings['service_location_titre']) ? $settings['service_location_titre'] : 'Location';
$service_location_desc = !empty($settings['service_location_desc']) ? $settings['service_location_desc'] : "Accédez à un catalogue varié de logements et locaux professionnels. Nous sécurisons la rédaction du bail et l'état des lieux pour une relation locative sereine.";

$service_estimation_titre = !empty($settings['service_estimation_titre']) ? $settings['service_estimation_titre'] : 'Estimation de bien';
$service_estimation_desc = !empty($settings['service_estimation_desc']) ? $settings['service_estimation_desc'] : "Obtenez un avis de valeur professionnel et objectif basé sur les transactions réelles de votre secteur géographique et les spécificités de votre bien.";

$services = [
    [
        'title' => $service_achat_titre,
        'desc' => $service_achat_desc,
        'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=75',
        'icon' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'link' => '/client/biens.php?transaction=Acheter',
        'btn' => 'Découvrir les biens en vente'
    ],
    [
        'title' => 'Vente',
        'desc' => "Vendez votre bien au juste prix du marché. Nous assurons la valorisation photographique, la diffusion ciblée, le filtrage des acquéreurs et le suivi notarié rigoureux.",
        'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=600&q=75',
        'icon' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'link' => '/client/contact.php',
        'btn' => 'Confier mon bien à la vente'
    ],
    [
        'title' => $service_location_titre,
        'desc' => $service_location_desc,
        'image' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=600&q=75',
        'icon' => '<path d="m15 5 4 4"/><path d="M13 7 8.7 11.3a2 2 0 0 0-.58 1.23l-.18 3.53a1 1 0 0 0 1.05 1.05l3.53-.18a2 2 0 0 0 1.23-.58L18 12"/><circle cx="7.5" cy="16.5" r="2.5"/>',
        'link' => '/client/biens.php?transaction=Louer',
        'btn' => 'Découvrir les biens à louer'
    ],
    [
        'title' => 'Gestion immobilière',
        'desc' => "Déléguez l'administration de votre patrimoine : encaissement des loyers, gestion des charges, maintenance technique et veille juridique permanente sans contrainte.",
        'image' => 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?auto=format&fit=crop&w=600&q=75',
        'icon' => '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/>',
        'link' => '/client/contact.php',
        'btn' => 'En savoir plus'
    ],
    [
        'title' => $service_estimation_titre,
        'desc' => $service_estimation_desc,
        'image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=600&q=75',
        'icon' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'link' => '/client/estimation.php',
        'btn' => 'Estimer mon bien gratuitement'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Services — Imolode</title>
  <meta name="description" content="Découvrez tous les services de l'agence immobilière agréée Imolode : vente, achat, location, gestion et estimation.">
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
</head>
<body>

  <!-- En-tête PHP natif -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main>
    <section class="page-hero">
      <div class="container">
        <h1 class="section-title">Nos services</h1>
        <p class="section-lead">Un accompagnement complet et sécurisé pour tous vos projets immobiliers.</p>
      </div>
    </section>

    <!-- Grille des 5 services officiels en rendu direct -->
    <section class="section">
      <div class="container">
        <div class="property-grid">
          <?php foreach ($services as $s): ?>
            <article class="service-card">
              <div class="service-card-media">
                <img src="<?= e($s['image']) ?>" alt="<?= e($s['title']) ?>" loading="lazy">
              </div>
              <div class="service-card-body">
                <span class="service-card-icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $s['icon'] ?></svg>
                </span>
                <h3 class="service-card-title"><?= e($s['title']) ?></h3>
                <p class="service-card-text"><?= e($s['desc']) ?></p>
                <a href="<?= e($s['link']) ?>" class="service-card-link">
                  <span><?= e($s['btn']) ?></span>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Section Honoraires réglementaires (Article 34) -->
    <section class="section section--soft" id="honoraires">
      <div class="container">
        <header class="section-head section-head--center reveal">
          <span class="section-eyebrow">Agence Immobilière Agréée Imolode</span>
          <h2 class="section-title">Barème des honoraires réglementaires</h2>
          <p class="section-lead">Art. 34 — (Décret réglementant la profession de l'Agence immobilière)</p>
        </header>

        <div style="background:#FFFFFF;border:1px solid #E5E3DE;border-radius:var(--radius-md);overflow:hidden;max-width:880px;margin:0 auto;box-shadow:var(--shadow-xs);">
          <div style="padding:22px 28px;background-color:#FAF9F6;border-bottom:1px solid #E5E3DE;">
            <p style="margin:0;font-size:0.95rem;line-height:1.6;color:#4A4844;">
              Les honoraires de négociation de l'agent immobilier sont fixés d'un commun accord avec le mandant dans la limite des plafonds réglementaires suivants :
            </p>
          </div>

          <div style="padding:28px;">
            <h3 style="font-size:1.15rem;margin-bottom:16px;color:#1C1C1A;">1. Vente d'immeubles ou de fonds de commerce :</h3>
            <ul style="list-style:none;padding:0;margin:0 0 28px;display:flex;flex-direction:column;gap:10px;">
              <li style="padding:10px 14px;background:#F6F5F2;border-radius:6px;font-size:0.92rem;">
                <strong>3 %</strong> pour les montants inférieurs ou égaux à <strong>1 000 000 DA</strong>
              </li>
              <li style="padding:10px 14px;background:#F6F5F2;border-radius:6px;font-size:0.92rem;">
                <strong>2 %</strong> pour la tranche de <strong>1 000 001 DA à 5 000 000 DA</strong>
              </li>
              <li style="padding:10px 14px;background:#F6F5F2;border-radius:6px;font-size:0.92rem;">
                <strong>1 %</strong> pour les montants supérieurs à <strong>5 000 000 DA</strong>
              </li>
            </ul>

            <h3 style="font-size:1.15rem;margin-bottom:16px;color:#1C1C1A;">2. Location d'immeubles :</h3>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
              <li style="padding:10px 14px;background:#F6F5F2;border-radius:6px;font-size:0.92rem;">
                <strong>Un mois de loyer</strong> pour une location d'une durée inférieure ou égale à <strong>deux (2) années</strong>
              </li>
              <li style="padding:10px 14px;background:#F6F5F2;border-radius:6px;font-size:0.92rem;">
                <strong>Un mois et demi de loyer</strong> pour une location d'une durée de <strong>plus de deux (2) années</strong>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Bannière CTA -->
    <section class="cta-section">
      <div class="cta-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=75" alt="" loading="lazy">
      </div>
      <div class="container cta-inner">
        <h2 class="cta-title">Une question sur nos prestations ?</h2>
        <p class="cta-subtitle">Nos conseillers sont disponibles pour échanger directement et vous accompagner.</p>
        <div class="cta-actions">
          <a href="/client/contact.php" class="btn btn-primary btn-lg">Nous contacter</a>
          <a href="/client/estimation.php" class="btn btn-outline-light btn-lg">Estimer mon bien</a>
        </div>
      </div>
    </section>
  </main>

  <!-- Pied de page PHP natif -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
