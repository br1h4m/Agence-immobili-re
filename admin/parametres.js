// admin/parametres.js
// MODULE ES NAVIGATEUR — gestion des paramètres généraux et du CMS de contenu du site Imolode.

import {
  initAdminLayout, adminIcon, showToast, confirmModal, escapeHTML
} from '/admin/assets/js/admin.js';
import {
  getSettings, saveSettings
} from '/admin/data/admin-store.js';

async function init() {
  const ready = await initAdminLayout({
    activePage: 'parametres',
    title: 'Paramètres du site & CMS',
    breadcrumbs: [{ label: 'Paramètres' }]
  });

  if (!ready) return;

  const root = document.getElementById('parametres-content');
  if (!root) return;

  const s = getSettings();

  root.innerHTML = `
    <form id="settings-form" style="max-width: 1040px;">
      <!-- Entête & Sauvegarde -->
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
          <h2 style="margin: 0; font-family: var(--font-serif); font-size: 1.4rem; font-weight: 600;">Gestion du Site & Contenu CMS</h2>
          <p style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.88rem;">
            Modifiez en temps réel les coordonnées, les bannières et le contenu des pages publiques sans éditeur de code.
          </p>
        </div>
        <button type="submit" class="btn btn-primary" id="btn-save-settings-top">
          ${adminIcon('check', { size: 16 })} Enregistrer toutes les modifications
        </button>
      </div>

      <!-- Navigation par onglets simples -->
      <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; flex-wrap: wrap;">
        <button type="button" class="btn btn-primary btn-sm tab-btn" data-tab="tab-agency">Identité & Coordonnées</button>
        <button type="button" class="btn btn-outline btn-sm tab-btn" data-tab="tab-home">Accueil & Bannières</button>
        <button type="button" class="btn btn-outline btn-sm tab-btn" data-tab="tab-about">Page « À propos »</button>
        <button type="button" class="btn btn-outline btn-sm tab-btn" data-tab="tab-services">Page « Services »</button>
      </div>

      <!-- ONGLET 1 : Identité & Coordonnées -->
      <div class="tab-pane is-active" id="tab-agency">
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
                <input type="text" class="form-input" id="setting-agencyName" required value="${escapeHTML(s.agencyName || s.agency_name || 'Imolode')}">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-tagline">Slogan commercial</label>
                <input type="text" class="form-input" id="setting-tagline" value="${escapeHTML(s.tagline || '')}" placeholder="L'immobilier d'exception en Algérie">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-description">Présentation générale courte</label>
                <textarea class="form-textarea" id="setting-description" rows="3">${escapeHTML(s.description || '')}</textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <div>
              <h3 class="card-title">Coordonnées & Horaires d'accueil</h3>
              <p class="card-subtitle">Points de contact directs affichés sur le site et dans le pied de page</p>
            </div>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-phone">Téléphone principal <span class="required">*</span></label>
                <input type="text" class="form-input" id="setting-phone" required value="${escapeHTML(s.phone || s.agence_telephone || '0661.56.03.85')}" placeholder="0661.56.03.85">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-whatsapp">Numéro WhatsApp (avec indicatif)</label>
                <input type="text" class="form-input" id="setting-whatsapp" value="${escapeHTML(s.whatsapp || '213661560385')}" placeholder="213661560385">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-email">Adresse e-mail officielle <span class="required">*</span></label>
                <input type="email" class="form-input" id="setting-email" required value="${escapeHTML(s.email || 'gahamhocine2@gmail.com')}" placeholder="gahamhocine2@gmail.com">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-hours">Horaires d'ouverture</label>
                <input type="text" class="form-input" id="setting-hours" value="${escapeHTML(s.hours || 'Dimanche - Jeudi : 9h00 - 17h30')}" placeholder="Dimanche - Jeudi : 9h00 - 17h30">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-address">Adresse physique de l'agence</label>
                <input type="text" class="form-input" id="setting-address" value="${escapeHTML(s.address || '12 rue Didouche Mourad, Béjaïa 06000, Algérie')}">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ONGLET 2 : Page Accueil & Bannières -->
      <div class="tab-pane" id="tab-home" style="display: none;">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <div>
              <h3 class="card-title">Bannière principale (Hero Banner)</h3>
              <p class="card-subtitle">Personnalisez le grand titre et la photo de fond de la page d'accueil</p>
            </div>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-hero-title">Grand titre d'accueil</label>
                <input type="text" class="form-input" id="setting-hero-title" value="${escapeHTML(s.hero_title || 'L\'immobilier d\'exception en Algérie')}">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-hero-subtitle">Sous-titre d'accueil</label>
                <textarea class="form-textarea" id="setting-hero-subtitle" rows="2">${escapeHTML(s.hero_subtitle || 'Découvrez une sélection exclusive d\'appartements de haut standing, villas d\'architecte et locaux commerciaux.')}</textarea>
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label">Photo de fond d'accueil (Hero image)</label>
                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 8px;">
                  <input type="text" class="form-input" id="setting-hero-image" value="${escapeHTML(s.hero_image || 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=75')}">
                  <input type="file" id="file-hero-image" accept="image/*" style="display: none;">
                  <button type="button" class="btn btn-outline btn-sm" id="btn-upload-hero-image" style="flex-shrink: 0;">
                    ${adminIcon('upload', { size: 14 })} Téléverser
                  </button>
                </div>
                <div id="preview-hero-image" style="height: 140px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--admin-border); margin-top: 8px;">
                  <img src="${escapeHTML(s.hero_image || 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=75')}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ONGLET 3 : Page « À propos » -->
      <div class="tab-pane" id="tab-about" style="display: none;">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <div>
              <h3 class="card-title">Contenu de la page « À propos »</h3>
              <p class="card-subtitle">Présentez l'histoire, la vision et l'excellence de votre agence</p>
            </div>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-about-title">Titre de la section présentation</label>
                <input type="text" class="form-input" id="setting-about-title" value="${escapeHTML(s.about_title || 'L\'excellence immobilière à votre service depuis 2018')}">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-about-text">Texte descriptif détaillé</label>
                <textarea class="form-textarea" id="setting-about-text" rows="5">${escapeHTML(s.about_text || 'Fondée à Béjaïa et rayonnant à travers les plus prestigieuses wilayas d\'Algérie, l\'agence Immolode redéfinit les standards de l\'immobilier haut de gamme.')}</textarea>
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label">Photo illustrative de l'agence (À propos)</label>
                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 8px;">
                  <input type="text" class="form-input" id="setting-about-image" value="${escapeHTML(s.about_image || 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=75')}">
                  <input type="file" id="file-about-image" accept="image/*" style="display: none;">
                  <button type="button" class="btn btn-outline btn-sm" id="btn-upload-about-image" style="flex-shrink: 0;">
                    ${adminIcon('upload', { size: 14 })} Téléverser
                  </button>
                </div>
                <div id="preview-about-image" style="height: 140px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--admin-border); margin-top: 8px;">
                  <img src="${escapeHTML(s.about_image || 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=75')}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ONGLET 4 : Page « Services » -->
      <div class="tab-pane" id="tab-services" style="display: none;">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <div>
              <h3 class="card-title">Services Phares de l'Agence</h3>
              <p class="card-subtitle">Personnalisez les intitulés et textes des 3 services phares</p>
            </div>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <!-- Service 1 : Achat -->
              <div class="form-col-12 form-group" style="padding: 12px; background: var(--admin-surface-alt); border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
                <label class="form-label" style="font-weight: 700; color: var(--admin-gold);">1. Service Acquisition / Achat</label>
                <input type="text" class="form-input" id="setting-service-achat-titre" value="${escapeHTML(s.service_achat_titre || 'Acquisition sur mesure')}" style="margin-bottom: 8px;">
                <textarea class="form-textarea" id="setting-service-achat-desc" rows="2">${escapeHTML(s.service_achat_desc || 'Accédez à des biens d\'exception rigoureusement sélectionnés et certifiés juridiquement (actes notariés et livrets fonciers garantis).')}</textarea>
              </div>

              <!-- Service 2 : Location -->
              <div class="form-col-12 form-group" style="padding: 12px; background: var(--admin-surface-alt); border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
                <label class="form-label" style="font-weight: 700; color: var(--admin-gold);">2. Service Location Premium</label>
                <input type="text" class="form-input" id="setting-service-location-titre" value="${escapeHTML(s.service_location_titre || 'Location premium')}" style="margin-bottom: 8px;">
                <textarea class="form-textarea" id="setting-service-location-desc" rows="2">${escapeHTML(s.service_location_desc || 'Des résidences meublées et locaux professionnels prêts à vivre ou travailler, sélectionnés pour leur confort et leur emplacement stratégique.')}</textarea>
              </div>

              <!-- Service 3 : Estimation -->
              <div class="form-col-12 form-group" style="padding: 12px; background: var(--admin-surface-alt); border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
                <label class="form-label" style="font-weight: 700; color: var(--admin-gold);">3. Service Estimation & Conseil</label>
                <input type="text" class="form-input" id="setting-service-estimation-titre" value="${escapeHTML(s.service_estimation_titre || 'Estimation & Conseil')}" style="margin-bottom: 8px;">
                <textarea class="form-textarea" id="setting-service-estimation-desc" rows="2">${escapeHTML(s.service_estimation_desc || 'Une évaluation rigoureuse et réaliste de votre bien, alignée sur les dynamiques réelles du marché immobilier algérien.')}</textarea>
              </div>
            </div>
          </div>
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
  // Navigation par onglets
  const tabs = document.querySelectorAll('.tab-btn');
  tabs.forEach(t => {
    t.addEventListener('click', () => {
      tabs.forEach(btn => { btn.classList.remove('btn-primary'); btn.classList.add('btn-outline'); });
      t.classList.remove('btn-outline');
      t.classList.add('btn-primary');
      const targetId = t.dataset.tab;
      document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
      const pane = document.getElementById(targetId);
      if (pane) pane.style.display = 'block';
    });
  });

  // Uploader image Hero
  const fileHero = document.getElementById('file-hero-image');
  document.getElementById('btn-upload-hero-image')?.addEventListener('click', () => fileHero?.click());
  fileHero?.addEventListener('change', async () => {
    const file = fileHero.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('folder', 'properties');
    try {
      const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (res.ok && data.data?.url) {
        document.getElementById('setting-hero-image').value = data.data.url;
        document.getElementById('preview-hero-image').innerHTML = `<img src="${data.data.url}" style="width: 100%; height: 100%; object-fit: cover;">`;
        showToast('Image Hero mise à jour !');
      }
    } catch (_) {}
  });

  // Uploader image About
  const fileAbout = document.getElementById('file-about-image');
  document.getElementById('btn-upload-about-image')?.addEventListener('click', () => fileAbout?.click());
  fileAbout?.addEventListener('change', async () => {
    const file = fileAbout.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('folder', 'properties');
    try {
      const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (res.ok && data.data?.url) {
        document.getElementById('setting-about-image').value = data.data.url;
        document.getElementById('preview-about-image').innerHTML = `<img src="${data.data.url}" style="width: 100%; height: 100%; object-fit: cover;">`;
        showToast('Image « À propos » mise à jour !');
      }
    } catch (_) {}
  });

  // Soumission
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

    const hero_title = document.getElementById('setting-hero-title')?.value.trim();
    const hero_subtitle = document.getElementById('setting-hero-subtitle')?.value.trim();
    const hero_image = document.getElementById('setting-hero-image')?.value.trim();

    const about_title = document.getElementById('setting-about-title')?.value.trim();
    const about_text = document.getElementById('setting-about-text')?.value.trim();
    const about_image = document.getElementById('setting-about-image')?.value.trim();

    const service_achat_titre = document.getElementById('setting-service-achat-titre')?.value.trim();
    const service_achat_desc = document.getElementById('setting-service-achat-desc')?.value.trim();
    const service_location_titre = document.getElementById('setting-service-location-titre')?.value.trim();
    const service_location_desc = document.getElementById('setting-service-location-desc')?.value.trim();
    const service_estimation_titre = document.getElementById('setting-service-estimation-titre')?.value.trim();
    const service_estimation_desc = document.getElementById('setting-service-estimation-desc')?.value.trim();

    saveSettings({
      agencyName,
      agency_name: agencyName,
      tagline,
      description,
      phone,
      agence_telephone: phone,
      whatsapp,
      email,
      hours,
      address,
      hero_title,
      hero_subtitle,
      hero_image,
      about_title,
      about_text,
      about_image,
      service_achat_titre,
      service_achat_desc,
      service_location_titre,
      service_location_desc,
      service_estimation_titre,
      service_estimation_desc
    });

    showToast('Tous les paramètres et textes ont été enregistrés avec succès !');
  });
}

document.addEventListener('DOMContentLoaded', init);
