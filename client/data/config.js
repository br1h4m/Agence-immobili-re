// client/data/config.js
// Configuration globale de l'agence Imolode (carte de visite officielle).

export const CONFIG = Object.freeze({
  BASE_URL: '/client',
  ASSETS_URL: '/assets',

  AGENCY_NAME: 'Imolode',
  AGENCY_LEGAL: 'Agence Immobilière Agréée Imolode',
  CARD_TITLE: 'Agence Immobilière Agréée Imolode',
  MANAGER: 'G. Hocine',
  MANAGER_ROLE: 'Gérant',
  TAGLINE: 'Agence immobilière agréée',
  DESCRIPTION:
    'Imolode, agence immobilière agréée : découvrez des propriétés sélectionnées avec soin pour vos projets d\'achat et de location.',
  THEME_COLOR: '#F8F7F4',

  PHONE: '0661.56.03.85',
  PHONE_RAW: '+213661560385',
  EMAIL: 'gahamhocine2@gmail.com',
  ADDRESS: '41 Bld Soudani Boudjemaa El Mouradia - Alger',
  WHATSAPP: '213661560385',
  HOURS: 'Dim – Jeu : 9h00 – 17h30 · Sam : 9h00 – 13h00',
  SOCIAL: {
    facebook: 'https://facebook.com/imolode.hydra',
    facebookLabel: 'Imolode Hydra',
    whatsapp: 'https://wa.me/213661560385'
  },
  LEGAL_REGULATION: {
    title: 'Agence Immobilière Agréée Imolode',
    article: "Art. 34-(Décret réglementant la profession de l'Agence immobilière)",
    text: "L'agent immobilier a droit, dans le cadre de l'exercice de sa profession à une rémunération. Pour ce qui concerne l'agence et le courtier immobilier, Lorsque la valeur du bien à vendre équivaut à :",
    brackets: [
      { condition: '1.000.000 DA', rate: '3%' },
      { condition: 'Inférieur ou égal à 5.000.000 DA', rate: '2%' },
      { condition: 'Supérieur à 5.000.000 DA', rate: '1%' }
    ],
    rent: "Lorsqu'il s'agit d'un bien à louer, sa rémunération équivaut à un (1) mois de location par année de location."
  },

  CURRENCY: 'DA',
  LOCALE: 'fr-FR',
  PROPERTIES_PER_PAGE: 9,

  STATS: [
    { value: '10+', label: "Années d'expérience" },
    { value: '150+', label: 'Biens accompagnés' },
    { value: '500+', label: 'Clients satisfaits' }
  ],

  NAV: [
    { key: 'accueil',    label: 'Accueil',    path: '/' },
    { key: 'biens',      label: 'Nos biens',  path: '/biens' },
    { key: 'services',   label: 'Services',   path: '/services' },
    { key: 'estimation', label: 'Estimation', path: '/estimation' },
    { key: 'a-propos',   label: 'À propos',   path: '/a-propos' },
    { key: 'contact',    label: 'Contact',    path: '/contact' }
  ]
});