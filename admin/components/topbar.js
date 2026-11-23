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
    actionHtml = `
      <a href="${action.href}" class="btn btn-primary btn-sm topbar-action">
        ${action.icon ? adminIcon(action.icon, { size: 16 }) : ''}
        <span>${escapeHTML(action.label)}</span>
      </a>
    `;
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
        <div class="topbar-user">
          <span class="user-greeting">Bonjour, <strong>${escapeHTML(user.nom || 'Admin')}</strong></span>
        </div>
      </div>
    </header>
  `;
}
