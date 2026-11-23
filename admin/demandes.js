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
    const isFicheClient = item.type === 'Fiche client';

    let typeBadge = '';
    if (isFicheClient) {
      const isOffre = item.transaction === 'Offre';
      typeBadge = `<span class="badge ${isOffre ? 'badge--sale' : 'badge--info'}">${adminIcon('file-text', { size: 12 })} Dossier client (${escapeHTML(item.transaction || 'Demande')})</span>`;
    } else if (isEstimation) {
      typeBadge = `<span class="badge badge--sale">${adminIcon('building', { size: 12 })} Estimation</span>`;
    } else {
      typeBadge = `<span class="badge badge--info">${adminIcon('mail', { size: 12 })} Contact</span>`;
    }

    const hasDoc = Boolean(item.pieceIdentite?.name);
    const hasSig = Boolean(item.signature);
    const docTags = [];
    if (hasDoc) {
      docTags.push(`<span class="doc-badge" title="Pièce d'identité : ${escapeHTML(item.pieceIdentite.name)}">${adminIcon('file-text', { size: 11 })} CNI / Justificatif</span>`);
    }
    if (hasSig) {
      docTags.push(`<span class="badge badge--success" style="font-size: 0.7rem; padding: 2px 6px;">${adminIcon('check', { size: 10 })} Signé</span>`);
    }

    return `
      <tr data-id="${item.id}" title="Cliquez pour consulter le dossier complet">
        <td>
          <div style="font-weight: 600; color: var(--admin-text); font-size: 0.85rem;">
            ${formatDate(item.createdAt)}
          </div>
          <div style="font-size: 0.75rem; color: var(--admin-text-subtle);">
            ${item.reference ? `<span style="font-family: monospace; color: var(--admin-gold); font-weight: 600;">${escapeHTML(item.reference)}</span>` : (item.createdAt ? new Date(item.createdAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '')}
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
            <option value="Validé" ${item.status === 'Validé' ? 'selected' : ''}>Validé</option>
            <option value="Traite" ${item.status === 'Traite' ? 'selected' : ''}>Traité</option>
            <option value="Rejeté" ${item.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
          </select>
        </td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon btn-view-demande" data-id="${item.id}" title="Consulter la demande en détail" aria-label="Voir les détails">
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
        <h2 class="card-title">Demandes & Dossiers enregistrés (${list.length})</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Date & Réf</th>
              <th>Type de demande</th>
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
  // Ligne de tableau cliquable
  container.querySelectorAll('tbody tr').forEach((tr) => {
    tr.addEventListener('click', (e) => {
      if (e.target.closest('button') || e.target.closest('select') || e.target.closest('a')) {
        return;
      }
      openDrawer(tr.dataset.id);
    });
  });

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
  const isFicheClient = demande.type === 'Fiche client' || demande.isFicheClient;
  const d = demande.details || {};
  const doc = demande.pieceIdentite || {};
  const isImageDoc = doc.dataUrl && !doc.name?.toLowerCase().endsWith('.pdf');

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

  let ficheClientBlock = '';
  if (isFicheClient) {
    ficheClientBlock = `
      <!-- Nature de l'opération -->
      <div style="margin-bottom: 24px; padding: 18px; background-color: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
        <h4 style="margin: 0 0 14px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('building', { size: 16 })} Détails de la constitution de dossier
        </h4>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.88rem;">
          <div><span style="color: var(--admin-text-muted);">Opération :</span> <strong>${escapeHTML(demande.transaction || 'Demande')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Type de bien :</span> <strong>${escapeHTML(demande.typeBien || 'Appartement')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Référence :</span> <strong>${escapeHTML(demande.reference || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Date :</span> <strong>${formatDate(demande.createdAt)}</strong></div>
        </div>
      </div>

      <!-- Pièce d'identité avec fonction inspection / zoom -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('file-text', { size: 16 })} Justificatif d'identité (CNI, Permis, Passeport)
        </h4>

        ${doc.name ? `
          <div class="doc-inspect-card">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
              <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                ${adminIcon('file-text', { size: 22, className: 'text-gold' })}
                <div>
                  <p style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--admin-text);">${escapeHTML(doc.name)}</p>
                  <p style="margin: 2px 0 0; font-size: 0.78rem; color: var(--admin-text-muted);">
                    ${doc.size ? `${(doc.size / 1024).toFixed(1)} Ko` : 'Document vérifié'}
                  </p>
                </div>
              </div>
              <div style="display: flex; gap: 8px;">
                ${isImageDoc ? `
                  <button type="button" class="btn btn-outline btn-sm btn-zoom-doc" data-img="${escapeHTML(doc.dataUrl)}" data-caption="Carte d'identité — ${escapeHTML(demande.nom || '')}">
                    ${adminIcon('eye', { size: 14 })} Inspecter en grand
                  </button>
                ` : (doc.dataUrl ? `
                  <a href="${escapeHTML(doc.dataUrl)}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                    ${adminIcon('download', { size: 14 })} Télécharger le PDF
                  </a>
                ` : `
                  <span class="badge badge--success">${adminIcon('check', { size: 12 })} Certifié</span>
                `)}
              </div>
            </div>

            ${isImageDoc ? `
              <div class="doc-inspect-preview btn-zoom-doc" data-img="${escapeHTML(doc.dataUrl)}" data-caption="Carte d'identité — ${escapeHTML(demande.nom || '')}" title="Cliquez pour zoomer en plein écran">
                <img src="${escapeHTML(doc.dataUrl)}" alt="Pièce d'identité" class="doc-inspect-img">
                <div class="doc-inspect-overlay">
                  ${adminIcon('search', { size: 18 })}
                  <span>Cliquer pour inspecter en plein écran</span>
                </div>
              </div>
            ` : ''}
          </div>
        ` : `
          <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Aucune pièce d'identité jointe.</p>
        `}
      </div>

      <!-- Signature électronique -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('check', { size: 16 })} Engagement officiel & Signature électronique
        </h4>
        <div style="padding: 12px 14px; background-color: rgba(181, 154, 106, 0.08); border-left: 3px solid var(--admin-gold); border-radius: var(--radius-xs); font-size: 0.84rem; line-height: 1.5; color: var(--admin-text); margin-bottom: 10px;">
          « Ceci constitue un engagement officiel. En cas de transaction, le client est tenu d'honorer les frais de l'agence conformément aux dispositions réglementaires en vigueur. »
        </div>

        <div style="background: #FFFFFF; border: 1px solid var(--admin-border); border-radius: var(--radius-sm); padding: 16px; text-align: center;">
          ${demande.signature ? `
            <img src="${escapeHTML(demande.signature)}" alt="Signature électronique du client" style="max-height: 110px; max-width: 100%; display: inline-block; object-fit: contain;">
            <p style="margin: 8px 0 0; font-size: 0.75rem; color: var(--admin-text-muted);">
              Signature électronique manuscrite certifiée — Recueillie le ${formatDateTime(demande.createdAt)}
            </p>
          ` : `
            <p style="margin: 0; font-size: 0.85rem; color: var(--admin-text-muted);">Signature non enregistrée.</p>
          `}
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
            ${demande.phone ? `<span>•</span><a href="tel:${escapeHTML(demande.phone)}" style="color: var(--admin-text-muted); font-weight: 500;">📞 ${escapeHTML(demande.phone)}</a>` : ''}
          </div>
        </div>
      </div>

      <!-- Statut de traitement -->
      <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--admin-surface-alt); border-radius: var(--radius-sm);">
        <span style="font-weight: 600; font-size: 0.86rem; color: var(--admin-text);">Statut actuel :</span>
        <select class="form-select" id="drawer-status-select" style="padding: 6px 12px; font-weight: 600;">
          <option value="Nouveau" ${demande.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
          <option value="En cours" ${demande.status === 'En cours' ? 'selected' : ''}>En cours</option>
          <option value="Validé" ${demande.status === 'Validé' ? 'selected' : ''}>Validé</option>
          <option value="Traite" ${demande.status === 'Traite' || demande.status === 'Traité' ? 'selected' : ''}>Traité</option>
          <option value="Rejeté" ${demande.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
        </select>
      </div>

      <!-- Détails si fiche client -->
      ${ficheClientBlock}

      <!-- Détails si estimation -->
      ${detailsBlock}

      <!-- Message du client si contact classique -->
      ${!isFicheClient ? `
        <div style="margin-bottom: 24px;">
          <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text);">Message ou description</h4>
          <div style="padding: 16px; background-color: #FFF; border: 1px solid var(--admin-border); border-radius: var(--radius-sm); font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; color: var(--admin-text);">
            ${escapeHTML(demande.message || 'Aucun message textuel fourni.')}
          </div>
        </div>
      ` : ''}
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
          <a href="mailto:${escapeHTML(demande.email)}?subject=Re: ${encodeURIComponent(demande.subject || 'Votre dossier sur Imolode')}" class="btn btn-primary btn-sm">
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

  // Écouteurs pour agrandir la pièce d'identité en Lightbox
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
      <a href="${escapeHTML(imgUrl)}" download class="btn btn-outline btn-sm" style="color:#FFF; border-color:rgba(255,255,255,0.4);">
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
  const drawer = document.getElementById('demande-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  drawer?.classList.remove('is-open');
  backdrop?.classList.remove('is-open');
  activeDemandeId = null;
}

document.addEventListener('DOMContentLoaded', init);

