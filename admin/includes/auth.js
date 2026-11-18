// admin/includes/auth.js
// MODULE ES NAVIGATEUR — gestion de la session administrateur connectée au backend PHP.

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

export function updateLocalUser(updatedUser) {
  try {
    const session = getSession() || {};
    session.user = { ...(session.user || {}), ...updatedUser };
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(session));
  } catch (_) {}
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

export async function login(email, password) {
  const cleanEmail = String(email || '').trim().toLowerCase();
  const cleanPass = String(password || '').trim();

  try {
    // Appel du backend PHP
    const res = await fetch('/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: cleanEmail, password: cleanPass })
    });

    const data = await res.json();
    if (res.ok && data.success && data.data?.user) {
      const user = data.data.user;
      const session = {
        user,
        token: data.data.token || `php_token_${Date.now()}`,
        loggedInAt: new Date().toISOString()
      };
      sessionStorage.setItem(SESSION_KEY, JSON.stringify(session));
      return { success: true, user };
    }
  } catch (err) {
    console.warn('Backend PHP non joignable, tentative fallback local:', err);
  }

  // Fallback de démonstration si le backend n'a pas encore répondu
  if (
    (cleanEmail === 'gahamhocine2@gmail.com' && cleanPass === 'admin123') ||
    (cleanEmail === 'admin@immolode.dz' && cleanPass === 'admin123') ||
    (cleanEmail === 'admin@imolode.dz' && cleanPass === 'admin123') ||
    (cleanEmail.endsWith('@immolode.dz') && cleanPass.length >= 4) ||
    (cleanEmail === 'admin' && (cleanPass === 'admin' || cleanPass === 'admin123'))
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

  return { success: false, message: 'Identifiants incorrects. Utilisez gahamhocine2@gmail.com / admin123 ou admin@immolode.dz / admin123' };
}

export async function logout() {
  try {
    await fetch('/api/auth.php?action=logout', { method: 'POST' });
  } catch (_) {}
  try {
    sessionStorage.removeItem(SESSION_KEY);
  } catch (_) {}
  window.location.replace('/admin/login');
}
