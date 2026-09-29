// assets/components/team-card.js
import { icon, telLink, escapeHTML, resizeImage } from '../../client/includes/helpers.js';

export function TeamCard(member) {
  return `
    <article class="team-card">
      <div class="team-card-photo">
        <img src="${resizeImage(member.photo, 600)}" alt="${escapeHTML(member.nom)}" loading="lazy">
      </div>
      <div class="team-card-body">
        <h3 class="team-card-name">${escapeHTML(member.nom)}</h3>
        <p class="team-card-role">${escapeHTML(member.poste)}</p>
        <ul class="team-card-contact">
          <li><a href="${telLink(member.phone)}">${icon('phone', { size: 14 })}<span>${escapeHTML(member.phone)}</span></a></li>
          <li><a href="mailto:${escapeHTML(member.email)}">${icon('mail', { size: 14 })}<span>${escapeHTML(member.email)}</span></a></li>
        </ul>
      </div>
    </article>
  `;
}