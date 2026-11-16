<?php
// client/a-propos.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>À propos — Imolode</title>
  <link rel="icon" type="image/svg+xml" href="/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/component.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
  <link rel="modulepreload" href="/client/includes/scripts.js">
</head>
<body>
  <div id="header-slot"></div>

  <main>
    <section class="page-hero">
      <div class="container">
        <h1 class="section-title">À propos</h1>
        <p class="section-lead">Une agence indépendante, proche de ses clients et attachée à la qualité de son accompagnement.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="split">
          <div class="split-media">
            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80" alt="" loading="lazy">
          </div>
          <div class="split-body">
            <span class="section-eyebrow">Notre histoire</span>
            <h2 class="section-title" style="margin-top:8px">Une agence fondée sur le terrain</h2>
            <p class="text-muted">
              Imolode est née de la conviction qu'une transaction immobilière réussie repose avant
              tout sur la confiance. Depuis nos débuts, nous avons construit une méthode simple :
              visiter chaque bien, écouter chaque projet, et ne proposer que ce qui correspond
              réellement aux attentes.
            </p>
            <p class="text-muted">
              Cette exigence nous a permis d'accompagner des centaines de familles et
              d'investisseurs, sur des projets résidentiels comme professionnels.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="section section--soft">
      <div class="container">
        <header class="section-head section-head--center">
          <span class="section-eyebrow">Nos valeurs</span>
          <h2 class="section-title">Ce qui nous guide au quotidien</h2>
        </header>
        <div id="values-grid" class="reasons-grid"></div>
      </div>
    </section>

    <!-- Section Carte de visite numérique professionnelle -->
    <section class="section">
      <div class="container">
        <header class="section-head section-head--center reveal">
          <span class="section-eyebrow">Contact direct & Réseaux</span>
          <h2 class="section-title">Notre carte de visite numérique</h2>
          <p class="section-lead">Scannez le QR Code officiel ou enregistrez instantanément les coordonnées de l'agence.</p>
        </header>

        <div id="business-card-slot" style="margin: 0 auto 56px;"></div>
      </div>
    </section>

    <section class="section section--soft">
      <div class="container">
        <header class="section-head section-head--center">
          <span class="section-eyebrow">Notre équipe</span>
          <h2 class="section-title">Des professionnels à vos côtés</h2>
          <p class="section-lead">Une équipe resserrée, réactive et à votre disposition.</p>
        </header>
        <div id="team-grid" class="property-grid"></div>
      </div>
    </section>

    <div id="cta-slot"></div>
  </main>

  <div id="footer-slot"></div>

  <script type="module">
    import { initPage } from '/client/includes/scripts.js';
    import { TeamCard } from '/assets/components/team-card.js';
    import { BusinessCard } from '/assets/components/business-card.js';
    import { CTASection } from '/assets/components/cta-section.js';
    import { TEAM } from '/client/data/team.js';
    import { icon } from '/client/includes/helpers.js';

    await initPage({ page: 'a-propos', title: 'À propos' });

    // Injection de la carte de visite numérique interactive
    const cardSlot = document.getElementById('business-card-slot');
    if (cardSlot) {
      cardSlot.innerHTML = BusinessCard();
    }

    const values = [
      { icon: 'shield', title: 'Rigueur', text: 'Chaque dossier est traité avec minutie, de la première visite à la signature de l\'acte authentique.' },
      { icon: 'compass', title: 'Transparence', text: 'Prix, état du bien, démarches : nous partageons toutes les informations en toute franchise.' },
      { icon: 'users', title: 'Écoute', text: 'Comprendre vos besoins réels pour vous orienter vers les opportunités qui ont du sens.' },
      { icon: 'star', title: 'Qualité', text: 'Nous sélectionnons des biens qui répondent à nos critères d\'exigence technique et esthétique.' }
    ];

    document.getElementById('values-grid').innerHTML = values.map((v) => `
      <article class="reason">
        <span class="reason-icon">${icon(v.icon, { size: 22 })}</span>
        <h3 class="reason-title">${v.title}</h3>
        <p class="reason-text">${v.text}</p>
      </article>`).join('');

    document.getElementById('team-grid').innerHTML = TEAM.map(TeamCard).join('');
    document.getElementById('cta-slot').innerHTML = CTASection();
  </script>
</body>
</html>
