// admin/bien-edit.js
import {
  initAdminLayout, adminIcon, showToast, escapeHTML
} from '/admin/assets/js/admin.js';
import { getPropertyById, saveProperty, getProperties } from '/admin/data/admin-store.js';

// Dictionnaire algérien complet Wilayas -> Communes & Quartiers majeurs
export const ALGERIA_LOCATIONS = {
  "Béjaïa": [
    "Béjaïa (Centre)", "Tichy", "Aokas", "Souk El Ténine", "Akbou", "El Kseur",
    "Amizour", "Sidi Aïch", "Tazmalt", "Kherrata", "Adekar", "Ouzellaguen",
    "Darguina", "Barbacha", "Chemini", "Ighram", "Beni Maouche", "Tifra"
  ],
  "Alger": [
    "Alger Centre", "Hydra", "El Biar", "Ben Aknoun", "Chéraga", "Dely Ibrahim",
    "Zéralda", "Staoueli", "Kouba", "Bir Mourad Raïs", "Hussein Dey", "Bab El Oued",
    "Bouzaréah", "Bab Ezzouar", "Bordj El Kiffan", "Rouïba", "Dar El Beïda",
    "Saoula", "Draria", "Birtouta", "Ain Benian", "Bologhine"
  ],
  "Oran": [
    "Oran (Centre)", "Bir El Djir", "Es Senia", "Ain El Turk", "Arzew",
    "Mers El Kebir", "Bethioua", "Canastel", "Akid Lotfi", "Gdyel", "El Kerma"
  ],
  "Constantine": [
    "Constantine (Centre)", "Ali Mendjeli", "El Khroub", "Ain Smara",
    "Hamma Bouziane", "Zighoud Youcef", "Didouche Mourad"
  ],
  "Sétif": [
    "Sétif (Centre)", "El Eulma", "Ain Arnat", "Ain Oulmene", "Bouandas", "Beni Aziz"
  ],
  "Blida": [
    "Blida (Centre)", "Boufarik", "Ouled Yaich", "Mouzaia", "Larbaa", "Bougara", "El Affroun"
  ],
  "Tizi Ouzou": [
    "Tizi Ouzou (Centre)", "Azazga", "Tigzirt", "Draa Ben Khedda", "Boghni",
    "Larbaa Nath Irathen", "Azeffoun", "Ouadhia"
  ],
  "Boumerdès": [
    "Boumerdès (Centre)", "Zemmouri", "Dellys", "Bordj Menaiel", "Corso",
    "Thénia", "Khemis El Khechna", "Boudouaou"
  ],
  "Tipaza": [
    "Tipaza (Centre)", "Cherchell", "Kolea", "Bou Ismail", "Fouka",
    "Hadjout", "Douaouda", "Bousmail"
  ],
  "Annaba": [
    "Annaba (Centre)", "El Bouni", "Sidi Amar", "Berrahal", "El Hadjar", "Seraïdi"
  ],
  "Tlemcen": [
    "Tlemcen (Centre)", "Mansourah", "Chetouane", "Maghnia", "Remchi", "Ghazaouet"
  ],
  "Mostaganem": [
    "Mostaganem (Centre)", "Ain Tedeles", "Hassi Mameche", "Sidi Lakhdar", "Mazagran"
  ],
  "Jijel": [
    "Jijel (Centre)", "Taher", "El Milia", "Ziama Mansouriah", "Texenna"
  ]
};

// Galerie d'images sélectionnées par thématiques professionnelles
const CURATED_GALLERY = [
  {
    category: 'Villas & Extérieurs',
    title: 'Villa contemporaine avec piscine',
    url: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Villas & Extérieurs',
    title: 'Façade d\'architecte moderne',
    url: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Villas & Extérieurs',
    title: 'Villa d\'exception avec jardin paysager',
    url: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Villas & Extérieurs',
    title: 'Propriété de luxe avec terrasse panoramique',
    url: 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Appartements & Salons',
    title: 'Grand séjour lumineux et ouvert',
    url: 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Appartements & Salons',
    title: 'Salon moderne haut standing',
    url: 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Appartements & Salons',
    title: 'Appartement contemporain avec baie vitrée',
    url: 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Cuisines & Suites',
    title: 'Cuisine équipée moderne îlot central',
    url: 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Cuisines & Suites',
    title: 'Suite parentale spacieuse',
    url: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Cuisines & Suites',
    title: 'Salle de bain design marbre',
    url: 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Bureaux & Commerces',
    title: 'Immeuble de bureaux contemporain',
    url: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80'
  },
  {
    category: 'Bureaux & Commerces',
    title: 'Plateau bureau moderne et lumineux',
    url: 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'
  }
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

  currentImages = property?.images ? [...property.images] : [CURATED_GALLERY[0].url];

  const currentWilaya = property?.location?.wilaya || 'Béjaïa';
  const currentCity = property?.location?.city || 'Béjaïa';

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
                <input type="text" id="prop-title" name="title" class="form-input" required placeholder="ex. Villa contemporaine avec vue mer à Tichy" value="${escapeHTML(property?.title || '')}">
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

          <!-- Section 3 : Localisation dynamique liée Wilaya -> Communes -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('phone', { size: 18 })}
              <span>Localisation (Wilaya & Commune liées)</span>
            </h3>

            <div class="form-grid">
              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-wilaya-select">Wilaya <span class="required">*</span></label>
                <select id="prop-wilaya-select" name="wilaya" class="form-select" required>
                  ${Object.keys(ALGERIA_LOCATIONS).map((w) => `
                    <option value="${escapeHTML(w)}" ${w.toLowerCase() === currentWilaya.toLowerCase() ? 'selected' : ''}>${escapeHTML(w)}</option>
                  `).join('')}
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-city-select">Commune / Ville <span class="required">*</span></label>
                <select id="prop-city-select" name="city" class="form-select" required>
                  <!-- Rempli dynamiquement en JS selon la wilaya -->
                </select>
              </div>

              <div class="form-col-4 form-group">
                <label class="form-label" for="prop-district">Quartier / Adresse</label>
                <input type="text" id="prop-district" name="district" class="form-input" placeholder="ex. Boulevard de la Soummam, Côte Ouest..." value="${escapeHTML(property?.location?.district || '')}">
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

          <!-- Section 7 : Galerie Photos & Sélection depuis la Galerie de l'agence -->
          <div class="form-section">
            <h3 class="form-section-title">
              ${adminIcon('eye', { size: 18 })}
              <span>Galerie photos</span>
            </h3>

            <p style="font-size: 0.82rem; color: var(--admin-text-muted); margin: 0 0 12px;">
              Choisissez des photos depuis notre galerie officielle, téléchargez une image depuis votre appareil ou renseignez un lien URL direct.
            </p>

            <div class="image-add-box" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
              <input type="url" id="new-image-url" class="form-input" style="flex: 1; min-width: 250px;" placeholder="Coller une URL d'image (https://...)">
              <button type="button" class="btn btn-outline" id="btn-add-image">
                ${adminIcon('plus', { size: 15 })}
                <span>Ajouter l'URL</span>
              </button>
              
              <button type="button" class="btn btn-primary" id="btn-open-gallery-picker" style="background: var(--admin-gold); color: #fff; border: none;">
                ${adminIcon('eye', { size: 15 })}
                <span>Choisir depuis notre galerie</span>
              </button>

              <button type="button" class="btn btn-outline" id="btn-upload-file">
                ${adminIcon('upload', { size: 15 })}
                <span>Téléverser un fichier</span>
              </button>
              <input type="file" id="local-file-input" accept="image/*" multiple style="display: none;">
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

    <!-- Modal Sélecteur de Galerie Officielle Immolode -->
    <div id="gallery-picker-modal" class="admin-modal" hidden style="position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px;">
      <div class="admin-modal-card" style="background: var(--admin-surface); border-radius: var(--radius-sm); max-width: 900px; width: 100%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); border: 1px solid var(--admin-border);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600;">Galerie d'images Immolode</h3>
            <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--admin-text-muted);">Cliquez sur les photos pour les ajouter à votre bien</p>
          </div>
          <button type="button" class="btn-icon" id="btn-close-gallery-modal">${adminIcon('close', { size: 18 })}</button>
        </div>

        <!-- Filtres par catégorie -->
        <div style="padding: 10px 20px; background: var(--admin-surface-alt); border-bottom: 1px solid var(--admin-border); display: flex; gap: 8px; flex-wrap: wrap;" id="gallery-cat-pills">
          <button type="button" class="btn btn-outline btn-sm is-active" data-cat="all">Toutes</button>
          <button type="button" class="btn btn-outline btn-sm" data-cat="Villas & Extérieurs">Villas & Extérieurs</button>
          <button type="button" class="btn btn-outline btn-sm" data-cat="Appartements & Salons">Appartements & Salons</button>
          <button type="button" class="btn btn-outline btn-sm" data-cat="Cuisines & Suites">Cuisines & Suites</button>
          <button type="button" class="btn btn-outline btn-sm" data-cat="Bureaux & Commerces">Bureaux & Commerces</button>
        </div>

        <!-- Grille des photos de la galerie -->
        <div style="padding: 20px; overflow-y: auto; flex: 1;" id="gallery-grid-items">
          <!-- Injecté dynamiquement -->
        </div>

        <div style="padding: 14px 20px; border-top: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; background: var(--admin-surface-alt);">
          <span id="gallery-selected-count" style="font-size: 0.85rem; color: var(--admin-text-muted);">0 photo sélectionnée</span>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline btn-sm" id="btn-cancel-gallery-modal">Annuler</button>
            <button type="button" class="btn btn-primary btn-sm" id="btn-insert-gallery-photos">Insérer dans le bien</button>
          </div>
        </div>
      </div>
    </div>
  `;

  setupWilayaCommuneLinkage(currentWilaya, currentCity);
  renderImagesList();
  bindFormEvents(property, isEdit);
  setupGalleryModal();
}

function setupWilayaCommuneLinkage(defaultWilaya, defaultCity) {
  const wilayaSelect = document.getElementById('prop-wilaya-select');
  const citySelect = document.getElementById('prop-city-select');
  if (!wilayaSelect || !citySelect) return;

  function updateCities(wilayaName, selectedCity = '') {
    const cities = ALGERIA_LOCATIONS[wilayaName] || [wilayaName];
    citySelect.innerHTML = cities.map((c) => `
      <option value="${escapeHTML(c)}" ${c.toLowerCase() === selectedCity.toLowerCase() ? 'selected' : ''}>${escapeHTML(c)}</option>
    `).join('');
  }

  // Initialisation
  updateCities(wilayaSelect.value || defaultWilaya, defaultCity);

  // Écouteur de changement de Wilaya
  wilayaSelect.addEventListener('change', (e) => {
    updateCities(e.target.value);
  });
}

function renderImagesList() {
  const container = document.getElementById('images-container');
  if (!container) return;

  if (currentImages.length === 0) {
    container.innerHTML = `<p style="font-size: 0.82rem; color: var(--admin-text-subtle); grid-column: span 12;">Aucune image ajoutée pour le moment.</p>`;
    return;
  }

  container.innerHTML = currentImages.map((imgUrl, idx) => `
    <div class="image-preview-card" style="position: relative; border-radius: var(--radius-xs); overflow: hidden; border: 1px solid var(--admin-border);" title="Photo ${idx + 1} ${idx === 0 ? '(Couverture principale)' : ''}">
      <img src="${escapeHTML(imgUrl)}" alt="Photo ${idx + 1}" style="width: 100%; height: 110px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=400&q=80'">
      ${idx === 0 ? `<span style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 2px;">Couverture</span>` : ''}
      <button type="button" class="image-remove-btn btn-remove-image" data-index="${idx}" title="Supprimer cette photo" style="position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fff; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
        ${adminIcon('close', { size: 12 })}
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

let gallerySelectedUrls = new Set();

function setupGalleryModal() {
  const modal = document.getElementById('gallery-picker-modal');
  const openBtn = document.getElementById('btn-open-gallery-picker');
  const closeBtn = document.getElementById('btn-close-gallery-modal');
  const cancelBtn = document.getElementById('btn-cancel-gallery-modal');
  const insertBtn = document.getElementById('btn-insert-gallery-photos');
  const countSpan = document.getElementById('gallery-selected-count');
  const gridContainer = document.getElementById('gallery-grid-items');

  if (!modal || !openBtn) return;

  function renderGallery(cat = 'all') {
    if (!gridContainer) return;
    const filtered = cat === 'all'
      ? CURATED_GALLERY
      : CURATED_GALLERY.filter((item) => item.category === cat);

    gridContainer.innerHTML = `
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px;">
        ${filtered.map((item) => {
          const isSel = gallerySelectedUrls.has(item.url);
          return `
            <div class="gallery-picker-item" data-url="${escapeHTML(item.url)}" style="border: 2px solid ${isSel ? 'var(--admin-gold)' : 'var(--admin-border)'}; border-radius: var(--radius-xs); overflow: hidden; cursor: pointer; position: relative; background: var(--admin-surface-alt); transition: all 0.2s ease;">
              <img src="${escapeHTML(item.url)}" alt="${escapeHTML(item.title)}" style="width: 100%; height: 120px; object-fit: cover; display: block;">
              <div style="padding: 6px 8px; font-size: 0.75rem; font-weight: 500; color: var(--admin-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ${escapeHTML(item.title)}
              </div>
              ${isSel ? `
                <div style="position: absolute; top: 6px; right: 6px; width: 22px; height: 22px; border-radius: 50%; background: var(--admin-gold); color: #fff; display: flex; align-items: center; justify-content: center;">
                  ${adminIcon('check', { size: 14 })}
                </div>
              ` : ''}
            </div>
          `;
        }).join('')}
      </div>
    `;

    gridContainer.querySelectorAll('.gallery-picker-item').forEach((item) => {
      item.addEventListener('click', () => {
        const url = item.dataset.url;
        if (gallerySelectedUrls.has(url)) {
          gallerySelectedUrls.delete(url);
        } else {
          gallerySelectedUrls.add(url);
        }
        if (countSpan) countSpan.textContent = `${gallerySelectedUrls.size} photo${gallerySelectedUrls.size > 1 ? 's' : ''} sélectionnée${gallerySelectedUrls.size > 1 ? 's' : ''}`;
        renderGallery(currentCat);
      });
    });
  }

  let currentCat = 'all';

  document.querySelectorAll('#gallery-cat-pills button').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('#gallery-cat-pills button').forEach((b) => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      currentCat = btn.dataset.cat;
      renderGallery(currentCat);
    });
  });

  const openModal = () => {
    gallerySelectedUrls.clear();
    if (countSpan) countSpan.textContent = '0 photo sélectionnée';
    modal.style.display = 'flex';
    modal.hidden = false;
    renderGallery(currentCat);
  };

  const closeModal = () => {
    modal.style.display = 'none';
    modal.hidden = true;
  };

  openBtn.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);

  insertBtn?.addEventListener('click', () => {
    if (gallerySelectedUrls.size > 0) {
      gallerySelectedUrls.forEach((url) => {
        if (!currentImages.includes(url)) {
          currentImages.push(url);
        }
      });
      renderImagesList();
      showToast(`${gallerySelectedUrls.size} photo(s) ajoutée(s) à la galerie du bien`);
    }
    closeModal();
  });
}

function bindFormEvents(existingProperty, isEdit) {
  const form = document.getElementById('property-form');
  const newImgInput = document.getElementById('new-image-url');
  const addImgBtn = document.getElementById('btn-add-image');
  const uploadBtn = document.getElementById('btn-upload-file');
  const fileInput = document.getElementById('local-file-input');

  // Ajout par URL
  addImgBtn?.addEventListener('click', () => {
    const val = newImgInput?.value.trim();
    if (val) {
      currentImages.push(val);
      if (newImgInput) newImgInput.value = '';
      renderImagesList();
      showToast('Image ajoutée');
    }
  });

  // Téléversement de fichier local
  uploadBtn?.addEventListener('click', () => {
    fileInput?.click();
  });

  fileInput?.addEventListener('change', (e) => {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;

    files.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (loadEvent) => {
        const base64 = loadEvent.target.result;
        currentImages.push(base64);
        renderImagesList();
      };
      reader.readAsDataURL(file);
    });

    showToast(`${files.length} fichier(s) téléversé(s)`);
    fileInput.value = '';
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

    const wilaya = fd.get('wilaya')?.toString().trim() || 'Béjaïa';
    const city = fd.get('city')?.toString().trim() || 'Béjaïa';
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
      currentImages.push(CURATED_GALLERY[0].url);
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
