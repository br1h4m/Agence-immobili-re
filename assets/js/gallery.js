// assets/js/gallery.js
import { qs, qsa, lockScroll, resizeImage } from '/client/includes/helpers.js';

export function initGallery(images = []) {
  const root = qs('[data-gallery]');
  if (!root || !images.length) return;

  const main = qs('[data-gallery-main]', root);
  const thumbs = qsa('[data-gallery-thumb]', root);
  const counter = qs('[data-gallery-counter]', root);
  const lightbox = qs('[data-lightbox]', root);
  const lightboxImage = qs('[data-lightbox-image]', root);
  let index = 0;

  const update = (next, { fromLightbox = false } = {}) => {
    index = (next + images.length) % images.length;
    if (main) {
      main.style.opacity = '0';
      setTimeout(() => {
        main.src = resizeImage(images[index], 1600);
        main.style.opacity = '1';
      }, 80);
    }
    if (lightboxImage && fromLightbox) lightboxImage.src = resizeImage(images[index], 2200);
    if (counter) counter.textContent = `${index + 1} / ${images.length}`;
    thumbs.forEach((t, i) => t.classList.toggle('is-active', i === index));
  };

  thumbs.forEach((btn, i) => btn.addEventListener('click', () => update(i)));

  qsa('[data-gallery-prev]', root).forEach((b) =>
    b.addEventListener('click', () => update(index - 1, { fromLightbox: !lightbox?.hidden })));
  qsa('[data-gallery-next]', root).forEach((b) =>
    b.addEventListener('click', () => update(index + 1, { fromLightbox: !lightbox?.hidden })));

  qs('[data-gallery-open]', root)?.addEventListener('click', () => {
    if (!lightbox) return;
    lightbox.hidden = false;
    lightboxImage.src = resizeImage(images[index], 2200);
    lockScroll(true);
  });

  qs('[data-lightbox-close]', root)?.addEventListener('click', closeLightbox);
  lightbox?.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });

  document.addEventListener('keydown', (e) => {
    if (lightbox?.hidden) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') update(index - 1, { fromLightbox: true });
    if (e.key === 'ArrowRight') update(index + 1, { fromLightbox: true });
  });

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.hidden = true;
    lockScroll(false);
  }
}