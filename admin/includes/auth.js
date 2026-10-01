// admin/includes/auth.js
// MODULE ES NAVIGATEUR — gestion de la session administrateur (simulee via sessionStorage).
// Aucun backend requis.

const SESSION_KEY = 'immo_admin_session';

const DEFAULT_ADMIN = {
  id: 1,
  nom: 'G. Hocine',
  email: 'gahamhocine2@gmail.com',
  role: 'Gérant',
  poste: 'Gérant'
};

export function getSession() {
  try {
    const raw = sessionStorage.getItem(SESSION_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch (_) {
    return null;
  }
}

export function isAuthenticated() {
  return Boolean(getSession());
}

export function getCurrentUser() {
  const session = getSession();
  return session ? session.user : null;
}

export function requireAuth() {
  if (!isAuthenticated()) {
    const current = window.location.pathname + window.location.search;
    const redirectUrl = current && !current.includes('login') ? `?redirect=${encodeURIComponent(current)}` : '';
    window.location.replace(`/admin/login${redirectUrl}`);
    return false;
  }
  return true;
}

export function redirectIfAuth() {
  if (isAuthenticated()) {
    const params = new URLSearchParams(window.location.search);
    const destination = params.get('redirect') || '/admin/';
    window.location.replace(destination);
    return true;
  }
  return false;
}

export function login(email, password) {
  const cleanEmail = String(email || '').trim().toLowerCase();
  const cleanPass = String(password || '').trim();

  // Verifie le compte par defaut ou un mot de passe standard
  if (
    (cleanEmail === 'gahamhocine2@gmail.com' && cleanPass === 'admin123') ||
    (cleanEmail === 'admin@imolode.dz' && cleanPass === 'admin123') ||
    (cleanEmail.endsWith('@imolode.dz') && cleanPass.length >= 4) ||
    (cleanEmail === 'admin' && cleanPass === 'admin') ||
    (cleanEmail === 'admin' && cleanPass === 'admin123')
  ) {
    const user = {
      ...DEFAULT_ADMIN,
      email: cleanEmail === 'admin' ? DEFAULT_ADMIN.email : cleanEmail
    };

    const session = {
      user,
      token: `simulated_token_${Date.now()}`,
      loggedInAt: new Date().toISOString()
    };

    sessionStorage.setItem(SESSION_KEY, JSON.stringify(session));
    return { success: true, user };
  }

  return { success: false, message: 'Identifiants incorrects. Utilisez gahamhocine2@gmail.com / admin123' };
}

export function logout() {
  try {
    sessionStorage.removeItem(SESSION_KEY);
  } catch (_) {}
  window.location.replace('/admin/login');
}

