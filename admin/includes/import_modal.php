<?php
/**
 * admin/includes/import_modal.php - Modale statique d'import CSV / Excel (HTML brut + PHP)
 * Variables attendues : $importModalTitle, $importActionUrl
 */
declare(strict_types=1);
$importModalTitle = $importModalTitle ?? 'Importer CSV / Excel';
$importActionUrl = $importActionUrl ?? ($_SERVER['PHP_SELF'] ?? '');
?>
<div class="admin-modal-overlay" id="admin-import-modal">
  <div class="admin-modal-card" style="max-width: 540px; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 class="admin-modal-title" style="margin: 0;"><?= e($importModalTitle) ?></h3>
      <button type="button" class="btn-icon" id="import-modal-close" aria-label="Fermer" style="border: 0; background: transparent;">
        <?= admin_icon('close', 20) ?>
      </button>
    </div>

    <p style="color: var(--admin-text-muted); font-size: 0.9rem; margin-bottom: 20px;">
      Sélectionnez un fichier CSV ou Excel (.xlsx / .xls) pour ajouter automatiquement plusieurs enregistrements dans la base de données.
    </p>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px 14px; background: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); flex-wrap: wrap; gap: 10px;">
      <div>
        <strong style="font-size: 0.88rem; display: block;">Modèle type de fichier</strong>
        <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Téléchargez la structure attendue avec les colonnes correctes.</span>
      </div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="<?= e($importActionUrl) ?>?template=xlsx" class="btn btn-outline btn-sm">
          <?= admin_icon('download', 14) ?> Modèle Excel (.xlsx)
        </a>
        <a href="<?= e($importActionUrl) ?>?template=csv" class="btn btn-outline btn-sm">
          <?= admin_icon('download', 14) ?> Modèle CSV
        </a>
      </div>
    </div>

    <form method="POST" action="<?= e($importActionUrl) ?>" enctype="multipart/form-data">
      <input type="hidden" name="action" value="import_file">

      <div style="border: 2px dashed var(--admin-border); border-radius: var(--radius-md); padding: 24px; text-align: center; margin-bottom: 20px; background: var(--admin-surface-alt); cursor: pointer;" id="import-dropzone">
        <input type="file" name="import_file" id="import-file-input" accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" style="display:none;" required>
        <div style="color: var(--admin-gold); margin-bottom: 8px;">
          <?= admin_icon('upload', 32) ?>
        </div>
        <p style="margin: 0 0 4px; font-weight: 600; font-size: 0.92rem;">Cliquez pour sélectionner votre fichier CSV ou Excel</p>
        <span style="font-size: 0.8rem; color: var(--admin-text-muted);" id="import-file-name">Fichiers acceptés : .xlsx, .xls, .csv (UTF-8)</span>
      </div>

      <div class="admin-modal-actions">
        <button type="button" class="btn btn-outline" id="import-cancel-btn">Annuler</button>
        <button type="submit" class="btn btn-primary" id="import-submit-btn" disabled>
          <?= admin_icon('upload', 15) ?> <span>Lancer l'importation</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  (function() {
    var modal = document.getElementById('admin-import-modal');
    var openBtn = document.getElementById('btn-open-import');
    var closeBtn = document.getElementById('import-modal-close');
    var cancelBtn = document.getElementById('import-cancel-btn');
    var dropzone = document.getElementById('import-dropzone');
    var fileInput = document.getElementById('import-file-input');
    var fileNameSpan = document.getElementById('import-file-name');
    var submitBtn = document.getElementById('import-submit-btn');

    function openM() { modal?.classList.add('is-active'); }
    function closeM() { modal?.classList.remove('is-active'); }

    openBtn?.addEventListener('click', openM);
    closeBtn?.addEventListener('click', closeM);
    cancelBtn?.addEventListener('click', closeM);
    modal?.addEventListener('click', function(e) { if (e.target === modal) closeM(); });

    dropzone?.addEventListener('click', function() { fileInput?.click(); });
    fileInput?.addEventListener('change', function(e) {
      var f = e.target.files && e.target.files[0];
      if (f) {
        if (fileNameSpan) fileNameSpan.innerHTML = 'Fichier prêt : <strong>' + f.name.replace(/</g, '&lt;') + '</strong> (' + (f.size / 1024).toFixed(1) + ' Ko)';
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  })();
</script>
