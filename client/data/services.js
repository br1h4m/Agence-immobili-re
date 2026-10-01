// client/data/services.js
// Liste officielle des 5 services de l'agence Imolode.

// DONNEES MOCKEES - A SUPPRIMER quand le backend sera pret
export const SERVICES = [
  {
    id: 1,
    slug: 'achat',
    title: 'Achat',
    description:
      "Trouvez la propriété qui correspond à vos exigences. Nous sélectionnons des biens vérifiés et vous accompagnons de la première visite jusqu'à la signature de l'acte authentique.",
    image: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
    icon: 'search',
    link: '/biens?transaction=Acheter'
  },
  {
    id: 2,
    slug: 'vente',
    title: 'Vente',
    description:
      "Vendez votre bien au juste prix du marché. Nous assurons la valorisation photographique, la diffusion ciblée, le filtrage des acquéreurs et le suivi notarié rigoureux.",
    image: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80',
    icon: 'home',
    link: '/contact'
  },
  {
    id: 3,
    slug: 'location',
    title: 'Location',
    description:
      "Accédez à un catalogue varié de logements et locaux professionnels. Nous sécurisons la rédaction du bail et l'état des lieux pour une relation locative sereine.",
    image: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80',
    icon: 'key',
    link: '/biens?transaction=Louer'
  },
  {
    id: 4,
    slug: 'gestion-immobiliere',
    title: 'Gestion immobilière',
    description:
      "Déléguez l'administration de votre patrimoine : encaissement des loyers, gestion des charges, maintenance technique et veille juridique permanente sans contrainte.",
    image: 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?auto=format&fit=crop&w=1200&q=80',
    icon: 'building',
    link: '/contact'
  },
  {
    id: 5,
    slug: 'estimation',
    title: 'Estimation',
    description:
      "Obtenez un avis de valeur professionnel et objectif basé sur les transactions réelles de votre secteur géographique et les spécificités de votre bien.",
    image: 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1200&q=80',
    icon: 'trending-up',
    link: '/estimation'
  }
];