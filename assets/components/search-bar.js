// assets/components/search-bar.js
import { CONFIG } from '/client/data/config.js';
import { url, icon, escapeHTML } from '/client/includes/helpers.js';

const TYPES = ['Appartement', 'Villa', 'Maison', 'Terrain', 'Local', 'Bureau'];

export function SearchBar({ compact = false, values = {} } = {}) {
  const transaction = values.transaction || 'Acheter';
  const currentType = values.type || '';
  const q = values.q || '';
  const maxPrice = values.maxPrice != null ? String(values.maxPrice) : '';

  const typeOptions = TYPES.map((t) =>
    `<option value="${t}" ${t === currentType ? 'selected' : ''}>${t}</option>`
  ).join('');

  return `
    <form class="search-bar${compact ? ' search-bar--compact' : ''}" data-search-bar
      action="${url('/biens')}" method="get" role="search" aria-label="Rechercher un bien">
      <div class="search-field">
        <label for="sb-transaction">Transaction</label>
        <select id="sb-transaction" name="transaction">
          <option value="Acheter" ${transaction === 'Acheter' ? 'selected' : ''}>Acheter</option>
          <option value="Louer" ${transaction === 'Louer' ? 'selected' : ''}>Louer</option>
        </select>
      </div>

      <div class="search-field">
        <label for="sb-type">Type de bien</label>
        <select id="sb-type" name="type">
          <option value="">Tous les types</option>
          ${typeOptions}
        </select>
      </div>

      <div class="search-field">
        <label for="sb-location">Localisation</label>
        <input id="sb-location" name="q" type="text" placeholder="Ville, quartier..." autocomplete="address-level2" value="${escapeHTML(q)}">
      </div>

      <div class="search-field">
        <label for="sb-price">Budget</label>
        <select id="sb-price" name="maxPrice">
          <option value="" ${maxPrice === '' ? 'selected' : ''}>Indifférent</option>
          <option value="5000000" ${maxPrice === '5000000' ? 'selected' : ''}>Jusqu'à 5 000 000 DA</option>
          <option value="10000000" ${maxPrice === '10000000' ? 'selected' : ''}>Jusqu'à 10 000 000 DA</option>
          <option value="20000000" ${maxPrice === '20000000' ? 'selected' : ''}>Jusqu'à 20 000 000 DA</option>
          <option value="40000000" ${maxPrice === '40000000' ? 'selected' : ''}>Jusqu'à 40 000 000 DA</option>
          <option value="80000000" ${maxPrice === '80000000' ? 'selected' : ''}>Jusqu'à 80 000 000 DA</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary search-submit">
        ${icon('search', { size: 18 })}<span>Rechercher</span>
      </button>
    </form>
  `;
}