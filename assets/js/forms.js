// assets/js/forms.js
// Soumission du formulaire de contact connectée à l'API PHP

import { qs, qsa } from '/client/includes/helpers.js';

export function initForms() {
  qsa('[data-contact-form]').forEach(setupForm);
}

function setupForm(form) {
  if (form.dataset.bound === '1') return;
  form.dataset.bound = '1';

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validate(form)) return;

    const feedback = form.querySelector('[data-form-feedback]');
    const button = form.querySelector('button[type="submit"]');

    if (button) {
      button.disabled = true;
      button.dataset.label = button.innerHTML;
      button.innerHTML = 'Envoi en cours...';
    }

    const data = new FormData(form);
    const payload = {
      type: 'Contact',
      nom: `${data.get('firstname') || ''} ${data.get('lastname') || ''}`.trim() || 'Visiteur',
      email: data.get('email') || '',
      phone: data.get('phone') || '',
      subject: data.get('subject') || 'Prise de contact',
      message: data.get('message') || '',
      status: 'Nouveau'
    };

    // 1. Enregistrement direct dans le backend PHP
    try {
      await fetch('/api/demandes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
    } catch (err) {
      console.warn('API PHP non joignable, enregistrement en local:', err);
    }

    // 2. Synchronisation locale de secours
    try {
      const raw = localStorage.getItem('immo_demandes');
      const list = raw ? JSON.parse(raw) : [];
      list.unshift({ ...payload, id: Date.now(), createdAt: new Date().toISOString() });
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
    }, 400);
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
