// admin/bien-edit.js
import {
  initAdminLayout, adminIcon, showToast, escapeHTML
} from '/admin/assets/js/admin.js';
import { getPropertyById, saveProperty } from '/admin/data/admin-store.js';

const SAMPLE_IMAGES = [
  'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80'
];

const ALL_FEATURES = [
  'Jardin', 'Piscine', 'Terrasse', 'Garage', 'Climatisation', 'Chauffage central',
  'Ascenseur', 'Vue mer', 'Cuisine équipée', 'Fibre optique', 'Gardiennage',
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
      showToast('Bien introuvable.', 'error');
      setTimeout(() => window.location.replace('/admin/biens'), 1000);
      return;
    }
  }

  const pageTitle = isEdit ? `Modifier : ${property.title}` : 'Ajouter un nouveau bien';

  const ready = await initAdminLayout({
    activePage: 'biens',
    title: pageTitle,
    breadcrumbs: [
      { label: 'Biens', href: '/admin/biens' },
      { label: isEdit ? 'Modifier' : 'Nouveau' }
    ]
  });

  if (!ready) return;

  const root = document.getElementById('bien-edit-content');
  if (!root) return;

  currentImages = property?.images ? [...property.images] : [SAMPLE_IMAGES[0]];

  root.innerHTML = `
    <form id="property-form">
      <div class="card" style="margin-bottom: 28px;">
        <div class="card-header">
          <div>
            <h2 class="card-title">${isEdit ? 'Modification de la fiche' : 'Création de la fiche'}</h2>
            <p class="card-subtitle">Renseignez les détails du bien pour l'afficher sur le catalogue et le site public.</p>
          </div>
          <div style="display: flex; gap: 10px;">
            <a href="/admin/biens" class="btn btn-outline btn-sm">Annuler</a>
            <button type="submit" class="btn btn-primary btn-sm" id="btn-save-top">
              ${adminIcon('check', { size: 16 })}
              <span>Enregistrer le bien</span>
            </button>
          </div>
        </div>

        <div class="card-body">
          <!-- Section 1 : Informations Générales -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('building', { size: 18 })}
              <span>Informations principales</span>
            </h3>
            
            <div class="form-grid">
              <div class="form-col-8 form-group">
                <label class="form-label" for="prop-title">Titre de l'annonce <span class="required">*</span></label>
                <input type="text" id="prop-title" name="title" class="form-input" required placeholder="ex. Villa contemporaine avec vue dégagée" value="${escapeHTML(property?.title || '')}">
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-ref">Référence de l'agence</label>
                <input type="text" id="prop-ref" name="reference" class="form-input" placeholder="ex. IML-001 (auto si vide)" value="${escapeHTML(property?.reference || '')}">
                <span class="form-hint">Laisser vide pour générer automatiquement</span>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-type">Type de bien <span class="required">*</span></label>
                <select id="prop-type" name="type" class="form-select" required>
                  <option value="Villa" ${property?.type === 'Villa' ? 'selected' : ''}>Villa</option>
                  <option value="Appartement" ${property?.type === 'Appartement' ? 'selected' : ''}>Appartement</option>
                  <option value="Duplex" ${property?.type === 'Duplex' ? 'selected' : ''}>Duplex</option>
                  <option value="Terrain" ${property?.type === 'Terrain' ? 'selected' : ''}>Terrain</option>
                  <option value="Bureau" ${property?.type === 'Bureau' ? 'selected' : ''}>Bureau</option>
                  <option value="Local commercial" ${property?.type === 'Local commercial' ? 'selected' : ''}>Local commercial</option>
                  <option value="Immeuble" ${property?.type === 'Immeuble' ? 'selected' : ''}>Immeuble</option>
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-transaction">Transaction <span class="required">*</span></label>
                <select id="prop-transaction" name="transaction" class="form-select" required>
                  <option value="Acheter" ${property?.transaction === 'Acheter' ? 'selected' : ''}>Vente (Acheter)</option>
                  <option value="Louer" ${property?.transaction === 'Louer' ? 'selected' : ''}>Location (Louer)</option>
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-status">Statut de disponibilité</label>
                <select id="prop-status" name="status" class="form-select">
                  <option value="Disponible" ${property?.status === 'Disponible' ? 'selected' : ''}>Disponible</option>
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

          <!-- Section 3 : Localisation -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('phone', { size: 18 })}
              <span>Localisation</span>
            </h3>

            <div class="form-grid">
              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-wilaya">Wilaya <span class="required">*</span></label>
                <input type="text" id="prop-wilaya" name="wilaya" class="form-input" required placeholder="ex. Béjaïa" value="${escapeHTML(property?.location?.wilaya || '')}">
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-city">Commune / Ville <span class="required">*</span></label>
                <input type="text" id="prop-city" name="city" class="form-input" required placeholder="ex. Béjaïa" value="${escapeHTML(property?.location?.city || '')}">
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-district">Quartier / Adresse</label>
                <input type="text" id="prop-district" name="district" class="form-input" placeholder="ex. Tichy, Côte Ouest..." value="${escapeHTML(property?.location?.district || '')}">
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
                <input type="number" id="prop-floor" name="floor" class="form-input" placeholder="ex. 0 (RDC), 2..." value="${property?.floor ?? ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-floors">Nombre total d'étages</label>
                <input type="number" id="prop-floors" name="floors" class="form-input" min="1" placeholder="ex. 2" value="${property?.floors || ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="form-label" for="prop-year">Année de construction</label>
                <input type="number" id="prop-year" name="year" class="form-input" min="1900" max="2035" placeholder="ex. 2021" value="${property?.year || ''}">
              </div>

              <div class="form-col-3 form-group">
                <label class="checkbox-label" style="margin-top: 30px;">
                  <input type="checkbox" id="prop-garage" name="garage" ${property?.garage ? 'checked' : ''}>
                  <span>Possède un garage</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Section 5 : Équipements & Prestations -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('check', { size: 18 })}
              <span>Équipements & Prestations</span>
            </h3>

            <div class="form-checkbox-group">
              ${ALL_FEATURES.map((feat) => {
                const checked = Array.isArray(property?.features) && property.features.includes(feat);
                return `
                  <label class="checkbox-label">
                    <input type="checkbox" name="features" value="${escapeHTML(feat)}" ${checked ? 'checked' : ''}>
                    <span>${escapeHTML(feat)}</span>
                  </label>
                `;
              }).join('')}
            </div>
          </div>

          <!-- Section 6 : Description -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('edit', { size: 18 })}
              <span>Description détaillée</span>
            </h3>

            <div class="form-group">
              <label class="form-label" for="prop-desc">Texte descriptif du bien <span class="required">*</span></label>
              <textarea id="prop-desc" name="description" class="form-textarea" required rows="6" placeholder="Décrivez les atouts majeurs du bien, l'agencement des pièces, les finitions, l'environnement et le quartier...">${escapeHTML(property?.description || '')}</textarea>
            </div>
          </div>

          <!-- Section 7 : Galerie Photos -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('eye', { size: 18 })}
              <span>Galerie photos</span>
            </h3>

            <p style="font-size: 0.82rem; color: var(--admin-text-muted); margin: 0 0 12px;">
              Ajoutez des URLs d'images hébergées (ex. Unsplash, Cloudinary, ou vos liens directs). La première photo servira de couverture principale.
            </p>

            <div class="image-add-box">
              <input type="url" id="new-image-url" class="form-input" placeholder="https://images.unsplash.com/...">
              <button type="button" class="btn btn-outline" id="btn-add-image">
                ${adminIcon('plus', { size: 15 })}
                <span>Ajouter</span>
              </button>
              <button type="button" class="btn btn-outline" id="btn-sample-image" title="Ajouter une photo exemple moderne">
                Photo d'exemple
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

  renderImagesList();
  bindFormEvents(property, isEdit);
}

function renderImagesList() {
  const container = document.getElementById('images-container');
  if (!container) return;

  if (currentImages.length === 0) {
    container.innerHTML = `<p style="font-size: 0.82rem; color: var(--admin-text-subtle); grid-column: span 12;">Aucune image ajoutée pour le moment.</p>`;
    return;
  }

  container.innerHTML = currentImages.map((imgUrl, idx) => `
    <div class="image-preview-card" title="Image ${idx + 1}">
      <img src="${escapeHTML(imgUrl)}" alt="Photo ${idx + 1}" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=400&q=80'">
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

function bindFormEvents(existingProperty, isEdit) {
  const form = document.getElementById('property-form');
  const newImgInput = document.getElementById('new-image-url');
  const addImgBtn = document.getElementById('btn-add-image');
  const sampleImgBtn = document.getElementById('btn-sample-image');

  // Ajout d'image manuelle
  addImgBtn?.addEventListener('click', () => {
    const val = newImgInput?.value.trim();
    if (val) {
      currentImages.push(val);
      if (newImgInput) newImgInput.value = '';
      renderImagesList();
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
  form?.addEventListener('submit', (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    const title = fd.get('title')?.toString().trim();
    const reference = fd.get('reference')?.toString().trim() || null;
    const type = fd.get('type')?.toString();
    const transaction = fd.get('transaction')?.toString();
    const status = fd.get('status')?.toString();
    const isFeatured = fd.get('isFeatured') !== null;
    const isNew = fd.get('isNew') !== null;
    const price = Number(fd.get('price')) || 0;
    const pricePeriod = fd.get('pricePeriod')?.toString() || null;

    const wilaya = fd.get('wilaya')?.toString().trim() || '';
    const city = fd.get('city')?.toString().trim() || '';
    const district = fd.get('district')?.toString().trim() || '';

    const surface = Number(fd.get('surface')) || 0;
    const landSurface = fd.get('landSurface') ? Number(fd.get('landSurface')) : null;
    const bedrooms = fd.get('bedrooms') !== '' ? Number(fd.get('bedrooms')) : null;
    const bathrooms = fd.get('bathrooms') !== '' ? Number(fd.get('bathrooms')) : null;
    const floor = fd.get('floor') !== '' ? Number(fd.get('floor')) : null;
    const floors = fd.get('floors') !== '' ? Number(fd.get('floors')) : null;
    const year = fd.get('year') !== '' ? Number(fd.get('year')) : null;
    const garage = fd.get('garage') !== null;

    const features = fd.getAll('features').map((f) => f.toString());
    if (garage && !features.includes('Garage')) {
      features.push('Garage');
    }

    const description = fd.get('description')?.toString().trim() || '';

    if (currentImages.length === 0) {
      currentImages.push(SAMPLE_IMAGES[0]);
    }

    const payload = {
      ...(existingProperty || {}),
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
    if (existingProperty?.id) payload.id = existingProperty.id;

    try {
      saveProperty(payload);
      showToast(isEdit ? 'Bien mis à jour avec succès !' : 'Nouveau bien créé avec succès !');
      setTimeout(() => {
        window.location.replace('/admin/biens');
      }, 700);
    } catch (err) {
      showToast('Une erreur est survenue lors de l\'enregistrement.', 'error');
    }
  });
}

init();
