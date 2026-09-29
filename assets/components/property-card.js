// assets/components/property-card.js
import { CONFIG } from '../../client/data/config.js';
import {
  propertyUrl, formatPropertyPrice, formatLocation,
  formatSurface, icon, resizeImage, escapeHTML
} from '../../client/includes/helpers.js';

const STATUS_LABELS = {
  'Disponible': null,
  'Réservé': { label: 'Réservé', mod: 'reserved' },
  'Vendu': { label: 'Vendu', mod: 'sold' },
  'Loué': { label: 'Loué', mod: 'rented' }
};

export function PropertyCard(property) {
  const cover = property.images?.[0] || '';
  const transactionLabel = property.transaction === 'Louer' ? 'À louer' : 'À vendre';
  const statusInfo = STATUS_LABELS[property.status] || null;

  const statusBadge = statusInfo
    ? `<span class="badge badge-status badge-status--${statusInfo.mod}">${statusInfo.label}</span>`
    : '';

  const newBadge = property.isNew
    ? `<span class="badge badge-new">Nouveau</span>`
    : '';

  const specs = [
    property.surface ? `${formatSurface(property.surface)}` : null,
    property.bedrooms ? `${property.bedrooms} ch.` : null,
    property.bathrooms ? `${property.bathrooms} sdb` : null
  ].filter(Boolean);

  return `
    <article class="property-card" data-property-id="${property.id}">
      <a href="${propertyUrl(property.id)}" class="property-card-media" aria-label="${escapeHTML(property.title)}">
        <img src="${resizeImage(cover, 800)}" alt="${escapeHTML(property.title)}" loading="lazy" decoding="async">
        <div class="property-card-badges">
          <span class="badge badge-transaction">${transactionLabel}</span>
          ${newBadge}
          ${statusBadge}
        </div>
      </a>

      <div class="property-card-body">
        <h3 class="property-card-title">
          <a href="${propertyUrl(property.id)}">${escapeHTML(property.title)}</a>
        </h3>
        <p class="property-card-location">
          ${icon('pin', { size: 14 })}
          <span>${escapeHTML(formatLocation(property.location))}</span>
        </p>
        <p class="property-card-price">${formatPropertyPrice(property)}</p>
        <ul class="property-card-specs">
          ${specs.map((s) => `<li>${escapeHTML(s)}</li>`).join('')}
        </ul>
      </div>

      <a href="${propertyUrl(property.id)}" class="property-card-link">
        <span>Voir le bien</span>
        ${icon('arrow-right', { size: 14 })}
      </a>
    </article>
  `;
}