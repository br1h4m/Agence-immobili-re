// client/data/config.js
// MODULE ES NAVIGATEUR — chargé via <script type="module"> (import / export uniquement)
// Configuration globale du site : identité, coordonnées, navigation.
// ⚠️ Les coordonnées ci-dessous sont des PLACEHOLDERS à remplacer par les vraies données.

export const CONFIG = Object.freeze({
  // ---- Chemins ----
  BASE_URL: '/client',     // préfixe de toutes les pages publiques
  ASSETS_URL: '/assets',   // racine des assets (css, images, ...)

  // ---- Identité ----
  AGENCY_NAME: 'Immolode',
  TAGLINE: 'Agence immobilière',
  DESCRIPTION:
    'Immolode, agence immobilière : découvrez des propriétés sélectionnées avec soin pour vos projets d\'achat et de location.',
  THEME_COLOR: '#F8F7F4',

  // ---- Coordonnées ----
  PHONE: '+213 5XX XX XX XX',
  PHONE_RAW: '+2135XXXXXXXX',          // version utilisée dans les liens tel:
  EMAIL: 'contact@immolode.dz',
  ADDRESS: '12 rue Didouche Mourad, Béjaïa 06000, Algérie',
  WHATSAPP: '2135XXXXXXXX',            // format international, sans "+" ni espaces
  HOURS: 'Dim – Jeu : 9h00 – 17h30 · Sam : 9h00 – 13h00',
  SOCIAL: { facebook: '#', instagram: '#', whatsapp: '#' },

  // ---- Affichage ----
  CURRENCY: 'DA',
  LOCALE: 'fr-FR',
  PROPERTIES_PER_PAGE: 9,

  // ---- Navigation (partagée par header.js et footer.js) ----
  // "path" est relatif à BASE_URL : utiliser url(path) de helpers.js
  NAV: [
    { key: 'accueil',    label: 'Accueil',    path: '/' },
    { key: 'biens',      label: 'Nos biens',  path: '/biens' },
    { key: 'services',   label: 'Services',   path: '/services' },
    { key: 'estimation', label: 'Estimation', path: '/estimation' },
    { key: 'a-propos',   label: 'À propos',   path: '/a-propos' },
    { key: 'contact',    label: 'Contact',    path: '/contact' }
  ]
});