// client/includes/helpers.js
// MODULE ES NAVIGATEUR — fonctions utilitaires pures, partagées par les composants et les pages.
// (import / export uniquement : pas de Node.js)

import { CONFIG } from '/client/data/config.js';

/* ==========================================================================
   URLs & liens
   ========================================================================== */

/** Construit un lien interne à partir de CONFIG.BASE_URL. url('/biens') -> '/client/biens' */
export function url(path = '/') {
  if (/^(https?:|mailto:|tel:|#)/i.test(path)) return path;
  const clean = path.startsWith('/') ? path : `/${path}`;
  return `${CONFIG.BASE_URL}${clean}`;
}

/** Chemin vers un asset. asset('css/style.css') -> '/assets/css/style.css' */
export function asset(path = '') {
  return `${CONFIG.ASSETS_URL}/${String(path).replace(/^\/+/, '')}`;
}

/** URL propre d'une fiche bien : /client/bien/12 */
export function propertyUrl(id) {
  return url(`/bien/${id}`);
}

/** Lien tel: (ne garde que les chiffres et le "+") */
export function telLink(phone = CONFIG.PHONE_RAW) {
  return `tel:${String(phone).replace(/[^\d+]/g, '')}`;
}

/** Lien WhatsApp avec message prérempli optionnel */
export function whatsappLink(message = '') {
  const query = message ? `?text=${encodeURIComponent(message)}` : '';
  return `https://wa.me/${CONFIG.WHATSAPP}${query}`;
}

/** Message WhatsApp prérempli pour un bien */
export function propertyWhatsAppMessage(property) {
  return `Bonjour, je suis intéressé par le bien ${property.title}.`;
}

/** Paramètres de l'URL courante sous forme d'objet (?a=1&b=2 -> {a:'1', b:'2'}) */
export function getQueryParams() {
  return Object.fromEntries(new URLSearchParams(window.location.search));
}

/**
 * Récupère l'id du bien courant.
 * Avec les URLs propres (/client/bien/12), la réécriture .htaccess est interne :
 * le navigateur voit /client/bien/12 (pas de ?id=12). On lit donc aussi le chemin.
 */
export function getPropertyId() {
  const fromQuery = new URLSearchParams(window.location.search).get('id');
  if (fromQuery) return fromQuery;
  const match = window.location.pathname.match(/\/bien\/([^/]+)\/?$/);
  return match ? decodeURIComponent(match[1]) : null;
}

/** Compare deux ids (nombre ou chaîne) */
export function sameId(a, b) {
  return String(a) === String(b);
}

/* ==========================================================================
   Formatage
   ========================================================================== */

const numberFormatter = new Intl.NumberFormat(CONFIG.LOCALE, { maximumFractionDigits: 0 });

/** 25000000 -> "25 000 000 DA" ; avec period -> "85 000 DA / mois" */
export function formatPrice(price, { period = null } = {}) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) return 'Prix sur demande';
  const base = `${numberFormatter.format(price)} ${CONFIG.CURRENCY}`;
  return period ? `${base} / ${period}` : base;
}

/** Prix formaté d'un bien (gère la location mensuelle) */
export function formatPropertyPrice(property) {
  return formatPrice(property.price, { period: property.pricePeriod });
}

export function formatSurface(value) {
  return value ? `${numberFormatter.format(value)} m²` : '—';
}

/** { district, city, wilaya } -> "Tichy, Béjaïa" (full: ajoute la wilaya si différente) */
export function formatLocation(location = {}, { full = false } = {}) {
  const { district, city, wilaya } = location;
  const parts = [district, city];
  if (full && wilaya) parts.push(wilaya);
  return [...new Set(parts.filter(Boolean))].join(', ');
}

export function formatDate(isoDate) {
  if (!isoDate) return '';
  return new Intl.DateTimeFormat(CONFIG.LOCALE, { day: 'numeric', month: 'long', year: 'numeric' })
    .format(new Date(isoDate));
}

/** Accord singulier / pluriel : plural(3, 'bien') -> 'biens' */
export function plural(count, singular, pluralForm = `${singular}s`) {
  return count > 1 ? pluralForm : singular;
}

export function truncate(text = '', max = 160) {
  const value = String(text);
  return value.length > max ? `${value.slice(0, max).trimEnd()}…` : value;
}

export function isRental(property) {
  return property.transaction === 'Louer';
}

/* ==========================================================================
   DOM & sécurité
   ========================================================================== */

/** Échappe les caractères HTML (à utiliser pour toute valeur injectée dans un template) */
export function escapeHTML(value) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  return String(value ?? '').replace(/[&<>"']/g, (char) => map[char]);
}

export const qs = (selector, root = document) => root.querySelector(selector);
export const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];

/** Injecte du HTML dans un conteneur (sélecteur CSS ou élément). Retourne l'élément. */
export function render(target, html) {
  const element = typeof target === 'string' ? document.querySelector(target) : target;
  if (!element) {
    console.warn(`[Immolode] Conteneur introuvable : ${target}`);
    return null;
  }
  element.innerHTML = html;
  return element;
}

export function debounce(fn, delay = 250) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

/** Bloque / débloque le scroll de la page (menu mobile, modal filtres, lightbox) */
export function lockScroll(locked) {
  document.documentElement.classList.toggle('no-scroll', Boolean(locked));
}

/* ==========================================================================
   Images
   ========================================================================== */

const PLACEHOLDER_SVG =
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600">' +
  '<rect width="800" height="600" fill="#EDEBE6"/>' +
  '<g fill="none" stroke="#B59A6A" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" transform="translate(300 200)">' +
  '<path d="M0 100 100 20 200 100"/><path d="M25 80v100h150V80"/><path d="M85 180v-50h30v50"/>' +
  '</g></svg>';

/** Image de secours (data URI, aucune requête réseau) */
export const PLACEHOLDER_IMAGE = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(PLACEHOLDER_SVG)}`;

/** Ajuste la largeur d'une URL Unsplash (les autres URLs sont renvoyées telles quelles) */
export function resizeImage(src, width = 800) {
  try {
    const parsed = new URL(src);
    if (parsed.hostname === 'images.unsplash.com') {
      parsed.searchParams.set('auto', 'format');
      parsed.searchParams.set('fit', 'crop');
      parsed.searchParams.set('w', String(width));
      parsed.searchParams.set('q', '80');
      return parsed.toString();
    }
  } catch {
    /* URL relative ou invalide : on la renvoie telle quelle */
  }
  return src;
}

let fallbackInstalled = false;

/** Remplace automatiquement toute image cassée par le placeholder (écouteur global, installé une seule fois) */
export function installImageFallback() {
  if (fallbackInstalled) return;
  fallbackInstalled = true;
  document.addEventListener(
    'error',
    (event) => {
      const target = event.target;
      if (target instanceof HTMLImageElement && !target.dataset.fallback) {
        target.dataset.fallback = '1';
        target.src = PLACEHOLDER_IMAGE;
      }
    },
    true // "error" ne remonte pas : on écoute en phase de capture
  );
}

/* ==========================================================================
   Icônes SVG inline (style trait, 24x24, héritent de currentColor)
   ========================================================================== */

const ICONS = {
  'pin': '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  'bed': '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
  'bath': '<path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.683 3 4 3.683 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><line x1="10" x2="8" y1="5" y2="7"/><line x1="2" x2="22" y1="12" y2="12"/><line x1="7" x2="7" y1="19" y2="21"/><line x1="17" x2="17" y1="19" y2="21"/>',
  'area': '<path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/>',
  'car': '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
  'sprout': '<path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/>',
  'waves': '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
  'sun': '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
  'elevator': '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="m9 10 3-3 3 3"/><path d="m9 14 3 3 3-3"/>',
  'sofa': '<path d="M20 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v3"/><path d="M2 11v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v2H6v-2a2 2 0 0 0-4 0Z"/><path d="M4 18v2"/><path d="M20 18v2"/>',
  'snowflake': '<line x1="2" x2="22" y1="12" y2="12"/><line x1="12" x2="12" y1="2" y2="22"/><path d="m20 16-4-4 4-4"/><path d="m4 8 4 4-4 4"/><path d="m16 4-4 4-4-4"/><path d="m8 20 4-4 4 4"/>',
  'flame': '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
  'shield': '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
  'wifi': '<path d="M12 20h.01"/><path d="M2 8.82a15 15 0 0 1 20 0"/><path d="M5 12.859a10 10 0 0 1 14 0"/><path d="M8.5 16.429a5 5 0 0 1 7 0"/>',
  'home': '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
  'building': '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M12 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/>',
  'key': '<path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
  'compass': '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
  'star': '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
  'heart': '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
  'users': '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  'user': '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  'briefcase': '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
  'trending-up': '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
  'file-text': '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
  'layers': '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
  'calendar': '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
  'clock': '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
  'phone': '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
  'mail': '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
  'whatsapp': '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
  'instagram': '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
  'facebook': '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
  'search': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  'sliders': '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
  'menu': '<line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/>',
  'close': '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
  'check': '<path d="M20 6 9 17l-5-5"/>',
  'plus': '<path d="M5 12h14"/><path d="M12 5v14"/>',
  'arrow-right': '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
  'arrow-left': '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
  'chevron-left': '<path d="m15 18-6-6 6-6"/>',
  'chevron-right': '<path d="m9 18 6-6-6-6"/>',
  'chevron-down': '<path d="m6 9 6 6 6-6"/>'
};

/** Retourne une icône SVG inline. icon('bed', { size: 18 }) */
export function icon(name, { size = 20, className = '', strokeWidth = 1.75, label = '' } = {}) {
  const body = ICONS[name] ?? ICONS.check;
  const a11y = label
    ? `role="img" aria-label="${escapeHTML(label)}"`
    : 'aria-hidden="true" focusable="false"';
  const classes = `icon icon-${name}${className ? ` ${className}` : ''}`;
  return `<svg class="${classes}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${strokeWidth}" stroke-linecap="round" stroke-linejoin="round" ${a11y}>${body}</svg>`;
}

/* ==========================================================================
   Équipements -> icônes (utilisé par property-features.js et property-filters.js)
   ========================================================================== */

export const FEATURE_ICONS = {
  'Jardin': 'sprout',
  'Piscine': 'waves',
  'Terrasse': 'sun',
  'Balcon': 'sun',
  'Garage': 'car',
  'Ascenseur': 'elevator',
  'Meublé': 'sofa',
  'Climatisation': 'snowflake',
  'Chauffage central': 'flame',
  'Sécurité 24/7': 'shield',
  'Interphone': 'shield',
  'Rideau métallique': 'shield',
  'Fibre optique': 'wifi',
  'Vue mer': 'waves',
  'Puits': 'waves',
  'Cuisine équipée': 'home',
  'Double vitrage': 'layers',
  'Vitrine': 'building',
  'Livret foncier': 'file-text',
  'Acte notarié': 'file-text',
  'Accès route': 'pin',
  'Viabilisé': 'check'
};

/** Icône d'un équipement (repli sur "check" si inconnu) */
export function featureIcon(name, options) {
  return icon(FEATURE_ICONS[name] ?? 'check', options);
}