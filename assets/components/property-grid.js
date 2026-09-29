// assets/components/property-grid.js
import { PropertyCard } from '/assets/components/property-card.js';

export function PropertyGrid(properties = [], { emptyMessage = 'Aucun bien ne correspond à votre recherche.' } = {}) {
  if (!properties.length) {
    return `
      <div class="property-grid-empty">
        <p>${emptyMessage}</p>
      </div>
    `;
  }

  return `
    <div class="property-grid">
      ${properties.map((p) => PropertyCard(p)).join('')}
    </div>
  `;
}