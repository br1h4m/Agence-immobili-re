// assets/components/property-filters.js
import { icon } from '/client/includes/helpers.js';

const TYPES = ['Appartement', 'Villa', 'Maison', 'Terrain', 'Local', 'Bureau'];
const AMENITIES = ['Garage', 'Jardin', 'Piscine', 'Terrasse', 'Ascenseur', 'Meublé'];

export function PropertyFilters({ wilayas = [], cities = [] } = {}) {
  const typeOptions = TYPES.map((t) => `<option value="${t}">${t}</option>`).join('');
  const wilayaOptions = wilayas.map((w) => `<option value="${w}">${w}</option>`).join('');
  const cityOptions = cities.map((c) => `<option value="${c}">${c}</option>`).join('');
  const amenityInputs = AMENITIES.map((a) => `
    <label class="filter-check">
      <input type="checkbox" name="amenities" value="${a}">
      <span>${a}</span>
    </label>`).join('');

  return `
    <aside class="filters" data-filters aria-label="Filtres">
      <div class="filters-head">
        <h2 class="filters-title">Filtres</h2>
        <button type="button" class="filters-close" data-filters-close aria-label="Fermer les filtres">
          ${icon('close', { size: 20 })}
        </button>
      </div>

      <form class="filters-form" data-filters-form>
        <fieldset class="filter-group">
          <legend>Transaction</legend>
          <div class="filter-toggle" role="radiogroup" aria-label="Type de transaction">
            <label><input type="radio" name="transaction" value="Acheter" checked><span>Acheter</span></label>
            <label><input type="radio" name="transaction" value="Louer"><span>Louer</span></label>
          </div>
        </fieldset>

        <fieldset class="filter-group">
          <legend>Type de bien</legend>
          <select name="type">
            <option value="">Tous les types</option>
            ${typeOptions}
          </select>
        </fieldset>

        <fieldset class="filter-group">
          <legend>Localisation</legend>
          <label class="filter-field">
            <span>Wilaya</span>
            <select name="wilaya">
              <option value="">Toutes les wilayas</option>
              ${wilayaOptions}
            </select>
          </label>
          <label class="filter-field">
            <span>Ville / Quartier</span>
            <select name="city">
              <option value="">Toutes les villes</option>
              ${cityOptions}
            </select>
          </label>
        </fieldset>

        <fieldset class="filter-group">
          <legend>Prix (DA)</legend>
          <div class="filter-range">
            <input type="number" name="minPrice" min="0" placeholder="Min">
            <input type="number" name="maxPrice" min="0" placeholder="Max">
          </div>
        </fieldset>

        <fieldset class="filter-group">
          <legend>Surface (m²)</legend>
          <div class="filter-range">
            <input type="number" name="minSurface" min="0" placeholder="Min">
            <input type="number" name="maxSurface" min="0" placeholder="Max">
          </div>
        </fieldset>

        <fieldset class="filter-group">
          <legend>Chambres / Salles de bain</legend>
          <div class="filter-range">
            <select name="bedrooms">
              <option value="">Chambres</option>
              <option value="1">1+</option><option value="2">2+</option>
              <option value="3">3+</option><option value="4">4+</option>
              <option value="5">5+</option>
            </select>
            <select name="bathrooms">
              <option value="">Salles de bain</option>
              <option value="1">1+</option><option value="2">2+</option>
              <option value="3">3+</option>
            </select>
          </div>
        </fieldset>

        <fieldset class="filter-group">
          <legend>Équipements</legend>
          <div class="filter-checks">${amenityInputs}</div>
        </fieldset>

        <div class="filters-actions">
          <button type="button" class="btn btn-ghost" data-filters-reset>Réinitialiser</button>
          <button type="submit" class="btn btn-primary">Appliquer</button>
        </div>
      </form>
    </aside>
  `;
}