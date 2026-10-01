// admin/assets/js/admin.js
// MODULE ES NAVIGATEUR — utilitaires transversaux de l'interface d'administration.

export function escapeHTML(value) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  return String(value ?? '').replace(/[&<>"']/g, (char) => map[char]);
}

const numberFormatter = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });

export function formatPrice(price, period = null) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) return 'Prix sur demande';
  const base = `${numberFormatter.format(price)} DA`;
  return period ? `${base} / ${period}` : base;
}

export function formatSurface(val) {
  return val ? `${numberFormatter.format(val)} m²` : '—';
}

export function formatDate(isoDate) {
  if (!isoDate) return '—';
  try {
    const d = new Date(isoDate);
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    }).format(d);
  } catch (_) {
    return isoDate;
  }
}

export function formatDateTime(isoDate) {
  if (!isoDate) return '—';
  try {
    const d = new Date(isoDate);
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(d);
  } catch (_) {
    return isoDate;
  }
}

/* ==========================================================================
   Icones SVG inline (trait 24x24)
   ========================================================================== */

const ICONS = {
  'dashboard': '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
  'building': '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M12 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/>',
  'mail': '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
  'users': '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  'settings': '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
  'external': '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/>',
  'logout': '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
  'plus': '<path d="M5 12h14"/><path d="M12 5v14"/>',
  'edit': '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
  'trash': '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
  'eye': '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
  'search': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  'check': '<path d="M20 6 9 17l-5-5"/>',
  'close': '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
  'filter': '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
  'chevron-right': '<path d="m9 18 6-6-6-6"/>',
  'menu': '<line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/>',
  'arrow-left': '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
  'user': '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  'phone': '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>'
};

export function adminIcon(name, { size = 18, className = '' } = {}) {
  const body = ICONS[name] || ICONS.check;
  const classes = `admin-icon${className ? ` ${className}` : ''}`;
  return `<svg class="${classes}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${body}</svg>`;
}

/* ==========================================================================
   Badges
   ========================================================================== */

export function statusBadge(status) {
  let badgeClass = 'badge--neutral';
  if (status === 'Disponible' || status === 'Traite') {
    badgeClass = 'badge--success';
  } else if (status === 'Reserve' || status === 'En cours') {
    badgeClass = 'badge--warning';
  } else if (status === 'Nouveau') {
    badgeClass = 'badge--info';
  } else if (status === 'Vendu' || status === 'Loue') {
    badgeClass = 'badge--muted';
  }
  return `<span class="badge ${badgeClass}">${escapeHTML(status)}</span>`;
}

export function transactionBadge(transaction) {
  const isRent = transaction === 'Louer';
  const label = isRent ? 'Location' : 'Vente';
  const cls = isRent ? 'badge--rent' : 'badge--sale';
  return `<span class="badge ${cls}">${label}</span>`;
}

/* ==========================================================================
   Notifications Toast
   ========================================================================== */

export function showToast(message, type = 'success') {
  let container = document.getElementById('admin-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'admin-toast-container';
    container.className = 'admin-toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `admin-toast admin-toast--${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${type === 'success' ? adminIcon('check', { size: 16 }) : adminIcon('close', { size: 16 })}</span>
    <span class="toast-msg">${escapeHTML(message)}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('is-visible');
  }, 10);

  setTimeout(() => {
    toast.classList.remove('is-visible');
    setTimeout(() => toast.remove(), 300);
  }, 3200);
}

/* ==========================================================================
   Modale de confirmation
   ========================================================================== */

export function confirmModal({ title = 'Confirmation', message = 'Voulez-vous continuer ?', confirmText = 'Confirmer', cancelText = 'Annuler' } = {}) {
  return new Promise((resolve) => {
    let overlay = document.getElementById('admin-modal-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'admin-modal-overlay';
      overlay.className = 'admin-modal-overlay';
      document.body.appendChild(overlay);
    }

    overlay.innerHTML = `
      <div class="admin-modal-card">
        <h3 class="admin-modal-title">${escapeHTML(title)}</h3>
        <p class="admin-modal-body">${escapeHTML(message)}</p>
        <div class="admin-modal-actions">
          <button type="button" class="btn btn-outline" id="modal-cancel-btn">${escapeHTML(cancelText)}</button>
          <button type="button" class="btn btn-danger" id="modal-confirm-btn">${escapeHTML(confirmText)}</button>
        </div>
      </div>
    `;

    overlay.classList.add('is-active');

    const cleanUp = (result) => {
      overlay.classList.remove('is-active');
      overlay.innerHTML = '';
      resolve(result);
    };

    document.getElementById('modal-confirm-btn')?.addEventListener('click', () => cleanUp(true));
    document.getElementById('modal-cancel-btn')?.addEventListener('click', () => cleanUp(false));
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) cleanUp(false);
    });
  });
}

/* ==========================================================================
   Initialisation globale du layout d'administration
   ========================================================================== */

import { Sidebar } from '/admin/components/sidebar.js';
import { Topbar } from '/admin/components/topbar.js';
import { logout, requireAuth } from '/admin/includes/auth.js';
import { initAdminHead } from '/admin/includes/admin-head.js';

export async function initAdminLayout({ activePage = 'dashboard', title = 'Administration', breadcrumbs = [], action = null } = {}) {
  if (!requireAuth()) return false;
  await initAdminHead({ title });

  const sidebarSlot = document.getElementById('sidebar-slot');
  if (sidebarSlot) {
    sidebarSlot.innerHTML = Sidebar({ activePage });
  }

  const topbarSlot = document.getElementById('topbar-slot');
  if (topbarSlot) {
    topbarSlot.innerHTML = Topbar({ title, breadcrumbs, action });
  }

  // Deconnexion
  document.getElementById('btn-logout')?.addEventListener('click', () => {
    logout();
  });

  // Gestion du menu lateral mobile
  const sidebar = document.getElementById('admin-sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  const toggleBtn = document.getElementById('sidebar-toggle-btn');
  const closeBtn = document.getElementById('sidebar-close-btn');

  const openSidebar = () => {
    sidebar?.classList.add('is-open');
    backdrop?.classList.add('is-open');
  };
  const closeSidebar = () => {
    sidebar?.classList.remove('is-open');
    backdrop?.classList.remove('is-open');
  };

  toggleBtn?.addEventListener('click', openSidebar);
  closeBtn?.addEventListener('click', closeSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  return true;
}

