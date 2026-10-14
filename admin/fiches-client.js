// admin/fiches-client.js
// MODULE ES NAVIGATEUR — gestion et consultation complète des fiches client (dossiers).

import {
  initAdminLayout, adminIcon, formatDateTime, formatDate,
  statusBadge, showToast, confirmModal, escapeHTML
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

    <!-- Barre de filtres et recherche -->
    <div class="filters-bar">
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

  bindFilterEvents();
  setupDrawerListeners();
  renderStats();
  renderResults();

  // Si un id est passé en paramètre URL, ouvrir automatiquement le tiroir
  const params = new URLSearchParams(window.location.search);
  const targetId = params.get('id');
  if (targetId) {
    openDrawer(targetId);
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

function renderResults() {
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
            Aucun dossier ne correspond à vos critères de recherche actuels.
          </p>
        </div>
      </div>
    `;
    return;
  }

  const rows = list.map((item) => {
    const isOffre = item.transaction === 'Offre';
    const transBadge = isOffre
      ? `<span class="badge badge--sale">${adminIcon('building', { size: 12 })} Offre</span>`
      : `<span class="badge badge--info">${adminIcon('search', { size: 12 })} Demande</span>`;

    const docName = item.pieceIdentite?.name || 'Document joint';
    const hasSignature = Boolean(item.signature);

    return `
      <tr data-id="${item.id}">
        <td>
          <div style="font-weight: 600; color: var(--admin-text); font-size: 0.85rem;">
            ${formatDate(item.createdAt)}
          </div>
          <div style="font-size: 0.76rem; font-family: monospace; color: var(--admin-gold); font-weight: 600;">
            ${escapeHTML(item.reference || 'IML-DOS')}
          </div>
        </td>
        <td>
          <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML(item.nom || '')} ${escapeHTML(item.prenom || '')}</div>
          <div style="font-size: 0.78rem; color: var(--admin-text-muted); display: flex; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
            ${item.email ? `<a href="mailto:${escapeHTML(item.email)}" style="color: var(--admin-gold);">${escapeHTML(item.email)}</a>` : ''}
            ${item.telephone ? `<span>•</span><a href="tel:${escapeHTML(item.telephone)}">${escapeHTML(item.telephone)}</a>` : ''}
          </div>
        </td>
        <td>${transBadge}</td>
        <td>
          <span style="font-weight: 600; font-size: 0.88rem; color: var(--admin-text);">${escapeHTML(item.typeBien || '—')}</span>
        </td>
        <td>
          <span class="doc-badge" title="${escapeHTML(docName)}">
            ${adminIcon('file-text', { size: 13 })}
            <span>${escapeHTML(docName)}</span>
          </span>
        </td>
        <td>
          ${hasSignature ? `
            <div style="display: flex; align-items: center; gap: 6px;">
              <img src="${escapeHTML(item.signature)}" alt="Signature" class="fiche-signature-preview" title="Signature manuscrite enregistrée">
            </div>
          ` : `
            <span style="font-size: 0.75rem; color: var(--admin-text-subtle);">Non signée</span>
          `}
        </td>
        <td>
          <select class="form-select select-status-quick" data-id="${item.id}" style="padding: 4px 8px; font-size: 0.8rem; border-radius: var(--radius-xs);">
            <option value="Nouveau" ${item.status === 'Nouveau' ? 'selected' : ''}>Nouveau</option>
            <option value="En cours" ${item.status === 'En cours' ? 'selected' : ''}>En cours</option>
            <option value="Validé" ${item.status === 'Validé' ? 'selected' : ''}>Validé</option>
            <option value="Traité" ${item.status === 'Traité' ? 'selected' : ''}>Traité</option>
            <option value="Rejeté" ${item.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
          </select>
        </td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon btn-view-fiche" data-id="${item.id}" title="Consulter le dossier complet" aria-label="Consulter">
              ${adminIcon('eye', { size: 16 })}
            </button>
            <button type="button" class="btn-icon btn-icon--danger btn-delete-fiche" data-id="${item.id}" title="Supprimer le dossier" aria-label="Supprimer">
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
        <h2 class="card-title">Dossiers clients répertoriés (${list.length})</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Date & Réf</th>
              <th>Client</th>
              <th>Opération</th>
              <th>Bien</th>
              <th>Pièce d'identité</th>
              <th>Signature</th>
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
  // Clic sur une ligne du tableau pour ouvrir le tiroir
  container.querySelectorAll('.admin-table tbody tr').forEach((tr) => {
    tr.addEventListener('click', (e) => {
      if (
        e.target.closest('button') ||
        e.target.closest('a') ||
        e.target.closest('select') ||
        e.target.closest('input')
      ) {
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
      if (updateFicheClientStatus(id, newStatus)) {
        showToast(`Statut mis à jour : ${newStatus}`);
        renderStats();
      }
    });
  });

  // Bouton voir fiche
  container.querySelectorAll('.btn-view-fiche').forEach((btn) => {
    btn.addEventListener('click', () => {
      openDrawer(btn.dataset.id);
    });
  });

  // Bouton supprimer fiche
  container.querySelectorAll('.btn-delete-fiche').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const ok = await confirmModal({
        title: 'Supprimer la fiche client',
        message: 'Êtes-vous sûr de vouloir supprimer définitivement ce dossier client ? Cette action est irréversible.',
        confirmText: 'Supprimer'
      });
      if (ok) {
        deleteFicheClient(id);
        showToast('Fiche client supprimée avec succès');
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
  const drawer = document.getElementById('fiche-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  if (!drawer) return;

  const isOffre = fiche.transaction === 'Offre';
  const doc = fiche.pieceIdentite || {};
  const isImageDoc = doc.dataUrl && !doc.name?.toLowerCase().endsWith('.pdf');

  drawer.innerHTML = `
    <div class="drawer-header">
      <div>
        <h3 class="drawer-title">Dossier ${escapeHTML(fiche.reference || 'IML-DOS')}</h3>
        <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">
          Reçu le ${formatDateTime(fiche.createdAt)}
        </p>
      </div>
      <button type="button" class="btn-icon" id="btn-close-drawer" aria-label="Fermer">
        ${adminIcon('close', { size: 18 })}
      </button>
    </div>

    <div class="drawer-body">
      <!-- Fiche Client & Identité -->
      <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid var(--admin-border-subtle);">
        <div class="user-avatar" style="width: 48px; height: 48px; font-size: 1.1rem; background-color: var(--admin-gold); color: #FFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
          ${escapeHTML(fiche.nom?.charAt(0) || 'C')}
        </div>
        <div style="flex: 1;">
          <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--admin-text);">
            ${escapeHTML(fiche.nom || '')} ${escapeHTML(fiche.prenom || '')}
          </h4>
          <div style="display: flex; gap: 12px; margin-top: 4px; font-size: 0.84rem; flex-wrap: wrap;">
            ${fiche.email ? `<a href="mailto:${escapeHTML(fiche.email)}" style="color: var(--admin-gold); font-weight: 500;">✉ ${escapeHTML(fiche.email)}</a>` : ''}
            ${fiche.telephone ? `<span>•</span><a href="tel:${escapeHTML(fiche.telephone)}" style="color: var(--admin-text); font-weight: 500;">📞 ${escapeHTML(fiche.telephone)}</a>` : ''}
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
          <option value="Traité" ${fiche.status === 'Traité' ? 'selected' : ''}>Traité</option>
          <option value="Rejeté" ${fiche.status === 'Rejeté' ? 'selected' : ''}>Rejeté</option>
        </select>
      </div>

      <!-- Objet de la fiche -->
      <div style="margin-bottom: 24px; padding: 18px; background-color: var(--admin-surface-alt); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
        <h4 style="margin: 0 0 14px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('building', { size: 16 })} Nature de la demande
        </h4>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.88rem;">
          <div><span style="color: var(--admin-text-muted);">Opération :</span> <strong>${escapeHTML(fiche.transaction || 'Demande')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Type de bien :</span> <strong>${escapeHTML(fiche.typeBien || 'Appartement')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Référence :</span> <strong>${escapeHTML(fiche.reference || '—')}</strong></div>
          <div><span style="color: var(--admin-text-muted);">Date :</span> <strong>${formatDate(fiche.createdAt)}</strong></div>
        </div>
      </div>

      <!-- Pièce d'identité jointe avec inspection haute résolution -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('file-text', { size: 16 })} Justificatif d'identité (CNI, Permis, Passeport)
        </h4>
        ${doc.name ? `
          <div class="doc-inspect-card">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
              <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                ${adminIcon('file-text', { size: 22, className: 'text-gold' })}
                <div style="min-width: 0;">
                  <p style="margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--admin-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${escapeHTML(doc.name)}
                  </p>
                  <p style="margin: 2px 0 0; font-size: 0.78rem; color: var(--admin-text-muted);">
                    ${doc.size ? `${(doc.size / 1024).toFixed(1)} Ko` : 'Fichier vérifié'}
                  </p>
                </div>
              </div>
              <div style="display: flex; gap: 8px;">
                ${isImageDoc ? `
                  <button type="button" class="btn btn-outline btn-sm btn-zoom-doc" data-img="${escapeHTML(doc.dataUrl)}" data-caption="Pièce d'identité — ${escapeHTML(fiche.nom || '')} ${escapeHTML(fiche.prenom || '')}">
                    ${adminIcon('eye', { size: 14 })} Inspecter en grand
                  </button>
                ` : (doc.dataUrl ? `
                  <a href="${escapeHTML(doc.dataUrl)}" target="_blank" rel="noopener" download="${escapeHTML(doc.name)}" class="btn btn-outline btn-sm">
                    ${adminIcon('download', { size: 14 })} Télécharger le PDF
                  </a>
                ` : `
                  <span class="badge badge--success">${adminIcon('check', { size: 12 })} Certifié</span>
                `)}
              </div>
            </div>

            ${isImageDoc ? `
              <div class="doc-inspect-preview btn-zoom-doc" data-img="${escapeHTML(doc.dataUrl)}" data-caption="Pièce d'identité — ${escapeHTML(fiche.nom || '')} ${escapeHTML(fiche.prenom || '')}" title="Cliquez pour zoomer en plein écran">
                <img src="${escapeHTML(doc.dataUrl)}" alt="Pièce d'identité" class="doc-inspect-img">
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

      <!-- Signature et Engagement officiel -->
      <div style="margin-bottom: 24px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
          ${adminIcon('check', { size: 16 })} Engagement et Signature Électronique
        </h4>
        <div style="padding: 12px 14px; background-color: rgba(181, 154, 106, 0.08); border-left: 3px solid var(--admin-gold); border-radius: var(--radius-xs); font-size: 0.84rem; line-height: 1.5; color: var(--admin-text); margin-bottom: 10px;">
          « Ceci constitue un engagement officiel. En cas de transaction, le client est tenu d'honorer les frais de l'agence conformément aux dispositions réglementaires en vigueur. »
        </div>

        <div class="drawer-signature-box">
          ${fiche.signature ? `
            <img src="${escapeHTML(fiche.signature)}" alt="Signature électronique du client" class="drawer-signature-img">
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
        <button type="button" class="btn btn-outline btn-sm" id="drawer-btn-print" title="Imprimer le dossier">
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

  document.getElementById('drawer-btn-print')?.addEventListener('click', () => {
    window.print();
  });

  document.getElementById('drawer-btn-delete')?.addEventListener('click', async () => {
    const ok = await confirmModal({
      title: 'Supprimer la fiche client',
      message: 'Êtes-vous sûr de vouloir supprimer définitivement ce dossier client ?',
      confirmText: 'Supprimer'
    });
    if (ok) {
      deleteFicheClient(id);
      showToast('Dossier client supprimé');
      closeDrawer();
      renderStats();
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
  const drawer = document.getElementById('fiche-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  drawer?.classList.remove('is-open');
  backdrop?.classList.remove('is-open');
  activeFicheId = null;
}

init();
