<?php
/**
 * admin/includes/sidebar.php - Barre latérale de navigation admin (HTML brut + PHP)
 */
declare(strict_types=1);

$activePage = $activePage ?? 'dashboard';
$b = defined('BASE_URL') ? BASE_URL : '/immolode-php';
$unreadCounts = isset($conn) ? get_unread_counts($conn) : ['demandes' => 0, 'fiches' => 0];
$totalDemandes = $unreadCounts['demandes'] + $unreadCounts['fiches'];

$menuItems = [
    ['key' => 'dashboard',     'label' => 'Tableau de bord',   'href' => $b . '/admin/index.php',         'icon' => 'dashboard'],
    ['key' => 'biens',         'label' => 'Biens immobiliers', 'href' => $b . '/admin/biens.php',         'icon' => 'building'],
    ['key' => 'demandes',      'label' => 'Demandes',          'href' => $b . '/admin/demandes.php',      'icon' => 'mail',      'badge' => $totalDemandes,            'badge_id' => 'badge-demandes'],
    ['key' => 'fiches-client', 'label' => 'Fiches client',     'href' => $b . '/admin/fiches-client.php', 'icon' => 'file-text', 'badge' => $unreadCounts['fiches'],   'badge_id' => 'badge-fiches-client'],
    ['key' => 'utilisateurs',  'label' => 'Utilisateurs',      'href' => $b . '/admin/utilisateurs.php',  'icon' => 'users'],
    ['key' => 'parametres',    'label' => 'Paramètres',        'href' => $b . '/admin/parametres.php',    'icon' => 'settings'],
];
?>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigation administration">
  <div class="sidebar-brand">
    <a href="<?= $b ?>/admin/index.php" class="brand-link">
      <img src="<?= $b ?>/assets/images/logo.svg" alt="Imolode" class="brand-logo" width="30" height="30">
      <div class="brand-info">
        <span class="brand-title">Imolode</span>
        <span class="brand-tag">Administration</span>
      </div>
    </a>
    <div style="display: flex; align-items: center; gap: 6px;">
      <button type="button" class="sidebar-collapse-btn" id="sidebar-collapse-btn" title="Réduire ou agrandir le menu" aria-label="Réduire ou agrandir le menu">
        <?= admin_icon('chevrons-left', 16) ?>
      </button>
      <button type="button" class="sidebar-close-btn" id="sidebar-close-btn" aria-label="Fermer le menu">
        <?= admin_icon('close', 20) ?>
      </button>
    </div>
  </div>

  <nav class="sidebar-nav">
    <p class="sidebar-heading">Menu principal</p>
    <ul class="sidebar-menu">
      <?php foreach ($menuItems as $item): ?>
        <?php 
          $isActive = ($item['key'] === $activePage);
          $hasBadge = isset($item['badge_id']);
          $count = (int)($item['badge'] ?? 0);
        ?>
        <li class="sidebar-item">
          <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $isActive ? 'is-active' : '' ?>" title="<?= e($item['label']) ?>">
            <span class="sidebar-icon"><?= admin_icon($item['icon'], 18) ?></span>
            <span class="sidebar-label"><?= e($item['label']) ?></span>
            <?php if ($hasBadge): ?>
              <span class="sidebar-badge <?= $count > 0 ? '' : 'is-empty' ?>" id="<?= e($item['badge_id']) ?>" <?= $count > 0 ? '' : 'hidden' ?> data-count="<?= $count ?>"><?= $count ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= $b ?>/client/index.php" target="_blank" rel="noopener" class="sidebar-external-link" title="Voir le site public">
      <span><?= admin_icon('external', 16) ?></span>
      <span class="sidebar-label">Voir le site public</span>
    </a>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
