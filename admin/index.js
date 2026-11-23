// admin/index.js
import { initAdminLayout, adminIcon, formatPrice, formatDate, statusBadge, transactionBadge, escapeHTML } from '/admin/assets/js/admin.js';
import { getDashboardStats } from '/admin/data/admin-store.js';

async function renderDashboard() {
  const ready = await initAdminLayout({
    activePage: 'dashboard',
    title: 'Tableau de bord',
    action: {
      label: 'Nouveau bien',
      href: '/admin/bien-edit',
      icon: 'plus'
    }
  });

  if (!ready) return;

  const stats = getDashboardStats();
  const root = document.getElementById('dashboard-content');
  if (!root) return;

  // Calcul KPI
  const { totalProperties, forSale, forRent, available, totalDemandes, pendingDemandes, totalFichesClient = 0, pendingFichesClient = 0, recentProperties, recentDemandes, recentFichesClient = [] } = stats;

  root.innerHTML = `
    <!-- Statistiques KPI -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon stat-icon--gold">
          ${adminIcon('building', { size: 24 })}
        </div>
        <div class="stat-info">
          <p class="stat-value">${totalProperties}</p>
          <p class="stat-label">Total biens en portefeuille</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon stat-icon--blue">
          ${adminIcon('check', { size: 24 })}
        </div>
        <div class="stat-info">
          <p class="stat-value">${available}</p>
          <p class="stat-label">Biens actuellement disponibles</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon stat-icon--purple">
          ${adminIcon('dashboard', { size: 24 })}
        </div>
        <div class="stat-info">
          <p class="stat-value">${forSale} / ${forRent}</p>
          <p class="stat-label">À la vente / À la location</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon ${pendingDemandes > 0 ? 'stat-icon--gold' : 'stat-icon--green'}">
          ${adminIcon('mail', { size: 24 })}
        </div>
        <div class="stat-info">
          <p class="stat-value">${pendingDemandes}</p>
          <p class="stat-label">Demandes en attente (Total: ${totalDemandes})</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon ${pendingFichesClient > 0 ? 'stat-icon--gold' : 'stat-icon--blue'}">
          ${adminIcon('file-text', { size: 24 })}
        </div>
        <div class="stat-info">
          <p class="stat-value">${totalFichesClient}</p>
          <p class="stat-label">Fiches client enregistrées (${pendingFichesClient} en cours)</p>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div style="display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap;">
      <a href="/admin/bien-edit" class="btn btn-primary">
        ${adminIcon('plus', { size: 16 })}
        <span>Publier un nouveau bien</span>
      </a>
      <a href="/admin/fiches-client" class="btn btn-outline" style="border-color: var(--admin-gold); color: var(--admin-gold);">
        ${adminIcon('file-text', { size: 16 })}
        <span>Consulter les fiches client ${pendingFichesClient > 0 ? `<span class="sidebar-badge" style="margin-left: 4px;">${pendingFichesClient}</span>` : ''}</span>
      </a>
      <a href="/admin/demandes" class="btn btn-outline">
        ${adminIcon('mail', { size: 16 })}
        <span>Consulter toutes les demandes ${pendingDemandes > 0 ? `<span class="sidebar-badge" style="margin-left: 4px;">${pendingDemandes}</span>` : ''}</span>
      </a>
      <a href="/admin/biens" class="btn btn-outline">
        ${adminIcon('building', { size: 16 })}
        <span>Gérer l'ensemble des biens</span>
      </a>
      <a href="/admin/parametres" class="btn btn-outline">
        ${adminIcon('settings', { size: 16 })}
        <span>Paramètres de l'agence</span>
      </a>
    </div>

    <!-- Grille des 2 blocs principaux -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 24px;">
      
      <!-- Dernières demandes -->
      <div class="card">
        <div class="card-header">
          <div>
            <h2 class="card-title">Dernières demandes reçues</h2>
            <p class="card-subtitle">Contacts et estimations envoyés depuis le site</p>
          </div>
          <a href="/admin/demandes" class="btn btn-outline btn-sm">Voir tout</a>
        </div>
        <div class="card-body" style="padding: 0;">
          ${recentDemandes.length === 0 ? `
            <div class="empty-state">
              <div class="empty-state-icon">${adminIcon('mail', { size: 24 })}</div>
              <p class="empty-state-title">Aucune demande reçue</p>
              <p class="empty-state-text">Les messages de contact et formulaires d'estimation apparaîtront ici.</p>
            </div>
          ` : `
            <div class="table-responsive">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Prospect</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th style="text-align: right;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  ${recentDemandes.map((d) => `
                    <tr>
                      <td>
                        <strong style="color: var(--admin-text); display: block;">${escapeHTML(d.nom)}</strong>
                        <span style="font-size: 0.78rem; color: var(--admin-text-subtle);">${escapeHTML(d.email || d.phone || '')}</span>
                      </td>
                      <td>
                        <span class="badge ${d.type === 'Estimation' ? 'badge--info' : 'badge--neutral'}">
                          ${escapeHTML(d.type)}
                        </span>
                      </td>
                      <td style="color: var(--admin-text-muted); font-size: 0.82rem;">
                        ${formatDate(d.createdAt)}
                      </td>
                      <td>
                        ${statusBadge(d.status)}
                      </td>
                      <td style="text-align: right;">
                        <a href="/admin/demandes?id=${d.id}" class="btn-icon" title="Consulter la demande">
                          ${adminIcon('eye', { size: 16 })}
                        </a>
                      </td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
      </div>

      <!-- Derniers biens ajoutés -->
      <div class="card">
        <div class="card-header">
          <div>
            <h2 class="card-title">Derniers biens ajoutés</h2>
            <p class="card-subtitle">Propriétés récentes de votre portefeuille</p>
          </div>
          <a href="/admin/biens" class="btn btn-outline btn-sm">Gérer les biens</a>
        </div>
        <div class="card-body" style="padding: 0;">
          ${recentProperties.length === 0 ? `
            <div class="empty-state">
              <div class="empty-state-icon">${adminIcon('building', { size: 24 })}</div>
              <p class="empty-state-title">Aucun bien enregistré</p>
              <p class="empty-state-text">Commencez par ajouter votre première propriété au catalogue.</p>
              <a href="/admin/bien-edit" class="btn btn-primary btn-sm">Ajouter un bien</a>
            </div>
          ` : `
            <div class="table-responsive">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Bien</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th style="text-align: right;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  ${recentProperties.map((p) => {
                    const thumb = p.images && p.images[0] ? p.images[0] : '';
                    return `
                      <tr>
                        <td>
                          <div class="table-property-cell">
                            ${thumb ? `<img src="${escapeHTML(thumb)}" alt="" class="table-thumb" loading="lazy">` : ''}
                            <div>
                              <a href="/admin/bien-edit?id=${p.id}" class="table-property-title" title="${escapeHTML(p.title)}">
                                ${escapeHTML(p.title)}
                              </a>
                              <span class="table-property-ref">${escapeHTML(p.reference || '')} · ${escapeHTML(p.location?.city || '')}</span>
                            </div>
                          </div>
                        </td>
                        <td style="font-weight: 600; white-space: nowrap;">
                          ${formatPrice(p.price, p.pricePeriod)}
                        </td>
                        <td>
                          ${statusBadge(p.status)}
                        </td>
                        <td style="text-align: right;">
                          <div class="table-actions" style="justify-content: flex-end;">
                            <a href="/client/bien?id=${p.id}" target="_blank" rel="noopener" class="btn-icon" title="Voir sur le site public">
                              ${adminIcon('external', { size: 15 })}
                            </a>
                            <a href="/admin/bien-edit?id=${p.id}" class="btn-icon" title="Modifier">
                              ${adminIcon('edit', { size: 15 })}
                            </a>
                          </div>
                        </td>
                      </tr>
                    `;
                  }).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
      </div>

    </div>

    <!-- Derniers dossiers Fiches client -->
    <div class="card" style="margin-top: 24px;">
      <div class="card-header">
        <div>
          <h2 class="card-title">Dossiers Fiches Client récents</h2>
          <p class="card-subtitle">Constitutions de dossiers officiels avec pièces d'identité et signatures électroniques</p>
        </div>
        <a href="/admin/fiches-client" class="btn btn-outline btn-sm">Voir toutes les fiches</a>
      </div>
      <div class="card-body" style="padding: 0;">
        ${recentFichesClient.length === 0 ? `
          <div class="empty-state">
            <div class="empty-state-icon">${adminIcon('file-text', { size: 24 })}</div>
            <p class="empty-state-title">Aucune fiche client enregistrée</p>
            <p class="empty-state-text">Les dossiers soumis depuis la page publique apparaîtront ici.</p>
          </div>
        ` : `
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Date & Réf</th>
                  <th>Client</th>
                  <th>Opération</th>
                  <th>Type de bien</th>
                  <th>Statut</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                ${recentFichesClient.map((f) => `
                  <tr>
                    <td>
                      <div style="font-weight: 600; color: var(--admin-text); font-size: 0.85rem;">${formatDate(f.createdAt)}</div>
                      <span style="font-size: 0.76rem; font-family: monospace; color: var(--admin-gold); font-weight: 600;">${escapeHTML(f.reference || '')}</span>
                    </td>
                    <td>
                      <div style="font-weight: 600; color: var(--admin-text);">${escapeHTML(f.nom || '')} ${escapeHTML(f.prenom || '')}</div>
                      <div style="font-size: 0.78rem; color: var(--admin-text-muted);">${escapeHTML(f.telephone || '')} · ${escapeHTML(f.email || '')}</div>
                    </td>
                    <td>
                      <span class="badge ${f.transaction === 'Offre' ? 'badge--sale' : 'badge--info'}">${escapeHTML(f.transaction || 'Demande')}</span>
                    </td>
                    <td style="font-weight: 500;">
                      ${escapeHTML(f.typeBien || '—')}
                    </td>
                    <td>
                      ${statusBadge(f.status || 'Nouveau')}
                    </td>
                    <td style="text-align: right;">
                      <a href="/admin/fiches-client?id=${f.id}" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 0.78rem;">
                        ${adminIcon('eye', { size: 14 })} Consulter
                      </a>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `}
      </div>
    </div>
  `;
}

renderDashboard();
