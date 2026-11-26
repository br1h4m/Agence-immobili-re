<?php
/**
 * client/includes/card.php - Carte d'annonce immobilière en HTML brut + PHP
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

function render_property_card(array $p): void {
    $b = defined('BASE_URL') ? BASE_URL : '/immolode-php';
    $id = (int)$p['id'];
    $titre = $p['titre'] ?? 'Bien immobilier';
    $url = "{$b}/client/bien.php?id={$id}";
    $trans = ($p['transaction'] ?? '') === 'Louer' ? 'À louer' : 'À vendre';

    $images = json_decode($p['images'] ?? '[]', true) ?: [];
    $rawCover = !empty($images[0]) ? asset_url((string)$images[0]) : 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=75';

    if (str_contains($rawCover, 'images.unsplash.com')) {
        $cover = preg_replace('/w=\d+/', 'w=600', $rawCover);
        $cover = preg_replace('/q=\d+/', 'q=75', (string)$cover);
    } else {
        $cover = $rawCover;
    }

    $loc = e($p['commune'] ?? '');
    if (!empty($p['quartier'])) {
        $loc .= ' · ' . e($p['quartier']);
    }
    if (!empty($p['wilaya']) && $p['wilaya'] !== $p['commune']) {
        $loc .= ' (' . e($p['wilaya']) . ')';
    }

    $prix = format_price($p['prix'], $p['periode_prix'] ?? null);

    $specs = [];
    if (!empty($p['surface'])) {
        $specs[] = number_format((float)$p['surface'], 0, ',', ' ') . ' m²';
    }
    if (!empty($p['chambres'])) {
        $specs[] = (int)$p['chambres'] . ' ch.';
    }
    if (!empty($p['salles_de_bain'])) {
        $specs[] = (int)$p['salles_de_bain'] . ' sdb';
    }
    ?>
    <article class="property-card" data-property-id="<?= $id ?>">
      <a href="<?= e($url) ?>" class="property-card-media" aria-label="<?= e($titre) ?>">
        <img src="<?= e($cover) ?>" alt="<?= e($titre) ?>" loading="lazy" decoding="async">
        <div class="property-card-badges">
          <span class="badge badge-transaction"><?= $trans ?></span>
          <?php if (!empty($p['nouveau'])): ?>
            <span class="badge badge-new">Nouveau</span>
          <?php endif; ?>
          <?php if (!empty($p['statut']) && $p['statut'] !== 'Disponible' && $p['statut'] !== 'Nouveau'): ?>
            <span class="badge badge-status"><?= e($p['statut']) ?></span>
          <?php endif; ?>
        </div>
      </a>

      <div class="property-card-body">
        <h3 class="property-card-title">
          <a href="<?= e($url) ?>"><?= e($titre) ?></a>
        </h3>
        <p class="property-card-location">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
          <span><?= $loc ?></span>
        </p>
        <p class="property-card-price"><?= $prix ?></p>
        <?php if (!empty($specs)): ?>
          <ul class="property-card-specs">
            <?php foreach ($specs as $spec): ?>
              <li><?= e($spec) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <a href="<?= e($url) ?>" class="property-card-link">
        <span>Voir le bien</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </a>
    </article>
    <?php
}
