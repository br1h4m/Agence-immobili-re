// assets/js/filters.js
import { PROPERTIES } from '/client/data/properties.js';
import { qs, qsa, formatPropertyPrice, formatLocation, formatSurface,
         propertyUrl, icon, resizeImage, escapeHTML } from '/client/includes/helpers.js';
const DEFAULT_STATE = {
  transaction: 'Acheter',
  type: '',
  wilaya: '',
  city: '',
  minPrice: null,
  maxPrice: null,
  minSurface: null,
  maxSurface: null,
  bedrooms: null,
  bathrooms: null,
  amenities: [],
  q: ''
};

export function getFilterOptions() {
  const wilayas = [...new Set(PROPERTIES.map((p) => p.location.wilaya))].sort();
  const cities = [...new Set(PROPERTIES.map((p) => p.location.city))].sort();
  return { wilayas, cities };
}

export function readStateFromURL() {
  const params = new URLSearchParams(window.location.search);
  const state = { ...DEFAULT_STATE };
  if (params.has('transaction')) state.transaction = params.get('transaction');
  ['type','wilaya','city'].forEach((k) => { if (params.has(k)) state[k] = params.get(k); });
  ['minPrice','maxPrice','minSurface','maxSurface','bedrooms','bathrooms'].forEach((k) => {
    if (params.has(k)) state[k] = Number(params.get(k));
  });
  if (params.has('q')) state.q = params.get('q');
  if (params.has('amenities')) state.amenities = params.get('amenities').split(',');
  return state;
}

function writeStateToURL(state) {
  const params = new URLSearchParams();
  Object.entries(state).forEach(([k, v]) => {
    if (v === null || v === '' || (Array.isArray(v) && !v.length)) return;
    if (Array.isArray(v)) params.set(k, v.join(','));
    else params.set(k, String(v));
  });
  const qs = params.toString();
  const url = `${window.location.pathname}${qs ? `?${qs}` : ''}`;
  window.history.replaceState({}, '', url);
}

export function filterProperties(state, properties = PROPERTIES) {
  return properties.filter((p) => {
    if (state.transaction && p.transaction !== state.transaction) return false;
    if (state.type && p.type !== state.type) return false;
    if (state.wilaya && p.location.wilaya !== state.wilaya) return false;
    if (state.city && p.location.city !== state.city) return false;
    if (state.minPrice != null && p.price < state.minPrice) return false;
    if (state.maxPrice != null && p.price > state.maxPrice) return false;
    if (state.minSurface != null && p.surface < state.minSurface) return false;
    if (state.maxSurface != null && p.surface > state.maxSurface) return false;
    if (state.bedrooms != null && (p.bedrooms || 0) < state.bedrooms) return false;
    if (state.bathrooms != null && (p.bathrooms || 0) < state.bathrooms) return false;
    if (state.amenities.length && !state.amenities.every((a) => (p.features || []).includes(a))) return false;
    if (state.q) {
      const q = state.q.toLowerCase();
      const haystack = [
        p.title, p.type, p.location.city, p.location.district, p.location.wilaya
      ].join(' ').toLowerCase();
      if (!haystack.includes(q)) return false;
    }
    return true;
  });
}

export function sortProperties(list, sort) {
  const copy = [...list];
  switch (sort) {
    case 'price-asc': return copy.sort((a, b) => a.price - b.price);
    case 'price-desc': return copy.sort((a, b) => b.price - a.price);
    case 'surface-asc': return copy.sort((a, b) => a.surface - b.surface);
    case 'surface-desc': return copy.sort((a, b) => b.surface - a.surface);
    default: return copy.sort((a, b) => (b.createdAt || '').localeCompare(a.createdAt || ''));
  }
}

export function initBiensPage() {
  const form = qs('[data-filters-form]');
  const resultsSlot = qs('#properties-results');
  const countSlot = qs('#properties-count');
  const sortSelect = qs('#properties-sort');
  const loadMoreBtn = qs('#properties-load-more');
  if (!form || !resultsSlot) return;

  let state = readStateFromURL();
  let page = 1;
  const perPage = 9;
  let currentList = [];
  let sort = 'recent';

  hydrateForm(state);

  const render = () => {
    const filtered = sortProperties(filterProperties(state), sort);
    currentList = filtered;
    const sliced = filtered.slice(0, page * perPage);

    if (countSlot) {
      countSlot.textContent = `${filtered.length} bien${filtered.length > 1 ? 's' : ''} disponible${filtered.length > 1 ? 's' : ''}`;
    }

    if (!sliced.length) {
      resultsSlot.innerHTML = `
        <div class="property-grid-empty">
          <p>Aucun bien ne correspond à votre recherche.</p>
          <button type="button" class="btn btn-outline" data-filters-reset-inline>Réinitialiser les filtres</button>
        </div>`;
    } else {
      resultsSlot.innerHTML = `
        <div class="property-grid">
          ${sliced.map((p) => cardHTML(p)).join('')}
        </div>`;
    }

    if (loadMoreBtn) {
      loadMoreBtn.hidden = sliced.length >= filtered.length;
    }
  };

  const applyFromForm = () => {
    const data = new FormData(form);
    state = {
      transaction: data.get('transaction') || 'Acheter',
      type: data.get('type') || '',
      wilaya: data.get('wilaya') || '',
      city: data.get('city') || '',
      minPrice: numOrNull(data.get('minPrice')),
      maxPrice: numOrNull(data.get('maxPrice')),
      minSurface: numOrNull(data.get('minSurface')),
      maxSurface: numOrNull(data.get('maxSurface')),
      bedrooms: numOrNull(data.get('bedrooms')),
      bathrooms: numOrNull(data.get('bathrooms')),
      amenities: data.getAll('amenities'),
      q: state.q
    };
    page = 1;
    writeStateToURL(state);
    render();
    qs('[data-filters]')?.classList.remove('is-open');
    document.documentElement.classList.remove('no-scroll');
  };

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    applyFromForm();
  });

  form.addEventListener('change', (e) => {
    if (e.target.name === 'wilaya' || e.target.name === 'city' || e.target.name === 'transaction') {
      // applique immédiatement pour ces champs de haut niveau
      applyFromForm();
    }
  });

  qs('[data-filters-reset]')?.addEventListener('click', () => {
    form.reset();
    state = { ...DEFAULT_STATE };
    page = 1;
    writeStateToURL(state);
    hydrateForm(state);
    render();
  });

  resultsSlot.addEventListener('click', (e) => {
    if (e.target.matches('[data-filters-reset-inline]')) {
      form.reset();
      state = { ...DEFAULT_STATE };
      page = 1;
      writeStateToURL(state);
      hydrateForm(state);
      render();
    }
  });

  sortSelect?.addEventListener('change', () => {
    sort = sortSelect.value;
    page = 1;
    render();
  });

  loadMoreBtn?.addEventListener('click', () => {
    page += 1;
    render();
  });

  render();

  /* ---------- Helpers internes ---------- */
  function hydrateForm(s) {
    if (s.transaction) {
      const radio = form.querySelector(`input[name="transaction"][value="${s.transaction}"]`);
      if (radio) radio.checked = true;
    }
    ['type','wilaya','city'].forEach((k) => {
      const field = form.querySelector(`[name="${k}"]`);
      if (field) field.value = s[k] || '';
    });
    ['minPrice','maxPrice','minSurface','maxSurface','bedrooms','bathrooms'].forEach((k) => {
      const field = form.querySelector(`[name="${k}"]`);
      if (field && s[k] != null) field.value = s[k];
    });
    form.querySelectorAll('input[name="amenities"]').forEach((cb) => {
      cb.checked = s.amenities.includes(cb.value);
    });
  }

  function numOrNull(v) {
    const n = Number(v);
    return v === '' || v === null || Number.isNaN(n) ? null : n;
  }

  function cardHTML(p) {
    const cover = p.images?.[0] || '';
    const label = p.transaction === 'Louer' ? 'À louer' : 'À vendre';
    const statusBadge = p.status === 'Réservé'
      ? '<span class="badge badge-status badge-status--reserved">Réservé</span>'
      : p.status === 'Vendu'
        ? '<span class="badge badge-status badge-status--sold">Vendu</span>'
        : p.status === 'Loué'
          ? '<span class="badge badge-status badge-status--rented">Loué</span>'
          : '';
    const newBadge = p.isNew ? '<span class="badge badge-new">Nouveau</span>' : '';
    const specs = [
      p.surface ? formatSurface(p.surface) : null,
      p.bedrooms ? `${p.bedrooms} ch.` : null,
      p.bathrooms ? `${p.bathrooms} sdb` : null
    ].filter(Boolean);
    return `
      <article class="property-card">
        <a href="${propertyUrl(p.id)}" class="property-card-media">
          <img src="${resizeImage(cover, 800)}" alt="${escapeHTML(p.title)}" loading="lazy">
          <div class="property-card-badges">
            <span class="badge badge-transaction">${label}</span>
            ${newBadge}${statusBadge}
          </div>
        </a>
        <div class="property-card-body">
          <h3 class="property-card-title"><a href="${propertyUrl(p.id)}">${escapeHTML(p.title)}</a></h3>
          <p class="property-card-location">${icon('pin', { size: 14 })}<span>${escapeHTML(formatLocation(p.location))}</span></p>
          <p class="property-card-price">${formatPropertyPrice(p)}</p>
          <ul class="property-card-specs">${specs.map((s) => `<li>${escapeHTML(s)}</li>`).join('')}</ul>
        </div>
        <a href="${propertyUrl(p.id)}" class="property-card-link"><span>Voir le bien</span>${icon('arrow-right', { size: 14 })}</a>
      </article>`;
  }
}