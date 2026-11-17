<?php
/**
 * ============================================================================
 * client/a-propos.php - Page À propos de l'agence Immolode
 * ============================================================================
 * Rendu direct côté serveur en PHP pour un affichage instantané et haute performance.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

$activePage = 'a-propos';

$values = [
    [
        'title' => 'Rigueur & Conformité',
        'desc' => "Chaque dossier est traité avec minutie, de la première visite à la signature de l'acte authentique chez le notaire, dans le strict respect de la réglementation.",
        'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'
    ],
    [
        'title' => 'Transparence totale',
        'desc' => "Prix, état réel du bien, servitudes et démarches : nous partageons toutes les informations avec vous en toute franchise et clarté.",
        'icon' => '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>'
    ],
    [
        'title' => 'Écoute & Disponibilité',
        'desc' => "Comprendre vos besoins réels pour vous orienter vers les opportunités qui ont du sens pour votre foyer ou votre investissement.",
        'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'
    ],
    [
        'title' => 'Sélection de qualité',
        'desc' => "Nous refusons les annonces douteuses et ne sélectionnons que des biens répondant à nos critères d'exigence technique et juridique.",
        'icon' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'
    ]
];

$team = [
    [
        'name' => 'G. Hocine',
        'role' => 'Gérant & Conseiller Principal',
        'image' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=600&q=75',
        'bio' => 'Plus de 12 ans d\'expertise sur le marché immobilier de la région de Béjaïa et Alger.'
    ],
    [
        'name' => 'Karim Benali',
        'role' => 'Responsable des transactions',
        'image' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=600&q=75',
        'bio' => 'Spécialiste de la valorisation de biens résidentiels et du suivi des dossiers notariés.'
    ],
    [
        'name' => 'Amel Dahmani',
        'role' => 'Conseillère Location & Gestion',
        'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=75',
        'bio' => 'Dédiée aux relations locataires-propriétaires, états des lieux et gestion locative.'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>À propos — Imolode</title>
  <meta name="description" content="Découvrez l'histoire, les valeurs et l'équipe de l'agence immobilière agréée Imolode.">
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
        <h1 class="section-title">À propos</h1>
        <p class="section-lead">Une agence indépendante, proche de ses clients et attachée à la rigueur de son accompagnement.</p>
      </div>
    </section>

    <!-- Histoire de l'agence -->
    <section class="section">
      <div class="container">
        <div class="split">
          <div class="split-media">
            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=75" alt="Agence Immolode" loading="lazy">
          </div>
          <div class="split-body">
            <span class="section-eyebrow">Notre histoire</span>
            <h2 class="section-title" style="margin-top:8px">Une agence fondée sur le terrain</h2>
            <p class="text-muted">
              Imolode est née de la conviction qu'une transaction immobilière réussie repose avant tout sur la confiance mutuelle. Depuis nos débuts, nous avons construit une méthode claire et sans détour : visiter chaque bien, écouter attentivement chaque projet de vie, et ne proposer que ce qui correspond réellement aux attentes.
            </p>
            <p class="text-muted">
              Cette exigence nous a permis d'accompagner des centaines de familles et d'investisseurs, sur des projets résidentiels comme professionnels, avec un taux de satisfaction exceptionnel.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Valeurs de l'agence -->
    <section class="section section--soft">
      <div class="container">
        <header class="section-head section-head--center reveal">
          <span class="section-eyebrow">Nos engagements</span>
          <h2 class="section-title">Ce qui nous guide au quotidien</h2>
        </header>

        <div class="reasons-grid">
          <?php foreach ($values as $v): ?>
            <article class="reason">
              <span class="reason-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $v['icon'] ?></svg>
              </span>
              <h3 class="reason-title"><?= e($v['title']) ?></h3>
              <p class="reason-text"><?= e($v['desc']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Notre équipe -->
    <section class="section">
      <div class="container">
        <header class="section-head section-head--center reveal">
          <span class="section-eyebrow">Notre équipe</span>
          <h2 class="section-title">Des professionnels à vos côtés</h2>
          <p class="section-lead">Une équipe resserrée, réactive et à votre entière disposition.</p>
        </header>

        <div class="property-grid">
          <?php foreach ($team as $member): ?>
            <article class="service-card" style="text-align:center;">
              <div class="service-card-media" style="height:240px;">
                <img src="<?= e($member['image']) ?>" alt="<?= e($member['name']) ?>" loading="lazy" style="object-position:top;">
              </div>
              <div class="service-card-body" style="align-items:center;">
                <h3 class="service-card-title" style="margin-bottom:4px;"><?= e($member['name']) ?></h3>
                <p style="color:var(--color-primary);font-size:0.88rem;font-weight:600;margin-bottom:12px;"><?= e($member['role']) ?></p>
                <p class="service-card-text"><?= e($member['bio']) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Bannière CTA -->
    <section class="cta-section">
      <div class="cta-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=75" alt="" loading="lazy">
      </div>
      <div class="container cta-inner">
        <h2 class="cta-title">Discutons de votre projet</h2>
        <p class="cta-subtitle">Passez nous voir à l'agence ou contactez-nous dès aujourd'hui.</p>
        <div class="cta-actions">
          <a href="/client/contact.php" class="btn btn-primary btn-lg">Prendre contact</a>
          <a href="/client/biens.php" class="btn btn-outline-light btn-lg">Explorer nos biens</a>
        </div>
      </div>
    </section>
  </main>

  <!-- Pied de page PHP natif -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
