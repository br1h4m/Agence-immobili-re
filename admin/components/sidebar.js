// admin/components/sidebar.js
// MODULE ES NAVIGATEUR — barre latérale de navigation de l'administration.

import { adminIcon } from '/admin/assets/js/admin.js';
import { getUnreadBadgeCounts } from '/admin/data/admin-store.js';

export function Sidebar({ activePage = 'dashboard' } = {}) {
  const counts = getUnreadBadgeCounts();
  const unreadDemandes = counts.unreadDemandes || 0;
  const unreadFiches = counts.unreadFichesClient || 0;

  const items = [
    { key: 'dashboard', label: 'Tableau de bord', href: '/admin/', icon: 'dashboard' },
    { key: 'biens', label: 'Biens immobiliers', href: '/admin/biens', icon: 'building' },
    { key: 'demandes', label: 'Demandes', href: '/admin/demandes', icon: 'mail', badge: unreadDemandes, badgeId: 'badge-demandes' },
    { key: 'fiches-client', label: 'Fiches client', href: '/admin/fiches-client', icon: 'file-text', badge: unreadFiches, badgeId: 'badge-fiches' },
    { key: 'utilisateurs', label: 'Utilisateurs', href: '/admin/utilisateurs', icon: 'users' },
    { key: 'parametres', label: 'Paramètres', href: '/admin/parametres', icon: 'settings' }
  ];

  const navHtml = items.map((item) => {
    const isActive = item.key === activePage;
    const hasCount = typeof item.badge === 'number' && item.badge > 0;
    const badgeHtml = item.badgeId
      ? `<span class="sidebar-badge${hasCount ? '' : ' is-empty'}" id="${item.badgeId}"${hasCount ? '' : ' hidden'}>${item.badge}</span>`
      : '';
    return `
      <li class="sidebar-item">
        <a href="${item.href}" class="sidebar-link${isActive ? ' is-active' : ''}" title="${item.label}">
          <span class="sidebar-icon">${adminIcon(item.icon, { size: 18 })}</span>
          <span class="sidebar-label">${item.label}</span>
          ${badgeHtml}
        </a>
      </li>
    `;
  }).join('');

  return `
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigation administration">
      <div class="sidebar-brand">
        <a href="/admin/" class="brand-link">
          <img src="/assets/images/logo.svg" alt="Imolode" class="brand-logo" width="30" height="30">
          <div class="brand-info">
            <span class="brand-title">Imolode</span>
            <span class="brand-tag">Administration</span>
          </div>
        </a>
        <div style="display: flex; align-items: center; gap: 6px;">
          <button type="button" class="sidebar-collapse-btn" id="sidebar-collapse-btn" title="Réduire ou agrandir le menu" aria-label="Réduire ou agrandir le menu" aria-expanded="true">
            ${adminIcon('chevrons-left', { size: 16 })}
          </button>
          <button type="button" class="sidebar-close-btn" id="sidebar-close-btn" aria-label="Fermer le menu">
            ${adminIcon('close', { size: 20 })}
          </button>
        </div>
      </div>

      <nav class="sidebar-nav">
        <p class="sidebar-heading">Menu principal</p>
        <ul class="sidebar-menu">
          ${navHtml}
        </ul>
      </nav>

      <div class="sidebar-footer">
        <a href="/client/" target="_blank" rel="noopener" class="sidebar-external-link" title="Voir le site public">
          <span>${adminIcon('external', { size: 16 })}</span>
          <span class="sidebar-label">Voir le site public</span>
        </a>
      </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
  `;
}
