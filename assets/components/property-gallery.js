// assets/components/property-gallery.js
import { icon, resizeImage, escapeHTML } from '../../client/includes/helpers.js';

export function PropertyGallery(images = [], { title = '' } = {}) {
  if (!images.length) {
    return `<div class="gallery gallery--empty"></div>`;
  }

  const slides = images
    .map((src, i) => `
      <button type="button" class="gallery-thumb${i === 0 ? ' is-active' : ''}"
        data-gallery-thumb data-index="${i}" aria-label="Photo ${i + 1}">
        <img src="${resizeImage(src, 240)}" alt="" loading="lazy">
      </button>`)
    .join('');

  return `
    <div class="gallery" data-gallery>
      <div class="gallery-stage">
        <img class="gallery-main" data-gallery-main
          src="${resizeImage(images[0], 1600)}"
          alt="${escapeHTML(title)}" data-index="0">

        <button type="button" class="gallery-nav gallery-nav--prev" data-gallery-prev aria-label="Photo précédente">
          ${icon('chevron-left', { size: 24 })}
        </button>
        <button type="button" class="gallery-nav gallery-nav--next" data-gallery-next aria-label="Photo suivante">
          ${icon('chevron-right', { size: 24 })}
        </button>
        <button type="button" class="gallery-expand" data-gallery-open aria-label="Voir en plein écran">
          ${icon('plus', { size: 18 })}
        </button>
        <span class="gallery-counter" data-gallery-counter>1 / ${images.length}</span>
      </div>

      <div class="gallery-thumbs" data-gallery-thumbs>${slides}</div>

      <div class="lightbox" data-lightbox hidden>
        <button type="button" class="lightbox-close" data-lightbox-close aria-label="Fermer">
          ${icon('close', { size: 22 })}
        </button>
        <button type="button" class="lightbox-nav lightbox-nav--prev" data-gallery-prev aria-label="Photo précédente">
          ${icon('chevron-left', { size: 28 })}
        </button>
        <img class="lightbox-image" data-lightbox-image src="" alt="${escapeHTML(title)}">
        <button type="button" class="lightbox-nav lightbox-nav--next" data-gallery-next aria-label="Photo suivante">
          ${icon('chevron-right', { size: 28 })}
        </button>
      </div>
    </div>
  `;
}