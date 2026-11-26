<?php
/**
 * admin/includes/topbar.php - Barre supérieure, notifications toast & modal Mon Profil (HTML brut + PHP)
 */
declare(strict_types=1);

$activePage = $activePage ?? 'dashboard';
$pageHeading = $pageHeading ?? 'Administration';
$breadcrumbs = $breadcrumbs ?? [];
$topbarAction = $topbarAction ?? null;
$b = defined('BASE_URL') ? BASE_URL : '/immolode-php';

$u = $currentUser ?? $_SESSION['admin_user'] ?? ['nom' => 'Administrateur', 'role' => 'Gérant', 'email' => '', 'telephone' => '', 'avatar' => ''];
$initials = '';
foreach (explode(' ', trim((string)($u['nom'] ?? 'A'))) as $part) {
    if ($part !== '') $initials .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
    if (mb_strlen($initials, 'UTF-8') >= 2) break;
}
if ($initials === '') $initials = 'A';
$avatarSrc = !empty($u['avatar']) ? asset_url((string)$u['avatar']) : '';
$flash = get_flash();
?>
<script>
  // Application immédiate du thème enregistré avant le rendu visuel
  (function() {
    var t = localStorage.getItem('immolode_admin_theme') || 'light';
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('admin-sidebar')?.classList.add('is-collapsed');
        document.body.classList.add('sidebar-collapsed');
      });
    }
  })();
</script>

<header class="admin-topbar">
  <div class="topbar-left">
    <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle-btn" aria-label="Ouvrir le menu">
      <?= admin_icon('menu', 22) ?>
    </button>

    <?php if ($activePage !== 'dashboard'): ?>
      <button type="button" class="topbar-back-btn" id="topbar-back-btn" title="Retour à la page précédente" aria-label="Retour">
        <?= admin_icon('arrow-left', 17) ?>
      </button>
    <?php endif; ?>

    <div class="topbar-titles">
      <?php if (!empty($breadcrumbs)): ?>
        <nav class="admin-breadcrumbs" aria-label="Fil d'Ariane">
          <a href="<?= $b ?>/admin/index.php">Admin</a>
          <?php foreach ($breadcrumbs as $bc): ?>
            <span>/</span>
            <?php if (!empty($bc['href'])): ?>
              <a href="<?= e($bc['href']) ?>"><?= e($bc['label']) ?></a>
            <?php else: ?>
              <span><?= e($bc['label']) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
      <h1 class="topbar-title"><?= e($pageHeading) ?></h1>
    </div>
  </div>

  <div class="topbar-right">
    <?php if (!empty($topbarAction)): ?>
      <?php if (!empty($topbarAction['href']) && $topbarAction['href'] !== '#'): ?>
        <a href="<?= e($topbarAction['href']) ?>" class="btn btn-primary btn-sm topbar-action" <?= !empty($topbarAction['target']) ? 'target="' . e($topbarAction['target']) . '"' : '' ?>>
          <?= !empty($topbarAction['icon']) ? admin_icon($topbarAction['icon'], 16) : '' ?>
          <span><?= e($topbarAction['label']) ?></span>
        </a>
      <?php else: ?>
        <button type="button" class="btn btn-primary btn-sm topbar-action" id="<?= e($topbarAction['id'] ?? 'topbar-btn-action') ?>">
          <?= !empty($topbarAction['icon']) ? admin_icon($topbarAction['icon'], 16) : '' ?>
          <span><?= e($topbarAction['label']) ?></span>
        </button>
      <?php endif; ?>
    <?php endif; ?>

    <button type="button" class="btn-icon" id="btn-theme-toggle" title="Basculer thème clair / sombre" aria-label="Thème">
      <span class="theme-icon-light"><?= admin_icon('moon', 17) ?></span>
      <span class="theme-icon-dark" style="display:none;"><?= admin_icon('sun', 17) ?></span>
    </button>

    <div class="topbar-user-menu" id="topbar-user-menu">
      <div class="topbar-avatar" id="topbar-avatar-btn" title="Mon compte">
        <?php if ($avatarSrc !== ''): ?>
          <img src="<?= e($avatarSrc) ?>" alt="Avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <span class="topbar-avatar-initials" style="display:none;"><?= e($initials) ?></span>
        <?php else: ?>
          <span class="topbar-avatar-initials"><?= e($initials) ?></span>
        <?php endif; ?>
      </div>

      <div class="topbar-dropdown" id="topbar-dropdown" style="display:none;">
        <div style="padding: 10px 16px; border-bottom: 1px solid var(--admin-border-subtle); font-size: 0.8rem;">
          <strong style="display:block; color: var(--admin-text);"><?= e($u['nom'] ?? 'Admin') ?></strong>
          <span style="color: var(--admin-text-muted);"><?= e($u['role'] ?? 'Administrateur') ?></span>
        </div>
        <button type="button" class="topbar-dropdown-item" id="topbar-menu-profile" style="width:100%; border:0; background:none; text-align:left;">
          <?= admin_icon('user', 16) ?> <span>Mon Profil</span>
        </button>
        <a href="<?= $b ?>/admin/parametres.php" class="topbar-dropdown-item">
          <?= admin_icon('settings', 16) ?> <span>Paramètres</span>
        </a>
        <a href="<?= $b ?>/admin/logout.php" class="topbar-dropdown-item" style="color: var(--admin-danger);">
          <?= admin_icon('logout', 16) ?> <span>Déconnexion</span>
        </a>
      </div>
    </div>
  </div>
</header>

<?php if ($flash): ?>
  <div class="admin-toast-container" id="admin-flash-toast">
    <div class="admin-toast admin-toast--<?= $flash['type'] === 'error' ? 'error' : 'success' ?> is-visible">
      <span class="toast-icon"><?= admin_icon($flash['type'] === 'error' ? 'close' : 'check', 16) ?></span>
      <span class="toast-msg"><?= e($flash['message']) ?></span>
    </div>
  </div>
<?php endif; ?>

<!-- Modale Mon Profil (HTML statique traité directement en PHP POST par handle_own_profile_post) -->
<div class="admin-modal-overlay" id="admin-profile-modal">
  <div class="admin-modal-card" style="max-width: 500px; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 class="admin-modal-title" style="margin: 0;">Mon compte personnel</h3>
      <button type="button" class="btn-icon" id="profile-close-btn" aria-label="Fermer" style="border: 0; background: transparent;">
        <?= admin_icon('close', 20) ?>
      </button>
    </div>

    <form method="POST" action="<?= e($_SERVER['REQUEST_URI'] ?? ($b . '/admin/index.php')) ?>" enctype="multipart/form-data">
      <input type="hidden" name="global_action" value="update_own_profile">
      <input type="hidden" name="remove_avatar" id="profile-remove-flag" value="">

      <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 24px; padding: 14px; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md);">
        <div id="profile-avatar-preview" style="width: 64px; height: 64px; border-radius: 50%; background: var(--admin-gold); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 700; overflow: hidden; flex-shrink: 0;">
          <?php if ($avatarSrc !== ''): ?>
            <img src="<?= e($avatarSrc) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <?= e($initials) ?>
          <?php endif; ?>
        </div>
        <div style="flex: 1;">
          <label class="form-label" style="margin-bottom: 4px; font-weight: 600;">Photo de profil</label>
          <input type="text" name="profile_avatar_url" id="profile-avatar-url" class="form-input" placeholder="URL de l'image" value="<?= e($u['avatar'] ?? '') ?>" style="margin-bottom: 6px; padding: 6px 10px; font-size: 0.84rem;">
          <input type="file" name="profile_avatar_file" id="profile-avatar-file" accept="image/*" style="display:none;">
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline btn-sm" id="btn-upload-avatar">
              <?= admin_icon('camera', 14) ?> <span>Téléverser</span>
            </button>
            <?php if (!empty($u['avatar'])): ?>
              <button type="button" class="btn btn-danger-outline btn-sm" id="btn-remove-avatar">Supprimer</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" for="profile-nom">Nom complet <span class="required">*</span></label>
        <input type="text" id="profile-nom" name="profile_nom" class="form-input" required value="<?= e($u['nom'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" for="profile-email">Adresse e-mail <span class="required">*</span></label>
        <input type="email" id="profile-email" name="profile_email" class="form-input" required value="<?= e($u['email'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" for="profile-tel">Numéro de téléphone</label>
        <input type="tel" id="profile-tel" name="profile_telephone" class="form-input" value="<?= e($u['telephone'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" for="profile-password">Nouveau mot de passe <span style="font-weight: 400; color: var(--admin-text-subtle);">(laisser vide pour ne pas modifier)</span></label>
        <input type="password" id="profile-password" name="profile_password" class="form-input" placeholder="••••••••">
      </div>

      <div class="admin-modal-actions">
        <button type="button" class="btn btn-outline" id="profile-cancel-btn">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
      </div>
    </form>
  </div>
</div>

<!-- Lightbox globale d'inspection de document -->
<div class="admin-lightbox" id="image-lightbox" hidden>
  <div class="admin-lightbox-content">
    <button type="button" class="admin-lightbox-close" id="lightbox-close" aria-label="Fermer l'aperçu">×</button>
    <img src="" alt="Pièce d'identité" id="lightbox-img" class="admin-lightbox-img">
    <div class="admin-lightbox-caption" id="lightbox-caption">
      <span id="lightbox-caption-text">Pièce d'identité officielle</span>
      <a href="" id="lightbox-download-link" download class="btn btn-sm" style="background: rgba(255,255,255,0.16); color:#FFF; border: 1px solid rgba(255,255,255,0.45);">
        <?= admin_icon('download', 14) ?> <span>Télécharger</span>
      </a>
    </div>
  </div>
</div>
