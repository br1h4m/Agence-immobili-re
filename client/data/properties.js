// client/data/properties.js
// MODULE ES NAVIGATEUR — aucune dépendance, aucun Node.js.
//
// Schéma d'un bien :
//   id, reference, title, type, transaction ('Acheter' | 'Louer'),
//   price (DA — par mois si transaction = 'Louer'), pricePeriod (null | 'mois'),
//   location { city, district, wilaya }, surface (m²), landSurface (m² | null),
//   bedrooms, bathrooms, garage (bool), floor (null si N/A), floors, year (null si N/A),
//   description, features [], images [], status ('Disponible' | 'Réservé' | 'Vendu' | 'Loué'),
//   isNew, isFeatured, createdAt (ISO)
//
// Convention : garage === true  <=>  features contient 'Garage'.

// Construit une URL Unsplash (la taille est ajustée à l'affichage via resizeImage() de helpers.js)
const img = (id, w = 1400) =>
  `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${w}&q=80`;

// DONNEES MOCKEES - A SUPPRIMER quand le backend sera pret
const DEFAULT_PROPERTIES = [
  {
    id: 1,
    reference: 'IML-001',
    title: 'Villa contemporaine',
    type: 'Villa',
    transaction: 'Acheter',
    price: 25000000,
    pricePeriod: null,
    location: { city: 'Béjaïa', district: 'Tichy', wilaya: 'Béjaïa' },
    surface: 180, landSurface: 400,
    bedrooms: 4, bathrooms: 2,
    garage: true, floor: 0, floors: 2, year: 2021,
    description:
      "Située dans le quartier résidentiel de Tichy, à quelques minutes de la plage, cette villa contemporaine de 180 m² allie lignes épurées et grands volumes lumineux. Le rez-de-chaussée s'ouvre sur un vaste séjour, une cuisine équipée et une terrasse qui donne sur le jardin et la piscine. À l'étage, quatre chambres dont une suite parentale avec dressing. Les finitions sont soignées : double vitrage, climatisation dans toutes les pièces et garage pour deux véhicules. Un bien rare, idéal pour une famille en quête de calme et de confort.",
    features: ['Jardin', 'Piscine', 'Terrasse', 'Garage', 'Climatisation'],
    images: [
      img('1613490493576-7fde63acd811'),
      img('1600585154340-be6161a56a0c'),
      img('1600607687939-ce8a6c25118c'),
      img('1600566753190-17f0baa2a6c3')
    ],
    status: 'Disponible',
    isNew: true,
    isFeatured: true,
    createdAt: '2026-09-24'
  },
  {
    id: 2,
    reference: 'IML-002',
    title: 'Appartement F4 standing',
    type: 'Appartement',
    transaction: 'Acheter',
    price: 32000000,
    pricePeriod: null,
    location: { city: 'Hydra', district: 'Les Sources', wilaya: 'Alger' },
    surface: 145, landSurface: null,
    bedrooms: 3, bathrooms: 2,
    garage: true, floor: 5, floors: 8, year: 2019,
    description:
      "Au cœur d'Hydra, dans une résidence sécurisée récente, appartement F4 de 145 m² situé au 5e étage. Séjour traversant très lumineux, cuisine équipée, deux salles de bains et grand balcon avec dégagement. Une place de garage en sous-sol est incluse. Résidence avec ascenseur, interphone et gardiennage.",
    features: ['Ascenseur', 'Garage', 'Climatisation', 'Sécurité 24/7', 'Balcon', 'Interphone'],
    images: [
      img('1502672260266-1c1ef2d93688'),
      img('1505691938895-1758d7feb511'),
      img('1484154218962-a197022b5858'),
      img('1540518614846-7eded433c457')
    ],
    status: 'Disponible',
    isNew: true,
    isFeatured: true,
    createdAt: '2026-09-22'
  },
  {
    id: 3,
    reference: 'IML-003',
    title: 'Appartement F3 meublé',
    type: 'Appartement',
    transaction: 'Louer',
    price: 85000,
    pricePeriod: 'mois',
    location: { city: 'Béjaïa', district: 'Centre-ville', wilaya: 'Béjaïa' },
    surface: 92, landSurface: null,
    bedrooms: 2, bathrooms: 1,
    garage: false, floor: 3, floors: 5, year: 2018,
    description:
      "Appartement F3 entièrement meublé et équipé, en plein centre-ville de Béjaïa, à proximité des commerces, des cafés et des transports. Idéal pour un couple ou une famille de passage. Fibre optique, climatisation et cuisine équipée. Disponible immédiatement, bail d'un an minimum.",
    features: ['Meublé', 'Balcon', 'Climatisation', 'Fibre optique', 'Cuisine équipée'],
    images: [
      img('1522708323590-d24dbb6b0267'),
      img('1556909114-f6e7ad7d3136'),
      img('1631679706909-1844bbd07221')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: true,
    createdAt: '2026-09-18'
  },
  {
    id: 4,
    reference: 'IML-004',
    title: 'Villa avec piscine',
    type: 'Villa',
    transaction: 'Acheter',
    price: 68000000,
    pricePeriod: null,
    location: { city: 'Ben Aknoun', district: 'Résidentiel', wilaya: 'Alger' },
    surface: 320, landSurface: 650,
    bedrooms: 5, bathrooms: 4,
    garage: true, floor: 0, floors: 2, year: 2020,
    description:
      "Somptueuse villa de 320 m² sur un terrain de 650 m² à Ben Aknoun, dans un environnement verdoyant et sécurisé. Cinq chambres, quatre salles de bains, double séjour, piscine et terrasse ombragée. Chauffage central, climatisation et système de sécurité 24h/24. Convient aussi bien à une résidence familiale qu'à une représentation d'entreprise.",
    features: ['Jardin', 'Piscine', 'Terrasse', 'Garage', 'Climatisation', 'Chauffage central', 'Sécurité 24/7'],
    images: [
      img('1512917774080-9991f1c4c750'),
      img('1613977257363-707ba9348227'),
      img('1600047509807-ba8f99d2cdde')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: true,
    createdAt: '2026-09-12'
  },
  {
    id: 5,
    reference: 'IML-005',
    title: 'Villa vue mer à Canastel',
    type: 'Villa',
    transaction: 'Acheter',
    price: 45000000,
    pricePeriod: null,
    location: { city: 'Oran', district: 'Canastel', wilaya: 'Oran' },
    surface: 240, landSurface: 500,
    bedrooms: 4, bathrooms: 3,
    garage: true, floor: 0, floors: 2, year: 2018,
    description:
      "Villa de 240 m² perchée sur les hauteurs de Canastel, avec une vue dégagée sur la Méditerranée. Ses larges baies vitrées et sa terrasse panoramique font entrer la lumière toute la journée. Jardin paysager, garage double, chauffage central et climatisation. Bien actuellement réservé : contactez-nous pour être informé en cas de désistement.",
    features: ['Vue mer', 'Jardin', 'Terrasse', 'Garage', 'Climatisation', 'Chauffage central'],
    images: [
      img('1580587771525-78b9dba3b914'),
      img('1600596542815-ffad4c1539a9'),
      img('1600573472592-401b489a3cdc')
    ],
    status: 'Réservé',
    isNew: false,
    isFeatured: true,
    createdAt: '2026-08-30'
  },
  {
    id: 6,
    reference: 'IML-006',
    title: 'Maison familiale rénovée',
    type: 'Maison',
    transaction: 'Acheter',
    price: 14500000,
    pricePeriod: null,
    location: { city: 'Azazga', district: 'Centre', wilaya: 'Tizi Ouzou' },
    surface: 160, landSurface: 300,
    bedrooms: 4, bathrooms: 2,
    garage: false, floor: 0, floors: 2, year: 2015,
    description:
      "Maison de 160 m² entièrement rénovée en 2023, dans un cadre calme au pied des montagnes de Kabylie. Quatre chambres, deux salles d'eau, une terrasse et un jardin de 300 m² au total. Double vitrage et chauffage central pour un confort optimal en hiver.",
    features: ['Jardin', 'Terrasse', 'Chauffage central', 'Double vitrage'],
    images: [
      img('1568605114967-8130f3a36994'),
      img('1570129477492-45c003edd2be'),
      img('1560448204-e02f11c3d0e2')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-08-20'
  },
  {
    id: 7,
    reference: 'IML-007',
    title: 'Maison de ville avec jardin',
    type: 'Maison',
    transaction: 'Louer',
    price: 120000,
    pricePeriod: 'mois',
    location: { city: 'Constantine', district: 'Sidi Mabrouk', wilaya: 'Constantine' },
    surface: 130, landSurface: 180,
    bedrooms: 3, bathrooms: 2,
    garage: true, floor: 0, floors: 2, year: 2017,
    description:
      "Maison de 130 m² sur deux niveaux dans le quartier résidentiel de Sidi Mabrouk. Trois chambres, deux salles de bains, cuisine indépendante, petit jardin privatif et garage. Idéale pour une famille. Location longue durée, caution demandée.",
    features: ['Garage', 'Jardin', 'Chauffage central', 'Climatisation'],
    images: [
      img('1518780664697-55e3ad937233'),
      img('1576941089067-2de3c901e126'),
      img('1493809842364-78817add7ffb')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-09-05'
  },
  {
    id: 8,
    reference: 'IML-008',
    title: 'Appartement F3 lumineux',
    type: 'Appartement',
    transaction: 'Acheter',
    price: 18500000,
    pricePeriod: null,
    location: { city: 'Bir El Djir', district: 'Haï Essabah', wilaya: 'Oran' },
    surface: 98, landSurface: null,
    bedrooms: 2, bathrooms: 1,
    garage: false, floor: 4, floors: 7, year: 2022,
    description:
      "Appartement F3 de 98 m² au 4e étage d'une résidence neuve à Bir El Djir. Séjour lumineux, balcon, climatisation et belles prestations. Ce bien a été vendu : d'autres opportunités similaires sont disponibles dans nos annonces.",
    features: ['Ascenseur', 'Balcon', 'Climatisation', 'Interphone'],
    images: [
      img('1560185893-a55cbc8c57e8'),
      img('1560185127-6ed189bf02f4'),
      img('1560185007-cde436f6a4d0')
    ],
    status: 'Vendu',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-07-15'
  },
  {
    id: 9,
    reference: 'IML-009',
    title: 'Appartement F3 à Tizi Ouzou',
    type: 'Appartement',
    transaction: 'Louer',
    price: 55000,
    pricePeriod: 'mois',
    location: { city: 'Tizi Ouzou', district: 'Nouvelle ville', wilaya: 'Tizi Ouzou' },
    surface: 88, landSurface: null,
    bedrooms: 2, bathrooms: 1,
    garage: false, floor: 2, floors: 6, year: 2020,
    description:
      "Appartement F3 de 88 m² dans un immeuble récent de la Nouvelle ville. Balcon, chauffage central et fibre optique. Quartier calme, proche des écoles et des commerces. Ce bien est actuellement loué.",
    features: ['Ascenseur', 'Balcon', 'Chauffage central', 'Fibre optique'],
    images: [
      img('1493663284031-b7e3aefcae8e'),
      img('1554995207-c18c203602cb'),
      img('1502005229762-cf1b2da7c5d6')
    ],
    status: 'Loué',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-09-08'
  },
  {
    id: 10,
    reference: 'IML-010',
    title: 'Studio meublé',
    type: 'Appartement',
    transaction: 'Louer',
    price: 45000,
    pricePeriod: 'mois',
    location: { city: 'Kouba', district: 'Centre', wilaya: 'Alger' },
    surface: 42, landSurface: null,
    bedrooms: 1, bathrooms: 1,
    garage: false, floor: 1, floors: 4, year: 2016,
    description:
      "Studio de 42 m² entièrement meublé, à deux pas des commerces et des transports. Cuisine équipée, climatisation et fibre optique. Idéal pour un étudiant ou un jeune actif. Disponible immédiatement.",
    features: ['Meublé', 'Climatisation', 'Fibre optique', 'Cuisine équipée'],
    images: [
      img('1536376072261-38c75010e6c9'),
      img('1503174971373-b1f69850bded'),
      img('1567767292278-a4f21aa2d36e')
    ],
    status: 'Disponible',
    isNew: true,
    isFeatured: false,
    createdAt: '2026-09-26'
  },
  {
    id: 11,
    reference: 'IML-011',
    title: 'Terrain constructible vue mer',
    type: 'Terrain',
    transaction: 'Acheter',
    price: 22000000,
    pricePeriod: null,
    location: { city: 'Aokas', district: 'Les Hauteurs', wilaya: 'Béjaïa' },
    surface: 600, landSurface: 600,
    bedrooms: 0, bathrooms: 0,
    garage: false, floor: null, floors: 0, year: null,
    description:
      "Terrain plat de 600 m² à Aokas, avec une vue imprenable sur la mer. Viabilisé, avec livret foncier, constructible en R+2. À cinq minutes de la plage, dans un secteur résidentiel en plein développement. Une belle opportunité pour bâtir la villa de vos rêves.",
    features: ['Vue mer', 'Viabilisé', 'Livret foncier'],
    images: [
      img('1500382017468-9049fed747ef'),
      img('1500530855697-b586d89ba3ee')
    ],
    status: 'Disponible',
    isNew: true,
    isFeatured: true,
    createdAt: '2026-09-20'
  },
  {
    id: 12,
    reference: 'IML-012',
    title: 'Terrain agricole avec puits',
    type: 'Terrain',
    transaction: 'Acheter',
    price: 9500000,
    pricePeriod: null,
    location: { city: 'Mouzaïa', district: 'Route communale', wilaya: 'Blida' },
    surface: 2500, landSurface: 2500,
    bedrooms: 0, bathrooms: 0,
    garage: false, floor: null, floors: 0, year: null,
    description:
      "Terrain agricole plat de 2 500 m² à Mouzaïa, dans la plaine de la Mitidja. Puits sur place, accès direct par une route communale, acte notarié. Idéal pour un projet agricole ou un investissement patrimonial.",
    features: ['Puits', 'Acte notarié', 'Accès route'],
    images: [
      img('1470071459604-3b5ec3a7fe05'),
      img('1501785888041-af3ef285b470')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-07-28'
  },
  {
    id: 13,
    reference: 'IML-013',
    title: 'Local commercial avec vitrine',
    type: 'Local',
    transaction: 'Louer',
    price: 150000,
    pricePeriod: 'mois',
    location: { city: 'Bab Ezzouar', district: 'Cité 5 Juillet', wilaya: 'Alger' },
    surface: 75, landSurface: null,
    bedrooms: 0, bathrooms: 1,
    garage: false, floor: 0, floors: 6, year: 2019,
    description:
      "Local commercial de 75 m² en rez-de-chaussée, avec grande vitrine sur une artère très passante de Bab Ezzouar, à proximité de l'université et du centre d'affaires. Climatisé, sécurisé, prêt à l'emploi. Convient à une boutique, un showroom ou un cabinet.",
    features: ['Vitrine', 'Climatisation', 'Sécurité 24/7'],
    images: [
      img('1441986300917-64674bd600d8'),
      img('1604014237800-1c9102c219da')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-09-10'
  },
  {
    id: 14,
    reference: 'IML-014',
    title: 'Local commercial centre-ville',
    type: 'Local',
    transaction: 'Acheter',
    price: 28000000,
    pricePeriod: null,
    location: { city: 'Oran', district: 'Centre-ville', wilaya: 'Oran' },
    surface: 110, landSurface: null,
    bedrooms: 0, bathrooms: 1,
    garage: false, floor: 0, floors: 4, year: 2010,
    description:
      "Local de 110 m² en plein centre-ville d'Oran, dans un immeuble de standing. Double vitrine, rideau métallique, climatisation. Emplacement premium à fort passage. Bien actuellement réservé.",
    features: ['Vitrine', 'Climatisation', 'Rideau métallique'],
    images: [
      img('1441986300917-64674bd600d8'),
      img('1604014237800-1c9102c219da')
    ],
    status: 'Réservé',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-08-05'
  },
  {
    id: 15,
    reference: 'IML-015',
    title: 'Plateau de bureaux',
    type: 'Bureau',
    transaction: 'Louer',
    price: 220000,
    pricePeriod: 'mois',
    location: { city: 'Alger-Centre', district: 'Didouche Mourad', wilaya: 'Alger' },
    surface: 160, landSurface: null,
    bedrooms: 0, bathrooms: 2,
    garage: true, floor: 3, floors: 7, year: 2018,
    description:
      "Plateau de bureaux de 160 m² au 3e étage d'un immeuble de standing, sur l'une des artères les plus prestigieuses d'Alger. Espace modulable, deux sanitaires, climatisation, fibre optique et place de parking. Idéal pour une PME, un cabinet ou une représentation.",
    features: ['Ascenseur', 'Climatisation', 'Fibre optique', 'Garage', 'Sécurité 24/7'],
    images: [
      img('1497366216548-37526070297c'),
      img('1497366811353-6870744d04b2'),
      img('1524758631624-e2822e304c36')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-09-15'
  },
  {
    id: 16,
    reference: 'IML-016',
    title: 'Bureau standing au Millénium',
    type: 'Bureau',
    transaction: 'Acheter',
    price: 19500000,
    pricePeriod: null,
    location: { city: 'Oran', district: 'Millénium', wilaya: 'Oran' },
    surface: 75, landSurface: null,
    bedrooms: 0, bathrooms: 1,
    garage: false, floor: 2, floors: 6, year: 2021,
    description:
      "Bureau de 75 m² dans un immeuble récent du quartier d'affaires du Millénium. Belles prestations, climatisation, fibre optique et ascenseur. Un investissement sûr dans un secteur en pleine croissance.",
    features: ['Ascenseur', 'Climatisation', 'Fibre optique', 'Interphone'],
    images: [
      img('1486406146926-c627a92ad1ab'),
      img('1497215728101-856f4ea42174'),
      img('1522071820081-009f0129c71c')
    ],
    status: 'Disponible',
    isNew: false,
    isFeatured: false,
    createdAt: '2026-08-12'
  }
];

function getInitialProperties() {
  if (typeof window !== 'undefined' && window.localStorage) {
    try {
      const stored = localStorage.getItem('immo_properties');
      if (stored) {
        const parsed = JSON.parse(stored);
        if (Array.isArray(parsed) && parsed.length > 0) {
          return parsed;
        }
      }
      localStorage.setItem('immo_properties', JSON.stringify(DEFAULT_PROPERTIES));
    } catch (_) {}
  }
  return DEFAULT_PROPERTIES;
}

export const PROPERTIES = getInitialProperties();