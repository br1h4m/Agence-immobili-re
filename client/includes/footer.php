<?php
/**
 * ============================================================================
 * client/includes/footer.php - Pied de page officiel Immolode
 * ============================================================================
 * Rendu direct côté serveur en PHP pour un affichage instantané.
 */
declare(strict_types=1);

$year = date('Y');

// Récupération automatique des coordonnées réelles depuis la base de données
if (isset($conn) && $conn instanceof mysqli) {
    $param_res = $conn->query("SELECT `cle`, `valeur` FROM `parametres` WHERE `cle` IN ('address', 'agence_adresse', 'agence_telephone', 'phone', 'email')");
    if ($param_res) {
        while ($p = $param_res->fetch_assoc()) {
            if (($p['cle'] === 'address' || $p['cle'] === 'agence_adresse') && !empty($p['valeur']) && !isset($agency_address)) {
                $agency_address = $p['valeur'];
            }
            if (($p['cle'] === 'agence_telephone' || $p['cle'] === 'phone') && !empty($p['valeur']) && !isset($agency_tel)) {
                $agency_tel = $p['valeur'];
            }
            if ($p['cle'] === 'email' && !empty($p['valeur']) && !isset($agency_email)) {
                $agency_email = $p['valeur'];
            }
        }
    }
}

$agency_tel = $agency_tel ?? '+213 555 12 34 56';
$agency_email = $agency_email ?? 'contact@immolode.dz';
$agency_address = $agency_address ?? '12 rue Didouche Mourad, Béjaïa 06000, Algérie';
$agency_wa = preg_replace('/[^0-9]/', '', $agency_tel);
if (empty($agency_wa)) $agency_wa = '213555123456';
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Colonne 1 : Présentation de l'agence -->
      <div class="footer-col footer-col--brand">
        <a href="/client/index.php" class="brand brand--footer">
          <img src="/assets/images/logo.svg" alt="Logo Immolode" class="brand-logo" width="30" height="30" loading="lazy">
          <span class="brand-name">Imolode</span>
        </a>
        <p class="footer-text">
          Agence Immobilière Agréée Imolode. G. Hocine, Gérant.
          Nous accompagnons acheteurs, vendeurs, propriétaires et locataires sur l'ensemble de leurs projets immobiliers en Algérie.
        </p>
        <ul class="footer-social">
          <li>
            <a href="https://facebook.com/imolode" aria-label="Page Facebook Imolode" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
          </li>
          <li>
            <a href="https://wa.me/<?= e($agency_wa) ?>" aria-label="Discuter sur WhatsApp" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </a>
          </li>
        </ul>
      </div>

      <!-- Colonne 2 : Navigation -->
      <div class="footer-col">
        <h4 class="footer-heading">Navigation</h4>
        <ul class="footer-list">
          <li><a href="/client/index.php">Accueil</a></li>
          <li><a href="/client/biens.php">Nos biens</a></li>
          <li><a href="/client/services.php">Services</a></li>
          <li><a href="/client/estimation.php">Estimation</a></li>
          <li><a href="/client/a-propos.php">À propos</a></li>
          <li><a href="/client/contact.php">Contact</a></li>
          <li><a href="/client/fiche-client.php">Fiche client</a></li>
        </ul>
      </div>

      <!-- Colonne 3 : Services rapides -->
      <div class="footer-col">
        <h4 class="footer-heading">Services</h4>
        <ul class="footer-list">
          <li><a href="/client/biens.php?transaction=Acheter">Acheter</a></li>
          <li><a href="/client/biens.php?transaction=Louer">Louer</a></li>
          <li><a href="/client/estimation.php">Estimer mon bien</a></li>
          <li><a href="/client/services.php#honoraires">Barème des honoraires</a></li>
        </ul>
      </div>

      <!-- Colonne 4 : Coordonnées directes -->
      <div class="footer-col">
        <h4 class="footer-heading">Contact</h4>
        <ul class="footer-list footer-contact">
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span><strong>G. Hocine</strong> (Gérant)</span>
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <a href="tel:<?= e($agency_wa) ?>"><?= e($agency_tel) ?></a>
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <a href="mailto:<?= e($agency_email) ?>"><?= e($agency_email) ?></a>
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            <span><?= e($agency_address) ?></span>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>© <?= $year ?> Imolode. Agence Immobilière Agréée. Tous droits réservés.</p>
      <ul class="footer-legal">
        <li><a href="/client/a-propos.php">À propos</a></li>
        <li><a href="/client/contact.php">Contact</a></li>
        <li><a href="/admin/login.php">Portail Gestion</a></li>
      </ul>
    </div>
  </div>
</footer>

<!-- Script léger et réactif pour le menu mobile et le défilement de l'en-tête -->
<script>
  (function() {
    // Menu mobile interactif
    var toggle = document.getElementById('menu-toggle-btn');
    var nav = document.getElementById('mobile-nav');
    if (toggle && nav) {
      toggle.addEventListener('click', function() {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', !expanded);
        nav.hidden = expanded;
        var openIco = toggle.querySelector('.menu-toggle-open');
        var closeIco = toggle.querySelector('.menu-toggle-close');
        if (openIco) openIco.style.display = expanded ? 'inline-flex' : 'none';
        if (closeIco) closeIco.style.display = expanded ? 'none' : 'inline-flex';
      });
    }

    // Effet d'en-tête transparent -> opaque au défilement
    var header = document.querySelector('[data-header]');
    if (header) {
      window.addEventListener('scroll', function() {
        header.classList.toggle('is-scrolled', window.scrollY > 40);
      }, { passive: true });
    }
  })();
</script>
