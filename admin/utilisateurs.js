// admin/utilisateurs.js
// MODULE ES NAVIGATEUR — gestion des utilisateurs et conseillers de l'agence Imolode.

import {
  initAdminLayout, adminIcon, showToast, confirmModal, openCsvImportModal, escapeHTML
} from '/admin/assets/js/admin.js';
import { getCurrentUser } from '/admin/includes/auth.js';
import {
  getUsers, saveUser, deleteUser
} from '/admin/data/admin-store.js';

let editingUserId = null;
let selectedUserIds = new Set();

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
    <!-- Entête avec explication & bouton d'import CSV -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
      <div>
        <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
          Gérez les comptes d'accès à l'interface d'administration ainsi que les conseillers affichés sur le site public.
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-import-users">
          ${adminIcon('upload', { size: 15 })}
          <span>Importer CSV / Excel</span>
        </button>
      </div>
    </div>

    <!-- Barre d'actions groupées -->
    <div id="users-bulk-bar" class="bulk-actions-bar" style="display: none;">
      <div class="bulk-actions-info">
        <span id="users-selected-count">0 sélectionné(s)</span>
      </div>
      <div class="bulk-actions-btns">
        <button type="button" class="btn btn-outline btn-sm" id="btn-cancel-users-bulk">Annuler</button>
        <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected-users">
          ${adminIcon('trash', { size: 14 })} Supprimer la sélection
        </button>
      </div>
    </div>

    <!-- Conteneur de la liste -->
    <div id="users-table-container"></div>
  `;

  // Écoute du bouton d'action du topbar "Ajouter un membre"
  document.getElementById('btn-add-user')?.addEventListener('click', (e) => {
    e.preventDefault();
    openUserDrawer();
  });

  // Bouton d'import CSV / Excel
  document.getElementById('btn-import-users')?.addEventListener('click', () => {
    openCsvImportModal({
      type: 'users',
      title: 'Importer des membres de l\'équipe',
      onSuccess: () => {
        setTimeout(() => window.location.reload(), 400);
      }
    });
  });

  // Bouton de suppression groupée
  document.getElementById('btn-delete-selected-users')?.addEventListener('click', async () => {
    if (selectedUserIds.size === 0) return;
    const ok = await confirmModal({
      title: 'Suppression groupée',
      message: `Êtes-vous sûr de vouloir supprimer les ${selectedUserIds.size} utilisateur(s) sélectionné(s) ?`,
      confirmText: 'Supprimer définitivement'
    });

    if (ok) {
      try {
        const res = await fetch('/api/users.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'bulk_delete',
            ids: Array.from(selectedUserIds)
          })
        });
        const data = await res.json();
        showToast(data.message || 'Membres supprimés avec succès');
      } catch (_) {
        // Fallback local
        selectedUserIds.forEach(id => deleteUser(id));
        showToast('Membres supprimés');
      }
      selectedUserIds.clear();
      updateBulkBar();
      renderUsers();
    }
  });

  document.getElementById('btn-cancel-users-bulk')?.addEventListener('click', () => {
    selectedUserIds.clear();
    updateBulkBar();
    renderUsers();
  });

  setupDrawerListeners();
  renderUsers();
}

function updateBulkBar() {
  const bar = document.getElementById('users-bulk-bar');
  const countEl = document.getElementById('users-selected-count');
  if (!bar) return;

  if (selectedUserIds.size > 0) {
    bar.style.display = 'flex';
    if (countEl) countEl.textContent = `${selectedUserIds.size} membre(s) sélectionné(s)`;
  } else {
    bar.style.display = 'none';
  }
}

function renderUsers() {
  const container = document.getElementById('users-table-container');
  if (!container) return;

  const users = getUsers();
  const currentUser = getCurrentUser();

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
    const isSelf = currentUser && (
      (currentUser.id && String(currentUser.id) === String(u.id)) ||
      (currentUser.email && currentUser.email.toLowerCase() === (u.email || '').toLowerCase())
    );

    const roleBadge = u.role === 'Administrateur' || u.role === 'Gérant'
      ? `<span class="badge badge--success">${escapeHTML(u.role)}</span>`
      : `<span class="badge badge--info">${escapeHTML(u.role || 'Agent commercial')}</span>`;

    const statusBadgeHtml = u.actif !== false
      ? `<span class="badge badge--success">Actif</span>`
      : `<span class="badge badge--muted">Inactif</span>`;

    const avatarHtml = u.avatar
      ? `<img src="${escapeHTML(u.avatar)}" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`
      : escapeHTML(u.nom?.charAt(0) || 'U');

    // Pour son propre compte : verrouillage pour éviter de se modifier par erreur (Point 21)
    let actionsHtml = '';
    if (isSelf) {
      actionsHtml = `
        <div style="display: flex; align-items: center; gap: 6px;">
          <span class="badge badge--gold" style="font-size: 0.72rem;" title="Votre propre compte de gérant. Pour modifier vos informations, utilisez 'Mon profil' dans la barre latérale.">Votre compte</span>
          <button type="button" class="btn-icon" disabled style="opacity: 0.35; cursor: not-allowed;" title="Modification désactivée ici pour éviter toute erreur. Rendez-vous dans 'Mon profil'">${adminIcon('lock', { size: 15 })}</button>
        </div>
      `;
    } else {
      actionsHtml = `
        <div class="table-actions">
          <button type="button" class="btn-icon btn-edit-user" data-id="${u.id}" title="Modifier" aria-label="Modifier">
            ${adminIcon('edit', { size: 16 })}
          </button>
          <button type="button" class="btn-icon btn-icon--danger btn-delete-user" data-id="${u.id}" title="Supprimer" aria-label="Supprimer">
            ${adminIcon('trash', { size: 16 })}
          </button>
        </div>
      `;
    }

    const isChecked = selectedUserIds.has(String(u.id));

    return `
      <tr data-id="${u.id}">
        <td style="width: 40px;">
          ${isSelf ? '' : `<input type="checkbox" class="admin-checkbox row-user-checkbox" data-id="${u.id}" ${isChecked ? 'checked' : ''}>`}
        </td>
        <td>
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="user-avatar" style="width: 38px; height: 38px; border-radius: 50%; background-color: var(--admin-gold); color: #FFF; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; overflow: hidden;">
              ${avatarHtml}
            </div>
            <div>
              <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML(u.nom || 'Sans nom')}</div>
              <div style="font-size: 0.78rem; color: var(--admin-text-subtle);">${escapeHTML(u.poste || 'Conseiller')}</div>
            </div>
          </div>
        </td>
        <td>
          <div><a href="mailto:${escapeHTML(u.email || '')}" style="color: var(--admin-gold);">${escapeHTML(u.email || '—')}</a></div>
          <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 2px;">${escapeHTML(u.phone || u.telephone || '—')}</div>
        </td>
        <td>${roleBadge}</td>
        <td>${statusBadgeHtml}</td>
        <td style="text-align: right;">${actionsHtml}</td>
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
              <th style="width: 40px;">
                <input type="checkbox" id="users-select-all" class="admin-checkbox" title="Tout sélectionner">
              </th>
              <th>Utilisateur / Conseiller</th>
              <th>Coordonnées</th>
              <th>Rôle</th>
              <th>Statut</th>
              <th style="width: 120px; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </div>
    </div>
  `;

  // Gestion des cases à cocher
  const selectAll = document.getElementById('users-select-all');
  selectAll?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    container.querySelectorAll('.row-user-checkbox').forEach((cb) => {
      cb.checked = checked;
      const id = cb.dataset.id;
      if (checked) selectedUserIds.add(String(id));
      else selectedUserIds.delete(String(id));
    });
    updateBulkBar();
  });

  container.querySelectorAll('.row-user-checkbox').forEach((cb) => {
    cb.addEventListener('change', () => {
      const id = cb.dataset.id;
      if (cb.checked) selectedUserIds.add(String(id));
      else selectedUserIds.delete(String(id));
      updateBulkBar();
    });
  });

  // Boutons Modifier
  container.querySelectorAll('.btn-edit-user').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      openUserDrawer(id);
    });
  });

  // Boutons Supprimer
  container.querySelectorAll('.btn-delete-user').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const user = users.find((u) => String(u.id) === String(id));
      const ok = await confirmModal({
        title: 'Supprimer ce collaborateur ?',
        message: `Êtes-vous sûr de vouloir supprimer "${user?.nom || 'cet utilisateur'}" ? Cette action est irréversible.`,
        confirmText: 'Supprimer'
      });

      if (ok) {
        deleteUser(id);
        selectedUserIds.delete(String(id));
        updateBulkBar();
        showToast('Collaborateur supprimé');
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
  const currentUser = getCurrentUser();
  if (id && currentUser && String(currentUser.id) === String(id)) {
    showToast('Pour modifier vos propres informations de gérant, veuillez utiliser le menu "Mon profil".', 'error');
    return;
  }

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
        <h3 class="drawer-title">${isNew ? 'Ajouter un membre' : 'Modifier le membre'}</h3>
        <p class="drawer-subtitle" style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.85rem;">
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
          <!-- Photo avatar -->
          <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding: 12px; background: var(--admin-surface-alt); border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
            <div id="drawer-avatar-preview" style="width: 56px; height: 56px; border-radius: 50%; background: var(--admin-gold); color: #FFF; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; overflow: hidden;">
              ${user?.avatar ? `<img src="${escapeHTML(user.avatar)}" style="width:100%; height:100%; object-fit:cover;">` : escapeHTML(user?.nom?.charAt(0) || 'U')}
            </div>
            <div>
              <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 4px;">Photo du collaborateur</div>
              <input type="file" id="drawer-avatar-file" accept="image/*" style="display: none;">
              <input type="hidden" id="user-input-avatar" value="${escapeHTML(user?.avatar || '')}">
              <button type="button" class="btn btn-outline btn-sm" id="btn-upload-drawer-avatar">
                ${adminIcon('upload', { size: 14 })} Téléverser une photo
              </button>
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-nom">Nom complet <span class="required">*</span></label>
            <input type="text" class="form-input" id="user-input-nom" required value="${escapeHTML(user?.nom || '')}" placeholder="Ex: G. Hocine">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-poste">Poste / Fonction <span class="required">*</span></label>
            <input type="text" class="form-input" id="user-input-poste" required value="${escapeHTML(user?.poste || '')}" placeholder="Ex: Conseiller immobilier">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-email">Adresse e-mail <span class="required">*</span></label>
            <input type="email" class="form-input" id="user-input-email" required value="${escapeHTML(user?.email || '')}" placeholder="collaborateur@immolode.dz">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-phone">Numéro de téléphone</label>
            <input type="text" class="form-input" id="user-input-phone" value="${escapeHTML(user?.phone || user?.telephone || '')}" placeholder="0550.12.34.56">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-role">Rôle d'administration</label>
            <select class="form-select" id="user-input-role" style="width: 100%;">
              <option value="Agent commercial" ${user?.role === 'Agent commercial' || !user?.role ? 'selected' : ''}>Agent commercial (biens & demandes)</option>
              <option value="Gestionnaire" ${user?.role === 'Gestionnaire' ? 'selected' : ''}>Gestionnaire de biens</option>
              <option value="Administrateur" ${user?.role === 'Administrateur' ? 'selected' : ''}>Administrateur (accès complet)</option>
              <option value="Gérant" ${user?.role === 'Gérant' ? 'selected' : ''}>Gérant (direction)</option>
            </select>
          </div>

          ${isNew ? `
            <div class="form-group" style="margin-bottom: 16px;">
              <label class="form-label" for="user-input-password">Mot de passe provisoire</label>
              <input type="password" class="form-input" id="user-input-password" value="admin123" placeholder="admin123">
            </div>
          ` : ''}

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

  // Upload photo
  const fileInput = document.getElementById('drawer-avatar-file');
  document.getElementById('btn-upload-drawer-avatar')?.addEventListener('click', () => fileInput?.click());
  fileInput?.addEventListener('change', async () => {
    const file = fileInput.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('folder', 'users');
    try {
      const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (res.ok && data.data?.url) {
        document.getElementById('user-input-avatar').value = data.data.url;
        document.getElementById('drawer-avatar-preview').innerHTML = `<img src="${data.data.url}" style="width:100%; height:100%; object-fit:cover;">`;
        showToast('Photo téléversée !');
      }
    } catch (_) {
      showToast('Erreur téléversement', 'error');
    }
  });

  const form = document.getElementById('form-user-drawer');
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const nom = document.getElementById('user-input-nom')?.value.trim();
    const poste = document.getElementById('user-input-poste')?.value.trim();
    const email = document.getElementById('user-input-email')?.value.trim();
    const phone = document.getElementById('user-input-phone')?.value.trim();
    const role = document.getElementById('user-input-role')?.value;
    const avatar = document.getElementById('user-input-avatar')?.value;
    const actif = document.getElementById('user-input-actif')?.checked;
    const password = document.getElementById('user-input-password')?.value;

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
      telephone: phone,
      role,
      avatar,
      actif
    };

    if (password) userData.password = password;
    if (id) userData.id = id;

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
