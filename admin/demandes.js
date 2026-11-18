// admin/demandes.js
// MODULE ES NAVIGATEUR — gestion et suivi des demandes de contact et d'estimations.

import {
  initAdminLayout, adminIcon, formatDateTime, formatDate,
  statusBadge, showToast, confirmModal, escapeHTML
} from '/admin/assets/js/admin.js';
import {
  getDemandes, getDemandeById, updateDemandeStatus, deleteDemande
} from '/admin/data/admin-store.js';

let currentFilters = {
  search: '',
  type: '',
  status: ''
};

let activeDemandeId = null;
let selectedDemandeIds = new Set();

async function init() {
  const ready = await initAdminLayout({
    activePage: 'demandes',
    title: 'Demandes & Estimations',
    breadcrumbs: [{ label: 'Demandes' }]
  });

  if (!ready) return;

  const root = document.getElementById('demandes-content');
  if (!root) return;

  root.innerHTML = `
    <!-- Encart d'accès direct aux Fiches client -->
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; background: rgba(181, 154, 106, 0.08); border-left: 3px solid var(--admin-gold); border-radius: var(--radius-xs); margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
      <div style="font-size: 0.88rem; color: var(--admin-text);">
        <strong>Dossiers officiels :</strong> Vous recherchez les fiches clients avec signatures électroniques et justificatifs d'identité ?
      </div>
      <a href="/admin/fiches-client" class="btn btn-primary btn-sm" style="flex-shrink: 0;">
        ${adminIcon('file-text', { size: 14 })}
        <span>Consulter les fiches client</span>
      </a>
    </div>

    <!-- Barre de filtres et recherche -->
    <div class="filters-bar">
      <div class="search-input-wrap">
        <span class="admin-icon">${adminIcon('search', { size: 16 })}</span>
        <input type="text" id="filter-search" placeholder="Rechercher par nom, email, téléphone, sujet..." value="${escapeHTML(currentFilters.search)}">
      </div>

      <div class="filters-group">
        <select class="form-select" id="filter-type">
          <option value="">Tous les types</option>
          <option value="Fiche client">Fiches client (avec CNI & Signature)</option>
          <option value="Estimation">Demandes d'estimation</option>
          <option value="Contact">Messages de contact</option>
        </select>

        <select class="form-select" id="filter-status">
          <option value="">Tous les statuts</option>
          <option value="Nouveau">Nouveau</option>
          <option value="En cours">En cours</option>
          <option value="Validé">Validé</option>
          <option value="Traite">Traité</option>
          <option value="Rejeté">Rejeté</option>
        </select>

        <button type="button" class="btn btn-outline btn-sm" id="btn-reset-filters" title="Réinitialiser les filtres">
          Effacer
        </button>
      </div>
    </div>

    <!-- Barre d'actions groupées -->
    <div id="demandes-bulk-bar" class="bulk-actions-bar" style="display: none;">
      <div class="bulk-actions-info">
        <span id="demandes-selected-count">0 sélectionnée(s)</span>
      </div>
      <div class="bulk-actions-btns">
        <button type="button" class="btn btn-outline btn-sm" id="btn-cancel-dem-bulk">Annuler</button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-bulk-read-dem">
          ${adminIcon('check', { size: 14 })} Marquer comme lues
        </button>
        <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected-dem">
          ${adminIcon('trash', { size: 14 })} Supprimer la sélection
        </button>
      </div>
    </div>

    <!-- Conteneur des résultats -->
    <div id="demandes-results"></div>
  `;

  // Actions groupées
  document.getElementById('btn-cancel-dem-bulk')?.addEventListener('click', () => {
    selectedDemandeIds.clear();
    updateBulkBar();
    renderResults();
  });

  document.getElementById('btn-bulk-read-dem')?.addEventListener('click', async () => {
    if (selectedDemandeIds.size === 0) return;
    try {
      await fetch('/api/demandes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_mark_read', ids: Array.from(selectedDemandeIds) })
      });
      showToast(`${selectedDemandeIds.size} demande(s) marquée(s) comme lues`);
    } catch (_) {}
    selectedDemandeIds.clear();
    updateBulkBar();
    renderResults();
  });

  document.getElementById('btn-delete-selected-dem')?.addEventListener('click', async () => {
    if (selectedDemandeIds.size === 0) return;
    const ok = await confirmModal({
      title: 'Suppression groupée',
      message: `Êtes-vous sûr de vouloir supprimer définitivement ces ${selectedDemandeIds.size} demande(s) ?`,
      confirmText: 'Supprimer'
    });

    if (ok) {
      try {
        await fetch('/api/demandes.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'bulk_delete', ids: Array.from(selectedDemandeIds) })
        });
        showToast('Demandes supprimées');
      } catch (_) {
        selectedDemandeIds.forEach(id => deleteDemande(id));
      }
      selectedDemandeIds.clear();
      updateBulkBar();
      renderResults();
    }
  });

  bindFilterEvents();
  setupDrawerListeners();
  renderResults();

  // Redirection ciblée avec scroll et ouverture automatique du tiroir (Point 14)
  const targetId = new URLSearchParams(window.location.search).get('id');
  if (targetId) {
    setTimeout(() => {
      const row = document.querySelector(`tr[data-id="${targetId}"]`);
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('row-highlight');
      }
      openDemandeDrawer(targetId);
    }, 250);
  }
}

function updateBulkBar() {
  const bar = document.getElementById('demandes-bulk-bar');
  const countEl = document.getElementById('demandes-selected-count');
  if (!bar) return;

  if (selectedDemandeIds.size > 0) {
    bar.style.display = 'flex';
    if (countEl) countEl.textContent = `${selectedDemandeIds.size} demande(s) sélectionnée(s)`;
  } else {
    bar.style.display = 'none';
  }
}

function bindFilterEvents() {
  const searchInput = document.getElementById('filter-search');
  const typeSelect = document.getElementById('filter-type');
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

  typeSelect?.addEventListener('change', (e) => {
    currentFilters.type = e.target.value;
    renderResults();
  });

  statusSelect?.addEventListener('change', (e) => {
    currentFilters.status = e.target.value;
    renderResults();
  });

  resetBtn?.addEventListener('click', () => {
    currentFilters = { search: '', type: '', status: '' };
    if (searchInput) searchInput.value = '';
    if (typeSelect) typeSelect.value = '';
    if (statusSelect) statusSelect.value = '';
    renderResults();
  });
}

function renderResults() {
  const container = document.getElementById('demandes-results');
  if (!container) return;

  const demandes = getDemandes(currentFilters);

  if (demandes.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">
            ${adminIcon('mail', { size: 28 })}
          </div>
          <h3 class="empty-state-title">Aucune demande trouvée</h3>
          <p class="empty-state-text">
            Aucun contact ou dossier ne correspond à vos critères de recherche actuels.
          </p>
          <button type="button" class="btn btn-outline" id="btn-empty-reset">
            Réinitialiser les filtres
          </button>
        </div>
      </div>
    `;
    document.getElementById('btn-empty-reset')?.addEventListener('click', () => {
      currentFilters = { search: '', type: '', status: '' };
      renderResults();
    });
    return;
  }

  const rowsHtml = demandes.map((item) => {
    let typeBadge = '';
    const isFicheClient = item.type === 'Fiche client' || Boolean(item.signature);
    const isEstimation = item.type === 'Estimation';

    if (isFicheClient) {
      typeBadge = `<span class="badge badge--gold">${adminIcon('file-text', { size: 12 })} Dossier client</span>`;
    } else if (isEstimation) {
      typeBadge = `<span class="badge badge--warning">${adminIcon('building', { size: 12 })} Estimation</span>`;
    } else {
      typeBadge = `<span class="badge badge--info">${adminIcon('mail', { size: 12 })} Contact</span>`;
    }

    const hasDoc = Boolean(item.pieceIdentite?.name);
    const hasSig = Boolean(item.signature);
    const docTags = [];
    if (hasDoc) {
      docTags.push(`<span class="doc-badge" title="Pièce d'identité">${adminIcon('file-text', { size: 11 })} CNI</span>`);
    }
    if (hasSig) {
      docTags.push(`<span class="badge badge--success" style="font-size: 0.7rem; padding: 2px 6px;">${adminIcon('check', { size: 10 })} Signé</span>`);
    }

    const isUnread = item.is_read === 0 || item.is_read === false;
    const isChecked = selectedDemandeIds.has(String(item.id));

    return `
      <tr data-id="${item.id}" style="${isUnread ? 'font-weight: 600; background-color: rgba(181, 154, 106, 0.04);' : ''}">
        <td style="width: 40px;">
          <input type="checkbox" class="admin-checkbox row-dem-checkbox" data-id="${item.id}" ${isChecked ? 'checked' : ''}>
        </td>
        <td>
          <div style="display: flex; align-items: center; gap: 6px;">
            ${isUnread ? `<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--admin-gold); flex-shrink:0;" title="Demande non lue"></span>` : ''}
            <div>
              <div style="font-size: 0.85rem; color: var(--admin-text);">
                ${formatDate(item.createdAt)}
              </div>
              <div style="font-size: 0.75rem; color: var(--admin-text-subtle);">
                ${item.reference ? `<span style="font-family: monospace; color: var(--admin-gold); font-weight: 600;">${escapeHTML(item.reference)}</span>` : (item.createdAt ? new Date(item.createdAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '')}
              </div>
            </div>
          </div>
        </td>
        <td>
          ${typeBadge}
          ${docTags.length ? `<div style="display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap;">${docTags.join('')}</div>` : ''}
        </td>
        <td>
          <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML(item.nom || 'Anonyme')}</div>
          <div style="font-size: 0.78rem; color: var(--admin-text-muted); display: flex; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
            ${item.email ? `<a href="mailto:${escapeHTML(item.email)}" style="color: var(--admin-gold);">${escapeHTML(item.email)}</a>` : ''}
            ${item.phone ? `<span>•</span><a href="tel:${escapeHTML(item.phone)}">${escapeHTML(item.phone)}</a>` : ''}
          </div>
        </td>
        <td>
          <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.88rem;">
            ${escapeHTML(item.subject || item.message || '—')}
          </div>
        </td>
        <td>
          <select class="form-select select-status-quick" data-id="${item.id}" style="padding: 4px 8px; font-size: 0.8rem; border-radius: var(--radius-xs);">
            <option value="Nouveau" ${item.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
            <option value="En cours" ${item.status === 'En cours' ? 'selected' : ''}>En cours</option>
            <option value="Validé" ${item.status === 'Validé' ? 'selected' : ''}>Validé</option>
            <option value="Traite" ${item.status === 'Traite' || item.status === 'Traité' ? 'selected' : ''}>Traité</option>
            <option value="Rejeté" ${item.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
          </select>
        </td>
        <td style="text-align: right;">
          <div class="table-actions" style="justify-content: flex-end;">
            <button type="button" class="btn-icon btn-toggle-read" data-id="${item.id}" title="${isUnread ? 'Marquer comme lu' : 'Marquer comme non lu'}">
              ${adminIcon(isUnread ? 'check' : 'mail', { size: 15 })}
            </button>
            <button type="button" class="btn-icon btn-view-demande" data-id="${item.id}" title="Consulter la demande" aria-label="Voir les détails">
              ${adminIcon('eye', { size: 15 })}
            </button>
            <button type="button" class="btn-icon btn-icon--danger btn-delete-demande" data-id="${item.id}" title="Supprimer la demande" aria-label="Supprimer">
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
        <h2 class="card-title">Demandes reçues (${demandes.length})</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 40px;">
                <input type="checkbox" id="demandes-select-all" class="admin-checkbox" title="Tout sélectionner">
              </th>
              <th>Date & Réf</th>
              <th>Type</th>
              <th>Contact / Prospect</th>
              <th>Sujet ou Message</th>
              <th>Statut</th>
              <th style="text-align: right; width: 130px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    </div>
  `;

  // Checkboxes
  const selectAll = document.getElementById('demandes-select-all');
  selectAll?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    container.querySelectorAll('.row-dem-checkbox').forEach((cb) => {
      cb.checked = checked;
      const id = cb.dataset.id;
      if (checked) selectedDemandeIds.add(String(id));
      else selectedDemandeIds.delete(String(id));
    });
    updateBulkBar();
  });

  container.querySelectorAll('.row-dem-checkbox').forEach((cb) => {
    cb.addEventListener('change', () => {
      const id = cb.dataset.id;
      if (cb.checked) selectedDemandeIds.add(String(id));
      else selectedDemandeIds.delete(String(id));
      updateBulkBar();
    });
  });

  // Marquer comme lu / non lu individuellement (Point 13)
  container.querySelectorAll('.btn-toggle-read').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;
      const dem = demandes.find((d) => String(d.id) === String(id));
      const nextRead = (dem?.is_read === 1) ? 0 : 1;
      const action = nextRead ? 'mark_read' : 'mark_unread';

      try {
        await fetch('/api/demandes.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action, id: parseInt(id, 10) })
        });
        if (dem) dem.is_read = nextRead;
        showToast(nextRead ? 'Demande marquée comme lue' : 'Demande marquée comme non lue');
        renderResults();
      } catch (_) {}
    });
  });

  // Changement rapide de statut
  container.querySelectorAll('.select-status-quick').forEach((sel) => {
    sel.addEventListener('change', (e) => {
      const id = sel.dataset.id;
      const newStatus = sel.value;
      updateDemandeStatus(id, newStatus);
      showToast(`Statut mis à jour : ${newStatus}`);
      renderResults();
    });
  });

  // Boutons Voir / Consulter
  container.querySelectorAll('.btn-view-demande').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      openDemandeDrawer(id);
    });
  });

  // Boutons Supprimer
  container.querySelectorAll('.btn-delete-demande').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const ok = await confirmModal({
        title: 'Supprimer la demande ?',
        message: 'Cette action est irréversible.',
        confirmText: 'Supprimer'
      });
      if (ok) {
        deleteDemande(id);
        selectedDemandeIds.delete(String(id));
        updateBulkBar();
        showToast('Demande supprimée');
        renderResults();
      }
    });
  });
}

function setupDrawerListeners() {
  const backdrop = document.getElementById('drawer-backdrop');
  backdrop?.addEventListener('click', closeDrawer);
}

function openDemandeDrawer(id) {
  activeDemandeId = id;
  const drawer = document.getElementById('demande-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  if (!drawer) return;

  const demande = getDemandeById(id);
  if (!demande) return;

  // Auto-marquer comme lu à la consultation (Point 13)
  if (!demande.is_read) {
    demande.is_read = 1;
    fetch('/api/demandes.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'mark_read', id: parseInt(id, 10) })
    }).catch(() => {});
  }

  const isFicheClient = demande.type === 'Fiche client' || Boolean(demande.signature);
  const isEstimation = demande.type === 'Estimation';

  drawer.innerHTML = `
    <div class="drawer-header">
      <div>
        <h3 class="drawer-title">Détails de la demande</h3>
        <p class="drawer-subtitle" style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.85rem;">
          Reçue le ${formatDateTime(demande.createdAt)}
        </p>
      </div>
      <button type="button" class="btn-icon" id="btn-close-drawer" aria-label="Fermer">
        ${adminIcon('close', { size: 18 })}
      </button>
    </div>

    <div class="drawer-body">
      <!-- Fiche récapitulative prospect -->
      <div class="detail-block" style="background: var(--admin-surface-alt); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--admin-border); margin-bottom: 20px;">
        <h4 style="margin: 0 0 12px; font-size: 1rem; color: var(--admin-text);">Coordonnées du prospect</h4>
        <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem;">
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Nom complet</span>
            <strong>${escapeHTML(demande.nom || '—')}</strong>
          </div>
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Téléphone</span>
            <strong>${demande.phone ? `<a href="tel:${escapeHTML(demande.phone)}">${escapeHTML(demande.phone)}</a>` : '—'}</strong>
          </div>
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Adresse e-mail</span>
            <strong>${demande.email ? `<a href="mailto:${escapeHTML(demande.email)}">${escapeHTML(demande.email)}</a>` : '—'}</strong>
          </div>
          <div>
            <span style="color: var(--admin-text-subtle); display: block; font-size: 0.78rem;">Type de démarche</span>
            <strong>${escapeHTML(demande.type || 'Contact')}</strong>
          </div>
        </div>
      </div>

      <!-- Statut de la demande -->
      <div style="margin-bottom: 20px;">
        <label class="form-label" style="margin-bottom: 6px; font-weight: 600;">Statut de traitement</label>
        <select class="form-select" id="drawer-status-select" style="padding: 6px 12px; font-weight: 600;">
          <option value="Nouveau" ${demande.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
          <option value="En cours" ${demande.status === 'En cours' ? 'selected' : ''}>En cours</option>
          <option value="Validé" ${demande.status === 'Validé' ? 'selected' : ''}>Validé</option>
          <option value="Traite" ${demande.status === 'Traite' || demande.status === 'Traité' ? 'selected' : ''}>Traité</option>
          <option value="Rejeté" ${demande.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
        </select>
      </div>

      <!-- Message du client -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text);">Message ou description</h4>
        <div style="padding: 16px; background-color: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm); font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; color: var(--admin-text);">
          ${escapeHTML(demande.message || demande.details?.description || 'Aucun message textuel fourni.')}
        </div>
      </div>
    </div>

    <div class="drawer-footer">
      <button type="button" class="btn btn-danger-outline btn-sm" id="drawer-btn-delete">
        ${adminIcon('trash', { size: 14 })} Supprimer
      </button>

      <div style="display: flex; gap: 8px;">
        <button type="button" class="btn btn-outline btn-sm" id="drawer-btn-print" title="Imprimer le dossier">
          ${adminIcon('printer', { size: 14 })} Imprimer
        </button>
        ${demande.phone ? `
          <a href="tel:${escapeHTML(demande.phone)}" class="btn btn-outline btn-sm">
            ${adminIcon('phone', { size: 14 })} Appeler
          </a>
        ` : ''}
        ${demande.email ? `
          <a href="mailto:${escapeHTML(demande.email)}" class="btn btn-primary btn-sm">
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
    updateDemandeStatus(id, newStatus);
    showToast(`Statut mis à jour : ${newStatus}`);
    renderResults();
  });

  document.getElementById('drawer-btn-print')?.addEventListener('click', () => {
    window.print();
  });

  document.getElementById('drawer-btn-delete')?.addEventListener('click', async () => {
    const ok = await confirmModal({
      title: 'Supprimer la demande ?',
      message: 'Êtes-vous sûr de vouloir supprimer cette demande ?',
      confirmText: 'Supprimer'
    });
    if (ok) {
      deleteDemande(id);
      showToast('Demande supprimée');
      closeDrawer();
      renderResults();
    }
  });
}

function closeDrawer() {
  const drawer = document.getElementById('demande-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  drawer?.classList.remove('is-open');
  backdrop?.classList.remove('is-open');
  activeDemandeId = null;
}

document.addEventListener('DOMContentLoaded', init);
