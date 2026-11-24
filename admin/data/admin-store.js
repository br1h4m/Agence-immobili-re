// admin/data/admin-store.js
// MODULE ES NAVIGATEUR — gestion de l'état administrateur connectée au Backend PHP.
// Persistance réelle via l'API REST PHP (/api/...) avec cache de synchronisation locale.

import { PROPERTIES } from '/client/data/properties.js';
import { TEAM } from '/client/data/team.js';
import { CONFIG } from '/client/data/config.js';

const KEY_PROPERTIES = 'immo_properties';
const KEY_DEMANDES = 'immo_demandes';
const KEY_FICHES_CLIENT = 'immo_fiches_client';
const KEY_USERS = 'immo_users';
const KEY_SETTINGS = 'immo_settings';

const INITIAL_FICHES_CLIENT = [
  {
    id: 1,
    reference: 'IML-DOS-2026-1042',
    nom: 'Benali',
    prenom: 'Karim',
    telephone: '0550 12 34 56',
    email: 'karim.benali@gmail.com',
    transaction: 'Demande',
    typeBien: 'Appartement',
    pieceIdentite: {
      name: 'cni_karim_benali.pdf',
      size: 450000,
      type: 'application/pdf',
      dataUrl: null
    },
    signature: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="80"><path d="M 20 50 Q 60 10 90 40 T 170 30" fill="none" stroke="%231C1C1C" stroke-width="2.5"/></svg>',
    engagementAccepte: true,
    status: 'Nouveau',
    createdAt: '2026-10-14T09:30:00Z'
  },
  {
    id: 2,
    reference: 'IML-DOS-2026-1043',
    nom: 'Amrani',
    prenom: 'Samira',
    telephone: '0661 88 99 22',
    email: 'samira.amrani@outlook.com',
    transaction: 'Offre',
    typeBien: 'Villa',
    pieceIdentite: {
      name: 'passeport_amrani.jpg',
      size: 1250000,
      type: 'image/jpeg',
      dataUrl: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&q=80'
    },
    signature: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="80"><path d="M 30 45 Q 70 20 110 50 T 165 40" fill="none" stroke="%231C1C1C" stroke-width="2.5"/></svg>',
    engagementAccepte: true,
    status: 'En cours',
    createdAt: '2026-10-13T15:20:00Z'
  }
];

const INITIAL_DEMANDES = [
  {
    id: 1,
    type: 'Contact',
    nom: 'Nadir Mansouri',
    email: 'nadir.mansouri@gmail.com',
    phone: '+213 550 12 34 56',
    subject: "Demande d'information — Villa contemporaine",
    message: "Bonjour, je souhaiterais visiter la villa contemporaine a Tichy ce week-end si possible. Merci de me recontacter.",
    status: 'Nouveau',
    createdAt: '2026-09-30T10:15:00Z'
  },
  {
    id: 2,
    type: 'Estimation',
    nom: 'Farida Ould Ali',
    email: 'farida.ouldali@yahoo.fr',
    phone: '+213 661 78 90 12',
    subject: 'Estimation : Appartement a Alger',
    message: 'Appartement F4 situe dans une residence fermee a Cheraga. Bon etat general.',
    details: {
      type: 'Appartement',
      wilaya: 'Alger',
      commune: 'Cheraga',
      quartier: 'Bouchaoui',
      surface: '120',
      land: '',
      bedrooms: '3',
      bathrooms: '2',
      garage: 'Oui'
    },
    status: 'En cours',
    createdAt: '2026-09-29T14:30:00Z'
  },
  {
    id: 3,
    type: 'Contact',
    nom: 'Rachid Belkacem',
    email: 'rachid.belkacem@pro-dz.com',
    phone: '+213 770 45 67 89',
    subject: 'Recherche bureau professionnel',
    message: "Nous sommes a la recherche d'une surface de bureau d'au moins 100 m² a Bejaia centre pour installer notre societe.",
    status: 'Traite',
    createdAt: '2026-09-28T09:00:00Z'
  }
];

function readStorage(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    if (raw) {
      return JSON.parse(raw);
    }
    localStorage.setItem(key, JSON.stringify(fallback));
    return fallback;
  } catch (_) {
    return fallback;
  }
}

function writeStorage(key, data) {
  try {
    localStorage.setItem(key, JSON.stringify(data));
  } catch (_) {}
}

/**
 * Synchronise les données locales avec l'API PHP en arrière-plan
 */
export async function syncWithBackend() {
  try {
    const [resProps, resDemandes, resFiches, resSettings, resUsers] = await Promise.all([
      fetch('/api/properties.php').then(r => r.ok ? r.json() : null),
      fetch('/api/demandes.php').then(r => r.ok ? r.json() : null),
      fetch('/api/fiches-client.php').then(r => r.ok ? r.json() : null),
      fetch('/api/settings.php').then(r => r.ok ? r.json() : null),
      fetch('/api/users.php').then(r => r.ok ? r.json() : null)
    ]);

    if (Array.isArray(resProps) && resProps.length > 0) {
      writeStorage(KEY_PROPERTIES, resProps);
    }
    if (Array.isArray(resDemandes) && resDemandes.length > 0) {
      writeStorage(KEY_DEMANDES, resDemandes);
    }
    if (Array.isArray(resFiches) && resFiches.length > 0) {
      writeStorage(KEY_FICHES_CLIENT, resFiches);
    }
    if (resSettings && typeof resSettings === 'object') {
      writeStorage(KEY_SETTINGS, resSettings);
    }
    if (Array.isArray(resUsers) && resUsers.length > 0) {
      writeStorage(KEY_USERS, resUsers);
    }
  } catch (err) {
    // Mode dégradé si serveur injoignable
    console.debug('Synchronisation API PHP en arrière-plan différée.');
  }
}

// Déclenche une synchronisation initiale
syncWithBackend();

/* ==========================================================================
   Biens immobiliers
   ========================================================================== */

export function getProperties({ search = '', transaction = '', type = '', status = '', sort = 'recent' } = {}) {
  let list = readStorage(KEY_PROPERTIES, PROPERTIES);

  if (transaction) {
    list = list.filter((p) => p.transaction === transaction);
  }
  if (type) {
    list = list.filter((p) => p.type === type);
  }
  if (status) {
    list = list.filter((p) => p.status === status);
  }
  if (search) {
    const q = search.toLowerCase().trim();
    list = list.filter((p) => {
      const matchTitle = (p.title || '').toLowerCase().includes(q);
      const matchRef = (p.reference || '').toLowerCase().includes(q);
      const matchCity = (p.location?.city || '').toLowerCase().includes(q);
      const matchWilaya = (p.location?.wilaya || '').toLowerCase().includes(q);
      return matchTitle || matchRef || matchCity || matchWilaya;
    });
  }

  // Tri
  if (sort === 'recent') {
    list = [...list].sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));
  } else if (sort === 'price-asc') {
    list = [...list].sort((a, b) => (a.price || 0) - (b.price || 0));
  } else if (sort === 'price-desc') {
    list = [...list].sort((a, b) => (b.price || 0) - (a.price || 0));
  } else if (sort === 'surface-desc') {
    list = [...list].sort((a, b) => (b.surface || 0) - (a.surface || 0));
  }

  return list;
}

export function getPropertyById(id) {
  const list = readStorage(KEY_PROPERTIES, PROPERTIES);
  return list.find((p) => String(p.id) === String(id)) || null;
}

export function saveProperty(propertyData) {
  const list = readStorage(KEY_PROPERTIES, PROPERTIES);
  let updated;

  if (propertyData.id) {
    // Modification
    const index = list.findIndex((p) => String(p.id) === String(propertyData.id));
    if (index !== -1) {
      updated = { ...list[index], ...propertyData, updatedAt: new Date().toISOString() };
      list[index] = updated;
    } else {
      updated = { ...propertyData, updatedAt: new Date().toISOString() };
      list.unshift(updated);
    }
  } else {
    // Creation
    const maxId = list.reduce((max, p) => Math.max(max, Number(p.id) || 0), 0);
    const newId = maxId + 1;
    const refNum = String(newId).padStart(3, '0');
    updated = {
      ...propertyData,
      id: newId,
      reference: propertyData.reference || `IML-${refNum}`,
      createdAt: new Date().toISOString().slice(0, 10),
      images: Array.isArray(propertyData.images) && propertyData.images.length
        ? propertyData.images
        : ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80']
    };
    list.unshift(updated);
  }

  writeStorage(KEY_PROPERTIES, list);

  // Envoi asynchrone au backend PHP
  try {
    const isUpdate = Boolean(propertyData.id);
    fetch('/api/properties.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...updated,
        action: isUpdate ? 'update' : 'create'
      })
    }).then(r => r.json()).then(res => {
      if (res?.data?.id) {
        // Met à jour l'ID si le serveur en a généré un nouveau
        syncWithBackend();
      }
    }).catch(() => {});
  } catch (_) {}

  return updated;
}

export function deleteProperty(id) {
  const list = readStorage(KEY_PROPERTIES, PROPERTIES);
  const filtered = list.filter((p) => String(p.id) !== String(id));
  writeStorage(KEY_PROPERTIES, filtered);

  // Envoi asynchrone au backend PHP
  try {
    fetch(`/api/properties.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' }).catch(() => {});
  } catch (_) {}

  return filtered.length !== list.length;
}

export function updatePropertyStatus(id, newStatus) {
  const list = readStorage(KEY_PROPERTIES, PROPERTIES);
  const item = list.find((p) => String(p.id) === String(id));
  if (item) {
    item.status = newStatus;
    writeStorage(KEY_PROPERTIES, list);

    // Envoi asynchrone au backend PHP
    try {
      fetch('/api/properties.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'status', id, status: newStatus })
      }).catch(() => {});
    } catch (_) {}

    return true;
  }
  return false;
}

/* ==========================================================================
   Demandes & Estimations
   ========================================================================== */

export function getDemandes({ type = '', status = '', search = '' } = {}) {
  const demandesRaw = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const fichesRaw = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);

  // Normaliser les fiches client en format enrichi
  const fichesFormatted = fichesRaw.map((f) => ({
    id: f.id,
    type: 'Fiche client',
    reference: f.reference,
    nom: `${f.nom || ''} ${f.prenom || ''}`.trim(),
    nomFamille: f.nom,
    prenom: f.prenom,
    email: f.email,
    phone: f.telephone,
    subject: `Dossier ${f.reference || ''} : ${f.transaction || 'Demande'} (${f.typeBien || 'Bien'})`,
    message: `Dossier officiel de constitution client — Opération : ${f.transaction || 'Demande'} | Bien : ${f.typeBien || 'Non spécifié'} | Justificatif : ${f.pieceIdentite?.name || 'Joint'} | Signature : Recueillie`,
    transaction: f.transaction,
    typeBien: f.typeBien,
    pieceIdentite: f.pieceIdentite,
    signature: f.signature,
    engagementAccepte: f.engagementAccepte,
    isFicheClient: true,
    is_read: f.is_read ?? 0,
    status: f.status || 'Nouveau',
    createdAt: f.createdAt
  }));

  let list = [...fichesFormatted, ...demandesRaw];

  if (type) {
    list = list.filter((d) => d.type === type);
  }
  if (status) {
    list = list.filter((d) => d.status === status);
  }
  if (search) {
    const q = search.toLowerCase().trim();
    list = list.filter((d) =>
      (d.nom || '').toLowerCase().includes(q) ||
      (d.email || '').toLowerCase().includes(q) ||
      (d.phone || '').toLowerCase().includes(q) ||
      (d.subject || '').toLowerCase().includes(q) ||
      (d.reference || '').toLowerCase().includes(q) ||
      (d.typeBien || '').toLowerCase().includes(q)
    );
  }

  return [...list].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

export function getDemandeById(id) {
  const all = getDemandes();
  return all.find((d) => String(d.id) === String(id)) || null;
}

export function updateDemandeStatus(id, newStatus) {
  // Vérifie d'abord si c'est une fiche client
  const fiches = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const ficheItem = fiches.find((f) => String(f.id) === String(id));
  if (ficheItem) {
    ficheItem.status = newStatus;
    writeStorage(KEY_FICHES_CLIENT, fiches);

    try {
      fetch('/api/fiches-client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'status', id, status: newStatus })
      }).catch(() => {});
    } catch (_) {}

    return true;
  }

  // Sinon demande classique
  const demandes = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const item = demandes.find((d) => String(d.id) === String(id));
  if (item) {
    item.status = newStatus;
    writeStorage(KEY_DEMANDES, demandes);

    try {
      fetch('/api/demandes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'status', id, status: newStatus })
      }).catch(() => {});
    } catch (_) {}

    return true;
  }
  return false;
}

export function deleteDemande(id) {
  let changed = false;

  const fiches = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const fichesFiltered = fiches.filter((f) => String(f.id) !== String(id));
  if (fichesFiltered.length !== fiches.length) {
    writeStorage(KEY_FICHES_CLIENT, fichesFiltered);
    changed = true;
    try {
      fetch(`/api/fiches-client.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' }).then(() => triggerBadgeSync()).catch(() => {});
    } catch (_) {}
  }

  const demandes = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const demandesFiltered = demandes.filter((d) => String(d.id) !== String(id));
  if (demandesFiltered.length !== demandes.length) {
    writeStorage(KEY_DEMANDES, demandesFiltered);
    changed = true;
    try {
      fetch(`/api/demandes.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' }).then(() => triggerBadgeSync()).catch(() => {});
    } catch (_) {}
  }

  return changed;
}

/* ==========================================================================
   Fiches Client (Dossiers)
   ========================================================================== */

export function getFichesClient({ search = '', transaction = '', typeBien = '', status = '' } = {}) {
  let list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);

  if (transaction) {
    list = list.filter((f) => f.transaction === transaction);
  }
  if (typeBien) {
    list = list.filter((f) => f.typeBien === typeBien);
  }
  if (status) {
    list = list.filter((f) => f.status === status);
  }
  if (search) {
    const q = search.toLowerCase().trim();
    list = list.filter((f) =>
      (f.reference || '').toLowerCase().includes(q) ||
      (f.nom || '').toLowerCase().includes(q) ||
      (f.prenom || '').toLowerCase().includes(q) ||
      `${f.nom || ''} ${f.prenom || ''}`.toLowerCase().includes(q) ||
      (f.email || '').toLowerCase().includes(q) ||
      (f.telephone || '').toLowerCase().includes(q)
    );
  }

  return [...list].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

export function getFicheClientById(id) {
  const list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  return list.find((f) => String(f.id) === String(id)) || null;
}

export function saveFicheClient(data) {
  const list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  let updated;

  if (data.id) {
    const index = list.findIndex((f) => String(f.id) === String(data.id));
    if (index !== -1) {
      updated = { ...list[index], ...data, updatedAt: new Date().toISOString() };
      list[index] = updated;
    } else {
      updated = { ...data, updatedAt: new Date().toISOString() };
      list.unshift(updated);
    }
  } else {
    updated = {
      ...data,
      id: Date.now(),
      status: data.status || 'Nouveau',
      createdAt: data.createdAt || new Date().toISOString()
    };
    list.unshift(updated);
  }

  writeStorage(KEY_FICHES_CLIENT, list);

  try {
    fetch('/api/fiches-client.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(updated)
    }).catch(() => {});
  } catch (_) {}

  return updated;
}

export function updateFicheClientStatus(id, newStatus) {
  const list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const item = list.find((f) => String(f.id) === String(id));
  if (item) {
    item.status = newStatus;
    writeStorage(KEY_FICHES_CLIENT, list);

    try {
      fetch('/api/fiches-client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'status', id, status: newStatus })
      }).catch(() => {});
    } catch (_) {}

    return true;
  }
  return false;
}

export function deleteFicheClient(id) {
  const list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const filtered = list.filter((f) => String(f.id) !== String(id));
  writeStorage(KEY_FICHES_CLIENT, filtered);

  try {
    fetch(`/api/fiches-client.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' }).then(() => triggerBadgeSync()).catch(() => {});
  } catch (_) {}

  return filtered.length !== list.length;
}

/* ==========================================================================
   Utilisateurs / Equipe
   ========================================================================== */

export function getUsers() {
  const defaultUsers = TEAM.map((t) => ({
    id: t.id,
    nom: t.nom,
    poste: t.poste,
    email: t.email,
    phone: t.phone,
    role: t.id === 1 ? 'Gérant' : 'Agent commercial',
    actif: true
  }));
  return readStorage(KEY_USERS, defaultUsers);
}

export function saveUser(userData) {
  const list = getUsers();
  let updated;

  if (userData.id) {
    const index = list.findIndex((u) => String(u.id) === String(userData.id));
    if (index !== -1) {
      updated = { ...list[index], ...userData };
      list[index] = updated;
    } else {
      updated = userData;
      list.push(updated);
    }
  } else {
    const maxId = list.reduce((max, u) => Math.max(max, Number(u.id) || 0), 0);
    updated = { ...userData, id: maxId + 1, actif: true };
    list.push(updated);
  }

  writeStorage(KEY_USERS, list);

  try {
    fetch('/api/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(updated)
    }).catch(() => {});
  } catch (_) {}

  return updated;
}

export function deleteUser(id) {
  const list = getUsers();
  const filtered = list.filter((u) => String(u.id) !== String(id));
  writeStorage(KEY_USERS, filtered);

  try {
    fetch(`/api/users.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' }).catch(() => {});
  } catch (_) {}

  return filtered.length !== list.length;
}

/* ==========================================================================
   Parametres
   ========================================================================== */

export function getSettings() {
  return readStorage(KEY_SETTINGS, {
    agencyName: CONFIG.AGENCY_NAME,
    tagline: CONFIG.TAGLINE,
    description: CONFIG.DESCRIPTION,
    phone: CONFIG.PHONE,
    phoneRaw: CONFIG.PHONE_RAW,
    email: CONFIG.EMAIL,
    address: CONFIG.ADDRESS,
    whatsapp: CONFIG.WHATSAPP,
    hours: CONFIG.HOURS,
    currency: CONFIG.CURRENCY,
    propertiesPerPage: CONFIG.PROPERTIES_PER_PAGE,
    facebook: CONFIG.SOCIAL?.facebook || '#',
    instagram: CONFIG.SOCIAL?.instagram || '#'
  });
}

export function saveSettings(data) {
  const current = getSettings();
  const updated = { ...current, ...data };
  writeStorage(KEY_SETTINGS, updated);

  try {
    fetch('/api/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(updated)
    }).catch(() => {});
  } catch (_) {}

  return updated;
}

/* ==========================================================================
   Statistiques Tableau de bord
   ========================================================================== */

export function getDashboardStats() {
  const properties = readStorage(KEY_PROPERTIES, PROPERTIES);
  const demandes = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const fichesClient = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);

  const totalProperties = properties.length;
  const forSale = properties.filter((p) => p.transaction === 'Acheter').length;
  const forRent = properties.filter((p) => p.transaction === 'Louer').length;
  const available = properties.filter((p) => p.status === 'Disponible').length;

  const totalDemandes = demandes.length;
  const pendingDemandes = demandes.filter((d) => d.status === 'Nouveau' || d.status === 'En cours').length;

  const totalFichesClient = fichesClient.length;
  const pendingFichesClient = fichesClient.filter((f) => f.status === 'Nouveau' || f.status === 'En cours').length;

  const recentProperties = [...properties]
    .sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0))
    .slice(0, 5);

  const recentDemandes = [...demandes]
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
    .slice(0, 5);

  const recentFichesClient = [...fichesClient]
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
    .slice(0, 5);

  return {
    totalProperties,
    forSale,
    forRent,
    available,
    totalDemandes,
    pendingDemandes,
    totalFichesClient,
    pendingFichesClient,
    recentProperties,
    recentDemandes,
    recentFichesClient
  };
}

/* ==========================================================================
   Opérations multiples (Bulk) & Gestion de la lecture (is_read)
   ========================================================================== */

function triggerBadgeSync() {
  if (typeof window !== 'undefined' && typeof window._syncBadgesNow === 'function') {
    window._syncBadgesNow();
  }
}

export async function bulkDeleteProperties(ids = []) {
  if (!ids.length) return false;
  const strIds = ids.map(String);
  const list = readStorage(KEY_PROPERTIES, PROPERTIES);
  const filtered = list.filter((p) => !strIds.includes(String(p.id)));
  writeStorage(KEY_PROPERTIES, filtered);

  try {
    const res = await fetch('/api/properties.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'bulk_delete', ids })
    });
    return res.ok;
  } catch (_) {
    return false;
  }
}

export async function bulkDeleteDemandes(ids = []) {
  if (!ids.length) return false;
  const strIds = ids.map(String);
  const demandes = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const fiches = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  writeStorage(KEY_DEMANDES, demandes.filter((d) => !strIds.includes(String(d.id))));
  writeStorage(KEY_FICHES_CLIENT, fiches.filter((f) => !strIds.includes(String(f.id))));

  try {
    await Promise.all([
      fetch('/api/demandes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_delete', ids })
      }).catch(() => {}),
      fetch('/api/fiches-client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_delete', ids })
      }).catch(() => {})
    ]);
    triggerBadgeSync();
    return true;
  } catch (_) {
    return false;
  }
}

export async function bulkDeleteFichesClient(ids = []) {
  if (!ids.length) return false;
  const strIds = ids.map(String);
  const list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const filtered = list.filter((f) => !strIds.includes(String(f.id)));
  writeStorage(KEY_FICHES_CLIENT, filtered);

  try {
    const res = await fetch('/api/fiches-client.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'bulk_delete', ids })
    });
    triggerBadgeSync();
    return res.ok;
  } catch (_) {
    return false;
  }
}

export async function bulkDeleteUsers(ids = []) {
  if (!ids.length) return false;
  const strIds = ids.map(String);
  const list = getUsers();
  const filtered = list.filter((u) => !strIds.includes(String(u.id)));
  writeStorage(KEY_USERS, filtered);

  try {
    const res = await fetch('/api/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'bulk_delete', ids })
    });
    const data = await res.json();
    return data?.success !== false;
  } catch (_) {
    return false;
  }
}

export async function markDemandeRead(id, is_read = 1) {
  const demandes = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const target = demandes.find((d) => String(d.id) === String(id));
  if (target) {
    target.is_read = is_read;
    writeStorage(KEY_DEMANDES, demandes);
  }
  const fiches = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const targetFiche = fiches.find((f) => String(f.id) === String(id));
  if (targetFiche) {
    targetFiche.is_read = is_read;
    writeStorage(KEY_FICHES_CLIENT, fiches);
  }

  const apiAction = is_read ? 'mark_read' : 'mark_unread';
  const requests = [];
  if (target || !targetFiche) {
    requests.push(
      fetch('/api/demandes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: apiAction, id, is_read })
      }).catch(() => {})
    );
  }
  if (targetFiche) {
    requests.push(
      fetch('/api/fiches-client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: apiAction, id, is_read })
      }).catch(() => {})
    );
  }
  await Promise.all(requests);
  triggerBadgeSync();
}

export async function bulkMarkDemandesRead(ids = [], is_read = 1) {
  if (!ids.length) return false;
  const strIds = ids.map(String);
  const demandes = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  demandes.forEach((d) => {
    if (strIds.includes(String(d.id))) d.is_read = is_read;
  });
  writeStorage(KEY_DEMANDES, demandes);

  const fiches = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  fiches.forEach((f) => {
    if (strIds.includes(String(f.id))) f.is_read = is_read;
  });
  writeStorage(KEY_FICHES_CLIENT, fiches);

  try {
    await Promise.all([
      fetch('/api/demandes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_mark_read', ids, is_read })
      }).catch(() => {}),
      fetch('/api/fiches-client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_mark_read', ids, is_read })
      }).catch(() => {})
    ]);
    triggerBadgeSync();
    return true;
  } catch (_) {
    return false;
  }
}

export async function markFicheRead(id, is_read = 1) {
  const fiches = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const target = fiches.find((f) => String(f.id) === String(id));
  if (target) {
    target.is_read = is_read;
    writeStorage(KEY_FICHES_CLIENT, fiches);
  }

  try {
    await fetch('/api/fiches-client.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: is_read ? 'mark_read' : 'mark_unread', id, is_read })
    });
    triggerBadgeSync();
  } catch (_) {}
}

export async function bulkMarkFichesRead(ids = [], is_read = 1) {
  if (!ids.length) return false;
  const strIds = ids.map(String);
  const fiches = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  fiches.forEach((f) => {
    if (strIds.includes(String(f.id))) f.is_read = is_read;
  });
  writeStorage(KEY_FICHES_CLIENT, fiches);

  try {
    await fetch('/api/fiches-client.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'bulk_mark_read', ids, is_read })
    });
    triggerBadgeSync();
    return true;
  } catch (_) {
    return false;
  }
}

