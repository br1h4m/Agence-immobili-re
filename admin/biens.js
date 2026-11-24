// admin/biens.js
import {
  initAdminLayout, adminIcon, formatPrice, formatSurface, formatDate,
  statusBadge, transactionBadge, showToast, confirmModal, escapeHTML, openCsvImportModal, exportRecords
} from '/admin/assets/js/admin.js';
import {
  getProperties, deleteProperty, updatePropertyStatus, bulkDeleteProperties, syncWithBackend
} from '/admin/data/admin-store.js';

let currentFilters = {
  search: '',
  transaction: '',
  type: '',
  status: '',
  sort: 'recent'
};

let isSelectMode = false;
let selectedPropertyIds = new Set();

async function init() {
  const ready = await initAdminLayout({
    activePage: 'biens',
    title: 'Biens immobiliers',
    breadcrumbs: [{ label: 'Biens' }],
    action: {
      label: 'Nouveau bien',
      href: '/admin/bien-edit',
      icon: 'plus'
    }
  });

  if (!ready) return;

  const root = document.getElementById('biens-content');
  if (!root) return;

  root.innerHTML = `
    <!-- Barre supérieure : Outils d'import et de sélection -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
      <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
        Catalogue complet des biens immobiliers, gestion des disponibilités et publication.
      </p>

      <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-import-biens" title="Importer des propriétés depuis un fichier Excel ou CSV">
          ${adminIcon('upload', { size: 14 })}
          <span>Importer CSV / Excel</span>
        </button>
        <button type="button" class="btn ${isSelectMode ? 'btn-primary' : 'btn-outline'} btn-sm" id="btn-toggle-select-mode">
          ${adminIcon('check', { size: 14 })}
          <span>${isSelectMode ? 'Annuler la sélection' : 'Sélectionner'}</span>
        </button>
      </div>
    </div>

    <!-- Conteneur de la barre d'actions groupées -->
    <div id="biens-bulk-bar-container"></div>

    <!-- Barre de recherche et filtres -->
    <div class="filters-bar" style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px;">
      <div class="search-input-wrap">
        <span class="admin-icon">${adminIcon('search', { size: 16 })}</span>
        <input type="text" id="filter-search" placeholder="Rechercher par titre, référence, ville..." value="${escapeHTML(currentFilters.search)}">
      </div>

      <div class="filters-group">
        <select class="form-select" id="filter-transaction">
          <option value="">Toutes transactions</option>
          <option value="Acheter">Vente</option>
          <option value="Louer">Location</option>
        </select>

        <select class="form-select" id="filter-type">
          <option value="">Tous les types</option>
          <option value="Villa">Villa</option>
          <option value="Appartement">Appartement</option>
          <option value="Terrain">Terrain</option>
          <option value="Bureau">Bureau</option>
          <option value="Local commercial">Local commercial</option>
          <option value="Duplex">Duplex</option>
        </select>

        <select class="form-select" id="filter-status">
          <option value="">Tous les statuts</option>
          <option value="Disponible">Disponible</option>
          <option value="Réservé">Réservé</option>
          <option value="Vendu">Vendu</option>
          <option value="Loué">Loué</option>
        </select>

        <select class="form-select" id="filter-sort">
          <option value="recent">Plus récents</option>
          <option value="price-asc">Prix croissant</option>
          <option value="price-desc">Prix décroissant</option>
          <option value="surface-asc">Surface croissante</option>
          <option value="surface-desc">Surface décroissante</option>
        </select>

        <button type="button" class="btn btn-outline btn-sm" id="btn-reset-filters" title="Réinitialiser les filtres">
          Effacer
        </button>
      </div>
    </div>

    <!-- Conteneur des résultats -->
    <div id="biens-results"></div>
  `;

  // Bouton importer
  document.getElementById('btn-import-biens')?.addEventListener('click', () => {
    openCsvImportModal({ model: 'biens', title: 'Importer des biens', onImportSuccess: () => {
      syncWithBackend().then(() => renderResults());
    } });
  });

  // Bouton mode sélection
  document.getElementById('btn-toggle-select-mode')?.addEventListener('click', () => {
    isSelectMode = !isSelectMode;
    selectedPropertyIds.clear();
    const btn = document.getElementById('btn-toggle-select-mode');
    if (btn) {
      btn.className = `btn ${isSelectMode ? 'btn-primary' : 'btn-outline'} btn-sm`;
      btn.innerHTML = `${adminIcon('check', { size: 14 })} <span>${isSelectMode ? 'Annuler la sélection' : 'Sélectionner'}</span>`;
    }
    renderResults();
  });

  bindFilterEvents();
  renderResults();
  handleUrlTarget();
}

function handleUrlTarget() {
  const params = new URLSearchParams(window.location.search);
  const targetId = params.get('id');
  if (targetId) {
    setTimeout(() => {
      const row = document.querySelector(`tr[data-property-id="${targetId}"]`);
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('row-highlight');
        setTimeout(() => row.classList.remove('row-highlight'), 3500);
      }
    }, 200);
  }
}

function bindFilterEvents() {
  const searchInput = document.getElementById('filter-search');
  const transSelect = document.getElementById('filter-transaction');
  const typeSelect = document.getElementById('filter-type');
  const statusSelect = document.getElementById('filter-status');
  const sortSelect = document.getElementById('filter-sort');
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
    currentFilters.type = e.target.value;
    renderResults();
  });

  statusSelect?.addEventListener('change', (e) => {
    currentFilters.status = e.target.value;
    renderResults();
  });

  sortSelect?.addEventListener('change', (e) => {
    currentFilters.sort = e.target.value;
    renderResults();
  });

  resetBtn?.addEventListener('click', () => {
    currentFilters = { search: '', transaction: '', type: '', status: '', sort: 'recent' };
    if (searchInput) searchInput.value = '';
    if (transSelect) transSelect.value = '';
    if (typeSelect) typeSelect.value = '';
    if (statusSelect) statusSelect.value = '';
    if (sortSelect) sortSelect.value = 'recent';
    renderResults();
  });
}

function renderBulkBar() {
  const container = document.getElementById('biens-bulk-bar-container');
  if (!container) return;

  if (!isSelectMode || selectedPropertyIds.size === 0) {
    container.innerHTML = '';
    return;
  }

  container.innerHTML = `
    <div class="bulk-actions-bar is-visible">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span class="bulk-badge">${selectedPropertyIds.size}</span>
        <span style="font-weight: 600;">bien${selectedPropertyIds.size > 1 ? 's' : ''} sélectionné${selectedPropertyIds.size > 1 ? 's' : ''}</span>
      </div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-cancel">Annuler</button>
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
          <span>Supprimer (${selectedPropertyIds.size})</span>
        </button>
      </div>
    </div>
  `;

  document.getElementById('btn-bulk-cancel')?.addEventListener('click', () => {
    selectedPropertyIds.clear();
    renderResults();
  });

  const exportSelected = (format) => {
    const selected = getProperties(currentFilters).filter((p) => selectedPropertyIds.has(String(p.id)));
    exportRecords({
      filename: `biens_immolode_${new Date().toISOString().slice(0, 10)}`,
      format,
      rows: selected,
      columns: [
        { label: 'Référence', key: 'reference' },
        { label: 'Titre', key: 'title' },
        { label: 'Type', key: 'type' },
        { label: 'Transaction', key: 'transaction' },
        { label: 'Prix (DA)', key: 'price' },
        { label: 'Wilaya', value: (r) => r.location?.wilaya || '' },
        { label: 'Commune', value: (r) => r.location?.city || '' },
        { label: 'Surface (m²)', key: 'surface' },
        { label: 'Chambres', key: 'bedrooms' },
        { label: 'Statut', key: 'status' }
      ]
    });
  };
  document.getElementById('btn-bulk-export-csv')?.addEventListener('click', () => exportSelected('csv'));
  document.getElementById('btn-bulk-export-excel')?.addEventListener('click', () => exportSelected('excel'));

  document.getElementById('btn-bulk-delete')?.addEventListener('click', async () => {
    const count = selectedPropertyIds.size;
    const ok = await confirmModal({
      title: 'Suppression groupée de biens',
      message: `Êtes-vous certain de vouloir supprimer définitivement ${count} bien${count > 1 ? 's' : ''} du catalogue et du site public ? Cette action est irréversible.`,
      confirmText: `Supprimer ${count} bien${count > 1 ? 's' : ''}`,
      cancelText: 'Annuler'
    });

    if (ok) {
      await bulkDeleteProperties(Array.from(selectedPropertyIds));
      showToast(`${count} bien${count > 1 ? 's' : ''} supprimé${count > 1 ? 's' : ''} avec succès`);
      selectedPropertyIds.clear();
      renderResults();
    }
  });
}

function renderResults() {
  renderBulkBar();
  const container = document.getElementById('biens-results');
  if (!container) return;

  const properties = getProperties(currentFilters);

  if (properties.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">${adminIcon('building', { size: 28 })}</div>
          <h3 class="empty-state-title">Aucun bien trouvé</h3>
          <p class="empty-state-text">Aucun bien ne correspond à vos critères de recherche actuels.</p>
          <a href="/admin/bien-edit" class="btn btn-primary btn-sm">Ajouter un nouveau bien</a>
        </div>
      </div>
    `;
    return;
  }

  const allChecked = properties.length > 0 && properties.every((p) => selectedPropertyIds.has(String(p.id)));

  container.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Catalogue des propriétés</h2>
          <p class="card-subtitle">${properties.length} bien${properties.length > 1 ? 's' : ''} correspondant${properties.length > 1 ? 's' : ''}</p>
        </div>
      </div>
      <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                ${isSelectMode ? `
                  <th style="width: 44px; text-align: center;">
                    <input type="checkbox" id="check-all-props" ${allChecked ? 'checked' : ''} title="Tout sélectionner">
                  </th>
                ` : ''}
                <th>Bien & Référence</th>
                <th>Type</th>
                <th>Localisation</th>
                <th>Surface</th>
                <th>Prix</th>
                <th>Statut</th>
                <th style="text-align: right; width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              ${properties.map((p) => {
                const thumb = p.images && p.images[0] ? p.images[0] : '';
                const isSelected = selectedPropertyIds.has(String(p.id));
                return `
                  <tr data-property-id="${p.id}" class="${isSelected ? 'is-selected' : ''}">
                    ${isSelectMode ? `
                      <td style="width: 44px; text-align: center;">
                        <input type="checkbox" class="prop-row-checkbox" data-id="${p.id}" ${isSelected ? 'checked' : ''}>
                      </td>
                    ` : ''}
                    <td>
                      <div class="table-property-cell">
                        ${thumb ? `<img src="${escapeHTML(thumb)}" alt="" class="table-thumb" loading="lazy">` : ''}
                        <div>
                          <a href="/admin/bien-edit?id=${p.id}" class="table-property-title" title="${escapeHTML(p.title)}">
                            ${escapeHTML(p.title)}
                          </a>
                          <span class="table-property-ref">${escapeHTML(p.reference || '')} ${p.isFeatured ? ' · <span style="color:var(--admin-gold);font-weight:600">À la une</span>' : ''}</span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style="display: flex; flex-direction: column; gap: 4px;">
                        <span>${escapeHTML(p.type)}</span>
                        <div>${transactionBadge(p.transaction)}</div>
                      </div>
                    </td>
                    <td>
                      <strong style="color: var(--admin-text); display: block;">${escapeHTML(p.location?.city || '—')}</strong>
                      <span style="font-size: 0.78rem; color: var(--admin-text-subtle);">${escapeHTML(p.location?.wilaya || '')}</span>
                    </td>
                    <td>
                      ${formatSurface(p.surface)}
                    </td>
                    <td style="font-weight: 600; white-space: nowrap;">
                      ${formatPrice(p.price, p.pricePeriod)}
                    </td>
                    <td>
                      <select class="form-select property-status-select" data-id="${p.id}" style="padding: 4px 8px; font-size: 0.78rem;">
                        <option value="Disponible" ${p.status === 'Disponible' ? 'selected' : ''}>Disponible</option>
                        <option value="Réservé" ${p.status === 'Réservé' ? 'selected' : ''}>Réservé</option>
                        <option value="Vendu" ${p.status === 'Vendu' ? 'selected' : ''}>Vendu</option>
                        <option value="Loué" ${p.status === 'Loué' ? 'selected' : ''}>Loué</option>
                      </select>
                    </td>
                    <td style="text-align: right;">
                      <div class="table-actions" style="justify-content: flex-end;">
                        <a href="/client/bien?id=${p.id}" target="_blank" rel="noopener" class="btn-icon" title="Voir sur le site public">
                          ${adminIcon('external', { size: 15 })}
                        </a>
                        <a href="/admin/bien-edit?id=${p.id}" class="btn-icon" title="Modifier">
                          ${adminIcon('edit', { size: 15 })}
                        </a>
                        <button type="button" class="btn-icon btn-icon--danger btn-delete-prop" data-id="${p.id}" title="Supprimer ce bien">
                          ${adminIcon('trash', { size: 15 })}
                        </button>
                      </div>
                    </td>
                  </tr>
                `;
              }).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;

  // Écouteur Check All
  container.querySelector('#check-all-props')?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    properties.forEach((p) => {
      if (checked) selectedPropertyIds.add(String(p.id));
      else selectedPropertyIds.delete(String(p.id));
    });
    renderResults();
  });

  // Écouteurs Checkbox individuel
  container.querySelectorAll('.prop-row-checkbox').forEach((cb) => {
    cb.addEventListener('change', (e) => {
      const id = e.target.dataset.id;
      if (e.target.checked) selectedPropertyIds.add(id);
      else selectedPropertyIds.delete(id);
      renderResults();
    });
  });

  // Clic sur ligne en mode sélection
  container.querySelectorAll('tbody tr').forEach((tr) => {
    tr.addEventListener('click', (e) => {
      if (!isSelectMode) return;
      if (e.target.closest('button') || e.target.closest('select') || e.target.closest('a') || e.target.closest('input[type="checkbox"]')) {
        return;
      }
      const id = tr.dataset.propertyId;
      if (!id) return;
      if (selectedPropertyIds.has(id)) selectedPropertyIds.delete(id);
      else selectedPropertyIds.add(id);
      renderResults();
    });
  });

  // Écouteurs pour le changement rapide de statut
  container.querySelectorAll('.property-status-select').forEach((sel) => {
    sel.addEventListener('change', (e) => {
      const id = e.target.dataset.id;
      const newStatus = e.target.value;
      if (updatePropertyStatus(id, newStatus)) {
        showToast(`Statut mis à jour : ${newStatus}`);
      } else {
        showToast('Erreur lors de la mise à jour du statut', 'error');
      }
    });
  });

  // Écouteurs pour la suppression
  container.querySelectorAll('.btn-delete-prop').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      const id = btn.dataset.id;
      const confirmed = await confirmModal({
        title: 'Supprimer ce bien ?',
        message: 'Cette action est irréversible. Le bien sera retiré du catalogue et du site public.',
        confirmText: 'Supprimer',
        cancelText: 'Annuler'
      });

      if (confirmed) {
        if (deleteProperty(id)) {
          selectedPropertyIds.delete(String(id));
          showToast('Le bien a été supprimé avec succès.');
          renderResults();
        } else {
          showToast('Erreur lors de la suppression.', 'error');
        }
      }
    });
  });
}

init();
