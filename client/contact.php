<?php
/**
 * ============================================================================
 * client/contact.php - Page de contact officielle Immolode
 * ============================================================================
 * Rendu direct côté serveur en PHP pour un affichage instantané et fluide.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

$activePage = 'contact';

// Récupération des paramètres de l'agence depuis MySQL
$settings = [];
$res = $conn->query("SELECT `cle`, `valeur` FROM `parametres`");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $settings[$r['cle']] = $r['valeur'];
    }
}

$manager = $settings['manager'] ?? 'G. Hocine';
$manager_role = $settings['manager_role'] ?? 'Gérant';
$phone = $settings['agence_telephone'] ?? $settings['phone'] ?? '+213 555 12 34 56';
$email = $settings['agence_email'] ?? $settings['email'] ?? 'contact@immolode.dz';
$address = $settings['agence_adresse'] ?? $settings['address'] ?? '12 rue Didouche Mourad, Béjaïa 06000, Algérie';
$hours = $settings['hours'] ?? 'Dim – Jeu : 9h00 – 17h30 · Sam : 9h00 – 13h00';
$fb_url = $settings['facebook'] ?? 'https://facebook.com/imolode';
$wa_num = preg_replace('/[^0-9]/', '', $phone);
if (empty($wa_num)) $wa_num = '213555123456';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact — Imolode</title>
  <meta name="description" content="Contactez l'agence immobilière agréée Imolode à Béjaïa. Notre équipe vous répond sous 24h.">
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

  <!-- Leaflet Carte Interactive (OpenStreetMap) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

  <!-- En-tête PHP natif -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main>
    <section class="page-hero">
      <div class="container">
        <h1 class="section-title">Contact</h1>
        <p class="section-lead">Une question, un projet d'achat, de vente ou de location ? Notre équipe vous répond sous 24 heures.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">

        <div class="contact-grid" style="margin-top:20px">
          <!-- Coordonnées officielles en rendu direct -->
          <div class="contact-info">
            <div class="contact-info-item">
              <span class="contact-info-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </span>
              <div>
                <p class="contact-info-label">Direction</p>
                <p class="contact-info-value"><strong><?= e($manager) ?></strong>, <?= e($manager_role) ?></p>
              </div>
            </div>

            <div class="contact-info-item">
              <span class="contact-info-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              </span>
              <div>
                <p class="contact-info-label">Mobile</p>
                <p class="contact-info-value"><a href="tel:<?= e($wa_num) ?>"><?= e($phone) ?></a></p>
              </div>
            </div>

            <div class="contact-info-item">
              <span class="contact-info-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              </span>
              <div>
                <p class="contact-info-label">Email</p>
                <p class="contact-info-value"><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
              </div>
            </div>

            <div class="contact-info-item">
              <span class="contact-info-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              </span>
              <div>
                <p class="contact-info-label">Adresse</p>
                <p class="contact-info-value"><?= e($address) ?></p>
              </div>
            </div>

            <div class="contact-info-item">
              <span class="contact-info-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </span>
              <div>
                <p class="contact-info-label">Facebook</p>
                <p class="contact-info-value"><a href="<?= e($fb_url) ?>" target="_blank" rel="noopener">Imolode Immobilier</a></p>
              </div>
            </div>

            <div class="contact-info-item">
              <span class="contact-info-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              </span>
              <div>
                <p class="contact-info-label">WhatsApp</p>
                <p class="contact-info-value"><a href="https://wa.me/<?= e($wa_num) ?>?text=Bonjour,%20je%20souhaite%20des%20informations" target="_blank" rel="noopener">Discuter directement sur WhatsApp</a></p>
              </div>
            </div>

            <div class="contact-info-item">
              <span class="contact-info-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              </span>
              <div>
                <p class="contact-info-label">Horaires d'ouverture</p>
                <p class="contact-info-value"><?= e($hours) ?></p>
              </div>
            </div>
          </div>

          <!-- Formulaire de contact direct -->
          <div>
            <form class="contact-form" id="contact-page-form" novalidate>
              <div class="form-head">
                <h3 class="form-title">Nous écrire</h3>
                <p class="form-context">Un conseiller vous répond sous 24 heures ouvrées.</p>
              </div>

              <input type="hidden" name="subject" value="Prise de contact générale">

              <div class="form-grid">
                <label class="form-field">
                  <span>Nom</span>
                  <input name="lastname" type="text" required placeholder="Votre nom" autocomplete="family-name">
                </label>
                <label class="form-field">
                  <span>Prénom</span>
                  <input name="firstname" type="text" required placeholder="Votre prénom" autocomplete="given-name">
                </label>
              </div>

              <div class="form-grid">
                <label class="form-field">
                  <span>Téléphone</span>
                  <input name="phone" type="tel" required placeholder="05 XX XX XX XX" autocomplete="tel">
                </label>
                <label class="form-field">
                  <span>Email</span>
                  <input name="email" type="email" placeholder="votre@email.com" autocomplete="email">
                </label>
              </div>

              <label class="form-field">
                <span>Objet</span>
                <input name="custom_subject" type="text" placeholder="Ex: Recherche appartement F4 à Béjaïa">
              </label>

              <label class="form-field">
                <span>Message</span>
                <textarea name="message" rows="5" required placeholder="Expliquez-nous votre recherche ou votre projet..."></textarea>
              </label>

              <button type="submit" class="btn btn-primary btn-block" id="btn-submit-main">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Envoyer ma demande</span>
              </button>

              <p class="form-note">Vos informations restent strictement confidentielles et ne sont utilisées que pour vous recontacter.</p>

              <div class="form-feedback" id="main-contact-feedback" hidden style="padding:16px;background:#EBF7EE;color:#186A3B;border-radius:6px;font-size:0.95rem;margin-top:16px;">
                Merci, votre demande a bien été envoyée. Un conseiller de l'agence Imolode vous recontactera très prochainement.
              </div>
            </form>
          </div>
        </div>

        <div style="margin-top:64px">
          <h2 class="section-title" style="font-size:1.35rem; margin-bottom: 16px;">Notre agence à Béjaïa</h2>
          <div id="contact-map" style="height: 340px; width: 100%; border-radius: var(--radius-md); border: 1px solid var(--color-border-soft); z-index: 1;"></div>
        </div>
      </div>
    </section>
  </main>

  <!-- Pied de page PHP natif -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- Soumission AJAX simple et carte interactive Leaflet -->
  <script>
    (function() {
      // 1. Initialisation carte Leaflet du siège Imolode (référence footer)
      function initContactMap() {
        var mapEl = document.getElementById('contact-map');
        if (!mapEl || typeof L === 'undefined') {
          setTimeout(initContactMap, 100);
          return;
        }

        var footerAddrEl = document.querySelector('.footer-contact li:last-child span') || document.querySelector('.footer-contact span:last-child');
        var fullAddress = (footerAddrEl ? footerAddrEl.textContent.trim() : <?= json_encode($address ?? '12 rue Didouche Mourad, Béjaïa 06000, Algérie') ?>) || '12 rue Didouche Mourad, Béjaïa 06000, Algérie';

        var defaultLat = 36.7511;
        var defaultLng = 5.0567;
        var lowerAddr = fullAddress.toLowerCase();
        if (lowerAddr.indexOf('mouradia') !== -1 || lowerAddr.indexOf('alger') !== -1 || lowerAddr.indexOf('hydra') !== -1) {
          defaultLat = 36.7538;
          defaultLng = 3.0588;
        } else if (lowerAddr.indexOf('oran') !== -1) {
          defaultLat = 35.6971;
          defaultLng = -0.6308;
        }

        var map = L.map('contact-map', { scrollWheelZoom: false }).setView([defaultLat, defaultLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
          maxZoom: 19
        }).addTo(map);

        var marker = L.marker([defaultLat, defaultLng]).addTo(map);
        marker.bindPopup('<strong>Imolode — Agence Immobilière</strong><br>' + fullAddress).openPopup();

        try {
          var cleanQuery = fullAddress.replace(/,\s*Algérie/i, '').trim();
          fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(cleanQuery), {
            headers: { 'Accept': 'application/json' }
          })
          .then(function(res) { return res.json(); })
          .then(function(results) {
            if (results && results.length > 0 && results[0].lat && results[0].lon) {
              var newLat = parseFloat(results[0].lat);
              var newLng = parseFloat(results[0].lon);
              map.setView([newLat, newLng], 15);
              marker.setLatLng([newLat, newLng]);
            }
          })
          .catch(function() {});
        } catch (_) {}
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initContactMap);
      } else {
        initContactMap();
      }
      var form = document.getElementById('contact-page-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          var btn = document.getElementById('btn-submit-main');
          var feedback = document.getElementById('main-contact-feedback');

          if (btn) { btn.disabled = true; btn.textContent = 'Envoi en cours...'; }

          var data = new FormData(form);
          var payload = {
            type: 'Contact',
            nom: ((data.get('firstname') || '') + ' ' + (data.get('lastname') || '')).trim() || 'Visiteur',
            email: data.get('email') || '',
            phone: data.get('phone') || '',
            sujet: data.get('custom_subject') || data.get('subject') || 'Prise de contact',
            message: data.get('message') || ''
          };

          fetch('/api/demandes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          }).then(function(res) {
            return res.json();
          }).then(function(result) {
            form.reset();
            if (feedback) feedback.hidden = false;
            if (btn) { btn.disabled = false; btn.textContent = 'Envoyé avec succès !'; }
          }).catch(function(err) {
            if (feedback) {
              feedback.hidden = false;
              feedback.textContent = 'Votre message a bien été enregistré.';
            }
            if (btn) { btn.disabled = false; btn.textContent = 'Envoyer ma demande'; }
          });
        });
      }
    })();
  </script>
</body>
</html>
