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
    const isLink = action.href && action.href !== '#';
    const tag = isLink ? 'a' : 'button';
    const hrefAttr = isLink ? `href="${escapeHTML(action.href)}"` : 'type="button"';
    const idAttr = action.id ? `id="${escapeHTML(action.id)}"` : '';
    actionHtml = `
      <${tag} ${hrefAttr} ${idAttr} class="btn btn-primary btn-sm topbar-action">
        ${action.icon ? adminIcon(action.icon, { size: 16 }) : ''}
        <span>${escapeHTML(action.label)}</span>
      </${tag}>
    `;
  }

  const avatarContent = user.avatar
    ? `<img src="${escapeHTML(user.avatar)}" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`
    : escapeHTML(user.nom?.charAt(0) || 'A');

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

        <!-- Sélecteur Thème Sombre / Clair -->
        <button type="button" class="btn-icon" id="btn-theme-toggle" title="Basculer le thème Sombre / Clair" aria-label="Changer de thème">
          <span class="theme-icon-light">${adminIcon('sun', { size: 18 })}</span>
          <span class="theme-icon-dark" style="display:none;">${adminIcon('moon', { size: 18 })}</span>
        </button>

        <!-- Profil utilisateur -->
        <button type="button" class="topbar-user" id="btn-open-profile-topbar" title="Gérer mon compte personnel" style="background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:10px;">
          <div class="user-avatar" style="width:34px; height:34px; border-radius:50%; background:var(--admin-gold); color:#FFF; font-weight:700; display:flex; align-items:center; justify-content:center; overflow:hidden; font-size:0.9rem;">
            ${avatarContent}
          </div>
          <span class="user-greeting">Bonjour, <strong>${escapeHTML(user.nom || 'Admin')}</strong></span>
        </button>
      </div>
    </header>
  `;
}
