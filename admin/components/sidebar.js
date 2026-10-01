// admin/components/sidebar.js
// MODULE ES NAVIGATEUR — barre laterale de navigation de l'administration.

import { adminIcon, escapeHTML } from '/admin/assets/js/admin.js';
import { getCurrentUser } from '/admin/includes/auth.js';
import { getDashboardStats } from '/admin/data/admin-store.js';

export function Sidebar({ activePage = 'dashboard' } = {}) {
  const user = getCurrentUser() || { nom: 'Administrateur', role: 'Admin' };
  const stats = getDashboardStats();
  const pendingCount = stats.pendingDemandes || 0;

  const items = [
    { key: 'dashboard', label: 'Tableau de bord', href: '/admin/', icon: 'dashboard' },
    { key: 'biens', label: 'Biens immobiliers', href: '/admin/biens', icon: 'building' },
    { key: 'demandes', label: 'Demandes', href: '/admin/demandes', icon: 'mail', badge: pendingCount > 0 ? pendingCount : null },
    { key: 'utilisateurs', label: 'Utilisateurs', href: '/admin/utilisateurs', icon: 'users' },
    { key: 'parametres', label: 'Paramètres', href: '/admin/parametres', icon: 'settings' }
  ];

  const navHtml = items.map((item) => {
    const isActive = item.key === activePage;
    return `
      <li class="sidebar-item">
        <a href="${item.href}" class="sidebar-link${isActive ? ' is-active' : ''}">
          <span class="sidebar-icon">${adminIcon(item.icon, { size: 18 })}</span>
          <span class="sidebar-label">${item.label}</span>
          ${item.badge ? `<span class="sidebar-badge">${item.badge}</span>` : ''}
        </a>
      </li>
    `;
  }).join('');

  return `
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigation administration">
      <div class="sidebar-brand">
        <a href="/admin/" class="brand-link">
          <img src="/assets/images/logo.svg" alt="" class="brand-logo" width="30" height="30">
          <div class="brand-info">
            <span class="brand-title">Imolode</span>
            <span class="brand-tag">Administration</span>
          </div>
        </a>
        <button type="button" class="sidebar-close-btn" id="sidebar-close-btn" aria-label="Fermer le menu">
          ${adminIcon('close', { size: 20 })}
        </button>
      </div>

      <nav class="sidebar-nav">
        <p class="sidebar-heading">Menu principal</p>
        <ul class="sidebar-menu">
          ${navHtml}
        </ul>
      </nav>

      <div class="sidebar-footer">
        <a href="/client/" target="_blank" rel="noopener" class="sidebar-external-link">
          <span>${adminIcon('external', { size: 16 })}</span>
          <span>Voir le site public</span>
        </a>

        <div class="sidebar-user">
          <div class="user-avatar">${escapeHTML(user.nom?.charAt(0) || 'A')}</div>
          <div class="user-meta">
            <p class="user-name">${escapeHTML(user.nom || 'Admin')}</p>
            <p class="user-role">${escapeHTML(user.role || 'Administrateur')}</p>
          </div>
          <button type="button" class="logout-btn" id="btn-logout" title="Déconnexion" aria-label="Déconnexion">
            ${adminIcon('logout', { size: 18 })}
          </button>
        </div>
      </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
  `;
}

