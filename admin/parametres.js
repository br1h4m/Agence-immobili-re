// admin/parametres.js
// MODULE ES NAVIGATEUR — gestion des paramètres généraux de l'agence Imolode.

import {
  initAdminLayout, adminIcon, showToast, confirmModal, escapeHTML
} from '/admin/assets/js/admin.js';
import {
  getSettings, saveSettings
} from '/admin/data/admin-store.js';

async function init() {
  const ready = await initAdminLayout({
    activePage: 'parametres',
    title: 'Paramètres généraux',
    breadcrumbs: [{ label: 'Paramètres' }]
  });

  if (!ready) return;

  const root = document.getElementById('parametres-content');
  if (!root) return;

  const s = getSettings();

  root.innerHTML = `
    <form id="settings-form" style="max-width: 1000px;">
      <!-- Entête & Sauvegarde -->
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
          <h2 style="margin: 0; font-family: var(--font-serif); font-size: 1.4rem; font-weight: 600;">Configuration globale</h2>
          <p style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.88rem;">
            Ces informations définissent l'identité de l'agence, les coordonnées affichées sur le site et les préférences système.
          </p>
        </div>
        <button type="submit" class="btn btn-primary">
          ${adminIcon('check', { size: 16 })} Enregistrer les modifications
        </button>
      </div>

      <!-- 1. Identité de l'agence -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <div>
            <h3 class="card-title">Identité de l'agence</h3>
            <p class="card-subtitle">Nom, slogan et présentation générale de l'agence immobilière</p>
          </div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-agencyName">Nom de l'agence <span class="required">*</span></label>
              <input type="text" class="form-input" id="setting-agencyName" required value="${escapeHTML(s.agencyName || 'Imolode')}">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-tagline">Slogan commercial</label>
              <input type="text" class="form-input" id="setting-tagline" value="${escapeHTML(s.tagline || '')}" placeholder="L'immobilier d'exception">
            </div>

            <div class="form-col-12 form-group">
              <label class="form-label" for="setting-description">Présentation générale</label>
              <textarea class="form-textarea" id="setting-description" rows="3">${escapeHTML(s.description || '')}</textarea>
              <p class="form-hint">Texte d'introduction utilisé dans le pied de page et la page À propos.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Coordonnées & Contact -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <div>
            <h3 class="card-title">Coordonnées & Horaires</h3>
            <p class="card-subtitle">Points de contact directs pour les clients et prospects</p>
          </div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-phone">Téléphone principal <span class="required">*</span></label>
              <input type="text" class="form-input" id="setting-phone" required value="${escapeHTML(s.phone || '')}" placeholder="0661.56.03.85">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-whatsapp">Numéro WhatsApp</label>
              <input type="text" class="form-input" id="setting-whatsapp" value="${escapeHTML(s.whatsapp || '')}" placeholder="213661560385">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-email">Adresse e-mail officielle <span class="required">*</span></label>
              <input type="email" class="form-input" id="setting-email" required value="${escapeHTML(s.email || '')}" placeholder="gahamhocine2@gmail.com">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-hours">Horaires d'ouverture</label>
              <input type="text" class="form-input" id="setting-hours" value="${escapeHTML(s.hours || '')}" placeholder="Dimanche - Jeudi : 9h00 - 17h30">
            </div>

            <div class="form-col-12 form-group">
              <label class="form-label" for="setting-address">Adresse physique de l'agence</label>
              <input type="text" class="form-input" id="setting-address" value="${escapeHTML(s.address || '')}" placeholder="41 Bld Soudani Boudjemaa El Mouradia - Alger">
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Réseaux sociaux & Préférences -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <div>
            <h3 class="card-title">Réseaux sociaux & Affichage</h3>
            <p class="card-subtitle">Visibilité externe et options du catalogue</p>
          </div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-facebook">Page Facebook (Imolode Hydra)</label>
              <input type="url" class="form-input" id="setting-facebook" value="${escapeHTML(s.facebook || '')}" placeholder="https://facebook.com/imolode.hydra">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-instagram">Profil Instagram</label>
              <input type="url" class="form-input" id="setting-instagram" value="${escapeHTML(s.instagram || '')}" placeholder="https://instagram.com/Imolode">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-currency">Devise d'affichage</label>
              <input type="text" class="form-input" id="setting-currency" value="${escapeHTML(s.currency || 'DA')}">
            </div>

            <div class="form-col-6 form-group">
              <label class="form-label" for="setting-perPage">Biens par page dans le catalogue</label>
              <input type="number" class="form-input" id="setting-perPage" min="3" max="50" value="${escapeHTML(s.propertiesPerPage || 9)}">
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Zone de danger / Réinitialisation -->
      <div class="card" style="border-color: #F0C4C1; margin-bottom: 32px;">
        <div class="card-header" style="background-color: var(--admin-danger-soft);">
          <div>
            <h3 class="card-title" style="color: var(--admin-danger);">Zone de maintenance des données</h3>
            <p class="card-subtitle" style="color: #8C2E24;">Réinitialiser les données locales stockées dans le navigateur</p>
          </div>
        </div>
        <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
          <div>
            <p style="margin: 0; font-weight: 600; font-size: 0.92rem;">Rétablir les données de démonstration</p>
            <p style="margin: 4px 0 0; font-size: 0.82rem; color: var(--admin-text-muted);">
              Efface le localStorage et restaure les biens, demandes et utilisateurs originaux du code source.
            </p>
          </div>
          <button type="button" class="btn btn-danger-outline" id="btn-reset-data">
            ${adminIcon('trash', { size: 16 })} Réinitialiser toutes les données
          </button>
        </div>
      </div>

      <!-- Bouton bas de page -->
      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
        <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 0.95rem;">
          ${adminIcon('check', { size: 18 })} Enregistrer tous les paramètres
        </button>
      </div>
    </form>
  `;

  bindEvents();
}

function bindEvents() {
  const form = document.getElementById('settings-form');
  form?.addEventListener('submit', (e) => {
    e.preventDefault();

    const agencyName = document.getElementById('setting-agencyName')?.value.trim();
    const tagline = document.getElementById('setting-tagline')?.value.trim();
    const description = document.getElementById('setting-description')?.value.trim();
    const phone = document.getElementById('setting-phone')?.value.trim();
    const whatsapp = document.getElementById('setting-whatsapp')?.value.trim();
    const email = document.getElementById('setting-email')?.value.trim();
    const hours = document.getElementById('setting-hours')?.value.trim();
    const address = document.getElementById('setting-address')?.value.trim();
    const facebook = document.getElementById('setting-facebook')?.value.trim();
    const instagram = document.getElementById('setting-instagram')?.value.trim();
    const currency = document.getElementById('setting-currency')?.value.trim() || 'DA';
    const propertiesPerPage = parseInt(document.getElementById('setting-perPage')?.value, 10) || 9;

    saveSettings({
      agencyName,
      tagline,
      description,
      phone,
      whatsapp,
      email,
      hours,
      address,
      facebook,
      instagram,
      currency,
      propertiesPerPage
    });

    showToast('Paramètres sauvegardés avec succès !');
  });

  document.getElementById('btn-reset-data')?.addEventListener('click', async () => {
    const ok = await confirmModal({
      title: 'Réinitialiser toutes les données ?',
      message: 'Attention : toutes vos modifications locales (biens ajoutés/modifiés, demandes traitées, utilisateurs personnalisés) seront réinitialisées aux valeurs de démonstration d\'origine.',
      confirmText: 'Oui, tout réinitialiser'
    });

    if (ok) {
      localStorage.removeItem('immo_properties');
      localStorage.removeItem('immo_demandes');
      localStorage.removeItem('immo_users');
      localStorage.removeItem('immo_settings');
      showToast('Données réinitialisées. Rechargement...');
      setTimeout(() => {
        window.location.reload();
      }, 800);
    }
  });
}

document.addEventListener('DOMContentLoaded', init);

