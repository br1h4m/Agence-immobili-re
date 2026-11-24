// admin/components/topbar.js
// MODULE ES NAVIGATEUR — barre superieure de l'administration.

import { adminIcon, escapeHTML } from '/admin/assets/js/admin.js';
import { getCurrentUser } from '/admin/includes/auth.js';

export function Topbar({ title = 'Administration', breadcrumbs = [], action = null } = {}) {
  const user = getCurrentUser() || { nom: 'Administrateur' };

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
        <div class="topbar-titles">
          ${breadcrumbsHtml}
          <h1 class="topbar-title">${escapeHTML(title)}</h1>
        </div>
      </div>

      <div class="topbar-right">
        ${actionHtml}

        <!-- Bouton bascule de thème clair/sombre -->
        <button type="button" class="btn-icon" id="btn-theme-toggle" title="Basculer entre thème clair et sombre" aria-label="Changer de thème" style="padding: 6px; border-radius: var(--radius-xs); border: 1px solid var(--admin-border); background: var(--admin-surface); color: var(--admin-text); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
          <span class="theme-icon-light">${adminIcon('sun', { size: 18 })}</span>
          <span class="theme-icon-dark" style="display:none;">${adminIcon('moon', { size: 18 })}</span>
        </button>

        <!-- Menu utilisateur avec dropdown -->
        <div class="topbar-user-menu" id="topbar-user-menu">
          <div class="topbar-avatar" id="topbar-avatar-btn" title="Mon compte">
            ${user.avatar
              ? `<img src="${escapeHTML(user.avatar)}" alt="Avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
              : ''}
            <span class="topbar-avatar-initials" style="${user.avatar ? 'display:none' : 'display:flex'}">${escapeHTML(initials)}</span>
          </div>
          <div class="topbar-dropdown" id="topbar-dropdown" style="display:none">
            <div class="topbar-dropdown-item" id="topbar-menu-profile">
              ${adminIcon('user', { size: 16 })} Mon Profil
            </div>
            <div class="topbar-dropdown-item" id="topbar-menu-logout">
              ${adminIcon('logout', { size: 16 })} Déconnexion
            </div>
          </div>
        </div>
      </div>
    </header>
  `;
}
