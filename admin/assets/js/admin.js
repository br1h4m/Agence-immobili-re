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
  'phone': '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
  'file-text': '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
  'printer': '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
  'download': '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
  'upload': '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
  'sun': '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
  'moon': '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
  'lock': '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
  'camera': '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
  'chevrons-left': '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>'
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
  if (status === 'Disponible' || status === 'Traite' || status === 'Traité' || status === 'Validé') {
    badgeClass = 'badge--success';
  } else if (status === 'Reserve' || status === 'Réservé' || status === 'En cours') {
    badgeClass = 'badge--warning';
  } else if (status === 'Nouveau') {
    badgeClass = 'badge--info';
  } else if (status === 'Vendu' || status === 'Loue' || status === 'Loué' || status === 'Rejeté') {
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
   Impression isolée et infaillible (Fix 4x duplication & scroll cut-off)
   ========================================================================== */

export function printDocument({
  title = 'Dossier Immolode',
  type = 'Demande',
  reference = '',
  date = '',
  status = '',
  client = {},
  details = [],
  message = '',
  signature = '',
  attachment = null
} = {}) {
  let iframe = document.getElementById('immolode-print-frame');
  if (iframe) iframe.remove();

  iframe = document.createElement('iframe');
  iframe.id = 'immolode-print-frame';
  iframe.style.position = 'fixed';
  iframe.style.right = '0';
  iframe.style.bottom = '0';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = 'none';
  document.body.appendChild(iframe);

  const doc = iframe.contentWindow.document;
  doc.open();

  const detailsHtml = details.length ? `
    <div class="print-section">
      <h3 class="print-section-title">Caractéristiques & Détails</h3>
      <table class="print-table">
        <tbody>
          ${details.map(([k, v]) => `<tr><th>${escapeHTML(k)}</th><td>${escapeHTML(v)}</td></tr>`).join('')}
        </tbody>
      </table>
    </div>
  ` : '';

  const messageHtml = message ? `
    <div class="print-section">
      <h3 class="print-section-title">Message / Description</h3>
      <div class="print-box">${escapeHTML(message)}</div>
    </div>
  ` : '';

  const signatureHtml = signature ? `
    <div class="print-section" style="margin-top: 24px; page-break-inside: avoid;">
      <h3 class="print-section-title">Signature client recueillie</h3>
      <div style="border: 1px solid #D6D2CA; border-radius: 4px; padding: 10px; display: inline-block; background: #FFF;">
        <img src="${signature}" alt="Signature client" style="max-height: 75px; max-width: 250px; display: block;">
      </div>
    </div>
  ` : '';

  const attachmentHtml = attachment ? `
    <div class="print-section" style="margin-top: 18px; page-break-inside: avoid;">
      <h3 class="print-section-title">Justificatif d'identité</h3>
      <p style="font-size: 9.5pt; color: #555; margin: 4px 0;">Fichier joint : <strong>${escapeHTML(attachment.name || 'Pièce d\'identité')}</strong></p>
      ${attachment.dataUrl && String(attachment.dataUrl).startsWith('http') ? `
        <div style="margin-top: 6px; border: 1px solid #D6D2CA; padding: 4px; display: inline-block;">
          <img src="${escapeHTML(attachment.dataUrl)}" alt="Pièce jointe" style="max-height: 160px; max-width: 300px; display: block;">
        </div>
      ` : ''}
    </div>
  ` : '';

  doc.write(`
    <!DOCTYPE html>
    <html lang="fr">
    <head>
      <meta charset="UTF-8">
      <title>${escapeHTML(title)} — Immolode</title>
      <style>
        @page { size: A4 portrait; margin: 15mm 20mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #1A1A18; background: #FFFFFF; font-size: 10pt; line-height: 1.5; }
        .print-header { display: flex; justify-content: space-between; border-bottom: 2px solid #8C7355; padding-bottom: 12px; margin-bottom: 18px; }
        .brand-name { font-size: 18pt; font-weight: 700; color: #1A1A18; }
        .brand-sub { font-size: 8.5pt; color: #8C7355; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .doc-meta { text-align: right; font-size: 9pt; color: #555; }
        .doc-ref { font-size: 11pt; font-weight: 700; color: #1A1A18; }
        .print-title-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .print-doc-title { font-size: 13.5pt; font-weight: 700; color: #1A1A18; }
        .print-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 8.5pt; font-weight: 600; background: #EFECE6; color: #1A1A18; border: 1px solid #D6D2CA; }
        .print-section { margin-bottom: 16px; }
        .print-section-title { font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #8C7355; border-bottom: 1px solid #EFECE6; padding-bottom: 4px; margin-bottom: 8px; }
        .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .print-field { font-size: 9pt; }
        .print-label { color: #666; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .print-value { font-weight: 600; color: #1A1A18; }
        .print-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 4px; }
        .print-table th { text-align: left; padding: 6px 10px; background: #F8F7F4; color: #555; font-weight: 600; width: 40%; border-bottom: 1px solid #EFECE6; }
        .print-table td { padding: 6px 10px; color: #1A1A18; border-bottom: 1px solid #EFECE6; }
        .print-box { background: #FAF9F6; border: 1px solid #EFECE6; border-radius: 4px; padding: 10px 14px; font-size: 9pt; white-space: pre-wrap; line-height: 1.6; }
        .print-footer { margin-top: 24px; border-top: 1px solid #D6D2CA; padding-top: 10px; font-size: 8pt; color: #888; text-align: center; }
      </style>
    </head>
    <body>
      <header class="print-header">
        <div>
          <div class="brand-name">Imolode</div>
          <div class="brand-sub">Agence Immobilière Agréée</div>
        </div>
        <div class="doc-meta">
          ${reference ? `<div class="doc-ref">Réf : ${escapeHTML(reference)}</div>` : ''}
          <div>Date : ${escapeHTML(date || new Date().toLocaleDateString('fr-FR'))}</div>
          <div>Édité depuis l'administration Immolode</div>
        </div>
      </header>

      <div class="print-title-bar">
        <h1 class="print-doc-title">${escapeHTML(title)}</h1>
        ${status ? `<span class="print-badge">${escapeHTML(status)}</span>` : ''}
      </div>

      <div class="print-section">
        <h3 class="print-section-title">Coordonnées du client</h3>
        <div class="print-grid">
          <div class="print-field">
            <span class="print-label">Nom & Prénom</span>
            <span class="print-value">${escapeHTML(client.nom || client.name || 'Non renseigné')}</span>
          </div>
          <div class="print-field">
            <span class="print-label">Téléphone</span>
            <span class="print-value">${escapeHTML(client.phone || client.telephone || 'Non renseigné')}</span>
          </div>
          <div class="print-field">
            <span class="print-label">Adresse E-mail</span>
            <span class="print-value">${escapeHTML(client.email || 'Non renseigné')}</span>
          </div>
          <div class="print-field">
            <span class="print-label">Type de dossier</span>
            <span class="print-value">${escapeHTML(type)}</span>
          </div>
        </div>
      </div>

      ${detailsHtml}
      ${messageHtml}
      ${attachmentHtml}
      ${signatureHtml}

      <footer class="print-footer">
        Immolode Immobilier — 04 Boulevard des Frères Bouaouina, Béjaïa 06000 — Tél : +213 555 12 34 56 — contact@immolode.dz
      </footer>
    </body>
    </html>
  `);

  doc.close();

  setTimeout(() => {
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
  }, 250);
}

/* ==========================================================================
   Gestion du Thème Sombre / Clair (Point 2.19)
   ========================================================================== */

const THEME_STORAGE_KEY = 'immolode_admin_theme';

export function initTheme() {
  const saved = localStorage.getItem(THEME_STORAGE_KEY) || 'light';
  applyTheme(saved);
}

export function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  const next = current === 'dark' ? 'light' : 'dark';
  applyTheme(next);
  localStorage.setItem(THEME_STORAGE_KEY, next);
  showToast(`Thème ${next === 'dark' ? 'sombre' : 'clair'} activé`);
}

function applyTheme(theme) {
  if (theme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  } else {
    document.documentElement.removeAttribute('data-theme');
  }
  const lightIcon = document.querySelector('.theme-icon-light');
  const darkIcon = document.querySelector('.theme-icon-dark');
  if (lightIcon && darkIcon) {
    if (theme === 'dark') {
      lightIcon.style.display = 'none';
      darkIcon.style.display = 'inline-flex';
    } else {
      lightIcon.style.display = 'inline-flex';
      darkIcon.style.display = 'none';
    }
  }
}

/* ==========================================================================
   Synchronisation Temps Réel des Badges Sidebar (Point 2.11)
   ========================================================================== */

let badgeSyncInterval = null;

export function startRealtimeBadgeSync() {
  if (badgeSyncInterval) clearInterval(badgeSyncInterval);

  const sync = async () => {
    try {
      const res = await fetch('/api/stats.php');
      if (!res.ok) return;
      const data = await res.json();
      
      const demandesBadge = document.getElementById('badge-demandes');
      if (demandesBadge) {
        const count = data.unreadDemandes ?? data.demandes_non_lues ?? 0;
        demandesBadge.textContent = count;
        demandesBadge.style.display = count > 0 ? '' : 'none';
      }

      const fichesBadge = document.getElementById('badge-fiches');
      if (fichesBadge) {
        const count = data.unreadFichesClient ?? data.fiches_non_lues ?? 0;
        fichesBadge.textContent = count;
        fichesBadge.style.display = count > 0 ? '' : 'none';
      }
    } catch (_) {}
  };

  sync();
  window._syncBadgesNow = sync; // expose pour appel externe immédiat
  badgeSyncInterval = setInterval(sync, 15000);
}

/* ==========================================================================
   Modale Gestion du Compte Personnel / Mon Profil (Point 2.18)
   ========================================================================== */

export async function openProfileModal() {
  const user = getCurrentUser();
  if (!user) return;

  let modal = document.getElementById('admin-profile-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'admin-profile-modal';
    modal.className = 'admin-modal-overlay';
    document.body.appendChild(modal);
  }

  modal.innerHTML = `
    <div class="admin-modal-card" style="max-width: 500px; text-align: left;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 class="admin-modal-title" style="margin: 0;">Mon compte personnel</h3>
        <button type="button" class="btn-icon" id="profile-close-btn" style="border: 0; background: transparent; cursor: pointer;">
          ${adminIcon('close', { size: 20 })}
        </button>
      </div>

      <form id="profile-form">
        <!-- Photo de profil -->
        <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 24px; padding: 14px; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-md);">
          <div id="profile-avatar-preview" style="width: 64px; height: 64px; border-radius: 50%; background: var(--admin-primary); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 700; overflow: hidden; flex-shrink: 0;">
            ${user.avatar ? `<img src="${escapeHTML(user.avatar)}" style="width:100%;height:100%;object-fit:cover;">` : escapeHTML(user.nom?.charAt(0) || 'A')}
          </div>
          <div style="flex: 1;">
            <label class="form-label" style="margin-bottom: 4px; font-weight: 600;">Photo de profil</label>
            <input type="text" id="profile-avatar-url" class="form-input form-input-sm" placeholder="URL de l'image (ex: https://...)" value="${escapeHTML(user.avatar || '')}" style="margin-bottom: 6px;">
            <input type="file" id="profile-avatar-file" accept="image/*" style="display:none;">
            <div style="display: flex; gap: 8px;">
              <button type="button" class="btn btn-outline btn-sm" id="btn-upload-avatar">
                ${adminIcon('camera', { size: 14 })} <span>Téléverser</span>
              </button>
              ${user.avatar ? `<button type="button" class="btn btn-danger-outline btn-sm" id="btn-remove-avatar">Supprimer</button>` : ''}
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 14px;">
          <label class="form-label" for="profile-nom">Nom complet <span class="required">*</span></label>
          <input type="text" id="profile-nom" class="form-input" required value="${escapeHTML(user.nom || '')}">
        </div>

        <div class="form-group" style="margin-bottom: 14px;">
          <label class="form-label" for="profile-email">Adresse e-mail <span class="required">*</span></label>
          <input type="email" id="profile-email" class="form-input" required value="${escapeHTML(user.email || '')}">
        </div>

        <div class="form-group" style="margin-bottom: 14px;">
          <label class="form-label" for="profile-tel">Numéro de téléphone</label>
          <input type="tel" id="profile-tel" class="form-input" value="${escapeHTML(user.telephone || user.phone || '')}">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
          <label class="form-label" for="profile-password">Nouveau mot de passe <span style="font-weight: 400; color: var(--admin-text-subtle);">(laisser vide pour ne pas modifier)</span></label>
          <input type="password" id="profile-password" class="form-input" placeholder="••••••••">
        </div>

        <div class="admin-modal-actions">
          <button type="button" class="btn btn-outline" id="profile-cancel-btn">Annuler</button>
          <button type="submit" class="btn btn-primary" id="profile-save-btn">Enregistrer les modifications</button>
        </div>
      </form>
    </div>
  `;

  modal.classList.add('is-active');

  const close = () => {
    modal.classList.remove('is-active');
  };

  document.getElementById('profile-close-btn')?.addEventListener('click', close);
  document.getElementById('profile-cancel-btn')?.addEventListener('click', close);

  // Upload photo
  const fileInput = document.getElementById('profile-avatar-file');
  const urlInput = document.getElementById('profile-avatar-url');
  const preview = document.getElementById('profile-avatar-preview');

  document.getElementById('btn-upload-avatar')?.addEventListener('click', () => {
    fileInput?.click();
  });

  fileInput?.addEventListener('change', async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    try {
      const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
      const d = await res.json();
      if (d.success && d.url) {
        urlInput.value = d.url;
        preview.innerHTML = `<img src="${escapeHTML(d.url)}" style="width:100%;height:100%;object-fit:cover;">`;
      }
    } catch (_) {
      const reader = new FileReader();
      reader.onload = (ev) => {
        urlInput.value = ev.target.result;
        preview.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
      };
      reader.readAsDataURL(file);
    }
  });

  document.getElementById('btn-remove-avatar')?.addEventListener('click', () => {
    urlInput.value = '';
    preview.innerHTML = escapeHTML(user.nom?.charAt(0) || 'A');
  });

  // Soumission
  document.getElementById('profile-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('profile-save-btn');
    if (btn) btn.disabled = true;

    const payload = {
      action: 'profile',
      id: user.id,
      current_user_id: user.id,
      nom: document.getElementById('profile-nom').value.trim(),
      email: document.getElementById('profile-email').value.trim(),
      telephone: document.getElementById('profile-tel').value.trim(),
      avatar: urlInput.value.trim() || null,
      password: document.getElementById('profile-password').value || null
    };

    try {
      const res = await fetch('/api/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (res.ok && data.success) {
        showToast('Profil mis à jour avec succès !');
        updateLocalUser(data.user || payload);
        close();
        setTimeout(() => window.location.reload(), 400);
      } else {
        showToast(data.error || 'Erreur lors de la mise à jour', 'error');
      }
    } catch (err) {
      showToast('Erreur réseau', 'error');
    } finally {
      if (btn) btn.disabled = false;
    }
  });
}

/* ==========================================================================
   Modale d'Importation CSV / Excel (Point 2.2)
   ========================================================================== */

export function openCsvImportModal({ model = 'properties', title = 'Importer des biens', onImportSuccess = null } = {}) {
  let modal = document.getElementById('admin-import-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'admin-import-modal';
    modal.className = 'admin-modal-overlay';
    document.body.appendChild(modal);
  }

  modal.innerHTML = `
    <div class="admin-modal-card" style="max-width: 540px; text-align: left;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 class="admin-modal-title" style="margin: 0;">${escapeHTML(title)}</h3>
        <button type="button" class="btn-icon" id="import-modal-close" style="border: 0; background: transparent; cursor: pointer;">
          ${adminIcon('close', { size: 20 })}
        </button>
      </div>

      <p style="color: var(--admin-text-muted); font-size: 0.9rem; margin-bottom: 20px;">
        Sélectionnez un fichier CSV ou Excel pour ajouter automatiquement plusieurs enregistrements dans la base de données.
      </p>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px 14px; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius-sm);">
        <div>
          <strong style="font-size: 0.88rem; display: block;">Modèle type de fichier</strong>
          <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Téléchargez la structure attendue avec les colonnes correctes.</span>
        </div>
        <a href="/api/import.php?action=template&model=${encodeURIComponent(model)}" class="btn btn-outline btn-sm" download>
          ${adminIcon('download', { size: 14 })} Modèle CSV
        </a>
      </div>

      <form id="import-form">
        <div style="border: 2px dashed var(--admin-border); border-radius: var(--radius-md); padding: 24px; text-align: center; margin-bottom: 20px; background: var(--admin-surface); cursor: pointer;" id="import-dropzone">
          <input type="file" id="import-file-input" accept=".csv, text/csv, application/vnd.ms-excel" style="display:none;">
          <div style="color: var(--admin-primary); margin-bottom: 8px;">
            ${adminIcon('upload', { size: 32 })}
          </div>
          <p style="margin: 0 0 4px; font-weight: 600; font-size: 0.92rem;">Cliquez ou glissez-déposez votre fichier CSV ici</p>
          <span style="font-size: 0.8rem; color: var(--admin-text-muted);" id="import-file-name">Fichiers acceptés : .csv (UTF-8)</span>
        </div>

        <div id="import-feedback" style="display:none; margin-bottom: 16px; padding: 12px; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.5;"></div>

        <div class="admin-modal-actions">
          <button type="button" class="btn btn-outline" id="import-cancel-btn">Annuler</button>
          <button type="submit" class="btn btn-primary" id="import-submit-btn" disabled>
            ${adminIcon('upload', { size: 15 })} <span>Lancer l'importation</span>
          </button>
        </div>
      </form>
    </div>
  `;

  modal.classList.add('is-active');

  const close = () => {
    modal.classList.remove('is-active');
  };

  document.getElementById('import-modal-close')?.addEventListener('click', close);
  document.getElementById('import-cancel-btn')?.addEventListener('click', close);

  const dropzone = document.getElementById('import-dropzone');
  const fileInput = document.getElementById('import-file-input');
  const fileNameSpan = document.getElementById('import-file-name');
  const submitBtn = document.getElementById('import-submit-btn');
  const feedback = document.getElementById('import-feedback');

  dropzone?.addEventListener('click', () => fileInput?.click());

  fileInput?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (file) {
      fileNameSpan.innerHTML = `Fichier prêt : <strong>${escapeHTML(file.name)}</strong> (${(file.size / 1024).toFixed(1)} Ko)`;
      submitBtn.disabled = false;
    }
  });

  document.getElementById('import-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const file = fileInput.files?.[0];
    if (!file) return;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Importation en cours...';
    feedback.style.display = 'none';

    const fd = new FormData();
    fd.append('file', file);
    fd.append('model', model);

    try {
      const res = await fetch('/api/import.php', { method: 'POST', body: fd });
      const data = await res.json();

      feedback.style.display = 'block';
      if (res.ok && data.success) {
        feedback.style.background = '#EBF7EE';
        feedback.style.color = '#186A3B';
        feedback.style.border = '1px solid #C3E6CB';
        feedback.innerHTML = `
          <strong>${data.message}</strong>
          ${data.errors?.length ? `<ul style="margin: 8px 0 0 16px;">${data.errors.map(err => `<li>${escapeHTML(err)}</li>`).join('')}</ul>` : ''}
        `;
        showToast(`Importation réussie : ${data.imported} élément(s)`);
        if (typeof onImportSuccess === 'function') {
          setTimeout(() => {
            close();
            onImportSuccess();
          }, 1200);
        }
      } else {
        feedback.style.background = '#FDE8E8';
        feedback.style.color = '#9B1C1C';
        feedback.style.border = '1px solid #F8B4B4';
        feedback.textContent = data.error || 'Erreur lors de l\'importation';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Réessayer';
      }
    } catch (err) {
      feedback.style.display = 'block';
      feedback.style.background = '#FDE8E8';
      feedback.style.color = '#9B1C1C';
      feedback.textContent = 'Erreur réseau lors de l\'envoi du fichier.';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Réessayer';
    }
  });
}

/* ==========================================================================
   Initialisation globale du layout d'administration
   ========================================================================== */

import { Sidebar } from '/admin/components/sidebar.js';
import { Topbar } from '/admin/components/topbar.js';
import { logout, requireAuth, getCurrentUser, updateLocalUser } from '/admin/includes/auth.js';
import { initAdminHead } from '/admin/includes/admin-head.js';

export async function initAdminLayout({ activePage = 'dashboard', title = 'Administration', breadcrumbs = [], action = null } = {}) {
  if (!requireAuth()) return false;
  await initAdminHead({ title });

  // 1. Initialiser le thème clair/sombre
  initTheme();

  const sidebarSlot = document.getElementById('sidebar-slot');
  if (sidebarSlot) {
    sidebarSlot.innerHTML = Sidebar({ activePage });
  }

  const topbarSlot = document.getElementById('topbar-slot');
  if (topbarSlot) {
    topbarSlot.innerHTML = Topbar({ title, breadcrumbs, action });
  }

  // 2. Bascule de thème
  document.getElementById('btn-theme-toggle')?.addEventListener('click', () => {
    toggleTheme();
  });

  // 3. Déclencheur Mon Profil (depuis dropdown avatar topbar)
  document.getElementById('topbar-menu-profile')?.addEventListener('click', () => {
    openProfileModal();
  });
  document.getElementById('sidebar-user-profile')?.addEventListener('click', (e) => {
    if (e.target.closest('#btn-logout')) return;
    openProfileModal();
  });

  // Dropdown avatar toggle
  const avatarBtn = document.getElementById('topbar-avatar-btn');
  const dropdown = document.getElementById('topbar-dropdown');
  if (avatarBtn && dropdown) {
    avatarBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('#topbar-user-menu')) {
        dropdown.style.display = 'none';
      }
    });
  }

  // Déconnexion depuis dropdown
  document.getElementById('topbar-menu-logout')?.addEventListener('click', async () => {
    const ok = await confirmModal({
      title: 'Déconnexion',
      message: 'Êtes-vous sûr de vouloir vous déconnecter de votre espace d\'administration ?',
      confirmText: 'Se déconnecter',
      cancelText: 'Rester connecté'
    });
    if (ok) logout();
  });

  // 4. Confirmation de déconnexion (sidebar logout)
  document.getElementById('btn-logout')?.addEventListener('click', async (e) => {
    e.preventDefault();
    const ok = await confirmModal({
      title: 'Déconnexion',
      message: 'Êtes-vous sûr de vouloir vous déconnecter de votre espace d\'administration ?',
      confirmText: 'Se déconnecter',
      cancelText: 'Rester connecté'
    });
    if (ok) {
      logout();
    }
  });

  // 5. Synchronisation temps réel des compteurs sidebar (Point 2.11)
  startRealtimeBadgeSync();

  // 6. Sidebar rétractable
  const sidebarEl = document.getElementById('admin-sidebar');
  const collapseBtn = document.getElementById('sidebar-collapse-btn');
  if (sidebarEl && collapseBtn) {
    const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (savedCollapsed) {
      sidebarEl.classList.add('is-collapsed');
      document.body.classList.add('sidebar-collapsed');
    }
    collapseBtn.addEventListener('click', () => {
      sidebarEl.classList.toggle('is-collapsed');
      document.body.classList.toggle('sidebar-collapsed');
      localStorage.setItem('sidebarCollapsed', sidebarEl.classList.contains('is-collapsed'));
    });
  }

  // 7. Gestion du menu latéral mobile
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

