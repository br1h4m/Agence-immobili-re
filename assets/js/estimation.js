// assets/js/estimation.js
import { qs, qsa } from '/client/includes/helpers.js';

export function initEstimation() {
  const root = qs('[data-estimation]');
  if (!root) return;

  const steps = qsa('.stepper-step', root);
  const panes = qsa('.stepper-pane', root);
  const progress = qsa('.stepper-step > span', root);
  const nextBtns = qsa('[data-step-next]', root);
  const prevBtns = qsa('[data-step-prev]', root);
  const form = qs('form', root);
  const success = qs('[data-estimation-success]', root);

  let current = 0;

  const render = () => {
    panes.forEach((p, i) => p.classList.toggle('is-active', i === current));
    steps.forEach((s, i) => {
      s.classList.toggle('is-active', i === current);
      s.classList.toggle('is-done', i < current);
    });
    progress.forEach((bar, i) => {
      bar.style.width = i <= current ? '100%' : '0%';
    });
    root.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  nextBtns.forEach((b) => b.addEventListener('click', () => {
    if (current < panes.length - 1) {
      current += 1;
      render();
    }
  }));
  prevBtns.forEach((b) => b.addEventListener('click', () => {
    if (current > 0) {
      current -= 1;
      render();
    }
  }));

  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const step = panes[current];

    // Validation basique des champs obligatoires de l'étape en cours
    let valid = true;
    step.querySelectorAll('input[required], select[required]').forEach((f) => {
      const ok = f.value.trim() !== '';
      f.style.borderColor = ok ? '' : 'var(--color-danger)';
      if (!ok) valid = false;
    });
    if (!valid) return;

    // Simulation d'envoi
    const submit = form.querySelector('button[type="submit"]');
    if (submit) { submit.disabled = true; submit.textContent = 'Envoi...'; }

    setTimeout(() => {
      form.hidden = true;
      if (success) success.hidden = false;
      steps.forEach((s) => s.classList.add('is-done'));
      progress.forEach((bar) => { bar.style.width = '100%'; });
    }, 700);
  });

  render();
}