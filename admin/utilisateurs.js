// admin/utilisateurs.js
// MODULE ES NAVIGATEUR — gestion des utilisateurs et conseillers de l'agence Imolode.

import {
  initAdminLayout, adminIcon, showToast, confirmModal, escapeHTML, openCsvImportModal, exportRecords
} from '/admin/assets/js/admin.js';
import {
  getUsers, saveUser, deleteUser, bulkDeleteUsers
} from '/admin/data/admin-store.js';
import { getCurrentUser } from '/admin/includes/auth.js';

let editingUserId = null;
let isSelectMode = false;
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
    <!-- Barre d'outils et actions globales -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
      <p style="color: var(--admin-text-muted); font-size: 0.92rem; margin: 0;">
        Gérez les comptes d'accès à l'interface d'administration ainsi que les conseillers affichés sur le site public.
      </p>

      <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline btn-sm" id="btn-import-users" title="Importer des membres depuis un fichier Excel ou CSV">
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
    <div id="users-bulk-bar-container"></div>

    <!-- Conteneur de la liste -->
    <div id="users-table-container"></div>
  `;

  // Bouton topbar
  document.getElementById('btn-add-user')?.addEventListener('click', (e) => {
    e.preventDefault();
    openUserDrawer();
  });

  // Bouton importer
  document.getElementById('btn-import-users')?.addEventListener('click', () => {
    openCsvImportModal({ model: 'utilisateurs', title: 'Importer des utilisateurs', onImportSuccess: () => {
      renderUsers();
    } });
  });

  // Bouton mode sélection
  document.getElementById('btn-toggle-select-mode')?.addEventListener('click', () => {
    isSelectMode = !isSelectMode;
    selectedUserIds.clear();
    const btn = document.getElementById('btn-toggle-select-mode');
    if (btn) {
      btn.className = `btn ${isSelectMode ? 'btn-primary' : 'btn-outline'} btn-sm`;
      btn.innerHTML = `${adminIcon('check', { size: 14 })} <span>${isSelectMode ? 'Annuler la sélection' : 'Sélectionner'}</span>`;
    }
    renderUsers();
  });

  setupDrawerListeners();
  renderUsers();
}

function renderBulkBar() {
  const container = document.getElementById('users-bulk-bar-container');
  if (!container) return;

  if (!isSelectMode || selectedUserIds.size === 0) {
    container.innerHTML = '';
    return;
  }

  container.innerHTML = `
    <div class="bulk-actions-bar is-visible">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span class="bulk-badge">${selectedUserIds.size}</span>
        <span style="font-weight: 600;">membre${selectedUserIds.size > 1 ? 's' : ''} sélectionné${selectedUserIds.size > 1 ? 's' : ''}</span>
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
          <span>Supprimer (${selectedUserIds.size})</span>
        </button>
      </div>
    </div>
  `;

  document.getElementById('btn-bulk-cancel')?.addEventListener('click', () => {
    selectedUserIds.clear();
    renderUsers();
  });

  const exportSelected = (format) => {
    const selected = getUsers().filter((u) => selectedUserIds.has(String(u.id)));
    exportRecords({
      filename: `membres_immolode_${new Date().toISOString().slice(0, 10)}`,
      format,
      rows: selected,
      columns: [
        { label: 'ID', key: 'id' },
        { label: 'Nom', key: 'nom' },
        { label: 'Poste', key: 'poste' },
        { label: 'Email', key: 'email' },
        { label: 'Téléphone', key: 'phone' },
        { label: 'Rôle', key: 'role' },
        { label: 'Actif', value: (r) => (r.actif !== false ? 'Oui' : 'Non') }
      ]
    });
  };
  document.getElementById('btn-bulk-export-csv')?.addEventListener('click', () => exportSelected('csv'));
  document.getElementById('btn-bulk-export-excel')?.addEventListener('click', () => exportSelected('excel'));

  document.getElementById('btn-bulk-delete')?.addEventListener('click', async () => {
    const count = selectedUserIds.size;
    const ok = await confirmModal({
      title: 'Suppression groupée de membres',
      message: `Êtes-vous certain de vouloir supprimer définitivement ${count} membre${count > 1 ? 's' : ''} ? Cette action est irréversible.`,
      confirmText: `Supprimer ${count} membre${count > 1 ? 's' : ''}`,
      cancelText: 'Annuler'
    });

    if (ok) {
      await bulkDeleteUsers(Array.from(selectedUserIds));
      showToast(`${count} membre${count > 1 ? 's' : ''} supprimé${count > 1 ? 's' : ''}`);
      selectedUserIds.clear();
      renderUsers();
    }
  });
}

function renderUsers() {
  renderBulkBar();
  const container = document.getElementById('users-table-container');
  if (!container) return;

  const users = getUsers();
  const currentUser = getCurrentUser() || {};

  if (users.length === 0) {
    container.innerHTML = `
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon">
            ${adminIcon('users', { size: 28 })}
          </div>
          <h3 class="empty-state-title">Aucun utilisateur enregistré</h3>
          <p class="empty-state-text">
            Cliquez sur « Ajouter un membre » ou importez un fichier CSV pour créer un nouveau compte.
          </p>
          <div style="display: flex; gap: 10px; margin-top: 14px;">
            <button type="button" class="btn btn-primary" id="btn-empty-add-user">
              ${adminIcon('plus', { size: 16 })} Ajouter un membre
            </button>
          </div>
        </div>
      </div>
    `;
    document.getElementById('btn-empty-add-user')?.addEventListener('click', () => openUserDrawer());
    return;
  }

  const rows = users.map((u) => {
    const isSelf = String(u.id) === String(currentUser.id) || (currentUser.email && u.email && currentUser.email.toLowerCase() === u.email.toLowerCase());

    const roleBadge = u.role === 'Administrateur' || u.role === 'Gérant'
      ? `<span class="badge badge--success">${escapeHTML(u.role)}</span>`
      : `<span class="badge badge--info">${escapeHTML(u.role || 'Agent commercial')}</span>`;

    const statusBadgeHtml = u.actif !== false
      ? `<span class="badge badge--success">Actif</span>`
      : `<span class="badge badge--muted">Inactif</span>`;

    const avatarHtml = u.avatar
      ? `<img src="${escapeHTML(u.avatar)}" alt="${escapeHTML(u.nom || '')}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;">`
      : `<div class="user-avatar" style="width: 38px; height: 38px; border-radius: 50%; background-color: var(--admin-gold); color: #FFF; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
          ${escapeHTML(u.nom?.charAt(0) || 'U')}
        </div>`;

    const checkboxTd = isSelectMode ? `
      <td style="width: 44px; text-align: center;">
        ${isSelf ? `
          <input type="checkbox" disabled title="Votre propre compte ne peut être sélectionné pour suppression" style="opacity: 0.35; cursor: not-allowed;">
        ` : `
          <input type="checkbox" class="user-row-checkbox" data-id="${u.id}" ${selectedUserIds.has(String(u.id)) ? 'checked' : ''}>
        `}
      </td>
    ` : '';

    const actionsTd = isSelf ? `
      <td>
        <span style="font-size: 0.76rem; font-weight: 600; color: var(--admin-gold); background: rgba(181, 154, 106, 0.12); padding: 4px 8px; border-radius: var(--radius-xs); white-space: nowrap; display: inline-block;">
          Votre compte (Mon Profil)
        </span>
      </td>
    ` : `
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
    `;

    return `
      <tr data-id="${u.id}" class="${selectedUserIds.has(String(u.id)) ? 'is-selected' : ''}">
        ${checkboxTd}
        <td>
          <div style="display: flex; align-items: center; gap: 12px;">
            ${avatarHtml}
            <div>
              <div style="font-weight: 600; color: var(--admin-text); display: flex; align-items: center; gap: 6px;">
                ${escapeHTML(u.nom || 'Sans nom')}
                ${isSelf ? `<span class="badge badge--gold" style="font-size: 0.68rem; padding: 1px 6px;">Vous</span>` : ''}
              </div>
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
        ${actionsTd}
      </tr>
    `;
  }).join('');

  const selectableUsers = users.filter((u) => {
    return !(String(u.id) === String(currentUser.id) || (currentUser.email && u.email && currentUser.email.toLowerCase() === u.email.toLowerCase()));
  });
  const allSelectableChecked = selectableUsers.length > 0 && selectableUsers.every((u) => selectedUserIds.has(String(u.id)));

  container.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Membres de l'équipe (${users.length})</h2>
          <p class="card-subtitle">Administrateurs et agents autorisés</p>
        </div>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              ${isSelectMode ? `
                <th style="width: 44px; text-align: center;">
                  <input type="checkbox" id="check-all-users" ${allSelectableChecked ? 'checked' : ''} title="Tout sélectionner">
                </th>
              ` : ''}
              <th>Utilisateur / Conseiller</th>
              <th>Coordonnées</th>
              <th>Rôle</th>
              <th>Statut</th>
              <th style="width: 140px;">Actions</th>
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
  // Checkbox tout sélectionner
  const checkAll = container.querySelector('#check-all-users');
  checkAll?.addEventListener('change', (e) => {
    const checked = e.target.checked;
    const users = getUsers();
    const currentUser = getCurrentUser() || {};

    users.forEach((u) => {
      const isSelf = String(u.id) === String(currentUser.id) || (currentUser.email && u.email && currentUser.email.toLowerCase() === u.email.toLowerCase());
      if (!isSelf) {
        if (checked) selectedUserIds.add(String(u.id));
        else selectedUserIds.delete(String(u.id));
      }
    });

    renderUsers();
  });

  // Checkbox individuel
  container.querySelectorAll('.user-row-checkbox').forEach((cb) => {
    cb.addEventListener('change', (e) => {
      const id = e.target.dataset.id;
      if (e.target.checked) selectedUserIds.add(id);
      else selectedUserIds.delete(id);
      renderUsers();
    });
  });

  // Clic sur ligne en mode sélection
  container.querySelectorAll('tbody tr').forEach((tr) => {
    tr.addEventListener('click', (e) => {
      if (!isSelectMode) return;
      if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input[type="checkbox"]')) {
        return;
      }
      const cb = tr.querySelector('.user-row-checkbox');
      if (!cb || cb.disabled) return;
      const id = String(cb.dataset.id);
      if (selectedUserIds.has(id)) selectedUserIds.delete(id);
      else selectedUserIds.add(id);
      renderUsers();
    });
  });

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
        selectedUserIds.delete(String(id));
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

    <form id="form-user-drawer" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;" novalidate>
      <div class="drawer-body">
        <div class="form-section">
          <!-- Photo / Avatar -->
          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="user-input-avatar">Photo de profil</label>
            <div style="display: flex; gap: 12px; align-items: center;">
              <div id="user-avatar-preview" style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; background: var(--admin-gold); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                ${user?.avatar ? `<img src="${escapeHTML(user.avatar)}" style="width:100%;height:100%;object-fit:cover;">` : escapeHTML(user?.nom?.charAt(0) || 'U')}
              </div>
              <div style="flex: 1;">
                <input type="url" class="form-input" id="user-input-avatar" value="${escapeHTML(user?.avatar || '')}" placeholder="https://images.unsplash.com/photo-..." style="margin-bottom: 6px;">
                <input type="file" id="user-avatar-file" accept="image/*" style="display:none;">
                <button type="button" class="btn btn-outline btn-sm" id="btn-upload-user-avatar">
                  ${adminIcon('camera', { size: 14 })} <span>Téléverser</span>
                </button>
              </div>
            </div>
            <p class="form-hint" style="font-size:0.75rem; color:var(--admin-text-muted); margin-top:4px;">
              Collez une URL ou téléversez une image. Laissez vide pour les initiales.
            </p>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-nom">Nom complet <span class="required">*</span></label>
            <input type="text" class="form-input" id="user-input-nom" required value="${escapeHTML(user?.nom || '')}" placeholder="Ex: G. Hocine">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-poste">Poste / Fonction <span class="required">*</span></label>
            <input type="text" class="form-input" id="user-input-poste" required value="${escapeHTML(user?.poste || '')}" placeholder="Ex: Conseiller immobilier senior">
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-email">Adresse e-mail <span class="required">*</span></label>
            <input type="email" class="form-input" id="user-input-email" required value="${escapeHTML(user?.email || '')}" placeholder="conseiller@immolode.dz">
            <span class="form-error-msg" id="err-user-email" style="display:none;"></span>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-phone">Numéro de téléphone</label>
            <input type="text" class="form-input" id="user-input-phone" value="${escapeHTML(user?.phone || '')}" placeholder="0550 12 34 56">
            <span class="form-error-msg" id="err-user-phone" style="display:none;"></span>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="user-input-role">Rôle d'administration</label>
            <select class="form-select" id="user-input-role" style="width: 100%;">
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

  const avatarInput = document.getElementById('user-input-avatar');
  avatarInput?.addEventListener('input', (e) => {
    const val = e.target.value.trim();
    const preview = document.getElementById('user-avatar-preview');
    if (preview) {
      if (val) preview.innerHTML = `<img src="${escapeHTML(val)}" style="width:100%;height:100%;object-fit:cover;">`;
      else preview.textContent = document.getElementById('user-input-nom')?.value?.charAt(0) || 'U';
    }
  });

  // Upload avatar file
  const avatarFileInput = document.getElementById('user-avatar-file');
  document.getElementById('btn-upload-user-avatar')?.addEventListener('click', () => {
    avatarFileInput?.click();
  });
  avatarFileInput?.addEventListener('change', async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('folder', 'avatars');
    try {
      const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success && data.url) {
        avatarInput.value = data.url;
        const preview = document.getElementById('user-avatar-preview');
        if (preview) preview.innerHTML = `<img src="${escapeHTML(data.url)}" style="width:100%;height:100%;object-fit:cover;">`;
      }
    } catch (err) {
      // Fallback : DataURL locale
      const reader = new FileReader();
      reader.onload = (ev) => {
        avatarInput.value = ev.target.result;
        const preview = document.getElementById('user-avatar-preview');
        if (preview) preview.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
      };
      reader.readAsDataURL(file);
    }
  });

  const form = document.getElementById('form-user-drawer');
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const nom = document.getElementById('user-input-nom')?.value.trim();
    const poste = document.getElementById('user-input-poste')?.value.trim();
    const emailEl = document.getElementById('user-input-email');
    const phoneEl = document.getElementById('user-input-phone');
    const errEmail = document.getElementById('err-user-email');
    const errPhone = document.getElementById('err-user-phone');
    const email = emailEl?.value.trim() || '';
    const phone = phoneEl?.value.trim() || '';
    const role = document.getElementById('user-input-role')?.value;
    const actif = document.getElementById('user-input-actif')?.checked;
    const avatar = document.getElementById('user-input-avatar')?.value.trim();

    if (errEmail) errEmail.style.display = 'none';
    if (errPhone) errPhone.style.display = 'none';
    emailEl?.classList.remove('is-invalid');
    phoneEl?.classList.remove('is-invalid');

    if (!nom || !email) {
      showToast('Le nom et l\'adresse e-mail sont obligatoires', 'error');
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      if (errEmail) {
        errEmail.textContent = 'Format d\'adresse e-mail invalide.';
        errEmail.style.display = 'block';
      }
      emailEl?.classList.add('is-invalid');
      showToast('Adresse e-mail invalide', 'error');
      return;
    }

    if (phone) {
      const cleanedPhone = phone.replace(/[\s.\-()]/g, '');
      const dzPhoneRegex = /^(?:0[5-7]\d{8}|(?:\+213|00213)[5-7]\d{8}|0[2-4]\d{7})$/;
      if (!dzPhoneRegex.test(cleanedPhone)) {
        if (errPhone) {
          errPhone.textContent = 'Numéro algérien invalide (ex: 0550 12 34 56 ou +213 550 12 34 56).';
          errPhone.style.display = 'block';
        }
        phoneEl?.classList.add('is-invalid');
        showToast('Numéro de téléphone invalide', 'error');
        return;
      }
    }

    const userData = {
      ...(user || {}),
      nom,
      poste,
      email,
      phone,
      role,
      actif,
      avatar
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
