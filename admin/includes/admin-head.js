// admin/includes/admin-head.js
// MODULE ES NAVIGATEUR — injecte dans document.head les métadonnées et la feuille de style admin.
// Suit strictement le même principe que client/includes/head.js.

const FONTS_HREF =
  'https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap';

const STYLESHEET_HREF = '/admin/assets/css/admin.css';
const STYLESHEET_TIMEOUT_MS = 4000;

function upsertMeta(attribute, key, content) {
  if (content === undefined || content === null || content === '') return;
  let element = document.head.querySelector(`meta[${attribute}="${key}"]`);
  if (!element) {
    element = document.createElement('meta');
    element.setAttribute(attribute, key);
    document.head.appendChild(element);
  }
  element.setAttribute('content', content);
}

function ensureMeta(name, content) {
  if (!document.head.querySelector(`meta[name="${name}"]`)) upsertMeta('name', name, content);
}

function upsertLink(rel, href, attributes = {}) {
  const existing = [...document.head.querySelectorAll(`link[rel="${rel}"]`)].find(
    (link) => link.getAttribute('href') === href
  );
  if (existing) return existing;

  const link = document.createElement('link');
  link.rel = rel;
  link.href = href;
  Object.entries(attributes).forEach(([key, value]) => link.setAttribute(key, value));
  document.head.appendChild(link);
  return link;
}

function loadStylesheet(href) {
  return new Promise((resolve) => {
    const link = upsertLink('stylesheet', href);
    if (link.sheet) return resolve();
    link.addEventListener('load', () => resolve(), { once: true });
    link.addEventListener('error', () => resolve(), { once: true });
    setTimeout(resolve, STYLESHEET_TIMEOUT_MS);
  });
}

export function updateAdminHead({ title } = {}) {
  const fullTitle = title
    ? `${title} — Administration Immolode`
    : 'Administration — Immolode';
  document.title = fullTitle;
}

export function initAdminHead({ title } = {}) {
  document.documentElement.lang = 'fr';

  ensureMeta('viewport', 'width=device-width, initial-scale=1');
  ensureMeta('robots', 'noindex, nofollow');
  upsertMeta('name', 'theme-color', '#F8F7F4');
  updateAdminHead({ title });

  upsertLink('icon', '/assets/images/logo.svg', { type: 'image/svg+xml' });

  const hasStylesheet = Boolean(
    document.head.querySelector(`link[rel="stylesheet"][href*="admin.css"]`)
  );

  if (hasStylesheet) {
    return Promise.resolve();
  }

  upsertLink('preconnect', 'https://fonts.googleapis.com');
  upsertLink('preconnect', 'https://fonts.gstatic.com', { crossorigin: '' });
  loadStylesheet(FONTS_HREF);

  return loadStylesheet(STYLESHEET_HREF);
}
