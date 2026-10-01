// admin/biens.js
import {
  initAdminLayout, adminIcon, formatPrice, formatSurface, formatDate,
  statusBadge, transactionBadge, showToast, confirmModal, escapeHTML
} from '/admin/assets/js/admin.js';
import { getProperties, deleteProperty, updatePropertyStatus } from '/admin/data/admin-store.js';

let currentFilters = {
  search: '',
  transaction: '',
  type: '',
  status: '',
  sort: 'recent'
};

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

  bindFilterEvents();
  renderResults();
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
                <th>Bien & Référence</th>
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
                return `
                  <tr data-property-id="${p.id}">
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
