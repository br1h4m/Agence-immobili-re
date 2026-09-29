// assets/js/forms.js
import { qs, qsa } from '/client/includes/helpers.js';

export function initForms() {
  qsa('[data-contact-form]').forEach(setupForm);
}

function setupForm(form) {
  if (form.dataset.bound === '1') return;
  form.dataset.bound = '1';

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!validate(form)) return;

    // Simulation d'envoi (remplacé par fetch plus tard)
    const feedback = form.querySelector('[data-form-feedback]');
    const button = form.querySelector('button[type="submit"]');

    if (button) {
      button.disabled = true;
      button.dataset.label = button.innerHTML;
      button.innerHTML = 'Envoi en cours...';
    }

    setTimeout(() => {
      form.reset();
      if (feedback) feedback.hidden = false;
      if (button) {
        button.disabled = false;
        button.innerHTML = button.dataset.label || 'Envoyer';
      }
      const head = form.querySelector('.form-head');
      if (head) head.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 650);
  });
}

function validate(form) {
  let valid = true;
  form.querySelectorAll('[required]').forEach((field) => {
    const ok = field.value.trim() !== '' && field.checkValidity();
    field.style.borderColor = ok ? '' : 'var(--color-danger)';
    if (!ok) valid = false;
  });
  return valid;
}