// client/data/services.js
// MODULE ES NAVIGATEUR — aucune dépendance.
// "link" est un chemin relatif à BASE_URL : utiliser url(service.link) de helpers.js.
// "icon" référence un nom d'icône de helpers.js (fonction icon()).

// DONNEES MOCKEES - A SUPPRIMER quand le backend sera pret
export const SERVICES = [
  {
    id: 1,
    slug: 'vente',
    title: 'Vente de biens',
    description:
      "Villas, appartements, terrains ou locaux : nous vous accompagnons de l'estimation à la signature, avec une sélection rigoureuse et des visites organisées à votre rythme.",
    image: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80',
    icon: 'home',
    link: '/biens?transaction=Acheter'
  },
  {
    id: 2,
    slug: 'location',
    title: 'Location',
    description:
      "Trouvez le logement ou le local qui vous correspond. Nous vérifions chaque bien, cadrons le contrat et vous guidons jusqu'à la remise des clés.",
    image: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80',
    icon: 'key',
    link: '/biens?transaction=Louer'
  },
  {
    id: 3,
    slug: 'estimation',
    title: 'Estimation gratuite',
    description:
      "Connaissez la valeur réelle de votre bien grâce à une analyse fondée sur le marché local, les ventes récentes et les atouts de votre propriété.",
    image: 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1200&q=80',
    icon: 'trending-up',
    link: '/estimation'
  },
  {
    id: 4,
    slug: 'gestion-locative',
    title: 'Gestion locative',
    description:
      "Confiez-nous votre bien : recherche de locataires, encaissement des loyers, suivi des travaux et relation quotidienne, pour un investissement sans contrainte.",
    image: 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?auto=format&fit=crop&w=1200&q=80',
    icon: 'building',
    link: '/contact'
  },
  {
    id: 5,
    slug: 'conseil',
    title: 'Conseil & accompagnement',
    description:
      "Investissement, financement, démarches administratives : un conseiller dédié vous éclaire à chaque étape pour sécuriser votre projet immobilier.",
    image: 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&q=80',
    icon: 'users',
    link: '/contact'
  }
];