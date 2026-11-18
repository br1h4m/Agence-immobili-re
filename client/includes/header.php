<?php
/**
 * ============================================================================
 * client/includes/header.php - En-tête de navigation officiel Immolode
 * ============================================================================
 * Rendu direct côté serveur en PHP pour un affichage instantané (0 délai JS).
 */
declare(strict_types=1);

$activePage = $activePage ?? '';

// Récupération des coordonnées si non définies
$agency_tel = $agency_tel ?? '+213 555 12 34 56';
$agency_tel_raw = preg_replace('/[^0-9+]/', '', $agency_tel);
?>
<header class="site-header" data-header>
  <div class="container header-inner">
    <a href="/client/index.php" class="brand" aria-label="Immolode">
      <img src="/assets/images/logo.svg" alt="Logo Immolode" class="brand-logo" width="34" height="34" loading="eager">
      <span class="brand-name">Imolode</span>
    </a>

    <nav class="main-nav" aria-label="Navigation principale">
      <ul class="nav-list">
        <li>
          <a href="/client/index.php" class="nav-link <?= $activePage === 'accueil' ? 'is-active' : '' ?>" <?= $activePage === 'accueil' ? 'aria-current="page"' : '' ?>>Accueil</a>
        </li>
        <li>
          <a href="/client/biens.php" class="nav-link <?= $activePage === 'biens' ? 'is-active' : '' ?>" <?= $activePage === 'biens' ? 'aria-current="page"' : '' ?>>Nos biens</a>
        </li>
        <li>
          <a href="/client/services.php" class="nav-link <?= $activePage === 'services' ? 'is-active' : '' ?>" <?= $activePage === 'services' ? 'aria-current="page"' : '' ?>>Services</a>
        </li>
        <li>
          <a href="/client/estimation.php" class="nav-link <?= $activePage === 'estimation' ? 'is-active' : '' ?>" <?= $activePage === 'estimation' ? 'aria-current="page"' : '' ?>>Estimation</a>
        </li>
        <li>
          <a href="/client/a-propos.php" class="nav-link <?= $activePage === 'a-propos' ? 'is-active' : '' ?>" <?= $activePage === 'a-propos' ? 'aria-current="page"' : '' ?>>À propos</a>
        </li>
        <li>
          <a href="/client/contact.php" class="nav-link <?= $activePage === 'contact' ? 'is-active' : '' ?>" <?= $activePage === 'contact' ? 'aria-current="page"' : '' ?>>Contact</a>
        </li>
      </ul>
    </nav>

    <div class="header-actions">
      <a href="/client/estimation.php" class="btn btn-outline btn-sm header-cta">Estimer mon bien</a>
      <a href="/client/fiche-client.php" class="btn btn-primary btn-sm header-cta">Fiche client</a>
      <a href="/admin/login.php" class="btn btn-ghost btn-sm header-cta" title="Espace Administrateur / Agent">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
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

  <!-- Menu navigation pour appareils mobiles -->
  <div id="mobile-nav" class="mobile-nav" hidden>
    <nav aria-label="Navigation mobile">
      <ul class="mobile-nav-list">
        <li><a href="/client/index.php">Accueil</a></li>
        <li><a href="/client/biens.php">Nos biens</a></li>
        <li><a href="/client/services.php">Services</a></li>
        <li><a href="/client/estimation.php">Estimation</a></li>
        <li><a href="/client/a-propos.php">À propos</a></li>
        <li><a href="/client/contact.php">Contact</a></li>
      </ul>
    </nav>
    <div class="mobile-nav-footer">
      <a href="/client/estimation.php" class="btn btn-outline btn-block">Estimer mon bien</a>
      <a href="/client/fiche-client.php" class="btn btn-primary btn-block">Fiche client</a>
      <a href="/admin/login.php" class="btn btn-outline btn-block" style="border-style:dashed;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span>Espace Pro (Connexion)</span>
      </a>
      <a href="tel:<?= e($agency_tel_raw) ?>" class="btn btn-ghost btn-block">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span><?= e($agency_tel) ?></span>
      </a>
    </div>
  </div>
</header>
