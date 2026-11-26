<?php
/**
 * admin/index.php - Tableau de bord de l'administration Imolode (HTML brut + PHP MySQLi)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_helpers.php';

$currentUser = require_admin($conn);
$activePage = 'dashboard';
$pageHeading = 'Tableau de bord';
$b = BASE_URL;
$topbarAction = ['label' => 'Nouveau bien', 'href' => $b . '/admin/bien-edit.php', 'icon' => 'plus'];

// 1. Calcul KPI depuis MySQL
$totalProperties = (int)$conn->query("SELECT COUNT(*) c FROM `properties`")->fetch_assoc()['c'];
$available       = (int)$conn->query("SELECT COUNT(*) c FROM `properties` WHERE `statut` = 'Disponible'")->fetch_assoc()['c'];
$forSale         = (int)$conn->query("SELECT COUNT(*) c FROM `properties` WHERE `transaction` = 'Acheter'")->fetch_assoc()['c'];
$forRent         = (int)$conn->query("SELECT COUNT(*) c FROM `properties` WHERE `transaction` = 'Louer'")->fetch_assoc()['c'];

$totalDemandes   = (int)$conn->query("SELECT COUNT(*) c FROM `demandes`")->fetch_assoc()['c'];
$pendingDemandes = (int)$conn->query("SELECT COUNT(*) c FROM `demandes` WHERE `statut` IN ('Nouveau', 'En cours')")->fetch_assoc()['c'];

$totalFiches     = (int)$conn->query("SELECT COUNT(*) c FROM `fiches_client`")->fetch_assoc()['c'];
$pendingFiches   = (int)$conn->query("SELECT COUNT(*) c FROM `fiches_client` WHERE `statut` IN ('Nouveau', 'En cours')")->fetch_assoc()['c'];

// 2. 5 dernières entrées de chaque table
$recentDemandes   = $conn->query("SELECT * FROM `demandes` ORDER BY `date_creation` DESC, `id` DESC LIMIT 5");
$recentProperties = $conn->query("SELECT * FROM `properties` ORDER BY `date_creation` DESC, `id` DESC LIMIT 5");
$recentFiches     = $conn->query("SELECT * FROM `fiches_client` ORDER BY `date_creation` DESC, `id` DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tableau de bord — Administration Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="<?= $b ?>/admin/assets/css/admin.css">
</head>
<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-main-wrap">
      <?php include __DIR__ . '/includes/topbar.php'; ?>

      <main class="admin-content">
        <!-- Statistiques KPI -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon stat-icon--gold"><?= admin_icon('building', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $totalProperties ?></p>
              <p class="stat-label">Total biens en portefeuille</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon--blue"><?= admin_icon('check', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $available ?></p>
              <p class="stat-label">Biens actuellement disponibles</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon stat-icon--purple"><?= admin_icon('dashboard', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $forSale ?> / <?= $forRent ?></p>
              <p class="stat-label">À la vente / À la location</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon <?= $pendingDemandes > 0 ? 'stat-icon--gold' : 'stat-icon--green' ?>"><?= admin_icon('mail', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $pendingDemandes ?></p>
              <p class="stat-label">Demandes en attente (Total: <?= $totalDemandes ?>)</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon <?= $pendingFiches > 0 ? 'stat-icon--gold' : 'stat-icon--blue' ?>"><?= admin_icon('file-text', 24) ?></div>
            <div class="stat-info">
              <p class="stat-value"><?= $totalFiches ?></p>
              <p class="stat-label">Fiches client enregistrées (<?= $pendingFiches ?> en cours)</p>
            </div>
          </div>
        </div>

        <!-- Actions rapides -->
        <div style="display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap;">
          <a href="<?= $b ?>/admin/bien-edit.php" class="btn btn-primary">
            <?= admin_icon('plus', 16) ?>
            <span>Publier un nouveau bien</span>
          </a>
          <a href="<?= $b ?>/admin/fiches-client.php" class="btn btn-outline" style="border-color: var(--admin-gold); color: var(--admin-gold);">
            <?= admin_icon('file-text', 16) ?>
            <span>Consulter les fiches client<?php if ($pendingFiches > 0): ?> <span class="sidebar-badge" style="margin-left:4px;"><?= $pendingFiches ?></span><?php endif; ?></span>
          </a>
          <a href="<?= $b ?>/admin/demandes.php" class="btn btn-outline">
            <?= admin_icon('mail', 16) ?>
            <span>Consulter toutes les demandes<?php if ($pendingDemandes > 0): ?> <span class="sidebar-badge" style="margin-left:4px;"><?= $pendingDemandes ?></span><?php endif; ?></span>
          </a>
          <a href="<?= $b ?>/admin/biens.php" class="btn btn-outline">
            <?= admin_icon('building', 16) ?>
            <span>Gérer l'ensemble des biens</span>
          </a>
          <a href="<?= $b ?>/admin/parametres.php" class="btn btn-outline">
            <?= admin_icon('settings', 16) ?>
            <span>Paramètres de l'agence</span>
          </a>
        </div>

        <!-- Grille des 2 blocs principaux -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 24px;">

          <!-- Dernières demandes reçues -->
          <div class="card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Dernières demandes reçues</h2>
                <p class="card-subtitle">Contacts et estimations envoyés depuis le site</p>
              </div>
              <a href="<?= $b ?>/admin/demandes.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <div class="card-body" style="padding: 0;">
              <?php if (!$recentDemandes || $recentDemandes->num_rows === 0): ?>
                <div class="empty-state">
                  <div class="empty-state-icon"><?= admin_icon('mail', 24) ?></div>
                  <p class="empty-state-title">Aucune demande reçue</p>
                  <p class="empty-state-text">Les messages de contact et formulaires d'estimation apparaîtront ici.</p>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="admin-table">
                    <thead>
                      <tr>
                        <th>Prospect</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($d = $recentDemandes->fetch_assoc()): ?>
                        <tr onclick="window.location.href='<?= $b ?>/admin/demandes.php?open_type=demande&id=<?= (int)$d['id'] ?>'">
                          <td>
                            <a href="<?= $b ?>/admin/demandes.php?open_type=demande&id=<?= (int)$d['id'] ?>">
                              <strong style="color: var(--admin-text); display: block;"><?= e($d['nom']) ?></strong>
                            </a>
                            <span style="font-size: 0.78rem; color: var(--admin-text-subtle);"><?= e($d['email'] ?: ($d['telephone'] ?? '')) ?></span>
                          </td>
                          <td>
                            <span class="badge <?= ($d['type'] === 'Estimation') ? 'badge--info' : 'badge--neutral' ?>"><?= e($d['type']) ?></span>
                          </td>
                          <td style="color: var(--admin-text-muted); font-size: 0.82rem;"><?= format_date($d['date_creation']) ?></td>
                          <td><?= status_badge($d['statut'] ?? 'Nouveau') ?></td>
                          <td style="text-align: right;" onclick="event.stopPropagation()">
                            <a href="<?= $b ?>/admin/demandes.php?open_type=demande&id=<?= (int)$d['id'] ?>" class="btn-icon" title="Consulter la demande">
                              <?= admin_icon('eye', 16) ?>
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Derniers biens ajoutés -->
          <div class="card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Derniers biens ajoutés</h2>
                <p class="card-subtitle">Propriétés récentes de votre portefeuille</p>
              </div>
              <a href="<?= $b ?>/admin/biens.php" class="btn btn-outline btn-sm">Gérer les biens</a>
            </div>
            <div class="card-body" style="padding: 0;">
              <?php if (!$recentProperties || $recentProperties->num_rows === 0): ?>
                <div class="empty-state">
                  <div class="empty-state-icon"><?= admin_icon('building', 24) ?></div>
                  <p class="empty-state-title">Aucun bien enregistré</p>
                  <p class="empty-state-text">Commencez par ajouter votre première propriété au catalogue.</p>
                  <a href="<?= $b ?>/admin/bien-edit.php" class="btn btn-primary btn-sm">Ajouter un bien</a>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="admin-table">
                    <thead>
                      <tr>
                        <th>Bien</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($p = $recentProperties->fetch_assoc()):
                        $imgs = json_decode($p['images'] ?? '[]', true) ?: [];
                        $thumb = !empty($imgs[0]) ? asset_url((string)$imgs[0]) : '';
                      ?>
                        <tr onclick="window.location.href='<?= $b ?>/admin/biens.php?highlight=<?= (int)$p['id'] ?>'">
                          <td>
                            <div class="table-property-cell">
                              <?php if ($thumb !== ''): ?>
                                <img src="<?= e($thumb) ?>" alt="" class="table-thumb" loading="lazy">
                              <?php endif; ?>
                              <div>
                                <a href="<?= $b ?>/admin/bien-edit.php?id=<?= (int)$p['id'] ?>" class="table-property-title" title="<?= e($p['titre']) ?>">
                                  <?= e($p['titre']) ?>
                                </a>
                                <span class="table-property-ref"><?= e($p['reference'] ?? '') ?> · <?= e($p['commune'] ?? '') ?></span>
                              </div>
                            </div>
                          </td>
                          <td style="font-weight: 600; white-space: nowrap;">
                            <?= format_price($p['prix'], $p['periode_prix'] ?? null) ?>
                          </td>
                          <td><?= status_badge($p['statut'] ?? 'Disponible') ?></td>
                          <td style="text-align: right;" onclick="event.stopPropagation()">
                            <div class="table-actions" style="justify-content: flex-end;">
                              <a href="<?= $b ?>/client/bien.php?id=<?= (int)$p['id'] ?>" target="_blank" rel="noopener" class="btn-icon" title="Voir sur le site public">
                                <?= admin_icon('external', 15) ?>
                              </a>
                              <a href="<?= $b ?>/admin/bien-edit.php?id=<?= (int)$p['id'] ?>" class="btn-icon" title="Modifier">
                                <?= admin_icon('edit', 15) ?>
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Derniers dossiers Fiches client -->
        <div class="card" style="margin-top: 24px;">
          <div class="card-header">
            <div>
              <h2 class="card-title">Dossiers Fiches Client récents</h2>
              <p class="card-subtitle">Constitutions de dossiers officiels avec pièces d'identité et signatures électroniques</p>
            </div>
            <a href="<?= $b ?>/admin/fiches-client.php" class="btn btn-outline btn-sm">Voir toutes les fiches</a>
          </div>
          <div class="card-body" style="padding: 0;">
            <?php if (!$recentFiches || $recentFiches->num_rows === 0): ?>
              <div class="empty-state">
                <div class="empty-state-icon"><?= admin_icon('file-text', 24) ?></div>
                <p class="empty-state-title">Aucune fiche client enregistrée</p>
                <p class="empty-state-text">Les dossiers soumis depuis la page publique apparaîtront ici.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="admin-table">
                  <thead>
                    <tr>
                      <th>Date &amp; Réf</th>
                      <th>Client</th>
                      <th>Opération</th>
                      <th>Type de bien</th>
                      <th>Statut</th>
                      <th style="text-align: right;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($f = $recentFiches->fetch_assoc()): ?>
                      <tr onclick="window.location.href='<?= $b ?>/admin/fiches-client.php?id=<?= (int)$f['id'] ?>'">
                        <td>
                          <div style="font-weight: 600; color: var(--admin-text); font-size: 0.85rem;"><?= format_date($f['date_creation']) ?></div>
                          <span style="font-size: 0.76rem; font-family: monospace; color: var(--admin-gold); font-weight: 600;"><?= e($f['reference'] ?? '') ?></span>
                        </td>
                        <td>
                          <div style="font-weight: 600; color: var(--admin-text);"><?= e(trim(($f['nom'] ?? '') . ' ' . ($f['prenom'] ?? ''))) ?></div>
                          <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= e($f['telephone'] ?? '') ?><?= !empty($f['email']) ? ' · ' . e($f['email']) : '' ?></div>
                        </td>
                        <td>
                          <span class="badge <?= ($f['transaction'] === 'Offre') ? 'badge--sale' : 'badge--info' ?>"><?= e($f['transaction'] ?? 'Demande') ?></span>
                        </td>
                        <td style="font-weight: 500;"><?= e($f['type_bien'] ?: '—') ?></td>
                        <td><?= status_badge($f['statut'] ?? 'Nouveau') ?></td>
                        <td style="text-align: right;" onclick="event.stopPropagation()">
                          <a href="<?= $b ?>/admin/fiches-client.php?id=<?= (int)$f['id'] ?>" class="btn-icon" title="Consulter la fiche client">
                            <?= admin_icon('eye', 16) ?>
                          </a>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </main>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
</body>
</html>
