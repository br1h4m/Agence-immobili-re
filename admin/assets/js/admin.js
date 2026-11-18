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
  'download': '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
  'upload': '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
  'printer': '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
  'lock': '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
  'sun': '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
  'moon': '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>'
};

export function adminIcon(name, { size = 18, className = '' } = {}) {
  const inner = ICONS[name] || ICONS['check'];
  return `<svg class="admin-icon${className ? ` ${className}` : ''}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;
}

/* ==========================================================================
   Badges de statut & transaction
   ========================================================================== */

export function statusBadge(status) {
  let badgeClass = 'badge--info';
  switch (status) {
    case 'Disponible':
    case 'Validé':
    case 'Traite':
    case 'Traité':
      badgeClass = 'badge--success';
      break;
    case 'Nouveau':
      badgeClass = 'badge--gold';
      break;
    case 'En cours':
      badgeClass = 'badge--warning';
      break;
    case 'Vendu':
    case 'Loué':
    case 'Rejeté':
      badgeClass = 'badge--danger';
      break;
    default:
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
   Modale d'importation CSV / Excel
   ========================================================================== */

export function openCsvImportModal({ type = 'properties', title = 'Importer des biens', onSuccess = null } = {}) {
  let overlay = document.getElementById('admin-modal-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'admin-modal-overlay';
    overlay.className = 'admin-modal-overlay';
    document.body.appendChild(overlay);
  }

  overlay.innerHTML = `
    <div class="admin-modal-card" style="max-width: 520px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
        <h3 class="admin-modal-title" style="margin:0;">${escapeHTML(title)}</h3>
        <button type="button" class="btn-icon" id="modal-close-import" aria-label="Fermer">${adminIcon('close', { size: 16 })}</button>
      </div>
      
      <p style="font-size: 0.88rem; color: var(--admin-text-muted); margin-bottom: 16px;">
        Importez vos données depuis un fichier CSV ou Excel enregistré au format .csv. Les séparateurs virgule (,) et point-virgule (;) sont automatiquement détectés.
      </p>

      <div style="margin-bottom: 20px;">
        <a href="/api/import.php?action=template&model=${encodeURIComponent(type)}" class="btn btn-outline btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
          ${adminIcon('download', { size: 14 })} Télécharger le modèle CSV
        </a>
      </div>

      <form id="form-csv-import" enctype="multipart/form-data">
        <div class="form-group" style="margin-bottom: 20px;">
          <label class="form-label">Sélectionnez le fichier CSV <span class="required">*</span></label>
          <input type="file" id="import-file-input" name="file" accept=".csv, .txt" required class="form-input" style="padding: 8px;">
        </div>

        <div class="admin-modal-actions">
          <button type="button" class="btn btn-outline" id="modal-cancel-import">Annuler</button>
          <button type="submit" class="btn btn-primary" id="modal-submit-import">
            ${adminIcon('upload', { size: 14 })} Lancer l'importation
          </button>
        </div>
      </form>
    </div>
  `;

  overlay.classList.add('is-active');

  const close = () => {
    overlay.classList.remove('is-active');
    overlay.innerHTML = '';
  };

  document.getElementById('modal-close-import')?.addEventListener('click', close);
  document.getElementById('modal-cancel-import')?.addEventListener('click', close);

  const form = document.getElementById('form-csv-import');
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fileInput = document.getElementById('import-file-input');
    if (!fileInput?.files?.[0]) return;

    const submitBtn = document.getElementById('modal-submit-import');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Importation en cours...';
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('type', type);

    try {
      const res = await fetch('/api/import.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (res.ok && data.success) {
        showToast(data.message || 'Importation terminée avec succès !');
        close();
        if (typeof onSuccess === 'function') onSuccess(data);
      } else {
        showToast(data.error || 'Erreur lors de l\'importation', 'error');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = `${adminIcon('upload', { size: 14 })} Lancer l'importation`;
        }
      }
    } catch (err) {
      showToast('Erreur de connexion au serveur', 'error');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `${adminIcon('upload', { size: 14 })} Lancer l'importation`;
      }
    }
  });
}

/* ==========================================================================
   Modale de gestion du profil personnel (Mon Profil)
   ========================================================================== */

export function openProfileModal() {
  import('/admin/includes/auth.js').then(({ getCurrentUser, updateLocalUser }) => {
    const user = getCurrentUser() || { nom: 'G. Hocine', email: 'gahamhocine2@gmail.com', phone: '0661.56.03.85', role: 'Gérant' };

    let overlay = document.getElementById('admin-modal-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'admin-modal-overlay';
      overlay.className = 'admin-modal-overlay';
      document.body.appendChild(overlay);
    }

    const avatarHtml = user.avatar
      ? `<img id="profile-preview-img" src="${escapeHTML(user.avatar)}" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--admin-gold);">`
      : `<div id="profile-preview-placeholder" style="width:72px; height:72px; border-radius:50%; background:var(--admin-gold); color:#FFF; font-size:1.8rem; font-weight:700; display:flex; align-items:center; justify-content:center;">${escapeHTML(user.nom?.charAt(0) || 'U')}</div>`;

    overlay.innerHTML = `
      <div class="admin-modal-card" style="max-width: 560px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
          <h3 class="admin-modal-title" style="margin:0;">Mon Compte Personnel</h3>
          <button type="button" class="btn-icon" id="modal-close-profile" aria-label="Fermer">${adminIcon('close', { size: 16 })}</button>
        </div>

        <form id="form-user-profile">
          <!-- Photo de profil -->
          <div style="display:flex; align-items:center; gap:20px; margin-bottom: 20px; padding:12px; background:var(--admin-surface-alt); border-radius:var(--radius-sm); border:1px solid var(--admin-border);">
            <div id="profile-avatar-container">
              ${avatarHtml}
            </div>
            <div>
              <div style="font-weight:600; font-size:0.9rem; margin-bottom:4px;">Photo de profil</div>
              <input type="file" id="profile-file-input" accept="image/*" style="display:none;">
              <input type="hidden" id="profile-avatar-val" value="${escapeHTML(user.avatar || '')}">
              <button type="button" class="btn btn-outline btn-sm" id="btn-upload-avatar">
                ${adminIcon('upload', { size: 14 })} Changer la photo
              </button>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-col-6 form-group">
              <label class="form-label" for="profile-nom">Nom complet <span class="required">*</span></label>
              <input type="text" class="form-input" id="profile-nom" required value="${escapeHTML(user.nom || '')}">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="profile-email">Adresse e-mail <span class="required">*</span></label>
              <input type="email" class="form-input" id="profile-email" required value="${escapeHTML(user.email || '')}">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="profile-phone">Téléphone</label>
              <input type="text" class="form-input" id="profile-phone" value="${escapeHTML(user.phone || user.telephone || '')}">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label">Rôle</label>
              <input type="text" class="form-input" disabled value="${escapeHTML(user.role || 'Administrateur')}" style="opacity:0.7;">
            </div>

            <div class="form-col-12 form-group">
              <label class="form-label" for="profile-newpass">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
              <input type="password" class="form-input" id="profile-newpass" placeholder="••••••••">
            </div>
          </div>

          <div class="admin-modal-actions" style="margin-top:20px;">
            <button type="button" class="btn btn-outline" id="modal-cancel-profile">Annuler</button>
            <button type="submit" class="btn btn-primary" id="modal-submit-profile">
              ${adminIcon('check', { size: 14 })} Enregistrer mon profil
            </button>
          </div>
        </form>
      </div>
    `;

    overlay.classList.add('is-active');

    const close = () => {
      overlay.classList.remove('is-active');
      overlay.innerHTML = '';
    };

    document.getElementById('modal-close-profile')?.addEventListener('click', close);
    document.getElementById('modal-cancel-profile')?.addEventListener('click', close);

    // Téléversement d'avatar
    const fileInput = document.getElementById('profile-file-input');
    document.getElementById('btn-upload-avatar')?.addEventListener('click', () => {
      fileInput?.click();
    });

    fileInput?.addEventListener('change', async () => {
      const file = fileInput.files?.[0];
      if (!file) return;

      const fd = new FormData();
      fd.append('file', file);
      fd.append('folder', 'users');

      try {
        const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (res.ok && data.data?.url) {
          const avatarUrl = data.data.url;
          document.getElementById('profile-avatar-val').value = avatarUrl;
          const container = document.getElementById('profile-avatar-container');
          if (container) {
            container.innerHTML = `<img id="profile-preview-img" src="${avatarUrl}" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--admin-gold);">`;
          }
          showToast('Photo de profil téléversée !');
        } else {
          showToast(data.message || 'Erreur lors du téléversement', 'error');
        }
      } catch (err) {
        showToast('Impossible d\'envoyer l\'image', 'error');
      }
    });

    // Enregistrement du profil
    document.getElementById('form-user-profile')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const nom = document.getElementById('profile-nom')?.value.trim();
      const email = document.getElementById('profile-email')?.value.trim();
      const phone = document.getElementById('profile-phone')?.value.trim();
      const avatar = document.getElementById('profile-avatar-val')?.value.trim();
      const password = document.getElementById('profile-newpass')?.value;

      if (!nom || !email) {
        showToast('Le nom et l\'email sont requis', 'error');
        return;
      }

      const payload = {
        action: 'profile',
        id: user.id || 1,
        nom,
        email,
        phone,
        avatar
      };
      if (password) payload.password = password;

      try {
        const res = await fetch('/api/users.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok && data.success) {
          // Mise à jour de la session locale
          updateLocalUser({
            ...user,
            nom,
            email,
            phone,
            avatar
          });
          showToast('Profil mis à jour avec succès !');
          close();
          // Rafraîchir l'affichage
          setTimeout(() => window.location.reload(), 400);
        } else {
          showToast(data.error || 'Erreur lors de la mise à jour', 'error');
        }
      } catch (err) {
        showToast('Erreur de communication avec le serveur', 'error');
      }
    });
  });
}

/* ==========================================================================
   Gestion du thème Sombre / Clair
   ========================================================================== */

export function initTheme() {
  const currentTheme = localStorage.getItem('admin_theme') || 'light';
  applyTheme(currentTheme);

  document.getElementById('btn-theme-toggle')?.addEventListener('click', () => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const nextTheme = isDark ? 'light' : 'dark';
    applyTheme(nextTheme);
    localStorage.setItem('admin_theme', nextTheme);
    showToast(`Thème ${nextTheme === 'dark' ? 'sombre' : 'clair'} activé`);
  });
}

function applyTheme(theme) {
  if (theme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
    document.querySelector('.theme-icon-light')?.style.setProperty('display', 'none');
    document.querySelector('.theme-icon-dark')?.style.setProperty('display', 'inline-flex');
  } else {
    document.documentElement.removeAttribute('data-theme');
    document.querySelector('.theme-icon-light')?.style.setProperty('display', 'inline-flex');
    document.querySelector('.theme-icon-dark')?.style.setProperty('display', 'none');
  }
}

/* ==========================================================================
   Synchronisation en temps réel des badges de la sidebar
   ========================================================================== */

export function startRealtimeBadgeSync(intervalMs = 8000) {
  async function updateBadges() {
    try {
      const res = await fetch('/api/stats.php');
      if (!res.ok) return;
      const stats = await res.json();

      const demandesCount = stats.unreadDemandes ?? stats.pendingDemandes ?? 0;
      const fichesCount = stats.unreadFichesClient ?? stats.pendingFichesClient ?? 0;

      const badgeDem = document.getElementById('badge-demandes');
      if (badgeDem) {
        if (demandesCount > 0) {
          badgeDem.textContent = demandesCount;
          badgeDem.style.display = 'inline-block';
        } else {
          badgeDem.style.display = 'none';
        }
      }

      const badgeFich = document.getElementById('badge-fiches');
      if (badgeFich) {
        if (fichesCount > 0) {
          badgeFich.textContent = fichesCount;
          badgeFich.style.display = 'inline-block';
        } else {
          badgeFich.style.display = 'none';
        }
      }
    } catch (_) {}
  }

  updateBadges();
  setInterval(updateBadges, intervalMs);
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

  // Initialisation du thème et du badge sync
  initTheme();
  startRealtimeBadgeSync();

  // Déconnexion sécurisée avec modale de confirmation
  document.getElementById('btn-logout')?.addEventListener('click', async (e) => {
    e.preventDefault();
    const confirmed = await confirmModal({
      title: 'Confirmation de déconnexion',
      message: 'Êtes-vous sûr de vouloir vous déconnecter de la session administrateur ?',
      confirmText: 'Se déconnecter',
      cancelText: 'Annuler'
    });
    if (confirmed) {
      logout();
    }
  });

  // Ouverture de la modale "Mon Profil"
  const openProf = () => openProfileModal();
  document.getElementById('btn-open-profile-topbar')?.addEventListener('click', openProf);
  document.getElementById('btn-open-profile-avatar')?.addEventListener('click', openProf);
  document.getElementById('btn-open-profile-meta')?.addEventListener('click', openProf);

  // Gestion du menu latéral mobile
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
