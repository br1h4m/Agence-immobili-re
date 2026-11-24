// admin/components/topbar.js
// MODULE ES NAVIGATEUR — barre superieure de l'administration.

import { adminIcon, escapeHTML } from '/admin/assets/js/admin.js';
import { getCurrentUser } from '/admin/includes/auth.js';

export function Topbar({ activePage = 'dashboard', title = 'Administration', breadcrumbs = [], action = null } = {}) {
  const user = getCurrentUser() || { nom: 'Administrateur' };

  const backBtnHtml = activePage !== 'dashboard'
    ? `<button type="button" class="topbar-back-btn" id="topbar-back-btn" title="Retour à la page précédente" aria-label="Retour">
        ${adminIcon('arrow-left', { size: 17 })}
       </button>`
    : '';

  const breadcrumbsHtml = breadcrumbs.length
    ? `<nav class="admin-breadcrumbs" aria-label="Fil d'Ariane">
        <a href="/admin/">Admin</a>
        ${breadcrumbs.map((b) => `<span>/</span>${b.href ? `<a href="${b.href}">${escapeHTML(b.label)}</a>` : `<span>${escapeHTML(b.label)}</span>`}`).join('')}
       </nav>`
    : '';

  let actionHtml = '';
  if (action) {
    if (action.href && action.href !== '#') {
      actionHtml = `
        <a href="${action.href}" class="btn btn-primary btn-sm topbar-action" id="${action.id || ''}" ${action.target ? `target="${action.target}"` : ''}>
          ${action.icon ? adminIcon(action.icon, { size: 16 }) : ''}
          <span>${escapeHTML(action.label)}</span>
        </a>
      `;
    } else {
      actionHtml = `
        <button type="button" class="btn btn-primary btn-sm topbar-action" id="${action.id || ''}">
          ${action.icon ? adminIcon(action.icon, { size: 16 }) : ''}
          <span>${escapeHTML(action.label)}</span>
        </button>
      `;
    }
  }

  const initials = (user.nom || 'A').split(' ').map(w => w.charAt(0)).slice(0, 2).join('').toUpperCase();

  return `
    <header class="admin-topbar">
      <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle-btn" aria-label="Ouvrir le menu">
          ${adminIcon('menu', { size: 22 })}
        </button>
        ${backBtnHtml}
        <div class="topbar-titles">
          ${breadcrumbsHtml}
          <h1 class="topbar-title">${escapeHTML(title)}</h1>
        </div>
      </div>

      <div class="topbar-right">
        ${actionHtml}

        <!-- Menu utilisateur avec dropdown -->
        <div class="topbar-user-menu" id="topbar-user-menu">
          <div class="topbar-avatar" id="topbar-avatar-btn" title="Mon compte">
            ${user.avatar
              ? `<img src="${escapeHTML(user.avatar)}" alt="Avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
              : ''}
            <span class="topbar-avatar-initials" style="${user.avatar ? 'display:none' : 'display:flex'}">${escapeHTML(initials)}</span>
          </div>
          <div class="topbar-dropdown" id="topbar-dropdown" style="display:none">
            <div style="padding: 10px 16px; border-bottom: 1px solid var(--admin-border-subtle); font-size: 0.8rem;">
              <strong style="display:block; color: var(--admin-text);">${escapeHTML(user.nom || 'Admin')}</strong>
              <span style="color: var(--admin-text-muted);">${escapeHTML(user.role || 'Administrateur')}</span>
            </div>
            <div class="topbar-dropdown-item" id="topbar-menu-profile">
              ${adminIcon('user', { size: 16 })} Mon Profil
            </div>
            <a href="/admin/parametres" class="topbar-dropdown-item">
              ${adminIcon('settings', { size: 16 })} Paramètres
            </a>
            <div class="topbar-dropdown-item" id="topbar-menu-logout" style="color: var(--admin-danger);">
              ${adminIcon('logout', { size: 16 })} Déconnexion
            </div>
          </div>
        </div>
      </div>
    </header>
  `;
}
