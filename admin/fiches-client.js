// admin/fiches-client.js
// MODULE ES NAVIGATEUR — gestion et consultation complète des fiches client (dossiers).

import {
  initAdminLayout, adminIcon, formatDateTime, formatDate,
  statusBadge, showToast, confirmModal, openCsvImportModal, escapeHTML
} from '/admin/assets/js/admin.js';
import {
  getFichesClient, getFicheClientById, updateFicheClientStatus, deleteFicheClient
} from '/admin/data/admin-store.js';

let currentFilters = {
  search: '',
  transaction: '',
  typeBien: '',
  status: ''
};

let activeFicheId = null;
let selectedFicheIds = new Set();

async function init() {
  const ready = await initAdminLayout({
    activePage: 'fiches-client',
    title: 'Fiches client & Dossiers',
    breadcrumbs: [{ label: 'Fiches client' }],
    action: {
      label: 'Nouveau dossier (public)',
      href: '/client/fiche-client.php',
      target: '_blank',
      icon: 'external'
    }
  });

  if (!ready) return;

  const root = document.getElementById('fiches-content');
  if (!root) return;

  root.innerHTML = `
    <!-- Entête d'outils avec bouton d'import CSV -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
      <div>
        <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
          Consultez et gérez les mandats et dossiers officiels avec pièces d'identité et signatures électroniques.
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-import-fiches">
          ${adminIcon('upload', { size: 15 })}
          <span>Importer CSV / Excel</span>
        </button>
        <a href="/client/fiche-client.php" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
          ${adminIcon('plus', { size: 15 })}
          <span>Nouvelle fiche (formulaire)</span>
        </a>
      </div>
    </div>

    <!-- Statistiques KPI spécifiques aux fiches client -->
    <div id="fiches-stats" class="stats-grid" style="margin-bottom: 24px;"></div>

    <!-- Barre de filtres et recherche -->
    <div class="filters-bar">
      <div class="search-input-wrap">
        <span class="admin-icon">${adminIcon('search', { size: 16 })}</span>
        <input type="text" id="filter-search" placeholder="Rechercher par référence, nom, prénom, téléphone, email..." value="${escapeHTML(currentFilters.search)}">
      </div>

      <div class="filters-group">
        <select class="form-select" id="filter-transaction">
          <option value="">Toutes opérations</option>
          <option value="Acheter">Acheter</option>
          <option value="Louer">Louer</option>
          <option value="Vendre">Vendre</option>
        </select>

        <select class="form-select" id="filter-type-bien">
          <option value="">Tous les types de bien</option>
          <option value="Appartement">Appartement</option>
          <option value="Villa">Villa</option>
          <option value="Terrain">Terrain</option>
          <option value="Local commercial">Local commercial</option>
          <option value="Bureau">Bureau</option>
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

    <!-- Barre d'actions groupées -->
    <div id="fiches-bulk-bar" class="bulk-actions-bar" style="display: none;">
      <div class="bulk-actions-info">
        <span id="fiches-selected-count">0 sélectionnée(s)</span>
      </div>
      <div class="bulk-actions-btns">
        <button type="button" class="btn btn-outline btn-sm" id="btn-cancel-fiches-bulk">Annuler</button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-read-fiches">
          ${adminIcon('check', { size: 14 })} Marquer comme lues
        </button>
        <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected-fiches">
          ${adminIcon('trash', { size: 14 })} Supprimer la sélection
        </button>
      </div>
    </div>

    <!-- Conteneur des résultats -->
    <div id="fiches-results"></div>
  `;

  // Import CSV / Excel
  document.getElementById('btn-import-fiches')?.addEventListener('click', () => {
    openCsvImportModal({
      type: 'fiches_client',
      title: 'Importer des fiches client',
      onSuccess: () => {
        setTimeout(() => window.location.reload(), 400);
      }
    });
  });

  // Actions groupées
  document.getElementById('btn-cancel-fiches-bulk')?.addEventListener('click', () => {
    selectedFicheIds.clear();
    updateBulkBar();
    renderResults();
  });

  document.getElementById('btn-bulk-read-fiches')?.addEventListener('click', async () => {
    if (selectedFicheIds.size === 0) return;
    try {
      await fetch('/api/fiches-client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_mark_read', ids: Array.from(selectedFicheIds) })
      });
      showToast(`${selectedFicheIds.size} fiche(s) marquée(s) comme lues`);
    } catch (_) {}
    selectedFicheIds.clear();
    updateBulkBar();
    renderResults();
  });

  document.getElementById('btn-delete-selected-fiches')?.addEventListener('click', async () => {
    if (selectedFicheIds.size === 0) return;
    const ok = await confirmModal({
      title: 'Suppression groupée de dossiers',
      message: `Êtes-vous sûr de vouloir supprimer définitivement ces ${selectedFicheIds.size} dossier(s) ?`,
      confirmText: 'Supprimer'
    });

    if (ok) {
      try {
        await fetch('/api/fiches-client.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'bulk_delete', ids: Array.from(selectedFicheIds) })
        });
        showToast('Fiches supprimées');
      } catch (_) {
        selectedFicheIds.forEach(id => deleteFicheClient(id));
      }
      selectedFicheIds.clear();
      updateBulkBar();
      renderStats();
      renderResults();
    }
  });

  bindFilterEvents();
  setupDrawerListeners();
  renderStats();
  renderResults();

  // Redirection ciblée avec scroll et ouverture automatique du dossier (Point 14)
  const targetId = new URLSearchParams(window.location.search).get('id');
  if (targetId) {
    setTimeout(() => {
      const row = document.querySelector(`tr[data-id="${targetId}"]`);
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('row-highlight');
      }
      openDossierDrawer(targetId);
    }, 250);
  }
}

function updateBulkBar() {
  const bar = document.getElementById('fiches-bulk-bar');
  const countEl = document.getElementById('fiches-selected-count');
  if (!bar) return;

  if (selectedFicheIds.size > 0) {
    bar.style.display = 'flex';
    if (countEl) countEl.textContent = `${selectedFicheIds.size} fiche(s) sélectionnée(s)`;
  } else {
    bar.style.display = 'none';
  }
}

function renderStats() {
  const container = document.getElementById('fiches-stats');
  if (!container) return;

  const fiches = getFichesClient();
  const total = fiches.length;
  const nouveaux = fiches.filter((f) => f.status === 'Nouveau' || !f.is_read).length;
  const valides = fiches.filter((f) => f.status === 'Validé' || f.status === 'Traité').length;
  const avecPieces = fiches.filter((f) => Boolean(f.piece_identite || f.pieceIdentite)).length;

  container.innerHTML = `
    <div class="stat-card">
      <div class="stat-icon stat-icon--gold">${adminIcon('file-text', { size: 24 })}</div>
      <div class="stat-info">
        <p class="stat-value">${total}</p>
        <p class="stat-label">Total dossiers</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon ${nouveaux > 0 ? 'stat-icon--warning' : 'stat-icon--green'}">${adminIcon('mail', { size: 24 })}</div>
      <div class="stat-info">
        <p class="stat-value">${nouveaux}</p>
        <p class="stat-label">À traiter / Non lus</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon--green">${adminIcon('check', { size: 24 })}</div>
      <div class="stat-info">
        <p class="stat-value">${valides}</p>
        <p class="stat-label">Validés & Traités</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon--blue">${adminIcon('user', { size: 24 })}</div>
      <div class="stat-info">
        <p class="stat-value">${avecPieces}</p>
        <p class="stat-label">Pièces d'identité</p>
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

function renderResults() {
  const container = document.getElementById('fiches-results');
  if (!container) return;

  const fiches = getFichesClient(currentFilters);

  if (fiches.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">${adminIcon('file-text', { size: 28 })}</div>
          <h3 class="empty-state-title">Aucune fiche client trouvée</h3>
          <p class="empty-state-text">Modifiez vos critères de recherche ou réinitialisez les filtres.</p>
          <button type="button" class="btn btn-outline" id="btn-empty-reset-fiches">Réinitialiser les filtres</button>
        </div>
      </div>
    `;
    document.getElementById('btn-empty-reset-fiches')?.addEventListener('click', () => {
      currentFilters = { search: '', transaction: '', typeBien: '', status: '' };
      renderResults();
    });
    return;
  }

  const rows = fiches.map((f) => {
    const isUnread = f.is_read === 0 || f.is_read === false;
    const isChecked = selectedFicheIds.has(String(f.id));

    return `
      <tr data-id="${f.id}" style="${isUnread ? 'font-weight: 600; background-color: rgba(181, 154, 106, 0.04);' : ''}">
        <td style="width: 40px;">
          <input type="checkbox" class="admin-checkbox row-fiche-checkbox" data-id="${f.id}" ${isChecked ? 'checked' : ''}>
        </td>
        <td>
          <div style="display: flex; align-items: center; gap: 6px;">
            ${isUnread ? `<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--admin-gold); flex-shrink:0;" title="Dossier non lu"></span>` : ''}
            <div>
              <div style="font-weight: 700; color: var(--admin-gold); font-family: monospace;">
                ${escapeHTML(f.reference || '—')}
              </div>
              <div style="font-size: 0.76rem; color: var(--admin-text-subtle);">
                ${formatDate(f.createdAt)}
              </div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML((f.nom || '') + ' ' + (f.prenom || ''))}</div>
          <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 2px;">
            <a href="tel:${escapeHTML(f.telephone || f.phone || '')}">${escapeHTML(f.telephone || f.phone || '—')}</a>
          </div>
        </td>
        <td>
          <div style="font-size: 0.88rem;">${escapeHTML(f.typeBien || f.type_bien || '—')}</div>
          <span class="badge badge--gold" style="font-size: 0.7rem; padding: 2px 6px;">${escapeHTML(f.transaction || 'Acheter')}</span>
        </td>
        <td>
          <select class="form-select select-fiche-status" data-id="${f.id}" style="padding: 4px 8px; font-size: 0.8rem; border-radius: var(--radius-xs);">
            <option value="Nouveau" ${f.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
            <option value="En cours" ${f.status === 'En cours' ? 'selected' : ''}>En cours</option>
            <option value="Validé" ${f.status === 'Validé' ? 'selected' : ''}>Validé</option>
            <option value="Traité" ${f.status === 'Traité' ? 'selected' : ''}>Traité</option>
            <option value="Rejeté" ${f.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
          </select>
        </td>
        <td style="text-align: right;">
          <div class="table-actions" style="justify-content: flex-end;">
            <button type="button" class="btn-icon btn-toggle-read-fiche" data-id="${f.id}" title="${isUnread ? 'Marquer comme lu' : 'Marquer comme non lu'}">
              ${adminIcon(isUnread ? 'check' : 'mail', { size: 15 })}
            </button>
            <button type="button" class="btn-icon btn-view-dossier" data-id="${f.id}" title="Consulter le dossier officiel">
              ${adminIcon('eye', { size: 15 })}
            </button>
            <button type="button" class="btn-icon btn-icon--danger btn-delete-fiche" data-id="${f.id}" title="Supprimer la fiche">
              ${adminIcon('trash', { size: 15 })}
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  container.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Dossiers officiels enregistrés (${fiches.length})</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 40px;">
                <input type="checkbox" id="fiches-select-all" class="admin-checkbox" title="Tout sélectionner">
              </th>
              <th>Référence & Date</th>
              <th>Client</th>
              <th>Bien recherché</th>
              <th>Statut</th>
              <th style="text-align: right; width: 130px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </div>
    </div>
  `;

  // Checkboxes
  const selectAll = document.getElementById('fiches-select-all');
  selectAll?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    container.querySelectorAll('.row-fiche-checkbox').forEach((cb) => {
      cb.checked = checked;
      const id = cb.dataset.id;
      if (checked) selectedFicheIds.add(String(id));
      else selectedFicheIds.delete(String(id));
    });
    updateBulkBar();
  });

  container.querySelectorAll('.row-fiche-checkbox').forEach((cb) => {
    cb.addEventListener('change', () => {
      const id = cb.dataset.id;
      if (cb.checked) selectedFicheIds.add(String(id));
      else selectedFicheIds.delete(String(id));
      updateBulkBar();
    });
  });

  // Toggle lu / non lu
  container.querySelectorAll('.btn-toggle-read-fiche').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;
      const fiche = fiches.find((f) => String(f.id) === String(id));
      const nextRead = (fiche?.is_read === 1) ? 0 : 1;
      const action = nextRead ? 'mark_read' : 'mark_unread';

      try {
        await fetch('/api/fiches-client.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action, id: parseInt(id, 10) })
        });
        if (fiche) fiche.is_read = nextRead;
        showToast(nextRead ? 'Fiche marquée comme lue' : 'Fiche marquée comme non lue');
        renderStats();
        renderResults();
      } catch (_) {}
    });
  });

  // Changement rapide de statut
  container.querySelectorAll('.select-fiche-status').forEach((sel) => {
    sel.addEventListener('change', (e) => {
      const id = sel.dataset.id;
      const newStatus = sel.value;
      updateFicheClientStatus(id, newStatus);
      showToast(`Statut du dossier mis à jour : ${newStatus}`);
      renderStats();
      renderResults();
    });
  });

  // Consulter
  container.querySelectorAll('.btn-view-dossier').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      openDossierDrawer(id);
    });
  });

  // Supprimer
  container.querySelectorAll('.btn-delete-fiche').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const ok = await confirmModal({
        title: 'Supprimer ce dossier ?',
        message: 'Cette action est irréversible.',
        confirmText: 'Supprimer'
      });
      if (ok) {
        deleteFicheClient(id);
        selectedFicheIds.delete(String(id));
        updateBulkBar();
        showToast('Dossier supprimé');
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

function openDossierDrawer(id) {
  activeFicheId = id;
  const drawer = document.getElementById('demande-drawer') || document.getElementById('dossier-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  if (!drawer) return;

  const fiche = getFicheClientById(id);
  if (!fiche) return;

  // Auto marquer comme lu (Point 13)
  if (!fiche.is_read) {
    fiche.is_read = 1;
    fetch('/api/fiches-client.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'mark_read', id: parseInt(id, 10) })
    }).catch(() => {});
  }

  drawer.innerHTML = `
    <div class="drawer-header">
      <div>
        <h3 class="drawer-title">Dossier Client officiel : ${escapeHTML(fiche.reference || 'N/A')}</h3>
        <p class="drawer-subtitle" style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.85rem;">
          Enregistré le ${formatDateTime(fiche.createdAt)}
        </p>
      </div>
      <button type="button" class="btn-icon" id="btn-close-dossier-drawer" aria-label="Fermer">
        ${adminIcon('close', { size: 18 })}
      </button>
    </div>

    <div class="drawer-body">
      <!-- Fiche d'identité du prospect -->
      <div class="detail-block" style="background: var(--admin-surface-alt); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--admin-border); margin-bottom: 20px;">
        <h4 style="margin: 0 0 12px; font-size: 1rem; color: var(--admin-text);">Informations Client</h4>
        <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem;">
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Nom complet</span>
            <strong>${escapeHTML((fiche.nom || '') + ' ' + (fiche.prenom || ''))}</strong>
          </div>
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Téléphone</span>
            <strong>${fiche.telephone ? `<a href="tel:${escapeHTML(fiche.telephone)}">${escapeHTML(fiche.telephone)}</a>` : '—'}</strong>
          </div>
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Adresse e-mail</span>
            <strong>${fiche.email ? `<a href="mailto:${escapeHTML(fiche.email)}">${escapeHTML(fiche.email)}</a>` : '—'}</strong>
          </div>
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Type d'opération</span>
            <strong>${escapeHTML(fiche.transaction || 'Acheter')}</strong>
          </div>
        </div>
      </div>

      <!-- Statut -->
      <div style="margin-bottom: 20px;">
        <label class="form-label" style="margin-bottom: 6px; font-weight: 600;">Statut de validation</label>
        <select class="form-select" id="drawer-fiche-status" style="padding: 6px 12px; font-weight: 600;">
          <option value="Nouveau" ${fiche.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
          <option value="En cours" ${fiche.status === 'En cours' ? 'selected' : ''}>En cours</option>
          <option value="Validé" ${fiche.status === 'Validé' ? 'selected' : ''}>Validé</option>
          <option value="Traité" ${fiche.status === 'Traité' ? 'selected' : ''}>Traité</option>
          <option value="Rejeté" ${fiche.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
        </select>
      </div>

      <!-- Pièce d'identité -->
      <div style="margin-bottom: 20px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text);">Pièce d'identité justificative</h4>
        ${(fiche.piece_identite || fiche.pieceIdentite?.dataUrl) ? `
          <div style="border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 12px; background: var(--admin-surface); text-align: center;">
            <img src="${escapeHTML(fiche.piece_identite || fiche.pieceIdentite?.dataUrl)}" alt="Pièce d'identité" style="max-height: 240px; max-width: 100%; border-radius: var(--radius-xs); object-fit: contain;">
          </div>
        ` : `
          <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Aucune pièce d'identité enregistrée.</p>
        `}
      </div>

      <!-- Signature électronique -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text);">Signature électronique certifiée</h4>
        <div style="border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 12px; background: #FFF; text-align: center;">
          ${fiche.signature ? `
            <img src="${escapeHTML(fiche.signature)}" alt="Signature client" style="max-height: 100px; max-width: 100%; object-fit: contain;">
          ` : `
            <p style="margin:0; font-size:0.85rem; color:var(--admin-text-muted);">Signature non enregistrée.</p>
          `}
        </div>
      </div>
    </div>

    <div class="drawer-footer">
      <button type="button" class="btn btn-danger-outline btn-sm" id="drawer-btn-del-fiche">
        ${adminIcon('trash', { size: 14 })} Supprimer
      </button>

      <div style="display: flex; gap: 8px;">
        <button type="button" class="btn btn-outline btn-sm" id="drawer-btn-print-fiche" title="Imprimer le dossier">
          ${adminIcon('printer', { size: 14 })} Imprimer
        </button>
        ${fiche.telephone ? `
          <a href="tel:${escapeHTML(fiche.telephone)}" class="btn btn-outline btn-sm">
            ${adminIcon('phone', { size: 14 })} Appeler
          </a>
        ` : ''}
        ${fiche.email ? `
          <a href="mailto:${escapeHTML(fiche.email)}" class="btn btn-primary btn-sm">
            ${adminIcon('mail', { size: 14 })} Répondre
          </a>
        ` : ''}
      </div>
    </div>
  `;

  drawer.classList.add('is-open');
  backdrop?.classList.add('is-open');

  document.getElementById('btn-close-dossier-drawer')?.addEventListener('click', closeDrawer);

  document.getElementById('drawer-fiche-status')?.addEventListener('change', (e) => {
    const newStatus = e.target.value;
    updateFicheClientStatus(id, newStatus);
    showToast(`Statut du dossier mis à jour : ${newStatus}`);
    renderStats();
    renderResults();
  });

  document.getElementById('drawer-btn-print-fiche')?.addEventListener('click', () => {
    window.print();
  });

  document.getElementById('drawer-btn-del-fiche')?.addEventListener('click', async () => {
    const ok = await confirmModal({
      title: 'Supprimer ce dossier ?',
      message: 'Cette action est irréversible.',
      confirmText: 'Supprimer'
    });
    if (ok) {
      deleteFicheClient(id);
      showToast('Dossier supprimé');
      closeDrawer();
      renderStats();
      renderResults();
    }
  });
}

function closeDrawer() {
  const drawer = document.getElementById('demande-drawer') || document.getElementById('dossier-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  drawer?.classList.remove('is-open');
  backdrop?.classList.remove('is-open');
  activeFicheId = null;
}

document.addEventListener('DOMContentLoaded', init);
