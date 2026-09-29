// client/includes/head.js
// MODULE ES NAVIGATEUR — remplace l'ancien include <head> : injecte dans document.head
// les métadonnées, Google Fonts et les feuilles de style.
//
// ⚠️ Les pages HTML gardent seulement <meta charset="UTF-8"> (doit figurer dans le HTML statique)
//    et NE contiennent AUCUN <link rel="stylesheet"> : tout est injecté ici.
//
// Note SEO : les métadonnées sont rendues côté navigateur. Un futur backend / SSR pourra
// les générer côté serveur sans changer l'API de ce module.

import { CONFIG } from '../data/config.js';
import { asset } from './helpers.js';

const FONTS_HREF =
  'https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap';

// Ordre important : responsive.css en dernier pour surcharger les autres.
const STYLESHEETS = ['css/style.css', 'css/component.css', 'css/responsive.css'];

const STYLESHEET_TIMEOUT_MS = 4000; // sécurité : on ne bloque jamais l'affichage plus longtemps

/* ---------- Utilitaires internes ---------- */

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

/** Charge une feuille de style ; la promesse se résout toujours (chargement, erreur ou délai dépassé) */
function loadStylesheet(href) {
  return new Promise((resolve) => {
    const link = upsertLink('stylesheet', href);
    if (link.sheet) return resolve();
    link.addEventListener('load', () => resolve(), { once: true });
    link.addEventListener('error', () => resolve(), { once: true });
    setTimeout(resolve, STYLESHEET_TIMEOUT_MS);
  });
}

/* ---------- API publique ---------- */

/**
 * Met à jour le titre et les métadonnées (utile aussi pour la fiche bien, une fois le bien chargé).
 * updateHead({ title: 'Villa contemporaine', description: '...', image: 'https://...' })
 */
export function updateHead({ title, description, image } = {}) {
  const fullTitle = title
    ? `${title} | ${CONFIG.AGENCY_NAME}`
    : `${CONFIG.AGENCY_NAME} — ${CONFIG.TAGLINE}`;
  const fullDescription = description || CONFIG.DESCRIPTION;

  document.title = fullTitle;
  upsertMeta('name', 'description', fullDescription);
  upsertMeta('property', 'og:title', fullTitle);
  upsertMeta('property', 'og:description', fullDescription);
  if (image) upsertMeta('property', 'og:image', image);
}

/**
 * Initialise le <head> de la page. Retourne une Promise résolue quand les CSS sont chargés :
 * on l'attend avant de rendre la page pour éviter tout flash de contenu non stylé.
 *
 * Usage : await initHead({ title: 'Nos biens', description: '...' });
 */
export function initHead(options = {}) {
  document.documentElement.lang = 'fr';

  ensureMeta('viewport', 'width=device-width, initial-scale=1');
  upsertMeta('name', 'theme-color', CONFIG.THEME_COLOR);
  upsertMeta('property', 'og:type', 'website');
  upsertMeta('property', 'og:site_name', CONFIG.AGENCY_NAME);
  upsertMeta('property', 'og:locale', 'fr_FR');
  updateHead(options);

  // Favicon
  upsertLink('icon', asset('images/logo.svg'), { type: 'image/svg+xml' });

  // Google Fonts (non bloquant : display=swap)
  upsertLink('preconnect', 'https://fonts.googleapis.com');
  upsertLink('preconnect', 'https://fonts.gstatic.com', { crossorigin: '' });
  loadStylesheet(FONTS_HREF);

  // CSS du site (bloquant pour le premier rendu)
  return Promise.all(STYLESHEETS.map((file) => loadStylesheet(asset(file)))).then(() => undefined);
}