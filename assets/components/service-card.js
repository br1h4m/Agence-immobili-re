// assets/components/service-card.js
import { url, icon, escapeHTML, resizeImage } from '/client/includes/helpers.js';

export function ServiceCard(service) {
  return `
    <article class="service-card">
      <div class="service-card-media">
        <img src="${resizeImage(service.image, 800)}" alt="" loading="lazy">
      </div>
      <div class="service-card-body">
        <span class="service-card-icon">${icon(service.icon || 'home', { size: 22 })}</span>
        <h3 class="service-card-title">${escapeHTML(service.title)}</h3>
        <p class="service-card-text">${escapeHTML(service.description)}</p>
        <a href="${url(service.link)}" class="service-card-link">
          <span>En savoir plus</span>
          ${icon('arrow-right', { size: 14 })}
        </a>
      </div>
    </article>
  `;
}