<?php
/**
 * client/includes/header.php - En-tête de navigation officiel Imolode (HTML + PHP)
 */
declare(strict_types=1);

$activePage = $activePage ?? '';
if (isset($conn) && $conn instanceof mysqli && !isset($agency_tel)) {
    $resTel = $conn->query("SELECT `valeur` FROM `parametres` WHERE `cle` IN ('agence_telephone', 'phone') LIMIT 1");
    if ($resTel && ($rTel = $resTel->fetch_assoc()) && !empty($rTel['valeur'])) {
        $agency_tel = $rTel['valeur'];
    }
}
$agency_tel = $agency_tel ?? '+213 555 12 34 56';
$agency_tel_raw = preg_replace('/[^0-9+]/', '', $agency_tel);
$b = defined('BASE_URL') ? BASE_URL : '/immolode-php';
?>
<header class="site-header" data-header>
  <div class="container header-inner">
    <a href="<?= $b ?>/client/index.php" class="brand" aria-label="Imolode">
      <img src="<?= $b ?>/assets/images/logo.svg" alt="Logo Imolode" class="brand-logo" width="34" height="34" loading="eager">
      <span class="brand-name">Imolode</span>
    </a>

    <nav class="main-nav" aria-label="Navigation principale">
      <ul class="nav-list">
        <li>
          <a href="<?= $b ?>/client/index.php" class="nav-link <?= $activePage === 'accueil' ? 'is-active' : '' ?>" <?= $activePage === 'accueil' ? 'aria-current="page"' : '' ?>>Accueil</a>
        </li>
        <li>
          <a href="<?= $b ?>/client/biens.php" class="nav-link <?= $activePage === 'biens' ? 'is-active' : '' ?>" <?= $activePage === 'biens' ? 'aria-current="page"' : '' ?>>Nos biens</a>
        </li>
        <li>
          <a href="<?= $b ?>/client/services.php" class="nav-link <?= $activePage === 'services' ? 'is-active' : '' ?>" <?= $activePage === 'services' ? 'aria-current="page"' : '' ?>>Services</a>
        </li>
        <li>
          <a href="<?= $b ?>/client/estimation.php" class="nav-link <?= $activePage === 'estimation' ? 'is-active' : '' ?>" <?= $activePage === 'estimation' ? 'aria-current="page"' : '' ?>>Estimation</a>
        </li>
        <li>
          <a href="<?= $b ?>/client/a-propos.php" class="nav-link <?= $activePage === 'a-propos' ? 'is-active' : '' ?>" <?= $activePage === 'a-propos' ? 'aria-current="page"' : '' ?>>À propos</a>
        </li>
        <li>
          <a href="<?= $b ?>/client/contact.php" class="nav-link <?= $activePage === 'contact' ? 'is-active' : '' ?>" <?= $activePage === 'contact' ? 'aria-current="page"' : '' ?>>Contact</a>
        </li>
      </ul>
    </nav>

    <div class="header-actions">
      <a href="<?= $b ?>/client/estimation.php" class="btn btn-outline btn-sm header-cta">Estimer mon bien</a>
      <a href="<?= $b ?>/client/fiche-client.php" class="btn btn-primary btn-sm header-cta">Fiche client</a>
      <a href="<?= $b ?>/admin/login.php" class="btn btn-ghost btn-sm" title="Espace Conseiller & Administration" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Espace Pro</span>
      </a>

      <button type="button" class="menu-toggle" id="menu-toggle-btn" aria-expanded="false" aria-controls="mobile-nav" aria-label="Ouvrir le menu">
        <span class="menu-toggle-open">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </span>
        <span class="menu-toggle-close" style="display:none;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </span>
      </button>
    </div>
  </div>

  <!-- Menu mobile -->
  <div id="mobile-nav" class="mobile-nav" hidden>
    <nav aria-label="Navigation mobile">
      <ul class="mobile-nav-list">
        <li><a href="<?= $b ?>/client/index.php">Accueil</a></li>
        <li><a href="<?= $b ?>/client/biens.php">Nos biens</a></li>
        <li><a href="<?= $b ?>/client/services.php">Services</a></li>
        <li><a href="<?= $b ?>/client/estimation.php">Estimation</a></li>
        <li><a href="<?= $b ?>/client/a-propos.php">À propos</a></li>
        <li><a href="<?= $b ?>/client/contact.php">Contact</a></li>
        <li><a href="<?= $b ?>/admin/login.php" style="color:var(--color-accent); font-weight:600;">Espace Pro (Connexion Admin)</a></li>
      </ul>
    </nav>
    <div class="mobile-nav-footer">
      <a href="<?= $b ?>/client/estimation.php" class="btn btn-outline btn-block">Estimer mon bien</a>
      <a href="<?= $b ?>/client/fiche-client.php" class="btn btn-primary btn-block">Fiche client</a>
      <a href="tel:<?= e($agency_tel_raw) ?>" class="btn btn-ghost btn-block">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span><?= e($agency_tel) ?></span>
      </a>
    </div>
  </div>
</header>
