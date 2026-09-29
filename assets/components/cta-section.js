// assets/components/cta-section.js
import { url, icon } from '../../client/includes/helpers.js';

export function CTASection({
  title = 'Vous avez un projet immobilier ?',
  subtitle = 'Notre équipe est à votre disposition pour vous accompagner.',
  image = 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=2000&q=80'
} = {}) {
  return `
    <section class="cta-section">
      <div class="cta-media" aria-hidden="true">
        <img src="${image}" alt="" loading="lazy">
      </div>
      <div class="container cta-inner">
        <h2 class="cta-title">${title}</h2>
        <p class="cta-subtitle">${subtitle}</p>
        <div class="cta-actions">
          <a href="${url('/contact')}" class="btn btn-primary btn-lg">
            <span>Nous contacter</span>${icon('arrow-right', { size: 16 })}
          </a>
          <a href="${url('/estimation')}" class="btn btn-outline-light btn-lg">Estimer mon bien</a>
        </div>
      </div>
    </section>
  `;
}