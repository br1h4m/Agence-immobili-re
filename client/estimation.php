<?php
/**
 * ============================================================================
 * client/estimation.php - Formulaire d'estimation de bien (Haute performance)
 * ============================================================================
 * Rendu direct côté serveur en PHP pour un affichage instantané.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

$activePage = 'estimation';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Estimer mon bien — Imolode</title>
  <meta name="description" content="Obtenez une première estimation gratuite de votre bien immobilier en Algérie en quelques minutes.">
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
        <h1 class="section-title">Estimer mon bien</h1>
        <p class="section-lead">Obtenez un avis de valeur professionnel et gratuit en quelques minutes.</p>
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

          <form class="stepper-card" id="estimation-form" novalidate>
            <!-- Étape 1 : Type de bien -->
            <div class="stepper-pane is-active">
              <h2 class="stepper-title">Type de bien</h2>
              <p class="stepper-subtitle">Sélectionnez la nature du bien immobilier à estimer.</p>
              <div class="stepper-choices">
                <label class="choice"><input type="radio" name="type" value="Appartement" checked><span>Appartement</span></label>
                <label class="choice"><input type="radio" name="type" value="Villa"><span>Villa</span></label>
                <label class="choice"><input type="radio" name="type" value="Duplex"><span>Duplex</span></label>
                <label class="choice"><input type="radio" name="type" value="Terrain"><span>Terrain</span></label>
                <label class="choice"><input type="radio" name="type" value="Local"><span>Local commercial</span></label>
                <label class="choice"><input type="radio" name="type" value="Bureau"><span>Bureau</span></label>
              </div>
            </div>

            <!-- Étape 2 : Localisation -->
            <div class="stepper-pane">
              <h2 class="stepper-title">Localisation</h2>
              <p class="stepper-subtitle">Indiquez l'emplacement géographique du bien.</p>
              <div class="form-grid">
                <label class="form-field"><span>Wilaya</span><input type="text" name="wilaya" placeholder="Ex: Béjaïa" required></label>
                <label class="form-field"><span>Commune</span><input type="text" name="commune" placeholder="Ex: Béjaïa ville" required></label>
              </div>
              <label class="form-field" style="margin-top:12px"><span>Quartier / Adresse</span><input type="text" name="quartier" placeholder="Ex: Tichy, Centre-ville, etc."></label>
            </div>

            <!-- Étape 3 : Caractéristiques -->
            <div class="stepper-pane">
              <h2 class="stepper-title">Caractéristiques</h2>
              <p class="stepper-subtitle">Précisez les dimensions et volumes du bien.</p>
              <div class="form-grid">
                <label class="form-field"><span>Surface habitable (m²)</span><input type="number" name="surface" min="1" placeholder="120" required></label>
                <label class="form-field"><span>Surface terrain (m²)</span><input type="number" name="land" min="0" placeholder="Optionnel"></label>
                <label class="form-field"><span>Chambres</span><input type="number" name="bedrooms" min="0" placeholder="3"></label>
                <label class="form-field"><span>Salles de bain</span><input type="number" name="bathrooms" min="0" placeholder="1"></label>
              </div>
              <label class="choice" style="margin-top:16px"><input type="checkbox" name="garage"><span>Garage ou place de stationnement</span></label>
            </div>

            <!-- Étape 4 : Coordonnées -->
            <div class="stepper-pane">
              <h2 class="stepper-title">Vos coordonnées</h2>
              <p class="stepper-subtitle">Pour recevoir votre étude de valeur personnalisée.</p>
              <div class="form-grid">
                <label class="form-field"><span>Prénom</span><input type="text" name="firstname" required placeholder="Votre prénom"></label>
                <label class="form-field"><span>Nom</span><input type="text" name="lastname" required placeholder="Votre nom"></label>
                <label class="form-field"><span>Email</span><input type="email" name="email" required placeholder="votre@email.com"></label>
                <label class="form-field"><span>Téléphone</span><input type="tel" name="phone" required placeholder="05 XX XX XX XX"></label>
              </div>
              <label class="form-field" style="margin-top:12px"><span>Remarques ou spécificités</span><textarea name="message" rows="3" placeholder="État du bien, vue mer, terrasse, ascenseur..."></textarea></label>
            </div>

            <div class="stepper-nav">
              <button type="button" class="btn btn-outline" data-step-prev>Précédent</button>
              <button type="button" class="btn btn-primary" data-step-next>Suivant</button>
              <button type="submit" class="btn btn-primary" id="btn-submit-estim">Valider l'estimation</button>
            </div>
          </form>

          <div class="estimation-success" id="estimation-success" hidden style="text-align:center;padding:60px 20px;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#27AE60" stroke-width="2" style="margin-bottom:16px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <h2 style="font-size:1.6rem;margin-bottom:12px;">Merci pour votre demande</h2>
            <p class="text-muted" style="max-width:540px;margin:0 auto 24px;">Un conseiller Immolode va étudier les caractéristiques de votre bien et vous recontactera sous 24 à 48 heures avec un dossier d'estimation détaillé.</p>
            <a class="btn btn-outline" href="/client/biens.php">Découvrir nos biens en vente</a>
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
        <h2 class="cta-title">Une question avant d'estimer ?</h2>
        <p class="cta-subtitle">Nos conseillers sont disponibles pour échanger directement par téléphone ou à l'agence.</p>
        <div class="cta-actions">
          <a href="/client/contact.php" class="btn btn-primary btn-lg">Nous contacter</a>
          <a href="tel:<?= e($agency_tel_raw ?? '0555123456') ?>" class="btn btn-outline-light btn-lg">Appeler un conseiller</a>
        </div>
      </div>
    </section>
  </main>

  <!-- Pied de page PHP natif -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Script fluide de l'assistant d'estimation (Stepper) -->
  <script>
    (function() {
      var root = document.querySelector('[data-estimation]');
      if (!root) return;

      var steps = root.querySelectorAll('.stepper-step');
      var panes = root.querySelectorAll('.stepper-pane');
      var progress = root.querySelectorAll('.stepper-step > span');
      var nextBtns = root.querySelectorAll('[data-step-next]');
      var prevBtns = root.querySelectorAll('[data-step-prev]');
      var form = document.getElementById('estimation-form');
      var success = document.getElementById('estimation-success');
      var current = 0;

      function render() {
        panes.forEach(function(p, i) { p.classList.toggle('is-active', i === current); });
        steps.forEach(function(s, i) {
          s.classList.toggle('is-active', i === current);
          s.classList.toggle('is-done', i < current);
        });
        progress.forEach(function(bar, i) {
          bar.style.width = i <= current ? '100%' : '0%';
        });
      }

      nextBtns.forEach(function(b) {
        b.addEventListener('click', function() {
          var step = panes[current];
          var valid = true;
          step.querySelectorAll('input[required]').forEach(function(f) {
            var ok = f.value.trim() !== '';
            f.style.borderColor = ok ? '' : 'var(--color-danger, #E74C3C)';
            if (!ok) valid = false;
          });
          if (!valid) return;

          if (current < panes.length - 1) {
            current += 1;
            render();
          }
        });
      });

      prevBtns.forEach(function(b) {
        b.addEventListener('click', function() {
          if (current > 0) {
            current -= 1;
            render();
          }
        });
      });

      if (form) {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          var step = panes[current];
          var valid = true;
          step.querySelectorAll('input[required]').forEach(function(f) {
            var ok = f.value.trim() !== '';
            f.style.borderColor = ok ? '' : 'var(--color-danger, #E74C3C)';
            if (!ok) valid = false;
          });
          if (!valid) return;

          var btn = document.getElementById('btn-submit-estim');
          if (btn) { btn.disabled = true; btn.textContent = 'Enregistrement...'; }

          var data = new FormData(form);
          var payload = {
            type: 'Estimation',
            nom: ((data.get('firstname') || '') + ' ' + (data.get('lastname') || '')).trim() || 'Visiteur',
            email: data.get('email') || '',
            phone: data.get('phone') || '',
            sujet: 'Demande d\'estimation : ' + (data.get('type') || 'Bien') + ' à ' + (data.get('wilaya') || ''),
            message: data.get('message') || '',
            details: {
              type: data.get('type') || '',
              wilaya: data.get('wilaya') || '',
              commune: data.get('commune') || '',
              quartier: data.get('quartier') || '',
              surface: data.get('surface') || '',
              land: data.get('land') || '',
              bedrooms: data.get('bedrooms') || '',
              bathrooms: data.get('bathrooms') || '',
              garage: data.get('garage') ? 'Oui' : 'Non'
            }
          };

          fetch('/api/demandes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          }).then(function() {
            form.hidden = true;
            if (success) success.hidden = false;
            steps.forEach(function(s) { s.classList.add('is-done'); });
          }).catch(function() {
            form.hidden = true;
            if (success) success.hidden = false;
          });
        });
      }

      render();
    })();
  </script>
</body>
</html>
