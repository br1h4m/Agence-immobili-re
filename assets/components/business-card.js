// assets/components/business-card.js
// Carte de visite officielle de l'Agence Immobilière Agréée Imolode.

import { CONFIG } from '/client/data/config.js';
import { asset, icon, telLink, escapeHTML } from '/client/includes/helpers.js';

export function BusinessCard() {
  return `
    <div class="agency-business-card">
      <div class="agency-business-card-header">
        <div class="agency-business-card-brand">
          <img src="${asset('images/logo.svg')}" alt="Logo Imolode" class="agency-business-card-logo" width="48" height="48">
          <div>
            <span class="agency-business-card-tag">Carte de visite officielle</span>
            <h3 class="agency-business-card-title">${escapeHTML(CONFIG.CARD_TITLE || CONFIG.AGENCY_LEGAL)}</h3>
          </div>
        </div>
        <div class="agency-business-card-manager">
          <span class="agency-manager-name">${escapeHTML(CONFIG.MANAGER)}</span>
          <span class="agency-manager-role">${escapeHTML(CONFIG.MANAGER_ROLE)}</span>
        </div>
      </div>

      <div class="agency-business-card-body">
        <div class="agency-business-card-col">
          <div class="agency-card-item">
            <span class="agency-card-item-icon">${icon('pin', { size: 16 })}</span>
            <div>
              <span class="agency-card-label">Adresse</span>
              <p class="agency-card-value">${escapeHTML(CONFIG.ADDRESS)}</p>
            </div>
          </div>
          <div class="agency-card-item">
            <span class="agency-card-item-icon">${icon('phone', { size: 16 })}</span>
            <div>
              <span class="agency-card-label">Mob</span>
              <p class="agency-card-value"><a href="${telLink(CONFIG.PHONE)}">${escapeHTML(CONFIG.PHONE)}</a></p>
            </div>
          </div>
        </div>

        <div class="agency-business-card-col">
          <div class="agency-card-item">
            <span class="agency-card-item-icon">${icon('mail', { size: 16 })}</span>
            <div>
              <span class="agency-card-label">E-mail</span>
              <p class="agency-card-value"><a href="mailto:${escapeHTML(CONFIG.EMAIL)}">${escapeHTML(CONFIG.EMAIL)}</a></p>
            </div>
          </div>
          <div class="agency-card-item">
            <span class="agency-card-item-icon">${icon('facebook', { size: 16 })}</span>
            <div>
              <span class="agency-card-label">Facebook</span>
              <p class="agency-card-value"><a href="${CONFIG.SOCIAL.facebook}" target="_blank" rel="noopener">${escapeHTML(CONFIG.SOCIAL.facebookLabel)}</a></p>
            </div>
          </div>
        </div>
      </div>

      <div class="agency-business-card-legal">
        <div class="agency-legal-header">
          <strong>Art. 34-(Décret réglementant la profession de l'Agence immobilière)</strong>
        </div>
        <p class="agency-legal-text">
          L'agent immobilier a droit, dans le cadre de l'exercice de sa profession à une rémunération. Pour ce qui concerne l'agence et le courtier immobilier, Lorsque la valeur du bien à vendre équivaut à :
        </p>
        <ul class="agency-legal-rates">
          <li><strong>1.000.000 DA :</strong> 3%</li>
          <li><strong>Inférieur ou égal à 5.000.000 DA :</strong> 2%</li>
          <li><strong>Supérieur à 5.000.000 DA :</strong> 1%</li>
        </ul>
        <p class="agency-legal-rent">
          Lorsqu'il s'agit d'un bien à louer, sa rémunération équivaut à <strong>un (1) mois de location par année de location</strong>.
        </p>
      </div>
    </div>
  `;
}
