// assets/components/property-features.js
import { featureIcon, escapeHTML } from '../../client/includes/helpers.js';

export function PropertyFeatures(features = []) {
  if (!features.length) return '';

  return `
    <section class="property-features">
      <h2 class="section-title">Caractéristiques</h2>
      <ul class="features-list">
        ${features.map((name) => `
          <li class="feature-item">
            <span class="feature-icon">${featureIcon(name, { size: 22 })}</span>
            <span class="feature-label">${escapeHTML(name)}</span>
          </li>`).join('')}
      </ul>
    </section>
  `;
}