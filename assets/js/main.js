// assets/js/main.js
import { qs, qsa, lockScroll } from '/client/includes/helpers.js';

export function initMain() {
  initHeaderScroll();
  initMobileMenu();
  initScrollReveal();
  initFormButtons();
}

function initHeaderScroll() {
  const header = qs('[data-header]');
  if (!header) return;

  const solidThreshold = 40;
  const onScroll = () => {
    header.classList.toggle('is-scrolled', window.scrollY > solidThreshold);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}

function initMobileMenu() {
  const toggle = qs('.menu-toggle');
  const menu = qs('#mobile-nav');
  if (!toggle || !menu) return;

  const close = () => {
    toggle.setAttribute('aria-expanded', 'false');
    menu.removeAttribute('data-open');
    menu.hidden = true;
    lockScroll(false);
  };

  toggle.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') === 'true';
    if (isOpen) {
      close();
      return;
    }
    toggle.setAttribute('aria-expanded', 'true');
    menu.hidden = false;
    menu.setAttribute('data-open', '');
    lockScroll(true);
  });

  qsa('a', menu).forEach((link) => link.addEventListener('click', close));

  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) close();
  });
}

function initScrollReveal() {
  const elements = qsa('.reveal');
  if (!elements.length) return;

  if (!('IntersectionObserver' in window)) {
    elements.forEach((el) => el.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  elements.forEach((el) => observer.observe(el));
}

/* Délègue la soumission des formulaires [data-contact-form] à forms.js si présent */
function initFormButtons() {
  document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-filters-open]');
    if (btn) {
      event.preventDefault();
      qs('[data-filters]')?.classList.add('is-open');
      lockScroll(true);
    }
    const closeBtn = event.target.closest('[data-filters-close]');
    if (closeBtn) {
      qs('[data-filters]')?.classList.remove('is-open');
      lockScroll(false);
    }
  });
}