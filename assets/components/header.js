// assets/components/header.js
import { CONFIG } from '/client/data/config.js';
import { url, asset, icon } from '/client/includes/helpers.js';

export function Header({ activePage = '' } = {}) {
  const navItems = CONFIG.NAV.map((item) => {
    const isActive = item.key === activePage;
    return `
      <li>
        <a href="${url(item.path)}" class="nav-link${isActive ? ' is-active' : ''}"
          ${isActive ? 'aria-current="page"' : ''}>${item.label}</a>
      </li>`;
  }).join('');

  return `
    <header class="site-header" data-header>
      <div class="container header-inner">
        <a href="${url('/')}" class="brand" aria-label="${CONFIG.AGENCY_NAME}">
          <img src="${asset('images/logo.svg')}" alt="" class="brand-logo" width="34" height="34">
          <span class="brand-name">${CONFIG.AGENCY_NAME}</span>
        </a>

        <nav class="main-nav" aria-label="Navigation principale">
          <ul class="nav-list">${navItems}</ul>
        </nav>

        <div class="header-actions">
          <a href="${url('/estimation')}" class="btn btn-outline btn-sm header-cta">Estimer mon bien</a>
          <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="mobile-nav"
            aria-label="Ouvrir le menu">
            <span class="menu-toggle-open">${icon('menu', { size: 22 })}</span>
            <span class="menu-toggle-close">${icon('close', { size: 22 })}</span>
          </button>
        </div>
      </div>

      <div id="mobile-nav" class="mobile-nav" hidden>
        <nav aria-label="Navigation mobile">
          <ul class="mobile-nav-list">${navItems}</ul>
        </nav>
        <div class="mobile-nav-footer">
          <a href="${url('/estimation')}" class="btn btn-primary btn-block">Estimer mon bien</a>
          <a href="tel:${CONFIG.PHONE_RAW}" class="btn btn-ghost btn-block">
            ${icon('phone', { size: 16 })}<span>${CONFIG.PHONE}</span>
          </a>
        </div>
      </div>
    </header>
  `;
}