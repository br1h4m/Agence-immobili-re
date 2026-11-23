// admin/parametres.js
// MODULE ES NAVIGATEUR — gestion des paramètres généraux & CMS de l'agence Imolode.

import {
  initAdminLayout, adminIcon, showToast, escapeHTML
} from '/admin/assets/js/admin.js';
import {
  getSettings, saveSettings
} from '/admin/data/admin-store.js';

let activeTab = 'identity';

async function init() {
  const ready = await initAdminLayout({
    activePage: 'parametres',
    title: 'Paramètres généraux & Contenu du site',
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
          <h2 style="margin: 0; font-family: var(--font-serif); font-size: 1.4rem; font-weight: 600;">Gestion du site & CMS</h2>
          <p style="margin: 4px 0 0; color: var(--admin-text-muted); font-size: 0.88rem;">
            Modifiez directement les coordonnées, les bannières, les photos et les textes des pages sans passer par le code.
          </p>
        </div>
        <button type="submit" class="btn btn-primary" id="btn-save-settings">
          ${adminIcon('check', { size: 16 })}
          <span>Enregistrer les modifications</span>
        </button>
      </div>

      <!-- Navigation par onglets -->
      <div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid var(--admin-border); padding-bottom: 2px; overflow-x: auto;" id="cms-tabs-nav">
        <button type="button" class="btn btn-outline btn-sm is-active" data-tab="identity">
          ${adminIcon('settings', { size: 14 })} Identité & Coordonnées
        </button>
        <button type="button" class="btn btn-outline btn-sm" data-tab="home">
          ${adminIcon('dashboard', { size: 14 })} Page d'accueil & Bannières
        </button>
        <button type="button" class="btn btn-outline btn-sm" data-tab="about">
          ${adminIcon('users', { size: 14 })} Page « À propos »
        </button>
        <button type="button" class="btn btn-outline btn-sm" data-tab="services">
          ${adminIcon('building', { size: 14 })} Page « Services »
        </button>
      </div>

      <!-- ONGLET 1 : Identité & Contact -->
      <div class="cms-tab-pane" id="pane-identity">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <h3 class="card-title">Identité de l'agence</h3>
            <p class="card-subtitle">Nom officiel, slogan et description de présentation</p>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-agencyName">Nom de l'agence <span class="required">*</span></label>
                <input type="text" class="form-input" id="setting-agencyName" required value="${escapeHTML(s.agencyName || s.agence_nom || 'Imolode')}">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-tagline">Slogan commercial</label>
                <input type="text" class="form-input" id="setting-tagline" value="${escapeHTML(s.tagline || s.agence_slogan || 'L\'immobilier d\'exception à Béjaïa et en Algérie')}">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-description">Présentation générale de l'agence</label>
                <textarea class="form-textarea" id="setting-description" rows="3">${escapeHTML(s.description || s.agence_description || 'Agence immobilière agréée, spécialisée dans l\'accompagnement sur mesure, l\'achat, la vente, la location et l\'estimation de biens d\'exception.')}</textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <h3 class="card-title">Coordonnées officielles & Horaires</h3>
            <p class="card-subtitle">Affichées sur le bandeau, le pied de page et la page contact</p>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-phone">Téléphone direct <span class="required">*</span></label>
                <input type="text" class="form-input" id="setting-phone" required value="${escapeHTML(s.phone || s.agence_telephone || '+213 555 12 34 56')}">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-whatsapp">Numéro WhatsApp (sans le +)</label>
                <input type="text" class="form-input" id="setting-whatsapp" value="${escapeHTML(s.whatsapp || s.agence_whatsapp || '213555123456')}">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-email">Email officiel <span class="required">*</span></label>
                <input type="email" class="form-input" id="setting-email" required value="${escapeHTML(s.email || s.agence_email || 'contact@immolode.dz')}">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-hours">Horaires d'ouverture</label>
                <input type="text" class="form-input" id="setting-hours" value="${escapeHTML(s.hours || s.agence_horaires || 'Dimanche - Jeudi : 9h00 - 17h30 · Samedi : 9h00 - 13h00')}">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-address">Adresse physique du siège</label>
                <input type="text" class="form-input" id="setting-address" value="${escapeHTML(s.address || s.agence_adresse || '04 Boulevard des Frères Bouaouina, Béjaïa 06000')}">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-facebook">Lien page Facebook</label>
                <input type="url" class="form-input" id="setting-facebook" value="${escapeHTML(s.facebook || 'https://facebook.com/immolode')}">
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-instagram">Lien page Instagram</label>
                <input type="url" class="form-input" id="setting-instagram" value="${escapeHTML(s.instagram || 'https://instagram.com/immolode')}">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ONGLET 2 : Page d'Accueil & Bannières -->
      <div class="cms-tab-pane" id="pane-home" style="display: none;">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <h3 class="card-title">Bannière principale (Hero Banner)</h3>
            <p class="card-subtitle">Titres et visuel affichés en haut de la page d'accueil</p>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-hero-title">Titre principal de l'accueil</label>
                <input type="text" class="form-input" id="setting-cms-hero-title" value="${escapeHTML(s.cms_hero_title || 'L\'adresse de vos plus beaux projets')}">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-hero-subtitle">Sous-titre explicatif</label>
                <textarea class="form-textarea" id="setting-cms-hero-subtitle" rows="2">${escapeHTML(s.cms_hero_subtitle || 'Découvrez une sélection exclusive de biens immobiliers d\'exception à Béjaïa et sur l\'ensemble du territoire algérien.')}</textarea>
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-hero-image">Image d'arrière-plan de la bannière (URL)</label>
                <input type="url" class="form-input" id="setting-cms-hero-image" value="${escapeHTML(s.cms_hero_image || 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1920&q=80')}">
                <div style="margin-top: 10px; height: 120px; border-radius: var(--radius-xs); overflow: hidden; border: 1px solid var(--admin-border);">
                  <img id="preview-hero-image" src="${escapeHTML(s.cms_hero_image || 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1920&q=80')}" alt="Aperçu Bannière" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ONGLET 3 : Page À propos -->
      <div class="cms-tab-pane" id="pane-about" style="display: none;">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <h3 class="card-title">Contenu de la page « À propos »</h3>
            <p class="card-subtitle">Histoire, valeurs et illustration de l'agence</p>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-about-title">Titre de la section À propos</label>
                <input type="text" class="form-input" id="setting-cms-about-title" value="${escapeHTML(s.cms_about_title || 'Une agence immobilière d\'exception au cœur de Béjaïa')}">
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-about-text">Texte de présentation / Notre histoire</label>
                <textarea class="form-textarea" id="setting-cms-about-text" rows="5">${escapeHTML(s.cms_about_text || 'Fondée avec la volonté de redéfinir les standards de l\'immobilier en Algérie, l\'agence Imolode vous garantit discrétion, expertise juridique et rigueur professionnelle. Nous sélectionnons des biens d\'exception et bâtissons des relations de confiance durables.')}</textarea>
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-cms-about-mission">Notre Mission</label>
                <textarea class="form-textarea" id="setting-cms-about-mission" rows="3">${escapeHTML(s.cms_about_mission || 'Offrir un accompagnement transparent, sécurisé et personnalisé pour chaque acquéreur, vendeur ou locataire.')}</textarea>
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="setting-cms-about-vision">Notre Vision</label>
                <textarea class="form-textarea" id="setting-cms-about-vision" rows="3">${escapeHTML(s.cms_about_vision || 'Devenir la référence incontournable de l\'immobilier premium et de la gestion de patrimoine en Algérie.')}</textarea>
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-about-image">Image d'illustration de l'agence (URL)</label>
                <input type="url" class="form-input" id="setting-cms-about-image" value="${escapeHTML(s.cms_about_image || 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80')}">
                <div style="margin-top: 10px; height: 120px; border-radius: var(--radius-xs); overflow: hidden; border: 1px solid var(--admin-border); max-width: 380px;">
                  <img id="preview-about-image" src="${escapeHTML(s.cms_about_image || 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80')}" alt="Aperçu Agence" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ONGLET 4 : Page Services -->
      <div class="cms-tab-pane" id="pane-services" style="display: none;">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <h3 class="card-title">Contenu de la page « Services »</h3>
            <p class="card-subtitle">Présentation globale de l'offre de services</p>
          </div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-services-intro">Texte d'introduction de la page Services</label>
                <textarea class="form-textarea" id="setting-cms-services-intro" rows="4">${escapeHTML(s.cms_services_intro || 'De l\'estimation rigoureuse de votre bien jusqu\'à la finalisation notariée, découvrez une gamme complète de services immobiliers sur mesure.')}</textarea>
              </div>

              <div class="form-col-12 form-group">
                <label class="form-label" for="setting-cms-services-image">Image d'en-tête Services (URL)</label>
                <input type="url" class="form-input" id="setting-cms-services-image" value="${escapeHTML(s.cms_services_image || 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80')}">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bouton Enregistrer bas de page -->
      <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
        <button type="submit" class="btn btn-primary btn-lg">
          ${adminIcon('check', { size: 16 })}
          <span>Enregistrer les paramètres du site</span>
        </button>
      </div>
    </form>
  `;

  // Gestion des onglets
  const tabButtons = document.querySelectorAll('#cms-tabs-nav button');
  tabButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      tabButtons.forEach((b) => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      const target = btn.dataset.tab;
      document.querySelectorAll('.cms-tab-pane').forEach((pane) => {
        pane.style.display = 'none';
      });
      const activePane = document.getElementById(`pane-${target}`);
      if (activePane) activePane.style.display = 'block';
    });
  });

  // Aperçus d'images en direct
  const heroInput = document.getElementById('setting-cms-hero-image');
  heroInput?.addEventListener('input', (e) => {
    const preview = document.getElementById('preview-hero-image');
    if (preview && e.target.value.trim()) preview.src = e.target.value.trim();
  });

  const aboutInput = document.getElementById('setting-cms-about-image');
  aboutInput?.addEventListener('input', (e) => {
    const preview = document.getElementById('preview-about-image');
    if (preview && e.target.value.trim()) preview.src = e.target.value.trim();
  });

  // Soumission du formulaire
  document.getElementById('settings-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const data = {
      agencyName: document.getElementById('setting-agencyName')?.value.trim(),
      tagline: document.getElementById('setting-tagline')?.value.trim(),
      description: document.getElementById('setting-description')?.value.trim(),
      phone: document.getElementById('setting-phone')?.value.trim(),
      whatsapp: document.getElementById('setting-whatsapp')?.value.trim(),
      email: document.getElementById('setting-email')?.value.trim(),
      hours: document.getElementById('setting-hours')?.value.trim(),
      address: document.getElementById('setting-address')?.value.trim(),
      facebook: document.getElementById('setting-facebook')?.value.trim(),
      instagram: document.getElementById('setting-instagram')?.value.trim(),

      // Champs CMS dynamiques
      cms_hero_title: document.getElementById('setting-cms-hero-title')?.value.trim(),
      cms_hero_subtitle: document.getElementById('setting-cms-hero-subtitle')?.value.trim(),
      cms_hero_image: document.getElementById('setting-cms-hero-image')?.value.trim(),
      cms_about_title: document.getElementById('setting-cms-about-title')?.value.trim(),
      cms_about_text: document.getElementById('setting-cms-about-text')?.value.trim(),
      cms_about_mission: document.getElementById('setting-cms-about-mission')?.value.trim(),
      cms_about_vision: document.getElementById('setting-cms-about-vision')?.value.trim(),
      cms_about_image: document.getElementById('setting-cms-about-image')?.value.trim(),
      cms_services_intro: document.getElementById('setting-cms-services-intro')?.value.trim(),
      cms_services_image: document.getElementById('setting-cms-services-image')?.value.trim()
    };

    saveSettings(data);
    showToast('Paramètres et contenus mis à jour avec succès');
  });
}

document.addEventListener('DOMContentLoaded', init);
