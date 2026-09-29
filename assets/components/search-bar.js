// assets/components/search-bar.js
import { CONFIG } from '/client/data/config.js';
import { url, icon } from '/client/includes/helpers.js';

const TYPES = ['Appartement', 'Villa', 'Maison', 'Terrain', 'Local', 'Bureau'];

export function SearchBar({ compact = false } = {}) {
  const typeOptions = TYPES.map((t) => `<option value="${t}">${t}</option>`).join('');

  return `
    <form class="search-bar${compact ? ' search-bar--compact' : ''}" data-search-bar
      action="${url('/biens')}" method="get" role="search" aria-label="Rechercher un bien">
      <div class="search-field">
        <label for="sb-transaction">Transaction</label>
        <select id="sb-transaction" name="transaction">
          <option value="Acheter">Acheter</option>
          <option value="Louer">Louer</option>
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
        <input id="sb-location" name="q" type="text" placeholder="Ville, quartier..." autocomplete="address-level2">
      </div>

      <div class="search-field">
        <label for="sb-price">Budget</label>
        <select id="sb-price" name="maxPrice">
          <option value="">Indifférent</option>
          <option value="5000000">Jusqu'à 5 000 000 DA</option>
          <option value="10000000">Jusqu'à 10 000 000 DA</option>
          <option value="20000000">Jusqu'à 20 000 000 DA</option>
          <option value="40000000">Jusqu'à 40 000 000 DA</option>
          <option value="80000000">Jusqu'à 80 000 000 DA</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary search-submit">
        ${icon('search', { size: 18 })}<span>Rechercher</span>
      </button>
    </form>
  `;
}