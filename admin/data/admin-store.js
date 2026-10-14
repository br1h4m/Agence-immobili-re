// admin/data/admin-store.js
// MODULE ES NAVIGATEUR — gestion de la persistance locale (localStorage).
// DONNEES MOCKEES - A SUPPRIMER quand le backend sera pret

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
    id: 1727720001,
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
    id: 1727720002,
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
  },
  {
    id: 1727720003,
    reference: 'IML-DOS-2026-1044',
    nom: 'Meziani',
    prenom: 'Sofiane',
    telephone: '0770 45 67 89',
    email: 'sofiane.meziani@yahoo.fr',
    transaction: 'Demande',
    typeBien: 'Commerce',
    pieceIdentite: {
      name: 'registre_commerce_cni.pdf',
      size: 820000,
      type: 'application/pdf',
      dataUrl: null
    },
    signature: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="80"><path d="M 25 55 Q 80 15 120 45 T 175 35" fill="none" stroke="%231C1C1C" stroke-width="2.5"/></svg>',
    engagementAccepte: true,
    status: 'Validé',
    createdAt: '2026-10-12T11:10:00Z'
  }
];

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
  return updated;
}

export function updateFicheClientStatus(id, newStatus) {
  const list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const item = list.find((f) => String(f.id) === String(id));
  if (item) {
    item.status = newStatus;
    writeStorage(KEY_FICHES_CLIENT, list);
    return true;
  }
  return false;
}

export function deleteFicheClient(id) {
  const list = readStorage(KEY_FICHES_CLIENT, INITIAL_FICHES_CLIENT);
  const filtered = list.filter((f) => String(f.id) !== String(id));
  writeStorage(KEY_FICHES_CLIENT, filtered);
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
