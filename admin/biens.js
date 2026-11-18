// admin/biens.js
import {
  initAdminLayout, adminIcon, formatPrice, formatSurface, formatDate,
  statusBadge, transactionBadge, showToast, confirmModal, openCsvImportModal, escapeHTML
} from '/admin/assets/js/admin.js';
import { getProperties, deleteProperty, updatePropertyStatus } from '/admin/data/admin-store.js';

let currentFilters = {
  search: '',
  transaction: '',
  type: '',
  status: '',
  sort: 'recent'
};

let selectedPropIds = new Set();

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
    <!-- Barre supérieure d'outils et Import CSV -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
      <div>
        <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
          Gérez votre catalogue de biens immobiliers (ventes et locations).
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-import-biens">
          ${adminIcon('upload', { size: 15 })}
          <span>Importer CSV / Excel</span>
        </button>
        <a href="/admin/bien-edit" class="btn btn-primary btn-sm">
          ${adminIcon('plus', { size: 15 })}
          <span>Ajouter un bien</span>
        </a>
      </div>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="filters-bar">
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

    <!-- Barre d'actions groupées -->
    <div id="biens-bulk-bar" class="bulk-actions-bar" style="display: none;">
      <div class="bulk-actions-info">
        <span id="biens-selected-count">0 sélectionné(s)</span>
      </div>
      <div class="bulk-actions-btns">
        <button type="button" class="btn btn-outline btn-sm" id="btn-cancel-biens-bulk">Annuler</button>
        <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected-biens">
          ${adminIcon('trash', { size: 14 })} Supprimer la sélection
        </button>
      </div>
    </div>

    <!-- Conteneur des résultats -->
    <div id="biens-results"></div>
  `;

  // Import CSV / Excel
  document.getElementById('btn-import-biens')?.addEventListener('click', () => {
    openCsvImportModal({
      type: 'properties',
      title: 'Importer des biens immobiliers',
      onSuccess: () => {
        setTimeout(() => window.location.reload(), 400);
      }
    });
  });

  // Suppression groupée
  document.getElementById('btn-delete-selected-biens')?.addEventListener('click', async () => {
    if (selectedPropIds.size === 0) return;
    const ok = await confirmModal({
      title: 'Suppression groupée de biens',
      message: `Êtes-vous sûr de vouloir supprimer définitivement les ${selectedPropIds.size} bien(s) sélectionné(s) ?`,
      confirmText: 'Supprimer'
    });

    if (ok) {
      try {
        const res = await fetch('/api/properties.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'bulk_delete',
            ids: Array.from(selectedPropIds)
          })
        });
        const data = await res.json();
        showToast(data.message || 'Biens supprimés');
      } catch (_) {
        selectedPropIds.forEach(id => deleteProperty(id));
        showToast('Biens supprimés');
      }
      selectedPropIds.clear();
      updateBulkBar();
      renderResults();
    }
  });

  document.getElementById('btn-cancel-biens-bulk')?.addEventListener('click', () => {
    selectedPropIds.clear();
    updateBulkBar();
    renderResults();
  });

  bindFilterEvents();
  renderResults();

  // Redirection avec scroll ciblé et surlignage (Point 14)
  const targetId = new URLSearchParams(window.location.search).get('id');
  if (targetId) {
    setTimeout(() => {
      const row = document.querySelector(`tr[data-property-id="${targetId}"]`);
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('row-highlight');
      }
    }, 200);
  }
}

function updateBulkBar() {
  const bar = document.getElementById('biens-bulk-bar');
  const countEl = document.getElementById('biens-selected-count');
  if (!bar) return;

  if (selectedPropIds.size > 0) {
    bar.style.display = 'flex';
    if (countEl) countEl.textContent = `${selectedPropIds.size} bien(s) sélectionné(s)`;
  } else {
    bar.style.display = 'none';
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

function renderResults() {
  const container = document.getElementById('biens-results');
  if (!container) return;

  let properties = getProperties(currentFilters);

  // Tri supplémentaire côté client
  if (currentFilters.sort === 'surface-asc') {
    properties = [...properties].sort((a, b) => (a.surface || 0) - (b.surface || 0));
  } else if (currentFilters.sort === 'surface-desc') {
    properties = [...properties].sort((a, b) => (b.surface || 0) - (a.surface || 0));
  }

  if (properties.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">${adminIcon('building', { size: 28 })}</div>
          <h3 class="empty-state-title">Aucun bien trouvé</h3>
          <p class="empty-state-text">Modifiez vos filtres ou ajoutez une nouvelle propriété à votre portefeuille.</p>
          <a href="/admin/bien-edit" class="btn btn-primary">
            ${adminIcon('plus', { size: 16 })} Ajouter un bien
          </a>
        </div>
      </div>
    `;
    return;
  }

  container.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Portefeuille immobilier (${properties.length})</h2>
      </div>
      <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width: 40px;">
                  <input type="checkbox" id="biens-select-all" class="admin-checkbox" title="Tout sélectionner">
                </th>
                <th>Bien immobilier</th>
                <th>Type</th>
                <th>Localisation</th>
                <th>Surface</th>
                <th>Prix</th>
                <th>Statut</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              ${properties.map((p) => {
                const thumb = p.images && p.images[0] ? p.images[0] : '';
                const isChecked = selectedPropIds.has(String(p.id));
                return `
                  <tr data-property-id="${p.id}">
                    <td style="width: 40px;">
                      <input type="checkbox" class="admin-checkbox row-prop-checkbox" data-id="${p.id}" ${isChecked ? 'checked' : ''}>
                    </td>
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
                      <strong style="color: var(--admin-text); display: block;">${escapeHTML(p.location?.city || p.commune || '—')}</strong>
                      <span style="font-size: 0.78rem; color: var(--admin-text-subtle);">${escapeHTML(p.location?.wilaya || p.wilaya || '')}</span>
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
                        <a href="/client/bien.php?id=${p.id}" target="_blank" rel="noopener" class="btn-icon" title="Voir sur le site public">
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

  // Gestion des cases à cocher
  const selectAll = document.getElementById('biens-select-all');
  selectAll?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    container.querySelectorAll('.row-prop-checkbox').forEach((cb) => {
      cb.checked = checked;
      const id = cb.dataset.id;
      if (checked) selectedPropIds.add(String(id));
      else selectedPropIds.delete(String(id));
    });
    updateBulkBar();
  });

  container.querySelectorAll('.row-prop-checkbox').forEach((cb) => {
    cb.addEventListener('change', () => {
      const id = cb.dataset.id;
      if (cb.checked) selectedPropIds.add(String(id));
      else selectedPropIds.delete(String(id));
      updateBulkBar();
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

  // Écouteurs pour la suppression individuelle
  container.querySelectorAll('.btn-delete-prop').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const confirmed = await confirmModal({
        title: 'Supprimer ce bien ?',
        message: 'Cette action est irréversible. Le bien sera retiré du catalogue et du site public.',
        confirmText: 'Supprimer',
        cancelText: 'Annuler'
      });

      if (confirmed) {
        if (deleteProperty(id)) {
          selectedPropIds.delete(String(id));
          updateBulkBar();
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
