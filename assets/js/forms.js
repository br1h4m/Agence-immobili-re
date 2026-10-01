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

    // Simulation d'envoi et persistance locale
    const feedback = form.querySelector('[data-form-feedback]');
    const button = form.querySelector('button[type="submit"]');

    if (button) {
      button.disabled = true;
      button.dataset.label = button.innerHTML;
      button.innerHTML = 'Envoi en cours...';
    }

    try {
      const data = new FormData(form);
      const raw = localStorage.getItem('immo_demandes');
      const list = raw ? JSON.parse(raw) : [];
      const newDemande = {
        id: Date.now(),
        type: 'Contact',
        nom: `${data.get('firstname') || ''} ${data.get('lastname') || ''}`.trim() || 'Visiteur',
        email: data.get('email') || '',
        phone: data.get('phone') || '',
        subject: data.get('subject') || 'Prise de contact',
        message: data.get('message') || '',
        status: 'Nouveau',
        createdAt: new Date().toISOString()
      };
      list.unshift(newDemande);
      localStorage.setItem('immo_demandes', JSON.stringify(list));
    } catch (_) {}

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