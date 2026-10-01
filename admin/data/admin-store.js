// admin/data/admin-store.js
// MODULE ES NAVIGATEUR — gestion de la persistance locale (localStorage).
// DONNEES MOCKEES - A SUPPRIMER quand le backend sera pret

import { PROPERTIES } from '/client/data/properties.js';
import { TEAM } from '/client/data/team.js';
import { CONFIG } from '/client/data/config.js';

const KEY_PROPERTIES = 'immo_properties';
const KEY_DEMANDES = 'immo_demandes';
const KEY_USERS = 'immo_users';
const KEY_SETTINGS = 'immo_settings';

const INITIAL_DEMANDES = [
  {
    id: 1727710001,
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
    id: 1727710002,
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
    id: 1727710003,
    type: 'Contact',
    nom: 'Rachid Belkacem',
    email: 'rachid.belkacem@pro-dz.com',
    phone: '+213 770 45 67 89',
    subject: 'Recherche bureau professionnel',
    message: "Nous sommes a la recherche d'une surface de bureau d'au moins 100 m² a Bejaia centre pour installer notre societe.",
    status: 'Traite',
    createdAt: '2026-09-28T09:00:00Z'
  },
  {
    id: 1727710004,
    type: 'Estimation',
    nom: 'Youcef Khellaf',
    email: 'youcef.khellaf@gmail.com',
    phone: '+213 555 88 99 00',
    subject: 'Estimation : Villa a Bejaia',
    message: 'Villa R+1 avec jardin et vue degagee sur le golfe de Bejaia.',
    details: {
      type: 'Villa',
      wilaya: 'Bejaia',
      commune: 'Bejaia',
      quartier: 'Targa Ouzemmour',
      surface: '220',
      land: '350',
      bedrooms: '5',
      bathrooms: '3',
      garage: 'Oui'
    },
    status: 'Nouveau',
    createdAt: '2026-09-30T16:45:00Z'
  }
];

function readStorage(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    if (raw) {
      const data = JSON.parse(raw);
      // Nettoie automatiquement les anciennes données obsolètes (faux noms ou numéros XX)
      if (key === KEY_USERS && Array.isArray(data)) {
        const hasOutdatedData = data.some((u) => !u.nom || u.nom.includes('Benali') || String(u.phone).includes('XX'));
        if (hasOutdatedData) {
          localStorage.setItem(key, JSON.stringify(fallback));
          return fallback;
        }
      }
      if (key === KEY_SETTINGS && data) {
        if (!data.phone || data.phone.includes('34 12') || data.email?.includes('Imolode.dz')) {
          localStorage.setItem(key, JSON.stringify(fallback));
          return fallback;
        }
      }
      return data;
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
  return updated;
}

export function deleteProperty(id) {
  const list = readStorage(KEY_PROPERTIES, PROPERTIES);
  const filtered = list.filter((p) => String(p.id) !== String(id));
  writeStorage(KEY_PROPERTIES, filtered);
  return filtered.length !== list.length;
}

export function updatePropertyStatus(id, newStatus) {
  const list = readStorage(KEY_PROPERTIES, PROPERTIES);
  const item = list.find((p) => String(p.id) === String(id));
  if (item) {
    item.status = newStatus;
    writeStorage(KEY_PROPERTIES, list);
    return true;
  }
  return false;
}

/* ==========================================================================
   Demandes & Estimations
   ========================================================================== */

export function getDemandes({ type = '', status = '', search = '' } = {}) {
  let list = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);

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
      (d.subject || '').toLowerCase().includes(q)
    );
  }

  return [...list].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

export function getDemandeById(id) {
  const list = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  return list.find((d) => String(d.id) === String(id)) || null;
}

export function updateDemandeStatus(id, newStatus) {
  const list = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const item = list.find((d) => String(d.id) === String(id));
  if (item) {
    item.status = newStatus;
    writeStorage(KEY_DEMANDES, list);
    return true;
  }
  return false;
}

export function deleteDemande(id) {
  const list = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);
  const filtered = list.filter((d) => String(d.id) !== String(id));
  writeStorage(KEY_DEMANDES, filtered);
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
  return updated;
}

export function deleteUser(id) {
  const list = getUsers();
  const filtered = list.filter((u) => String(u.id) !== String(id));
  writeStorage(KEY_USERS, filtered);
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
  return updated;
}

/* ==========================================================================
   Statistiques Tableau de bord
   ========================================================================== */

export function getDashboardStats() {
  const properties = readStorage(KEY_PROPERTIES, PROPERTIES);
  const demandes = readStorage(KEY_DEMANDES, INITIAL_DEMANDES);

  const totalProperties = properties.length;
  const forSale = properties.filter((p) => p.transaction === 'Acheter').length;
  const forRent = properties.filter((p) => p.transaction === 'Louer').length;
  const available = properties.filter((p) => p.status === 'Disponible').length;

  const totalDemandes = demandes.length;
  const pendingDemandes = demandes.filter((d) => d.status === 'Nouveau' || d.status === 'En cours').length;

  const recentProperties = [...properties]
    .sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0))
    .slice(0, 5);

  const recentDemandes = [...demandes]
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
    .slice(0, 5);

  return {
    totalProperties,
    forSale,
    forRent,
    available,
    totalDemandes,
    pendingDemandes,
    recentProperties,
    recentDemandes
  };
}
