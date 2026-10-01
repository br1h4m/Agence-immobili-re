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
    <!-- Barre de filtres et recherche -->
    <div class="filters-bar">
      <div class="search-input-wrap">
        <span class="admin-icon">${adminIcon('search', { size: 16 })}</span>
        <input type="text" id="filter-search" placeholder="Rechercher par nom, email, téléphone, sujet..." value="${escapeHTML(currentFilters.search)}">
      </div>

      <div class="filters-group">
        <select class="form-select" id="filter-type">
          <option value="">Tous les types</option>
          <option value="Contact">Contact direct</option>
          <option value="Estimation">Demande d'estimation</option>
        </select>

        <select class="form-select" id="filter-status">
          <option value="">Tous les statuts</option>
          <option value="Nouveau">Nouveau</option>
          <option value="En cours">En cours</option>
          <option value="Traite">Traité</option>
        </select>

        <button type="button" class="btn btn-outline btn-sm" id="btn-reset-filters" title="Réinitialiser les filtres">
          Effacer
        </button>
      </div>
    </div>

    <!-- Conteneur des résultats -->
    <div id="demandes-results"></div>
  `;

  bindFilterEvents();
  setupDrawerListeners();
  renderResults();
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

  const list = getDemandes(currentFilters);

  if (list.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">
            ${adminIcon('mail', { size: 28 })}
          </div>
          <h3 class="empty-state-title">Aucune demande trouvée</h3>
          <p class="empty-state-text">
            Aucun message ne correspond à vos critères de recherche actuels.
          </p>
        </div>
      </div>
    `;
    return;
  }

  const rows = list.map((item) => {
    const isEstimation = item.type === 'Estimation';
    const typeBadge = isEstimation
      ? `<span class="badge badge--sale">${adminIcon('building', { size: 12 })} Estimation</span>`
      : `<span class="badge badge--info">${adminIcon('mail', { size: 12 })} Contact</span>`;

    return `
      <tr data-id="${item.id}">
        <td>
          <div style="font-weight: 600; color: var(--admin-text); font-size: 0.85rem;">
            ${formatDate(item.createdAt)}
          </div>
          <div style="font-size: 0.75rem; color: var(--admin-text-subtle);">
            ${item.createdAt ? new Date(item.createdAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : ''}
          </div>
        </td>
        <td>${typeBadge}</td>
        <td>
          <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML(item.nom || 'Anonyme')}</div>
          <div style="font-size: 0.78rem; color: var(--admin-text-muted); display: flex; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
            ${item.email ? `<a href="mailto:${escapeHTML(item.email)}" style="color: var(--admin-gold);">${escapeHTML(item.email)}</a>` : ''}
            ${item.phone ? `<span>•</span><a href="tel:${escapeHTML(item.phone)}">${escapeHTML(item.phone)}</a>` : ''}
          </div>
        </td>
        <td>
          <div style="max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500;">
            ${escapeHTML(item.subject || item.message || '—')}
          </div>
          <div style="max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.78rem; color: var(--admin-text-subtle);">
            ${escapeHTML(item.message || '')}
          </div>
        </td>
        <td>
          <select class="form-select select-status-quick" data-id="${item.id}" style="padding: 4px 8px; font-size: 0.8rem; border-radius: var(--radius-xs);">
            <option value="Nouveau" ${item.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
            <option value="En cours" ${item.status === 'En cours' ? 'selected' : ''}>En cours</option>
            <option value="Traite" ${item.status === 'Traite' ? 'selected' : ''}>Traité</option>
          </select>
        </td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon btn-view-demande" data-id="${item.id}" title="Consulter la demande" aria-label="Voir les détails">
              ${adminIcon('eye', { size: 16 })}
            </button>
            <button type="button" class="btn-icon btn-icon--danger btn-delete-demande" data-id="${item.id}" title="Supprimer la demande" aria-label="Supprimer">
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
        <h2 class="card-title">Demandes enregistrées (${list.length})</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Client</th>
              <th>Sujet & Message</th>
              <th>Statut</th>
              <th style="width: 100px;">Actions</th>
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
  // Changement rapide de statut
  container.querySelectorAll('.select-status-quick').forEach((sel) => {
    sel.addEventListener('change', (e) => {
      const id = e.target.dataset.id;
      const newStatus = e.target.value;
      if (updateDemandeStatus(id, newStatus)) {
        showToast(`Statut mis à jour : ${newStatus}`);
      }
    });
  });

  // Bouton voir
  container.querySelectorAll('.btn-view-demande').forEach((btn) => {
    btn.addEventListener('click', () => {
      openDrawer(btn.dataset.id);
    });
  });

  // Bouton supprimer
  container.querySelectorAll('.btn-delete-demande').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const ok = await confirmModal({
        title: 'Supprimer la demande',
        message: 'Êtes-vous sûr de vouloir supprimer définitivement cette demande ? Cette action est irréversible.',
        confirmText: 'Supprimer'
      });
      if (ok) {
        deleteDemande(id);
        showToast('Demande supprimée avec succès');
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
  const demande = getDemandeById(id);
  if (!demande) return;

  activeDemandeId = id;
  const drawer = document.getElementById('demande-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  if (!drawer) return;

  const isEstimation = demande.type === 'Estimation';
  const d = demande.details || {};

  let detailsBlock = '';
  if (isEstimation) {
    detailsBlock = `
      <div style="margin-bottom: 24px; padding: 18px; background-color: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
        <h4 style="margin: 0 0 14px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('building', { size: 16 })} Détails du bien à estimer
        </h4>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.86rem;">
          <div><span style="color: var(--admin-text-muted);">Type de bien :</span> <strong>${escapeHTML(d.type || 'Non spécifié')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Wilaya :</span> <strong>${escapeHTML(d.wilaya || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Commune :</span> <strong>${escapeHTML(d.commune || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Quartier :</span> <strong>${escapeHTML(d.quartier || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Surface habitable :</span> <strong>${d.surface ? escapeHTML(d.surface) + ' m²' : '—'}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Surface terrain :</span> <strong>${d.land ? escapeHTML(d.land) + ' m²' : '—'}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Chambres :</span> <strong>${escapeHTML(d.bedrooms || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Salles de bain :</span> <strong>${escapeHTML(d.bathrooms || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Garage / Parking :</span> <strong>${escapeHTML(d.garage || '—')}</strong></div>
        </div>
      </div>
    `;
  }

  drawer.innerHTML = `
    <div class="drawer-header">
      <div>
        <h3 class="drawer-title">${escapeHTML(demande.subject || 'Demande')}</h3>
        <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">
          Reçue le ${formatDateTime(demande.createdAt)}
        </p>
      </div>
      <button type="button" class="btn-icon" id="btn-close-drawer" aria-label="Fermer">
        ${adminIcon('close', { size: 18 })}
      </button>
    </div>

    <div class="drawer-body">
      <!-- Fiche Client -->
      <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid var(--admin-border-subtle);">
        <div class="user-avatar" style="width: 48px; height: 48px; font-size: 1.1rem; background-color: var(--admin-gold); color: #FFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
          ${escapeHTML(demande.nom?.charAt(0) || '?')}
        </div>
        <div style="flex: 1;">
          <h4 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--admin-text);">${escapeHTML(demande.nom || 'Anonyme')}</h4>
          <div style="display: flex; gap: 12px; margin-top: 4px; font-size: 0.84rem; flex-wrap: wrap;">
            ${demande.email ? `<a href="mailto:${escapeHTML(demande.email)}" style="color: var(--admin-gold); font-weight: 500;">✉ ${escapeHTML(demande.email)}</a>` : ''}
            ${demande.phone ? `<a href="tel:${escapeHTML(demande.phone)}" style="color: var(--admin-text-muted); font-weight: 500;">📞 ${escapeHTML(demande.phone)}</a>` : ''}
          </div>
        </div>
      </div>

      <!-- Statut de traitement -->
      <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--admin-surface-alt); border-radius: var(--radius-sm);">
        <span style="font-weight: 600; font-size: 0.86rem; color: var(--admin-text);">Statut actuel :</span>
        <select class="form-select" id="drawer-status-select" style="padding: 6px 12px; font-weight: 600;">
          <option value="Nouveau" ${demande.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
          <option value="En cours" ${demande.status === 'En cours' ? 'selected' : ''}>En cours</option>
          <option value="Traite" ${demande.status === 'Traite' ? 'selected' : ''}>Traité</option>
        </select>
      </div>

      <!-- Détails si estimation -->
      ${detailsBlock}

      <!-- Message du client -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text);">Message ou description</h4>
        <div style="padding: 16px; background-color: #FFF; border: 1px solid var(--admin-border); border-radius: var(--radius-sm); font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; color: var(--admin-text);">
          ${escapeHTML(demande.message || 'Aucun message textuel fourni.')}
        </div>
      </div>
    </div>

    <div class="drawer-footer">
      <button type="button" class="btn btn-danger-outline btn-sm" id="drawer-btn-delete">
        ${adminIcon('trash', { size: 14 })} Supprimer
      </button>

      <div style="display: flex; gap: 8px;">
        ${demande.phone ? `
          <a href="tel:${escapeHTML(demande.phone)}" class="btn btn-outline btn-sm">
            ${adminIcon('phone', { size: 14 })} Appeler
          </a>
        ` : ''}
        ${demande.email ? `
          <a href="mailto:${escapeHTML(demande.email)}?subject=Re: ${encodeURIComponent(demande.subject || 'Votre demande sur Imolode')}" class="btn btn-primary btn-sm">
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

  document.getElementById('drawer-btn-delete')?.addEventListener('click', async () => {
    const ok = await confirmModal({
      title: 'Supprimer la demande',
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

