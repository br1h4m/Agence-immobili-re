<?php
// client/services.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Services — Imolode</title>
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
        <h1 class="section-title">Nos services</h1>
        <p class="section-lead">Un accompagnement complet pour tous vos projets immobiliers.</p>
      </div>
    </section>

    <!-- Grille des 5 services -->
    <section class="section">
      <div class="container">
        <div id="services-list" class="property-grid"></div>
      </div>
    </section>

    <!-- Section Honoraires réglementaires (Article 34) -->
    <section class="section section--soft" id="honoraires">
      <div class="container">
        <header class="section-head section-head--center reveal">
          <span class="section-eyebrow">Agence Immobilière Agréée Imolode</span>
          <h2 class="section-title">Barème des honoraires réglementaires</h2>
          <p class="section-lead">Art. 34-(Décret réglementant la profession de l'Agence immobilière)</p>
        </header>

        <div style="background:#FFFFFF;border:1px solid #E5E3DE;border-radius:var(--radius-md);overflow:hidden;max-width:880px;margin:0 auto;box-shadow:var(--shadow-xs);">
          <div style="padding:22px 28px;background-color:#FAF9F6;border-bottom:1px solid #E5E3DE;">
            <p style="margin:0;font-size:0.95rem;color:var(--color-text);line-height:1.6;">
              <strong>Art. 34-(Décret réglementant la profession de l'Agence immobilière) :</strong> L'agent immobilier a droit, dans le cadre de l'exercice de sa profession à une rémunération. Pour ce qui concerne l'agence et le courtier immobilier, Lorsque la valeur du bien à vendre équivaut à :
            </p>
          </div>
          <div class="table-responsive">
            <table style="width:100%;border-collapse:collapse;text-align:left;font-size:0.92rem;">
              <thead>
                <tr style="background:#F4EFE6;border-bottom:2px solid #DECBB3;">
                  <th style="padding:14px 24px;font-weight:700;color:var(--color-text);">Tranche de valeur du bien à vendre</th>
                  <th style="padding:14px 24px;font-weight:700;color:var(--color-text);text-align:right;">Taux de rémunération (Honoraire)</th>
                </tr>
              </thead>
              <tbody>
                <tr style="border-bottom:1px solid #E5E3DE;">
                  <td style="padding:14px 24px;">De <strong>1 DA</strong> à <strong>1 000 000 DA</strong></td>
                  <td style="padding:14px 24px;text-align:right;font-weight:700;color:#B59A6A;">3%</td>
                </tr>
                <tr style="border-bottom:1px solid #E5E3DE;background:#FAF9F6;">
                  <td style="padding:14px 24px;">De <strong>1 000 001 DA</strong> à <strong>3 000 000 DA</strong></td>
                  <td style="padding:14px 24px;text-align:right;font-weight:700;color:#B59A6A;">2%</td>
                </tr>
                <tr style="border-bottom:1px solid #E5E3DE;">
                  <td style="padding:14px 24px;">De <strong>3 000 001 DA</strong> à <strong>5 000 000 DA</strong></td>
                  <td style="padding:14px 24px;text-align:right;font-weight:700;color:#B59A6A;">1,5%</td>
                </tr>
                <tr style="border-bottom:1px solid #E5E3DE;background:#FAF9F6;">
                  <td style="padding:14px 24px;">De <strong>5 000 001 DA</strong> à <strong>10 000 000 DA</strong></td>
                  <td style="padding:14px 24px;text-align:right;font-weight:700;color:#B59A6A;">1%</td>
                </tr>
                <tr>
                  <td style="padding:14px 24px;">Supérieure à <strong>10 000 000 DA</strong></td>
                  <td style="padding:14px 24px;text-align:right;font-weight:700;color:#B59A6A;">0,75%</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div style="padding:14px 24px;background:#FAF9F6;border-top:1px solid #E5E3DE;font-size:0.82rem;color:var(--color-muted);">
            * Barème officiel conforme à la réglementation algérienne en vigueur. Des honoraires clairs, transparents et appliqués en toute rigueur déontologique.
          </div>
        </div>
      </div>
    </section>

    <div id="cta-slot"></div>
  </main>

  <div id="footer-slot"></div>

  <script type="module">
    import { initPage } from '/client/includes/scripts.js';
    import { ServiceCard } from '/assets/components/service-card.js';
    import { CTASection } from '/assets/components/cta-section.js';
    import { SERVICES } from '/client/data/services.js';

    await initPage({ page: 'services', title: 'Services' });

    document.getElementById('services-list').innerHTML = SERVICES.map(ServiceCard).join('');
    document.getElementById('cta-slot').innerHTML = CTASection({
      title: "Besoin d'un conseil personnalisé ?",
      subtitle: 'Nos équipes sont disponibles pour étudier votre projet.'
    });
  </script>
</body>
</html>
