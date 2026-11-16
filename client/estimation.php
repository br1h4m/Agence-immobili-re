<?php
// client/estimation.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Estimer mon bien — Imolode</title>
  <link rel="icon" type="image/svg+xml" href="/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/component.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
  <link rel="modulepreload" href="/client/includes/scripts.js">
  <link rel="modulepreload" href="/assets/js/forms.js">
</head>
<body>
  <div id="header-slot"></div>

  <main>
    <section class="page-hero">
      <div class="container">
        <h1 class="section-title">Estimer mon bien</h1>
        <p class="section-lead">Obtenez une première estimation gratuite en quelques minutes.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="stepper" data-estimation>
          <div class="stepper-progress">
            <div class="stepper-step is-active"><span></span></div>
            <div class="stepper-step"><span></span></div>
            <div class="stepper-step"><span></span></div>
            <div class="stepper-step"><span></span></div>
          </div>

          <form class="stepper-card" novalidate>
            <div class="stepper-pane is-active">
              <h2 class="stepper-title">Type de bien</h2>
              <p class="stepper-subtitle">Sélectionnez la nature du bien à estimer.</p>
              <div class="stepper-choices">
                <label class="choice"><input type="radio" name="type" value="Appartement" required><span>Appartement</span></label>
                <label class="choice"><input type="radio" name="type" value="Maison"><span>Maison</span></label>
                <label class="choice"><input type="radio" name="type" value="Villa"><span>Villa</span></label>
                <label class="choice"><input type="radio" name="type" value="Terrain"><span>Terrain</span></label>
                <label class="choice"><input type="radio" name="type" value="Local"><span>Local</span></label>
                <label class="choice"><input type="radio" name="type" value="Bureau"><span>Bureau</span></label>
              </div>
            </div>

            <div class="stepper-pane">
              <h2 class="stepper-title">Localisation</h2>
              <p class="stepper-subtitle">Indiquez où se situe le bien.</p>
              <div class="form-grid">
                <label class="form-field"><span>Wilaya</span><input type="text" name="wilaya" required></label>
                <label class="form-field"><span>Commune</span><input type="text" name="commune" required></label>
              </div>
              <label class="form-field" style="margin-top:12px"><span>Quartier</span><input type="text" name="quartier"></label>
            </div>

            <div class="stepper-pane">
              <h2 class="stepper-title">Caractéristiques</h2>
              <p class="stepper-subtitle">Détaillez la surface et les pièces.</p>
              <div class="form-grid">
                <label class="form-field"><span>Surface habitable (m²)</span><input type="number" name="surface" min="1" required></label>
                <label class="form-field"><span>Surface terrain (m²)</span><input type="number" name="land" min="0"></label>
                <label class="form-field"><span>Chambres</span><input type="number" name="bedrooms" min="0"></label>
                <label class="form-field"><span>Salles de bain</span><input type="number" name="bathrooms" min="0"></label>
              </div>
              <label class="choice" style="margin-top:16px"><input type="checkbox" name="garage"><span>Garage / stationnement</span></label>
            </div>

            <div class="stepper-pane">
              <h2 class="stepper-title">Vos coordonnées</h2>
              <p class="stepper-subtitle">Pour recevoir votre estimation détaillée.</p>
              <div class="form-grid">
                <label class="form-field"><span>Prénom</span><input type="text" name="firstname" required></label>
                <label class="form-field"><span>Nom</span><input type="text" name="lastname" required></label>
                <label class="form-field"><span>Email</span><input type="email" name="email" required></label>
                <label class="form-field"><span>Téléphone</span><input type="tel" name="phone" required></label>
              </div>
              <label class="form-field" style="margin-top:12px"><span>Remarques éventuelles</span><textarea name="message" rows="3"></textarea></label>
            </div>

            <div class="stepper-nav">
              <button type="button" class="btn btn-outline" data-step-prev>Précédent</button>
              <button type="button" class="btn btn-primary" data-step-next>Suivant</button>
              <button type="submit" class="btn btn-primary">Valider l'estimation</button>
            </div>
          </form>

          <div class="estimation-success" data-estimation-success hidden>
            <h2>Merci pour votre demande</h2>
            <p>Un conseiller Immolode va étudier les caractéristiques de votre bien et vous recontactera sous 24 à 48 heures avec une estimation personnalisée.</p>
            <a class="btn btn-outline" href="/client/biens">Découvrir nos biens en vente</a>
          </div>
        </div>
      </div>
    </section>

    <div id="cta-slot"></div>
  </main>

  <div id="footer-slot"></div>

  <script type="module">
    import { initPage } from '/client/includes/scripts.js';
    import { initEstimation } from '/assets/js/estimation.js';
    import { CTASection } from '/assets/components/cta-section.js';

    await initPage({ page: 'estimation', title: 'Estimation de bien' });

    initEstimation();
    document.getElementById('cta-slot').innerHTML = CTASection({
      title: "Une question avant d'estimer ?",
      subtitle: 'Nos conseillers sont disponibles pour échanger directement par téléphone.'
    });
  </script>
</body>
</html>
