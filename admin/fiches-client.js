// admin/fiches-client.js
// MODULE ES NAVIGATEUR — gestion et consultation complète des fiches client (dossiers).

import {
  initAdminLayout, adminIcon, formatDateTime, formatDate,
  statusBadge, showToast, confirmModal, escapeHTML, printDocument, openCsvImportModal, exportRecords
} from '/admin/assets/js/admin.js';
import {
  getFichesClient, getFicheClientById, updateFicheClientStatus, deleteFicheClient,
  bulkDeleteFichesClient, markFicheRead, bulkMarkFichesRead, syncWithBackend
} from '/admin/data/admin-store.js';

let currentFilters = {
  search: '',
  transaction: '',
  typeBien: '',
  status: ''
};

let activeFicheId = null;
let isSelectMode = false;
let selectedFicheIds = new Set();

async function init() {
  const ready = await initAdminLayout({
    activePage: 'fiches-client',
    title: 'Fiches client & Dossiers',
    breadcrumbs: [{ label: 'Fiches client' }],
    action: {
      label: 'Nouveau dossier (public)',
      href: '/client/fiche-client',
      target: '_blank',
      icon: 'external'
    }
  });

  if (!ready) return;

  const root = document.getElementById('fiches-content');
  if (!root) return;

  root.innerHTML = `
    <!-- Statistiques KPI spécifiques aux fiches client -->
    <div id="fiches-stats" class="stats-grid" style="margin-bottom: 24px;"></div>

    <!-- Barre supérieure : Outils d'import et de sélection -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
      <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
        Dossiers officiels de constitution client avec pièces d'identité et signatures certifiées.
      </p>

      <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-import-fiches" title="Importer des fiches clients depuis un fichier CSV ou Excel">
          ${adminIcon('upload', { size: 14 })}
          <span>Importer CSV / Excel</span>
        </button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-export-all-fiches-excel" title="Exporter toutes les fiches clients en Excel (.xlsx)">
          ${adminIcon('download', { size: 14 })}
          <span>Export Excel</span>
        </button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-export-all-fiches-csv" title="Exporter toutes les fiches clients en CSV">
          ${adminIcon('download', { size: 14 })}
          <span>Export CSV</span>
        </button>
        <button type="button" class="btn ${isSelectMode ? 'btn-primary' : 'btn-outline'} btn-sm" id="btn-toggle-select-mode">
          ${adminIcon('check', { size: 14 })}
          <span>${isSelectMode ? 'Annuler la sélection' : 'Sélectionner'}</span>
        </button>
      </div>
    </div>

    <!-- Conteneur de la barre d'actions groupées -->
    <div id="fiches-bulk-bar-container"></div>

    <!-- Barre de filtres et recherche -->
    <div class="filters-bar" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px;">
      <div class="search-input-wrap">
        <span class="admin-icon">${adminIcon('search', { size: 16 })}</span>
        <input type="text" id="filter-search" placeholder="Rechercher par référence, nom, prénom, téléphone, email..." value="${escapeHTML(currentFilters.search)}">
      </div>

      <div class="filters-group">
        <select class="form-select" id="filter-transaction">
          <option value="">Toutes opérations</option>
          <option value="Demande">Demande (recherche)</option>
          <option value="Offre">Offre (mise en vente / loc)</option>
        </select>

        <select class="form-select" id="filter-type-bien">
          <option value="">Tous les types de bien</option>
          <option value="Appartement">Appartement</option>
          <option value="Villa">Villa</option>
          <option value="Commerce">Commerce</option>
          <option value="Terrain">Terrain</option>
          <option value="Dépôt">Dépôt</option>
        </select>

        <select class="form-select" id="filter-status">
          <option value="">Tous les statuts</option>
          <option value="Nouveau">Nouveau</option>
          <option value="En cours">En cours</option>
          <option value="Validé">Validé</option>
          <option value="Traité">Traité</option>
          <option value="Rejeté">Rejeté</option>
        </select>

        <button type="button" class="btn btn-outline btn-sm" id="btn-reset-filters" title="Réinitialiser les filtres">
          Effacer
        </button>
      </div>
    </div>

    <!-- Conteneur des résultats -->
    <div id="fiches-results"></div>
  `;

  // Importer
  document.getElementById('btn-import-fiches')?.addEventListener('click', () => {
    openCsvImportModal({ model: 'fiches_client', title: 'Importer des fiches client', onImportSuccess: () => {
      syncWithBackend().then(() => {
        window._syncBadgesNow?.();
        renderStats();
        renderResults();
      });
    } });
  });

  // Export complet
  document.getElementById('btn-export-all-fiches-excel')?.addEventListener('click', () => {
    window.location.href = '/api/export.php?model=fiches_client&format=xlsx';
  });
  document.getElementById('btn-export-all-fiches-csv')?.addEventListener('click', () => {
    window.location.href = '/api/export.php?model=fiches_client&format=csv';
  });

  // Toggle mode sélection
  document.getElementById('btn-toggle-select-mode')?.addEventListener('click', () => {
    isSelectMode = !isSelectMode;
    selectedFicheIds.clear();
    const btn = document.getElementById('btn-toggle-select-mode');
    if (btn) {
      btn.className = `btn ${isSelectMode ? 'btn-primary' : 'btn-outline'} btn-sm`;
      btn.innerHTML = `${adminIcon('check', { size: 14 })} <span>${isSelectMode ? 'Annuler la sélection' : 'Sélectionner'}</span>`;
    }
    renderResults();
  });

  bindFilterEvents();
  setupDrawerListeners();
  renderStats();
  renderResults();
  handleUrlTarget();
}

function handleUrlTarget() {
  const params = new URLSearchParams(window.location.search);
  const targetId = params.get('id');
  if (targetId) {
    setTimeout(() => {
      const row = document.querySelector(`tr[data-id="${targetId}"]`);
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('row-highlight');
        setTimeout(() => row.classList.remove('row-highlight'), 3500);
      }
      openDrawer(targetId);
    }, 200);
  }
}

function renderStats() {
  const statsContainer = document.getElementById('fiches-stats');
  if (!statsContainer) return;

  const all = getFichesClient();
  const total = all.length;
  const pending = all.filter((f) => f.status === 'Nouveau' || f.status === 'En cours').length;
  const demandes = all.filter((f) => f.transaction === 'Demande').length;
  const offres = all.filter((f) => f.transaction === 'Offre').length;

  statsContainer.innerHTML = `
    <div class="stat-card">
      <div class="stat-icon stat-icon--gold">
        ${adminIcon('file-text', { size: 24 })}
      </div>
      <div class="stat-info">
        <p class="stat-value">${total}</p>
        <p class="stat-label">Total dossiers constitués</p>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon ${pending > 0 ? 'stat-icon--gold' : 'stat-icon--blue'}">
        ${adminIcon('check', { size: 24 })}
      </div>
      <div class="stat-info">
        <p class="stat-value">${pending}</p>
        <p class="stat-label">Dossiers à traiter / en cours</p>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon stat-icon--blue">
        ${adminIcon('search', { size: 24 })}
      </div>
      <div class="stat-info">
        <p class="stat-value">${demandes}</p>
        <p class="stat-label">Dossiers « Demande »</p>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon stat-icon--purple">
        ${adminIcon('building', { size: 24 })}
      </div>
      <div class="stat-info">
        <p class="stat-value">${offres}</p>
        <p class="stat-label">Dossiers « Offre »</p>
      </div>
    </div>
  `;
}

function bindFilterEvents() {
  const searchInput = document.getElementById('filter-search');
  const transSelect = document.getElementById('filter-transaction');
  const typeSelect = document.getElementById('filter-type-bien');
  const statusSelect = document.getElementById('filter-status');
  const resetBtn = document.getElementById('btn-reset-filters');

  let debounceTimer;
  searchInput?.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      currentFilters.search = e.target.value;
      renderResults();
    }, 250);
  });

  transSelect?.addEventListener('change', (e) => {
    currentFilters.transaction = e.target.value;
    renderResults();
  });

  typeSelect?.addEventListener('change', (e) => {
    currentFilters.typeBien = e.target.value;
    renderResults();
  });

  statusSelect?.addEventListener('change', (e) => {
    currentFilters.status = e.target.value;
    renderResults();
  });

  resetBtn?.addEventListener('click', () => {
    currentFilters = { search: '', transaction: '', typeBien: '', status: '' };
    if (searchInput) searchInput.value = '';
    if (transSelect) transSelect.value = '';
    if (typeSelect) typeSelect.value = '';
    if (statusSelect) statusSelect.value = '';
    renderResults();
  });
}

function renderBulkBar() {
  const container = document.getElementById('fiches-bulk-bar-container');
  if (!container) return;

  if (!isSelectMode || selectedFicheIds.size === 0) {
    container.innerHTML = '';
    return;
  }

  container.innerHTML = `
    <div class="bulk-actions-bar is-visible">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span class="bulk-badge">${selectedFicheIds.size}</span>
        <span style="font-weight: 600;">dossier${selectedFicheIds.size > 1 ? 's' : ''} sélectionné${selectedFicheIds.size > 1 ? 's' : ''}</span>
      </div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-cancel">Annuler</button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-mark-read">
          ${adminIcon('check', { size: 14 })}
          <span>Marquer lu (${selectedFicheIds.size})</span>
        </button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-export-csv">
          ${adminIcon('download', { size: 14 })}
          <span>CSV</span>
        </button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-export-excel">
          ${adminIcon('download', { size: 14 })}
          <span>Excel</span>
        </button>
        <button type="button" class="btn btn-icon--danger btn-sm" id="btn-bulk-delete" style="background: var(--admin-danger); color: #fff; border: none; padding: 6px 14px; border-radius: var(--radius-xs); cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
          ${adminIcon('trash', { size: 14 })}
          <span>Supprimer (${selectedFicheIds.size})</span>
        </button>
      </div>
    </div>
  `;

  document.getElementById('btn-bulk-cancel')?.addEventListener('click', () => {
    selectedFicheIds.clear();
    renderResults();
  });

  const exportSelected = (format) => {
    const ids = Array.from(selectedFicheIds);
    const selected = getFichesClient(currentFilters).filter((f) => selectedFicheIds.has(String(f.id)));
    exportRecords({
      filename: `fiches_client_selection_${new Date().toISOString().slice(0, 10)}`,
      format,
      model: 'fiches_client',
      ids,
      rows: selected,
      columns: [
        { label: 'Référence', key: 'reference' },
        { label: 'Date', value: (r) => formatDateTime(r.createdAt) },
        { label: 'Nom', key: 'nom' },
        { label: 'Prénom', key: 'prenom' },
        { label: 'Téléphone', key: 'telephone' },
        { label: 'Email', key: 'email' },
        { label: 'Opération', key: 'transaction' },
        { label: 'Type de bien', key: 'typeBien' },
        { label: 'Statut', key: 'status' }
      ]
    });
  };
  document.getElementById('btn-bulk-export-csv')?.addEventListener('click', () => exportSelected('csv'));
  document.getElementById('btn-bulk-export-excel')?.addEventListener('click', () => exportSelected('excel'));

  document.getElementById('btn-bulk-mark-read')?.addEventListener('click', async () => {
    const ids = Array.from(selectedFicheIds);
    await bulkMarkFichesRead(ids, 1);
    showToast(`${ids.length} dossier(s) marqué(s) comme lu(s)`);
    selectedFicheIds.clear();
    renderResults();
  });

  document.getElementById('btn-bulk-delete')?.addEventListener('click', async () => {
    const count = selectedFicheIds.size;
    const ok = await confirmModal({
      title: 'Suppression groupée de fiches clients',
      message: `Êtes-vous certain de vouloir supprimer définitivement ces ${count} dossier${count > 1 ? 's' : ''} ? Cette action est irréversible.`,
      confirmText: `Supprimer ${count} dossier${count > 1 ? 's' : ''}`,
      cancelText: 'Annuler'
    });

    if (ok) {
      await bulkDeleteFichesClient(Array.from(selectedFicheIds));
      showToast(`${count} dossier${count > 1 ? 's' : ''} supprimé${count > 1 ? 's' : ''}`);
      selectedFicheIds.clear();
      renderStats();
      renderResults();
    }
  });
}

function renderResults() {
  renderBulkBar();
  const container = document.getElementById('fiches-results');
  if (!container) return;

  const list = getFichesClient(currentFilters);

  if (list.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">
            ${adminIcon('file-text', { size: 28 })}
          </div>
          <h3 class="empty-state-title">Aucune fiche client trouvée</h3>
          <p class="empty-state-text">
            Aucun dossier ne correspond à vos filtres actuels.
          </p>
        </div>
      </div>
    `;
    return;
  }

  const allChecked = list.length > 0 && list.every((f) => selectedFicheIds.has(String(f.id)));

  const rows = list.map((item) => {
    const isOffre = item.transaction === 'Offre';
    const isUnread = item.is_read === 0 || item.is_read === '0' || item.is_read === false;
    const isSelected = selectedFicheIds.has(String(item.id));

    const transBadge = isOffre
      ? `<span class="badge badge--sale">${adminIcon('building', { size: 12 })} Offre (Vente/Loc)</span>`
      : `<span class="badge badge--info">${adminIcon('search', { size: 12 })} Demande</span>`;

    const hasDoc = Boolean(item.pieceIdentite?.name);
    const hasSig = Boolean(item.signature);
    const docTags = [];
    if (hasDoc) {
      docTags.push(`<span class="doc-badge" title="Justificatif : ${escapeHTML(item.pieceIdentite.name)}">${adminIcon('file-text', { size: 11 })} CNI</span>`);
    }
    if (hasSig) {
      docTags.push(`<span class="badge badge--success" style="font-size: 0.7rem; padding: 2px 6px;">${adminIcon('check', { size: 10 })} Signé</span>`);
    }

    return `
      <tr data-id="${item.id}" class="${isSelected ? 'is-selected' : ''} ${isUnread ? 'is-unread' : ''}" style="${isUnread ? 'font-weight: 600;' : ''}" title="Cliquer pour consulter le dossier complet">
        ${isSelectMode ? `
          <td style="width: 44px; text-align: center;">
            <input type="checkbox" class="fiche-row-checkbox" data-id="${item.id}" ${isSelected ? 'checked' : ''}>
          </td>
        ` : ''}
        <td>
          <div style="display: flex; align-items: center; gap: 8px;">
            ${isUnread ? `<span class="unread-dot" title="Nouveau / Non lu" style="width: 8px; height: 8px; border-radius: 50%; background: var(--admin-gold); display: inline-block; flex-shrink: 0;"></span>` : ''}
            <div>
              <div style="font-weight: 600; color: var(--admin-gold); font-family: monospace; font-size: 0.92rem;">
                ${escapeHTML(item.reference || `IML-DOS-${item.id}`)}
              </div>
              <div style="font-size: 0.75rem; color: var(--admin-text-subtle);">
                ${formatDate(item.createdAt)}
              </div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML(`${item.nom || ''} ${item.prenom || ''}`.trim() || 'Client sans nom')}</div>
          <div style="font-size: 0.78rem; color: var(--admin-text-muted); display: flex; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
            ${item.email ? `<a href="mailto:${escapeHTML(item.email)}" style="color: var(--admin-gold);">${escapeHTML(item.email)}</a>` : ''}
            ${item.telephone ? `<span>•</span><a href="tel:${escapeHTML(item.telephone)}">${escapeHTML(item.telephone)}</a>` : ''}
          </div>
        </td>
        <td>
          ${transBadge}
          <div style="font-size: 0.8rem; color: var(--admin-text-subtle); margin-top: 4px;">
            ${escapeHTML(item.typeBien || 'Appartement')}
          </div>
        </td>
        <td>
          <div style="display: flex; gap: 4px; flex-direction: column;">
            ${docTags.length ? docTags.join('') : '<span style="font-size:0.75rem; color:var(--admin-text-muted);">Sans pièce</span>'}
          </div>
        </td>
        <td>
          <select class="form-select select-status-quick" data-id="${item.id}" style="padding: 4px 8px; font-size: 0.8rem; border-radius: var(--radius-xs);">
            <option value="Nouveau" ${item.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
            <option value="En cours" ${item.status === 'En cours' ? 'selected' : ''}>En cours</option>
            <option value="Validé" ${item.status === 'Validé' ? 'selected' : ''}>Validé</option>
            <option value="Traité" ${item.status === 'Traité' || item.status === 'Traite' ? 'selected' : ''}>Traité</option>
            <option value="Rejeté" ${item.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
          </select>
        </td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon btn-toggle-read" data-id="${item.id}" data-read="${isUnread ? '0' : '1'}" title="${isUnread ? 'Marquer comme lu' : 'Marquer comme non lu'}">
              ${adminIcon(isUnread ? 'check' : 'mail', { size: 15 })}
            </button>
            <button type="button" class="btn-icon btn-view-fiche" data-id="${item.id}" title="Consulter la fiche client" aria-label="Voir la fiche">
              ${adminIcon('eye', { size: 16 })}
            </button>
            <button type="button" class="btn-icon btn-icon--danger btn-delete-fiche" data-id="${item.id}" title="Supprimer la fiche" aria-label="Supprimer">
              ${adminIcon('trash', { size: 16 })}
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  container.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Fiches client enregistrées (${list.length})</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              ${isSelectMode ? `
                <th style="width: 44px; text-align: center;">
                  <input type="checkbox" id="check-all-fiches" ${allChecked ? 'checked' : ''} title="Tout sélectionner">
                </th>
              ` : ''}
              <th>Référence & Date</th>
              <th>Client</th>
              <th>Opération & Bien</th>
              <th>Pièces & Signature</th>
              <th>Statut</th>
              <th style="width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </div>
    </div>
  `;

  bindTableEvents(container);
}

function bindTableEvents(container) {
  // Checkbox tout sélectionner
  container.querySelector('#check-all-fiches')?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    const list = getFichesClient(currentFilters);
    list.forEach((item) => {
      if (checked) selectedFicheIds.add(String(item.id));
      else selectedFicheIds.delete(String(item.id));
    });
    renderResults();
  });

  // Checkbox individuel
  container.querySelectorAll('.fiche-row-checkbox').forEach((cb) => {
    cb.addEventListener('change', (e) => {
      const id = e.target.dataset.id;
      if (e.target.checked) selectedFicheIds.add(id);
      else selectedFicheIds.delete(id);
      renderResults();
    });
  });

  // Toggle lecture rapide
  container.querySelectorAll('.btn-toggle-read').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;
      const currentRead = btn.dataset.read === '1';
      const newRead = currentRead ? 0 : 1;
      await markFicheRead(id, newRead);
      showToast(newRead ? 'Fiche marquée comme lue' : 'Fiche marquée comme non lue');
      renderResults();
    });
  });

  // Ligne de tableau cliquable (sélection en mode sélection, sinon ouverture dossier)
  container.querySelectorAll('tbody tr').forEach((tr) => {
    tr.addEventListener('click', (e) => {
      if (e.target.closest('button') || e.target.closest('select') || e.target.closest('a') || e.target.closest('input[type="checkbox"]')) {
        return;
      }
      const id = String(tr.dataset.id);
      if (isSelectMode) {
        if (selectedFicheIds.has(id)) selectedFicheIds.delete(id);
        else selectedFicheIds.add(id);
        renderResults();
        return;
      }
      openDrawer(id);
    });
  });

  // Changement rapide de statut
  container.querySelectorAll('.select-status-quick').forEach((sel) => {
    sel.addEventListener('change', (e) => {
      const id = e.target.dataset.id;
      const newStatus = e.target.value;
      if (updateFicheClientStatus(id, newStatus)) {
        showToast(`Statut mis à jour : ${newStatus}`);
        renderStats();
      }
    });
  });

  // Bouton voir
  container.querySelectorAll('.btn-view-fiche').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      openDrawer(btn.dataset.id);
    });
  });

  // Bouton supprimer
  container.querySelectorAll('.btn-delete-fiche').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;
      const ok = await confirmModal({
        title: 'Supprimer la fiche client',
        message: 'Êtes-vous certain de vouloir supprimer cette fiche client ? Cette action est irréversible.',
        confirmText: 'Supprimer'
      });
      if (ok) {
        deleteFicheClient(id);
        selectedFicheIds.delete(String(id));
        showToast('Fiche client supprimée');
        renderStats();
        renderResults();
      }
    });
  });
}

function setupDrawerListeners() {
  const backdrop = document.getElementById('drawer-backdrop');
  backdrop?.addEventListener('click', closeDrawer);
}

function openDrawer(id) {
  const fiche = getFicheClientById(id);
  if (!fiche) return;

  activeFicheId = id;

  // Marquer comme lu à l'ouverture
  markFicheRead(id, 1).then(() => {
    fiche.is_read = 1;
    renderResults();
  });

  const drawer = document.getElementById('fiche-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  if (!drawer) return;

  const doc = fiche.pieceIdentite || {};
  // Support both dataUrl (uploaded via browser) and filename (stored in DB as /uploads/documents/)
  const docSrc = doc.dataUrl || (doc.name ? '/uploads/documents/' + doc.name : null);
  const docName = doc.name || '';
  const isPdf = docName.toLowerCase().endsWith('.pdf');
  const isImageDoc = docSrc && !isPdf;

  drawer.innerHTML = `
    <div class="drawer-header">
      <div>
        <h3 class="drawer-title">Fiche client : ${escapeHTML(fiche.reference || `IML-DOS-${fiche.id}`)}</h3>
        <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">
          Constituée le ${formatDateTime(fiche.createdAt)}
        </p>
      </div>
      <button type="button" class="btn-icon" id="btn-close-drawer" aria-label="Fermer">
        ${adminIcon('close', { size: 18 })}
      </button>
    </div>

    <div class="drawer-body">
      <!-- Identité Client -->
      <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid var(--admin-border-subtle);">
        <div class="user-avatar" style="width: 52px; height: 52px; font-size: 1.2rem; background-color: var(--admin-gold); color: #FFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
          ${escapeHTML(fiche.nom?.charAt(0) || 'C')}
        </div>
        <div style="flex: 1;">
          <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--admin-text);">${escapeHTML(`${fiche.nom || ''} ${fiche.prenom || ''}`.trim() || 'Client')}</h4>
          <div style="display: flex; gap: 12px; margin-top: 4px; font-size: 0.85rem; flex-wrap: wrap;">
            ${fiche.email ? `<a href="mailto:${escapeHTML(fiche.email)}" style="color: var(--admin-gold); font-weight: 500;">✉ ${escapeHTML(fiche.email)}</a>` : ''}
            ${fiche.telephone ? `<span>•</span><a href="tel:${escapeHTML(fiche.telephone)}" style="color: var(--admin-text-muted); font-weight: 500;">📞 ${escapeHTML(fiche.telephone)}</a>` : ''}
          </div>
        </div>
      </div>

      <!-- Statut de traitement -->
      <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--admin-surface-alt); border-radius: var(--radius-sm);">
        <span style="font-weight: 600; font-size: 0.86rem; color: var(--admin-text);">Statut du dossier :</span>
        <select class="form-select" id="drawer-status-select" style="padding: 6px 12px; font-weight: 600;">
          <option value="Nouveau" ${fiche.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
          <option value="En cours" ${fiche.status === 'En cours' ? 'selected' : ''}>En cours</option>
          <option value="Validé" ${fiche.status === 'Validé' ? 'selected' : ''}>Validé</option>
          <option value="Traité" ${fiche.status === 'Traité' || fiche.status === 'Traite' ? 'selected' : ''}>Traité</option>
          <option value="Rejeté" ${fiche.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
        </select>
      </div>

      <!-- Objet & Nature de l'opération -->
      <div style="margin-bottom: 24px; padding: 18px; background-color: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
        <h4 style="margin: 0 0 14px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('building', { size: 16 })} Objet de la démarche
        </h4>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.88rem;">
          <div><span style="color: var(--admin-text-muted);">Nature de l'opération :</span> <strong>${escapeHTML(fiche.transaction || 'Demande')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Type de bien :</span> <strong>${escapeHTML(fiche.typeBien || 'Appartement')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Numéro de dossier :</span> <strong>${escapeHTML(fiche.reference || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Date de constitution :</span> <strong>${formatDate(fiche.createdAt)}</strong></div>
        </div>
      </div>

      <!-- Pièce d'identité jointe -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('file-text', { size: 16 })} Pièce d'identité officielle
        </h4>

        ${doc.name ? `
          <div class="doc-inspect-card">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
              <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                ${adminIcon('file-text', { size: 24, className: 'text-gold' })}
                <div>
                  <p style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--admin-text);">${escapeHTML(doc.name)}</p>
                  <p style="margin: 2px 0 0; font-size: 0.78rem; color: var(--admin-text-muted);">
                    ${doc.size ? `${(doc.size / 1024).toFixed(1)} Ko` : 'Document validé'}
                  </p>
                </div>
              </div>
              <div style="display: flex; gap: 8px;">
                ${isImageDoc ? `
                  <button type="button" class="btn btn-outline btn-sm btn-zoom-doc" data-img="${escapeHTML(docSrc)}" data-caption="Pièce d'identité — ${escapeHTML(`${fiche.nom || ''} ${fiche.prenom || ''}`)}">
                    ${adminIcon('eye', { size: 14 })} Agrandir
                  </button>
                ` : (docSrc ? `
                  <a href="${escapeHTML(docSrc)}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                    ${adminIcon('download', { size: 14 })} Télécharger
                  </a>
                ` : `
                  <span class="badge badge--success">${adminIcon('check', { size: 12 })} Conforme</span>
                `)}
              </div>
            </div>

            ${isImageDoc ? `
              <div class="doc-inspect-preview btn-zoom-doc" data-img="${escapeHTML(docSrc)}" data-caption="Pièce d'identité — ${escapeHTML(`${fiche.nom || ''} ${fiche.prenom || ''}`)}" title="Cliquez pour zoomer">
                <img src="${escapeHTML(docSrc)}" alt="Pièce d'identité" class="doc-inspect-img">
                <div class="doc-inspect-overlay">
                  ${adminIcon('search', { size: 18 })}
                  <span>Cliquer pour inspecter en plein écran</span>
                </div>
              </div>
            ` : ''}
          </div>
        ` : `
          <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Aucune pièce d'identité enregistrée.</p>
        `}
      </div>

      <!-- Signature manuscrite -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('check', { size: 16 })} Engagement officiel & Signature électronique
        </h4>
        <div style="padding: 12px 14px; background-color: rgba(181, 154, 106, 0.08); border-left: 3px solid var(--admin-gold); border-radius: var(--radius-xs); font-size: 0.84rem; line-height: 1.5; color: var(--admin-text); margin-bottom: 10px;">
          « Ceci constitue un engagement officiel. En cas de transaction, le client est tenu d'honorer les frais de l'agence conformément aux dispositions réglementaires en vigueur. »
        </div>

        <div style="background: #FFFFFF; border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
          ${fiche.signature ? `
            <img src="${escapeHTML(fiche.signature)}" alt="Signature électronique du client" style="max-height: 110px; max-width: 100%; display: inline-block; object-fit: contain;">
            <p style="margin: 8px 0 0; font-size: 0.75rem; color: var(--admin-text-muted);">
              Signature électronique manuscrite certifiée — Recueillie le ${formatDateTime(fiche.createdAt)}
            </p>
          ` : `
            <p style="margin: 0; font-size: 0.85rem; color: var(--admin-text-muted);">Signature non disponible.</p>
          `}
        </div>
      </div>
    </div>

    <div class="drawer-footer">
      <button type="button" class="btn btn-danger-outline btn-sm" id="drawer-btn-delete">
        ${adminIcon('trash', { size: 14 })} Supprimer
      </button>

      <div style="display: flex; gap: 8px;">
        <button type="button" class="btn btn-outline btn-sm" id="drawer-btn-print" title="Imprimer le dossier proprement (A4)">
          ${adminIcon('printer', { size: 14 })} Imprimer
        </button>
        ${fiche.telephone ? `
          <a href="tel:${escapeHTML(fiche.telephone)}" class="btn btn-outline btn-sm">
            ${adminIcon('phone', { size: 14 })} Appeler
          </a>
        ` : ''}
        ${fiche.email ? `
          <a href="mailto:${escapeHTML(fiche.email)}?subject=Suivi de votre dossier ${encodeURIComponent(fiche.reference || '')} — Agence Imolode" class="btn btn-primary btn-sm">
            ${adminIcon('mail', { size: 14 })} Répondre
          </a>
        ` : ''}
      </div>
    </div>
  `;

  drawer.classList.add('is-open');
  backdrop?.classList.add('is-open');

  document.getElementById('btn-close-drawer')?.addEventListener('click', closeDrawer);

  document.getElementById('drawer-status-select')?.addEventListener('change', (e) => {
    const newStatus = e.target.value;
    updateFicheClientStatus(id, newStatus);
    showToast(`Statut du dossier mis à jour : ${newStatus}`);
    renderStats();
    renderResults();
  });

  // IMPRESSION PROFESSIONNELLE DÉDIÉE (Sans duplication, sans coupure d'écran)
  document.getElementById('drawer-btn-print')?.addEventListener('click', () => {
    const fresh = getFicheClientById(id) || fiche;
    printDocument({
      title: 'Dossier officiel de constitution client',
      type: `Fiche client (${fresh.transaction || 'Demande'})`,
      reference: fresh.reference || `IML-DOS-${fresh.id}`,
      date: formatDateTime(fresh.createdAt),
      status: fresh.status,
      client: {
        nom: `${fresh.nom || ''} ${fresh.prenom || ''}`.trim(),
        telephone: fresh.telephone,
        email: fresh.email
      },
      details: [
        ['Numéro de dossier', fresh.reference || `IML-DOS-${fresh.id}`],
        ['Nature de l\'opération', fresh.transaction || 'Demande'],
        ['Type de bien recherché', fresh.typeBien || 'Appartement'],
        ['Engagement d\'honoraires', 'Accepté et certifié électroniquement'],
        ['Date de constitution', formatDateTime(fresh.createdAt)]
      ],
      message: 'Le client certifie sur l\'honneur l\'exactitude des informations transmises pour la constitution de son dossier immobilier.',
      signature: fresh.signature,
      attachment: fresh.pieceIdentite
    });
  });

  document.getElementById('drawer-btn-delete')?.addEventListener('click', async () => {
    const ok = await confirmModal({
      title: 'Supprimer la fiche client',
      message: 'Êtes-vous certain de vouloir supprimer définitivement ce dossier client ?',
      confirmText: 'Supprimer'
    });
    if (ok) {
      deleteFicheClient(id);
      selectedFicheIds.delete(String(id));
      showToast('Dossier client supprimé');
      closeDrawer();
      renderStats();
      renderResults();
    }
  });

  // Lightbox
  drawer.querySelectorAll('.btn-zoom-doc').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const img = btn.dataset.img;
      const caption = btn.dataset.caption;
      if (img) openLightbox(img, caption);
    });
  });
}

function openLightbox(imgUrl, captionText) {
  const lightbox = document.getElementById('image-lightbox');
  const lbImg = document.getElementById('lightbox-img');
  const lbCaption = document.getElementById('lightbox-caption');
  const lbClose = document.getElementById('lightbox-close');

  if (!lightbox || !lbImg) return;

  lbImg.src = imgUrl;
  if (lbCaption) {
    lbCaption.innerHTML = `
      <span>${escapeHTML(captionText || "Pièce d'identité officielle")}</span>
      <a href="${escapeHTML(imgUrl)}" download class="btn btn-sm" style="background: rgba(255,255,255,0.16); color:#FFF; border: 1px solid rgba(255,255,255,0.45);">
        ${adminIcon('download', { size: 14 })} Télécharger
      </a>
    `;
  }

  lightbox.hidden = false;

  const closeLb = () => {
    lightbox.hidden = true;
    lbImg.src = '';
  };

  lbClose.onclick = closeLb;
  lightbox.onclick = (e) => {
    if (e.target === lightbox || e.target === lbClose) {
      closeLb();
    }
  };
  window.onkeydown = (e) => {
    if (e.key === 'Escape') closeLb();
  };
}

function closeDrawer() {
  const drawer = document.getElementById('fiche-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  drawer?.classList.remove('is-open');
  backdrop?.classList.remove('is-open');
  activeFicheId = null;
}

document.addEventListener('DOMContentLoaded', init);
