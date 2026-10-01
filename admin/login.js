// admin/login.js
import { login, redirectIfAuth } from '/admin/includes/auth.js';
import { escapeHTML } from '/admin/assets/js/admin.js';

// Redirige vers le dashboard si l'utilisateur est déjà connecté
if (!redirectIfAuth()) {
  const form = document.getElementById('login-form');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const alertSlot = document.getElementById('login-alert-slot');
  const btnSubmit = document.getElementById('btn-submit');
  const btnFillDemo = document.getElementById('btn-fill-demo');

  btnFillDemo?.addEventListener('click', () => {
    emailInput.value = 'gahamhocine2@gmail.com';
    passwordInput.value = 'admin123';
    emailInput.focus();
  });

  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    alertSlot.innerHTML = '';

    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();

    if (!email || !password) {
      alertSlot.innerHTML = `<div class="login-alert">Veuillez renseigner tous les champs.</div>`;
      return;
    }

    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Connexion en cours...';

    const result = login(email, password);

    if (result.success) {
      // Redirection
      const params = new URLSearchParams(window.location.search);
      const destination = params.get('redirect') || '/admin/';
      window.location.replace(destination);
    } else {
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Se connecter';
      alertSlot.innerHTML = `<div class="login-alert">${escapeHTML(result.message || 'Identifiants invalides.')}</div>`;
      passwordInput.focus();
    }
  });
}

