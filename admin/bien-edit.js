// admin/bien-edit.js
import {
  initAdminLayout, adminIcon, showToast, confirmModal, escapeHTML
} from '/admin/assets/js/admin.js';
import { getPropertyById, getProperties, saveProperty } from '/admin/data/admin-store.js';

const SAMPLE_IMAGES = [
  'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80'
];

const WILAYA_COMMUNES = {
  'Béjaïa': ['Béjaïa', 'Tichy', 'Akbou', 'Amizour', 'Aokas', 'Souk El Ténine', 'Sidi Aïch', 'El Kseur', 'Oued Ghir', 'Kherrata'],
  'Alger': ['Hydra', 'El Biar', 'Ben Aknoun', 'Sidi Yahia', 'Bab Ezzouar', 'Dely Ibrahim', 'Zeralda', 'Staoueli', 'Chéraga', 'Kouba', 'Hussein Dey', 'Bordj El Kiffan', 'Ain Benian'],
  'Oran': ['Oran Centre', 'Canastel', 'Akid Lotfi', 'Bir El Djir', 'Maraval', 'Es Senia', 'Aïn El Turk', 'Arzew'],
  'Blida': ['Blida Centre', 'Boufarik', 'Ouled Yaïch', 'Chréa', 'Mouzaïa', 'Bougara'],
  'Constantine': ['Constantine Centre', 'Ali Mendjeli', 'El Khroub', 'Ain Smara', 'Hamma Bouziane'],
  'Sétif': ['Sétif Centre', 'El Eulma', 'Ain Arnat', 'Ain Oulmene', 'Bouandas'],
  'Tipaza': ['Tipaza Centre', 'Chenoua', 'Cherchell', 'Bousmail', 'Koléa', 'Fouka'],
  'Tizi Ouzou': ['Tizi Ouzou', 'Azazga', 'Tigzirt', 'Draa Ben Khedda', 'Boghni'],
  'Annaba': ['Annaba Centre', 'Seraïdi', 'El Bouni', 'El Hadjar', 'Berrahal']
};

const ALL_FEATURES = [
  'Vue mer', 'Piscine', 'Jardin', 'Terrasse', 'Garage', 'Ascenseur',
  'Climatisation', 'Chauffage central', 'Cuisine équipée', 'Sécurité 24/7',
  'Interphone', 'Balcon', 'Double vitrage', 'Suite parentale'
];

let currentImages = [];

async function init() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  const isEdit = Boolean(id);

  let property = null;
  if (isEdit) {
    property = getPropertyById(id);
    if (!property) {
      showToast('Bien introuvable dans le portefeuille', 'error');
      setTimeout(() => window.location.replace('/admin/biens'), 1500);
      return;
    }
  }

  const pageTitle = isEdit ? `Modifier : ${property.title}` : 'Ajouter un nouveau bien';

  const ready = await initAdminLayout({
    activePage: 'biens',
    title: pageTitle,
    breadcrumbs: [
      { label: 'Biens', href: '/admin/biens' },
      { label: isEdit ? 'Modification' : 'Nouveau bien' }
    ],
    action: {
      label: isEdit ? 'Enregistrer' : 'Publier',
      href: '#',
      icon: 'check',
      id: 'btn-save-property'
    }
  });

  if (!ready) return;

  const root = document.getElementById('bien-edit-content');
  if (!root) return;

  currentImages = property?.images ? [...property.images] : [SAMPLE_IMAGES[0]];

  const currentWilaya = property?.location?.wilaya || property?.wilaya || 'Béjaïa';
  const currentCommune = property?.location?.city || property?.commune || '';

  root.innerHTML = `
    <form id="property-form">
      <div class="card" style="margin-bottom: 32px;">
        <div class="card-header">
          <div>
            <h2 class="card-title">${escapeHTML(pageTitle)}</h2>
            <p class="card-subtitle">Complétez les informations détaillées de la propriété</p>
          </div>
          <div style="display: flex; gap: 8px;">
            <a href="/admin/biens" class="btn btn-outline btn-sm">Annuler</a>
            <button type="submit" class="btn btn-primary btn-sm">
              ${adminIcon('check', { size: 16 })}
              <span>${isEdit ? 'Enregistrer' : 'Publier le bien'}</span>
            </button>
          </div>
        </div>

        <div class="card-body">
          
          <!-- Section 1 : Généralités -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('building', { size: 18 })}
              <span>Informations Générales</span>
            </h3>

            <div class="form-grid">
              <div class="form-col-8 form-group">
                <label class="form-label" for="prop-title">Titre de l'annonce <span class="required">*</span></label>
                <input type="text" id="prop-title" name="title" class="form-input" required placeholder="ex. Superbe Villa d'Architecte avec Piscine" value="${escapeHTML(property?.title || '')}">
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-ref">Référence interne</label>
                <input type="text" id="prop-ref" name="reference" class="form-input" placeholder="ex. VIL-2026-042 (auto si vide)" value="${escapeHTML(property?.reference || '')}">
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-transaction">Type d'opération <span class="required">*</span></label>
                <select id="prop-transaction" name="transaction" class="form-select" required>
                  <option value="Acheter" ${property?.transaction === 'Acheter' ? 'selected' : ''}>Vente (À acheter)</option>
                  <option value="Louer" ${property?.transaction === 'Louer' ? 'selected' : ''}>Location (À louer)</option>
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-type">Type de bien <span class="required">*</span></label>
                <select id="prop-type" name="type" class="form-select" required>
                  <option value="Villa" ${property?.type === 'Villa' ? 'selected' : ''}>Villa</option>
                  <option value="Appartement" ${property?.type === 'Appartement' || !property?.type ? 'selected' : ''}>Appartement</option>
                  <option value="Duplex" ${property?.type === 'Duplex' ? 'selected' : ''}>Duplex / Penthouse</option>
                  <option value="Terrain" ${property?.type === 'Terrain' ? 'selected' : ''}>Terrain constructible</option>
                  <option value="Bureau" ${property?.type === 'Bureau' ? 'selected' : ''}>Bureau / Plateau</option>
                  <option value="Local commercial" ${property?.type === 'Local commercial' ? 'selected' : ''}>Local commercial</option>
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-status">Statut de disponibilité</label>
                <select id="prop-status" name="status" class="form-select">
                  <option value="Disponible" ${property?.status === 'Disponible' || !property?.status ? 'selected' : ''}>Disponible</option>
                  <option value="Réservé" ${property?.status === 'Réservé' ? 'selected' : ''}>Réservé</option>
                  <option value="Vendu" ${property?.status === 'Vendu' ? 'selected' : ''}>Vendu</option>
                  <option value="Loué" ${property?.status === 'Loué' ? 'selected' : ''}>Loué</option>
                </select>
              </div>

              <div class="form-col-6 form-group">
                <label class="checkbox-label" style="margin-top: 10px;">
                  <input type="checkbox" id="prop-featured" name="isFeatured" ${property?.isFeatured ? 'checked' : ''}>
                  <span>Mettre ce bien <strong>À la une</strong> (Coup de cœur sur l'accueil)</span>
                </label>
              </div>

              <div class="form-col-6 form-group">
                <label class="checkbox-label" style="margin-top: 10px;">
                  <input type="checkbox" id="prop-isnew" name="isNew" ${property?.isNew ? 'checked' : ''}>
                  <span>Badge <strong>Nouveauté</strong> sur la fiche</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Section 2 : Prix & Conditions -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('dashboard', { size: 18 })}
              <span>Prix & Conditions</span>
            </h3>

            <div class="form-grid">
              <div class="form-col-6 form-group">
                <label class="form-label" for="prop-price">Prix en Dinars Algériens (DA) <span class="required">*</span></label>
                <input type="number" id="prop-price" name="price" class="form-input" required min="0" step="1000" placeholder="ex. 25000000" value="${property?.price || ''}">
                <span class="form-hint">Indiquez le montant total pour une vente ou mensuel pour une location</span>
              </div>

              <div class="form-col-6 form-group">
                <label class="form-label" for="prop-priceperiod">Période du loyer (si location)</label>
                <select id="prop-priceperiod" name="pricePeriod" class="form-select">
                  <option value="" ${!property?.pricePeriod ? 'selected' : ''}>Prix net / global (Vente)</option>
                  <option value="mois" ${property?.pricePeriod === 'mois' ? 'selected' : ''}>Par mois (Location)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Section 3 : Localisation (Liée Wilaya & Commune) -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('phone', { size: 18 })}
              <span>Localisation</span>
            </h3>

            <div class="form-grid">
              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-wilaya">Wilaya <span class="required">*</span></label>
                <select id="prop-wilaya" name="wilaya" class="form-select" required>
                  ${Object.keys(WILAYA_COMMUNES).map(w => `<option value="${w}" ${currentWilaya === w ? 'selected' : ''}>${w}</option>`).join('')}
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-city">Commune / Ville <span class="required">*</span></label>
                <select id="prop-city" name="city" class="form-select" required>
                  <!-- Alimenté dynamiquement par JS -->
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-district">Quartier / Adresse</label>
                <input type="text" id="prop-district" name="district" class="form-input" placeholder="ex. Tichy, Côte Ouest..." value="${escapeHTML(property?.location?.district || property?.quartier || '')}">
              </div>
            </div>
          </div>

          <!-- Section 4 : Caractéristiques & Mesures -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('building', { size: 18 })}
              <span>Caractéristiques & Mesures</span>
            </h3>

            <div class="form-grid">
              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-surface">Surface habitable (m²) <span class="required">*</span></label>
                <input type="number" id="prop-surface" name="surface" class="form-input" required min="1" placeholder="ex. 180" value="${property?.surface || ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-landsurface">Surface du terrain (m²)</label>
                <input type="number" id="prop-landsurface" name="landSurface" class="form-input" min="0" placeholder="ex. 400 (optionnel)" value="${property?.landSurface || ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-bedrooms">Chambres</label>
                <input type="number" id="prop-bedrooms" name="bedrooms" class="form-input" min="0" placeholder="ex. 4" value="${property?.bedrooms ?? ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-bathrooms">Salles de bain</label>
                <input type="number" id="prop-bathrooms" name="bathrooms" class="form-input" min="0" placeholder="ex. 2" value="${property?.bathrooms ?? ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-floor">Étage</label>
                <input type="number" id="prop-floor" name="floor" class="form-input" placeholder="ex. 3 (0 pour RDC)" value="${property?.floor ?? ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-floors">Nombre total d'étages</label>
                <input type="number" id="prop-floors" name="floors" class="form-input" min="1" placeholder="ex. 5" value="${property?.floors ?? ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-year">Année de construction</label>
                <input type="number" id="prop-year" name="year" class="form-input" min="1900" max="2030" placeholder="ex. 2022" value="${property?.year ?? ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="checkbox-label" style="margin-top: 32px;">
                  <input type="checkbox" id="prop-garage" name="garage" ${property?.garage ? 'checked' : ''}>
                  <span>Garage / Parking inclus</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Section 5 : Équipements & Prestations -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('check', { size: 18 })}
              <span>Équipements & Atouts</span>
            </h3>

            <div class="features-checklist">
              ${ALL_FEATURES.map((feat) => {
                const checked = property?.features?.includes(feat) ? 'checked' : '';
                return `
                  <label class="checkbox-label feature-item-label">
                    <input type="checkbox" name="features" value="${escapeHTML(feat)}" ${checked}>
                    <span>${escapeHTML(feat)}</span>
                  </label>
                `;
              }).join('')}
            </div>
          </div>

          <!-- Section 6 : Description détaillée -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('file-text', { size: 18 })}
              <span>Description commerciale</span>
            </h3>

            <div class="form-group">
              <label class="form-label" for="prop-desc">Texte descriptif du bien <span class="required">*</span></label>
              <textarea id="prop-desc" name="description" class="form-textarea" required rows="6" placeholder="Décrivez les atouts majeurs du bien, l'agencement des pièces, les finitions, l'environnement et le quartier...">${escapeHTML(property?.description || '')}</textarea>
            </div>
          </div>

          <!-- Section 7 : Galerie Photos (Point 6 : Galerie & Téléversement) -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('eye', { size: 18 })}
              <span>Galerie photos</span>
            </h3>

            <p style="font-size: 0.82rem; color: var(--admin-text-muted); margin: 0 0 16px;">
              Ajoutez des photos pour mettre en valeur votre bien. Vous pouvez téléverser des images depuis votre appareil, choisir parmi les photos de votre galerie existante, ou saisir une URL externe.
            </p>

            <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
              <input type="file" id="input-upload-files" accept="image/*" multiple style="display: none;">
              <button type="button" class="btn btn-primary btn-sm" id="btn-upload-photos">
                ${adminIcon('upload', { size: 15 })}
                <span>Téléverser depuis l'ordinateur</span>
              </button>
              <button type="button" class="btn btn-outline btn-sm" id="btn-open-gallery-picker">
                ${adminIcon('eye', { size: 15 })}
                <span>Choisir depuis notre galerie</span>
              </button>
              <button type="button" class="btn btn-outline btn-sm" id="btn-sample-image">
                <span>Photo d'exemple moderne</span>
              </button>
            </div>

            <div class="image-add-box" style="margin-bottom: 16px;">
              <input type="url" id="new-image-url" class="form-input" placeholder="Ou coller une URL d'image directe (https://...)">
              <button type="button" class="btn btn-outline btn-sm" id="btn-add-image">
                ${adminIcon('plus', { size: 14 })}
                <span>Ajouter l'URL</span>
              </button>
            </div>

            <div class="images-gallery-editor" id="images-container">
              <!-- Prévisualisation des images -->
            </div>
          </div>

        </div>

        <div class="card-footer">
          <a href="/admin/biens" class="btn btn-outline">Annuler</a>
          <button type="submit" class="btn btn-primary" id="btn-save-bottom">
            ${adminIcon('check', { size: 16 })}
            <span>${isEdit ? 'Enregistrer les modifications' : 'Créer et publier le bien'}</span>
          </button>
        </div>
      </div>
    </form>
  `;

  // Liaison dynamique Wilaya -> Communes (Point 9)
  const wilayaSelect = document.getElementById('prop-wilaya');
  const citySelect = document.getElementById('prop-city');

  const updateCommunes = (selectedW, defaultCity = '') => {
    const communes = WILAYA_COMMUNES[selectedW] || ['Centre-ville', 'Autre'];
    citySelect.innerHTML = communes.map(c => `<option value="${c}" ${c === defaultCity ? 'selected' : ''}>${c}</option>`).join('');
  };

  updateCommunes(currentWilaya, currentCommune);
  wilayaSelect?.addEventListener('change', (e) => {
    updateCommunes(e.target.value);
  });

  renderImagesList();
  bindFormEvents(property, isEdit);
}

function renderImagesList() {
  const container = document.getElementById('images-container');
  if (!container) return;

  if (currentImages.length === 0) {
    container.innerHTML = `<p style="font-size: 0.82rem; color: var(--admin-text-subtle); grid-column: span 12;">Aucune image ajoutée pour le moment. La première image servira d'illustration principale.</p>`;
    return;
  }

  container.innerHTML = currentImages.map((imgUrl, idx) => `
    <div class="image-preview-card" title="Image ${idx + 1} ${idx === 0 ? '(Couverture principale)' : ''}">
      <img src="${escapeHTML(imgUrl)}" alt="Photo ${idx + 1}" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=400&q=80'">
      ${idx === 0 ? `<span style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #FFF; font-size: 0.65rem; padding: 2px 6px; border-radius: 3px; font-weight: 600;">Couverture</span>` : ''}
      <button type="button" class="image-remove-btn btn-remove-image" data-index="${idx}" title="Supprimer cette photo">
        ${adminIcon('close', { size: 14 })}
      </button>
    </div>
  `).join('');

  container.querySelectorAll('.btn-remove-image').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const index = parseInt(btn.dataset.index, 10);
      currentImages.splice(index, 1);
      renderImagesList();
    });
  });
}

// Modale de choix depuis la galerie (Point 6)
function openGalleryModal() {
  const allProperties = getProperties();
  const existingImages = new Set(SAMPLE_IMAGES);
  allProperties.forEach(p => {
    if (Array.isArray(p.images)) {
      p.images.forEach(img => { if (img) existingImages.add(img); });
    }
  });

  const imagesList = Array.from(existingImages);

  let overlay = document.getElementById('admin-modal-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'admin-modal-overlay';
    overlay.className = 'admin-modal-overlay';
    document.body.appendChild(overlay);
  }

  overlay.innerHTML = `
    <div class="admin-modal-card" style="max-width: 760px; max-height: 85vh; display: flex; flex-direction: column;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 class="admin-modal-title" style="margin: 0;">Choisir depuis notre galerie de photos</h3>
        <button type="button" class="btn-icon" id="modal-close-gallery">${adminIcon('close', { size: 16 })}</button>
      </div>

      <p style="font-size: 0.88rem; color: var(--admin-text-muted); margin-bottom: 16px;">
        Sélectionnez une ou plusieurs photos parmi la bibliothèque d'images de l'agence :
      </p>

      <div style="flex: 1; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; margin-bottom: 16px; padding: 4px;" id="gallery-grid">
        ${imagesList.map((img, i) => `
          <div class="gallery-thumb-item" data-url="${escapeHTML(img)}" style="position: relative; height: 100px; border-radius: var(--radius-xs); overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: all 0.2s;">
            <img src="${escapeHTML(img)}" style="width: 100%; height: 100%; object-fit: cover;">
            <div class="gallery-check" style="position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; border-radius: 50%; background: var(--admin-gold); color: #FFF; display: none; align-items: center; justify-content: center; font-size: 11px;">
              ✓
            </div>
          </div>
        `).join('')}
      </div>

      <div class="admin-modal-actions" style="border-top: 1px solid var(--admin-border); padding-top: 14px;">
        <span id="gallery-selected-count" style="font-size: 0.88rem; color: var(--admin-text-muted); margin-right: auto;">0 image(s) sélectionnée(s)</span>
        <button type="button" class="btn btn-outline" id="modal-cancel-gallery">Annuler</button>
        <button type="button" class="btn btn-primary" id="modal-confirm-gallery">Insérer les images</button>
      </div>
    </div>
  `;

  overlay.classList.add('is-active');

  const close = () => {
    overlay.classList.remove('is-active');
    overlay.innerHTML = '';
  };

  document.getElementById('modal-close-gallery')?.addEventListener('click', close);
  document.getElementById('modal-cancel-gallery')?.addEventListener('click', close);

  const selectedUrls = new Set();
  const countEl = document.getElementById('gallery-selected-count');

  document.querySelectorAll('.gallery-thumb-item').forEach(item => {
    item.addEventListener('click', () => {
      const url = item.dataset.url;
      const check = item.querySelector('.gallery-check');
      if (selectedUrls.has(url)) {
        selectedUrls.delete(url);
        item.style.borderColor = 'transparent';
        if (check) check.style.display = 'none';
      } else {
        selectedUrls.add(url);
        item.style.borderColor = 'var(--admin-gold)';
        if (check) check.style.display = 'flex';
      }
      if (countEl) countEl.textContent = `${selectedUrls.size} image(s) sélectionnée(s)`;
    });
  });

  document.getElementById('modal-confirm-gallery')?.addEventListener('click', () => {
    if (selectedUrls.size > 0) {
      selectedUrls.forEach(u => {
        if (!currentImages.includes(u)) currentImages.push(u);
      });
      renderImagesList();
      showToast(`${selectedUrls.size} photo(s) ajoutée(s) au bien`);
    }
    close();
  });
}

function bindFormEvents(existingProperty, isEdit) {
  const form = document.getElementById('property-form');
  const newImgInput = document.getElementById('new-image-url');
  const addImgBtn = document.getElementById('btn-add-image');
  const sampleImgBtn = document.getElementById('btn-sample-image');
  const uploadPhotosBtn = document.getElementById('btn-upload-photos');
  const uploadFilesInput = document.getElementById('input-upload-files');
  const openGalleryBtn = document.getElementById('btn-open-gallery-picker');

  // Ouvrir la galerie existante
  openGalleryBtn?.addEventListener('click', openGalleryModal);

  // Téléversement direct de photos locales
  uploadPhotosBtn?.addEventListener('click', () => uploadFilesInput?.click());
  uploadFilesInput?.addEventListener('change', async () => {
    const files = uploadFilesInput.files;
    if (!files || files.length === 0) return;

    showToast(`Téléversement de ${files.length} photo(s)...`);

    for (let i = 0; i < files.length; i++) {
      const fd = new FormData();
      fd.append('file', files[i]);
      fd.append('folder', 'properties');
      try {
        const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (res.ok && data.data?.url) {
          currentImages.push(data.data.url);
        }
      } catch (_) {}
    }

    renderImagesList();
    showToast('Photos téléversées avec succès !');
  });

  // Ajout d'image manuelle via URL
  addImgBtn?.addEventListener('click', () => {
    const val = newImgInput?.value.trim();
    if (val) {
      currentImages.push(val);
      if (newImgInput) newImgInput.value = '';
      renderImagesList();
      showToast('Image ajoutée');
    }
  });

  // Ajout de photo d'exemple aléatoire
  sampleImgBtn?.addEventListener('click', () => {
    const randomImg = SAMPLE_IMAGES[Math.floor(Math.random() * SAMPLE_IMAGES.length)];
    currentImages.push(randomImg);
    renderImagesList();
    showToast('Photo d\'exemple ajoutée');
  });

  // Soumission du formulaire
  const handleSave = async (e) => {
    e?.preventDefault();

    const fd = new FormData(form);
    const title = fd.get('title')?.toString().trim() || '';
    const reference = fd.get('reference')?.toString().trim() || '';
    const type = fd.get('type')?.toString() || 'Appartement';
    const transaction = fd.get('transaction')?.toString() || 'Acheter';
    const status = fd.get('status')?.toString() || 'Disponible';
    const isFeatured = fd.get('isFeatured') === 'on';
    const isNew = fd.get('isNew') === 'on';

    const price = parseFloat(fd.get('price')?.toString() || '0');
    const pricePeriod = fd.get('pricePeriod')?.toString() || null;

    const wilaya = fd.get('wilaya')?.toString().trim() || '';
    const city = fd.get('city')?.toString().trim() || '';
    const district = fd.get('district')?.toString().trim() || '';

    const surface = parseFloat(fd.get('surface')?.toString() || '0');
    const landSurfaceVal = fd.get('landSurface')?.toString().trim();
    const landSurface = landSurfaceVal ? parseFloat(landSurfaceVal) : null;

    const bedroomsVal = fd.get('bedrooms')?.toString().trim();
    const bedrooms = bedroomsVal !== '' ? parseInt(bedroomsVal, 10) : null;

    const bathroomsVal = fd.get('bathrooms')?.toString().trim();
    const bathrooms = bathroomsVal !== '' ? parseInt(bathroomsVal, 10) : null;

    const floorVal = fd.get('floor')?.toString().trim();
    const floor = floorVal !== '' ? parseInt(floorVal, 10) : null;

    const floorsVal = fd.get('floors')?.toString().trim();
    const floors = floorsVal !== '' ? parseInt(floorsVal, 10) : null;

    const yearVal = fd.get('year')?.toString().trim();
    const year = yearVal !== '' ? parseInt(yearVal, 10) : null;

    const garage = fd.get('garage') === 'on';
    const features = fd.getAll('features').map(f => f.toString());
    const description = fd.get('description')?.toString().trim() || '';

    if (!title || isNaN(price) || price <= 0 || isNaN(surface) || surface <= 0) {
      showToast('Veuillez renseigner un titre, un prix et une surface valides.', 'error');
      return;
    }

    if (currentImages.length === 0) {
      currentImages.push(SAMPLE_IMAGES[0]);
    }

    const payload = {
      title,
      type,
      transaction,
      status,
      isFeatured,
      isNew,
      price,
      pricePeriod,
      location: { wilaya, city, district },
      surface,
      landSurface,
      bedrooms,
      bathrooms,
      floor,
      floors,
      year,
      garage,
      features,
      description,
      images: currentImages
    };

    if (reference) payload.reference = reference;
    if (isEdit) payload.id = existingProperty.id;

    const saved = saveProperty(payload);
    if (saved) {
      showToast(isEdit ? 'Propriété mise à jour avec succès' : 'Nouveau bien publié');
      setTimeout(() => {
        window.location.replace('/admin/biens');
      }, 500);
    }
  };

  form?.addEventListener('submit', handleSave);
  document.getElementById('btn-save-property')?.addEventListener('click', handleSave);
}

document.addEventListener('DOMContentLoaded', init);
