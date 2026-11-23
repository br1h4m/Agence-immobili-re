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
        <a href="${action.href}" class="btn btn-primary btn-sm topbar-action" id="${action.id || ''}">
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

        <!-- Mon Compte -->
        <div class="topbar-user" id="topbar-user-profile" style="cursor: pointer;" title="Modifier mes informations personnelles">
          <div class="user-avatar" style="width: 28px; height: 28px; border-radius: 50%; background: var(--admin-primary); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 700; overflow: hidden;">
            ${user.avatar ? `<img src="${escapeHTML(user.avatar)}" alt="" style="width:100%;height:100%;object-fit:cover;">` : escapeHTML(user.nom?.charAt(0) || 'A')}
          </div>
          <span class="user-greeting">Bonjour, <strong>${escapeHTML(user.nom || 'Admin')}</strong></span>
        </div>
      </div>
    </header>
  `;
}
