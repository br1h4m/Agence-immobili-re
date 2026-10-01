// assets/components/footer.js
import { CONFIG } from '/client/data/config.js';
import { url, asset, icon, whatsappLink, escapeHTML } from '/client/includes/helpers.js';

export function Footer() {
  const year = new Date().getFullYear();

  const navLinks = CONFIG.NAV
    .map((item) => `<li><a href="${url(item.path)}">${item.label}</a></li>`)
    .join('');

  const serviceLinks = [
    { label: 'Acheter', path: '/biens?transaction=Acheter' },
    { label: 'Louer', path: '/biens?transaction=Louer' },
    { label: 'Vendre', path: '/estimation' },
    { label: 'Estimation', path: '/estimation' }
  ].map((s) => `<li><a href="${url(s.path)}">${s.label}</a></li>`).join('');

  return `
    <footer class="site-footer">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-col footer-col--brand">
            <a href="${url('/')}" class="brand brand--footer">
              <img src="${asset('images/logo.svg')}" alt="" class="brand-logo" width="30" height="30">
              <span class="brand-name">${CONFIG.AGENCY_NAME}</span>
            </a>
            <p class="footer-text">
              ${escapeHTML(CONFIG.CARD_TITLE || CONFIG.AGENCY_LEGAL)}.
              ${escapeHTML(CONFIG.MANAGER)}, ${escapeHTML(CONFIG.MANAGER_ROLE)}. Nous accompagnons vendeurs, acheteurs,
              propriétaires et locataires sur l'ensemble de leurs projets.
            </p>
            <ul class="footer-social">
              <li><a href="${CONFIG.SOCIAL.facebook}" aria-label="Facebook: ${escapeHTML(CONFIG.SOCIAL.facebookLabel)}" target="_blank" rel="noopener">${icon('facebook', { size: 18 })}</a></li>
              <li><a href="${whatsappLink()}" aria-label="WhatsApp" target="_blank" rel="noopener">${icon('whatsapp', { size: 18 })}</a></li>
            </ul>
          </div>

          <div class="footer-col">
            <h4 class="footer-heading">Navigation</h4>
            <ul class="footer-list">${navLinks}</ul>
          </div>

          <div class="footer-col">
            <h4 class="footer-heading">Services</h4>
            <ul class="footer-list">${serviceLinks}</ul>
          </div>

          <div class="footer-col">
            <h4 class="footer-heading">Contact</h4>
            <ul class="footer-list footer-contact">
              <li>${icon('user', { size: 16 })}<span><strong>${escapeHTML(CONFIG.MANAGER)}</strong> (${escapeHTML(CONFIG.MANAGER_ROLE)})</span></li>
              <li>${icon('phone', { size: 16 })}<a href="tel:${CONFIG.PHONE_RAW}">${CONFIG.PHONE}</a></li>
              <li>${icon('mail', { size: 16 })}<a href="mailto:${CONFIG.EMAIL}">${CONFIG.EMAIL}</a></li>
              <li>${icon('pin', { size: 16 })}<span>${CONFIG.ADDRESS}</span></li>
              <li>${icon('facebook', { size: 16 })}<a href="${CONFIG.SOCIAL.facebook}" target="_blank" rel="noopener">${escapeHTML(CONFIG.SOCIAL.facebookLabel)}</a></li>
            </ul>
          </div>
        </div>

        <div class="footer-bottom">
          <p>© ${year} ${CONFIG.AGENCY_NAME}. Tous droits réservés.</p>
          <ul class="footer-legal">
            <li><a href="${url('/mentions-legales')}">Mentions légales</a></li>
            <li><a href="${url('/confidentialite')}">Confidentialité</a></li>
          </ul>
        </div>
      </div>
    </footer>
  `;
}