// admin/utilisateurs.js
// MODULE ES NAVIGATEUR — gestion des utilisateurs et conseillers de l'agence Imolode.

import {
  initAdminLayout, adminIcon, showToast, confirmModal, escapeHTML
} from '/admin/assets/js/admin.js';
import {
  getUsers, saveUser, deleteUser
} from '/admin/data/admin-store.js';

let editingUserId = null;

async function init() {
  const ready = await initAdminLayout({
    activePage: 'utilisateurs',
    title: 'Utilisateurs & Équipe',
    breadcrumbs: [{ label: 'Utilisateurs' }],
    action: {
      label: 'Ajouter un membre',
      href: '#',
      icon: 'plus',
      id: 'btn-add-user'
    }
  });

  if (!ready) return;

  const root = document.getElementById('utilisateurs-content');
  if (!root) return;

  root.innerHTML = `
    <!-- Entête avec explication -->
    <div style="margin-bottom: 24px;">
      <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
        Gérez les comptes d'accès à l'interface d'administration ainsi que les conseillers affichés sur le site public.
      </p>
    </div>

    <!-- Conteneur de la liste -->
    <div id="users-table-container"></div>
  `;

  // Écoute du bouton d'action du topbar
  document.getElementById('btn-add-user')?.addEventListener('click', (e) => {
    e.preventDefault();
    openUserDrawer();
  });

  setupDrawerListeners();
  renderUsers();
}

function renderUsers() {
  const container = document.getElementById('users-table-container');
  if (!container) return;

  const users = getUsers();

  if (users.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">
            ${adminIcon('users', { size: 28 })}
          </div>
          <h3 class="empty-state-title">Aucun utilisateur enregistré</h3>
          <p class="empty-state-text">
            Cliquez sur « Ajouter un membre » pour créer un nouveau compte.
          </p>
          <button type="button" class="btn btn-primary" id="btn-empty-add-user">
            ${adminIcon('plus', { size: 16 })} Ajouter un membre
          </button>
        </div>
      </div>
    `;
    document.getElementById('btn-empty-add-user')?.addEventListener('click', () => openUserDrawer());
    return;
  }

  const rows = users.map((u) => {
    const roleBadge = u.role === 'Administrateur'
      ? `<span class="badge badge--success">${u.role}</span>`
      : `<span class="badge badge--info">${u.role || 'Agent commercial'}</span>`;

    const statusBadgeHtml = u.actif !== false
      ? `<span class="badge badge--success">Actif</span>`
      : `<span class="badge badge--muted">Inactif</span>`;

    return `
      <tr data-id="${u.id}">
        <td>
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="user-avatar" style="width: 38px; height: 38px; border-radius: 50%; background-color: var(--admin-gold); color: #FFF; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
              ${escapeHTML(u.nom?.charAt(0) || 'U')}
            </div>
            <div>
              <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML(u.nom || 'Sans nom')}</div>
              <div style="font-size: 0.78rem; color: var(--admin-text-subtle);">${escapeHTML(u.poste || 'Conseiller')}</div>
            </div>
          </div>
        </td>
        <td>
          <div><a href="mailto:${escapeHTML(u.email || '')}" style="color: var(--admin-gold);">${escapeHTML(u.email || '—')}</a></div>
          <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 2px;">${escapeHTML(u.phone || '—')}</div>
        </td>
        <td>${roleBadge}</td>
        <td>${statusBadgeHtml}</td>
        <td>
          <div class="table-actions">
            <button type="button" class="btn-icon btn-edit-user" data-id="${u.id}" title="Modifier" aria-label="Modifier">
              ${adminIcon('edit', { size: 16 })}
            </button>
            <button type="button" class="btn-icon btn-icon--danger btn-delete-user" data-id="${u.id}" title="Supprimer" aria-label="Supprimer">
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
        <h2 class="card-title">Membres de l'équipe (${users.length})</h2>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Utilisateur / Conseiller</th>
              <th>Coordonnées</th>
              <th>Rôle</th>
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
  // Éditer
  container.querySelectorAll('.btn-edit-user').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      openUserDrawer(id);
    });
  });

  // Supprimer
  container.querySelectorAll('.btn-delete-user').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const ok = await confirmModal({
        title: 'Supprimer cet utilisateur',
        message: 'Êtes-vous certain de vouloir supprimer ce compte ?',
        confirmText: 'Supprimer'
      });
      if (ok) {
        deleteUser(id);
        showToast('Utilisateur supprimé');
        renderUsers();
      }
    });
  });
}

function setupDrawerListeners() {
  const backdrop = document.getElementById('user-drawer-backdrop');
  backdrop?.addEventListener('click', closeUserDrawer);
}

function openUserDrawer(id = null) {
  editingUserId = id;
  const drawer = document.getElementById('user-drawer');
  const backdrop = document.getElementById('user-drawer-backdrop');
  if (!drawer) return;

  const users = getUsers();
  const user = id ? users.find((u) => String(u.id) === String(id)) : null;
  const isNew = !user;

  drawer.innerHTML = `
    <div class="drawer-header">
      <div>
        <h3 class="drawer-title">${isNew ? 'Nouveau collaborateur' : 'Modifier le profil'}</h3>
        <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">
          ${isNew ? 'Renseignez les coordonnées et le rôle' : escapeHTML(user.nom)}
        </p>
      </div>
      <button type="button" class="btn-icon" id="btn-close-user-drawer" aria-label="Fermer">
        ${adminIcon('close', { size: 18 })}
      </button>
    </div>

    <form id="form-user-drawer" style="display: flex; flex-direction: column; flex: 1;">
      <div class="drawer-body">
        <div class="form-section">
          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-nom">Nom complet <span class="required">*</span></label>
            <input type="text" class="form-input" id="user-input-nom" required value="${escapeHTML(user?.nom || '')}" placeholder="Ex: G. Hocine">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-poste">Poste / Fonction <span class="required">*</span></label>
            <input type="text" class="form-input" id="user-input-poste" required value="${escapeHTML(user?.poste || '')}" placeholder="Ex: Gérant">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-email">Adresse e-mail <span class="required">*</span></label>
            <input type="email" class="form-input" id="user-input-email" required value="${escapeHTML(user?.email || '')}" placeholder="gahamhocine2@gmail.com">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-phone">Numéro de téléphone</label>
            <input type="text" class="form-input" id="user-input-phone" value="${escapeHTML(user?.phone || '')}" placeholder="0661.56.03.85">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-role">Rôle d'administration</label>
            <select class="form-select" id="user-input-role" style="width: 100%;">
              <option value="Gérant" ${user?.role === 'Gérant' ? 'selected' : ''}>Gérant (direction & accès complet)</option>
              <option value="Administrateur" ${user?.role === 'Administrateur' ? 'selected' : ''}>Administrateur (accès complet)</option>
              <option value="Agent commercial" ${user?.role === 'Agent commercial' || !user?.role ? 'selected' : ''}>Agent commercial (biens & demandes)</option>
              <option value="Gestionnaire" ${user?.role === 'Gestionnaire' ? 'selected' : ''}>Gestionnaire de biens</option>
            </select>
          </div>

          <div class="form-group">
            <label class="checkbox-label" style="margin-top: 8px;">
              <input type="checkbox" id="user-input-actif" ${user?.actif !== false ? 'checked' : ''}>
              <span>Compte actif (autorisé à se connecter)</span>
            </label>
          </div>
        </div>
      </div>

      <div class="drawer-footer">
        <button type="button" class="btn btn-outline btn-sm" id="btn-cancel-user">Annuler</button>
        <button type="submit" class="btn btn-primary btn-sm">
          ${adminIcon('check', { size: 14 })} ${isNew ? 'Créer le membre' : 'Enregistrer'}
        </button>
      </div>
    </form>
  `;

  drawer.classList.add('is-open');
  backdrop?.classList.add('is-open');

  document.getElementById('btn-close-user-drawer')?.addEventListener('click', closeUserDrawer);
  document.getElementById('btn-cancel-user')?.addEventListener('click', closeUserDrawer);

  const form = document.getElementById('form-user-drawer');
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const nom = document.getElementById('user-input-nom')?.value.trim();
    const poste = document.getElementById('user-input-poste')?.value.trim();
    const email = document.getElementById('user-input-email')?.value.trim();
    const phone = document.getElementById('user-input-phone')?.value.trim();
    const role = document.getElementById('user-input-role')?.value;
    const actif = document.getElementById('user-input-actif')?.checked;

    if (!nom || !email) {
      showToast('Le nom et l\'email sont obligatoires', 'error');
      return;
    }

    const userData = {
      ...(user || {}),
      nom,
      poste,
      email,
      phone,
      role,
      actif
    };

    if (id) {
      userData.id = id;
    }

    saveUser(userData);
    showToast(isNew ? 'Nouveau collaborateur ajouté' : 'Modifications enregistrées');
    closeUserDrawer();
    renderUsers();
  });
}

function closeUserDrawer() {
  const drawer = document.getElementById('user-drawer');
  const backdrop = document.getElementById('user-drawer-backdrop');
  drawer?.classList.remove('is-open');
  backdrop?.classList.remove('is-open');
  editingUserId = null;
}

document.addEventListener('DOMContentLoaded', init);

