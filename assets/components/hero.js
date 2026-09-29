// assets/components/hero.js
import { url, icon } from '/client/includes/helpers.js';
import { SearchBar } from '/assets/components/search-bar.js';

export function Hero({
  title = 'Votre prochain bien commence ici',
  subtitle = 'Découvrez des propriétés sélectionnées avec soin pour vos projets d\'achat et de location.',
  image = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=2200&q=80'
} = {}) {
  return `
    <section class="hero">
      <div class="hero-media" aria-hidden="true">
        <img src="${image}" alt="" fetchpriority="high">
        <span class="hero-overlay"></span>
      </div>

      <div class="container hero-inner">
        <div class="hero-content">
          <p class="hero-eyebrow">Agence immobilière</p>
          <h1 class="hero-title">${title}</h1>
          <p class="hero-subtitle">${subtitle}</p>
        </div>

        <div class="hero-search">
          ${SearchBar()}
        </div>

        <div class="hero-meta">
          <a href="${url('/biens')}" class="hero-link">
            <span>Parcourir tous les biens</span>
            ${icon('arrow-right', { size: 16 })}
          </a>
        </div>
      </div>
    </section>
  `;
}