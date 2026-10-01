// client/includes/scripts.js
// MODULE ES NAVIGATEUR — point d'entrée commun à toutes les pages (remplace l'ancien include de scripts).
// Il initialise le <head>, monte le header et le footer, puis lance les comportements globaux.
//
// Dépendances (livrées dans les phases suivantes, contrats à respecter) :
//   - assets/components/header.js  -> export function Header({ activePage }) : string (HTML)
//   - assets/components/footer.js  -> export function Footer() : string (HTML)
//   - assets/js/main.js            -> export function initMain() : void
//
// Usage dans une page HTML :
//   <div id="header-slot"></div> ... <div id="footer-slot"></div>
//   <script type="module">
//     import { initPage } from './includes/scripts.js';
//     await initPage({ page: 'accueil', title: 'Accueil', description: '...' });
//     // ... rendu propre à la page
//   </script>

import { Header } from '/assets/components/header.js';
import { Footer } from '/assets/components/footer.js';
import { initMain } from '/assets/js/main.js';
import { initHead } from '/client/includes/head.js';
import { installImageFallback, render } from '/client/includes/helpers.js';

/**
 * @param {Object} options
 * @param {string} options.page          clé de navigation active : 'accueil' | 'biens' | 'services' | 'estimation' | 'a-propos' | 'contact'
 * @param {string} [options.title]       titre de la page (suffixé du nom de l'agence)
 * @param {string} [options.description] meta description
 * @param {string} [options.image]       image Open Graph
 * @param {string} [options.headerSlot]  sélecteur du conteneur du header
 * @param {string} [options.footerSlot]  sélecteur du conteneur du footer
 */
export async function initPage({
  page = '',
  title,
  description,
  image,
  headerSlot = '#header-slot',
  footerSlot = '#footer-slot'
} = {}) {
  // 1. <head> : métadonnées, polices, CSS (on attend les styles avant de rendre)
  await initHead({ title, description, image });

  // 2. Filet de sécurité : image cassée -> placeholder
  installImageFallback();

  // 3. Structure commune
  render(headerSlot, Header({ activePage: page }));
  render(footerSlot, Footer());

  // 4. Comportements globaux (menu mobile, header au scroll, animations au scroll...)
  initMain();

  document.body.classList.add('is-ready');
}