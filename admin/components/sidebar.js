// admin/components/sidebar.js
// MODULE ES NAVIGATEUR — barre laterale de navigation de l'administration.

import { adminIcon, escapeHTML } from '/admin/assets/js/admin.js';
import { getCurrentUser } from '/admin/includes/auth.js';
import { getDashboardStats } from '/admin/data/admin-store.js';

const ICONS_SIDEBAR = {
  'chevrons-left': '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>'
};

export function Sidebar({ activePage = 'dashboard' } = {}) {
  const user = getCurrentUser() || { nom: 'Administrateur', role: 'Admin' };
  const stats = getDashboardStats();
  const pendingCount = stats.pendingDemandes || 0;
  const pendingFiches = stats.pendingFichesClient || 0;

  const items = [
    { key: 'dashboard', label: 'Tableau de bord', href: '/admin/', icon: 'dashboard' },
    { key: 'biens', label: 'Biens immobiliers', href: '/admin/biens', icon: 'building' },
    { key: 'demandes', label: 'Demandes', href: '/admin/demandes', icon: 'mail', badge: pendingCount > 0 ? pendingCount : null, badgeId: 'badge-demandes' },
    { key: 'fiches-client', label: 'Fiches client', href: '/admin/fiches-client', icon: 'file-text', badge: pendingFiches > 0 ? pendingFiches : null, badgeId: 'badge-fiches' },
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
          ${item.badgeId ? `<span class="sidebar-badge" id="${item.badgeId}" style="${item.badge ? '' : 'display:none;'}">${item.badge || 0}</span>` : (item.badge ? `<span class="sidebar-badge">${item.badge}</span>` : '')}
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
          <span class="sidebar-label">Voir le site public</span>
        </a>

        <div class="sidebar-user" id="sidebar-user-profile" style="cursor: pointer;" title="Modifier mes informations personnelles">
          <div class="user-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: var(--admin-primary); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; overflow: hidden;">
            ${user.avatar ? `<img src="${escapeHTML(user.avatar)}" alt="" style="width:100%;height:100%;object-fit:cover;">` : escapeHTML(user.nom?.charAt(0) || 'A')}
          </div>
          <div class="user-meta sidebar-label">
            <p class="user-name">${escapeHTML(user.nom || 'Admin')}</p>
            <p class="user-role">${escapeHTML(user.role || 'Administrateur')}</p>
          </div>
          <button type="button" class="logout-btn sidebar-label" id="btn-logout" title="Déconnexion" aria-label="Déconnexion">
            ${adminIcon('logout', { size: 18 })}
          </button>
        </div>

        <div class="sidebar-collapse-btn" id="sidebar-collapse-btn" title="Réduire le menu">
          ${adminIcon('chevrons-left', { size: 20 })}
        </div>
      </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
  `;
}

