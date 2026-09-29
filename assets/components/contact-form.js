// assets/components/contact-form.js
import { icon } from '/client/includes/helpers.js';

let formId = 0;

export function ContactForm({
  title = 'Envoyer une demande',
  subject = '',
  contextLabel = '',
  compact = false
} = {}) {
  formId += 1;
  const uid = `cf${formId}`;

  return `
    <form class="contact-form${compact ? ' contact-form--compact' : ''}" data-contact-form novalidate>
      <div class="form-head">
        <h3 class="form-title">${title}</h3>
        ${contextLabel ? `<p class="form-context">${contextLabel}</p>` : ''}
      </div>

      <input type="hidden" name="subject" value="${subject}">

      <div class="form-grid">
        <label class="form-field">
          <span>Nom</span>
          <input id="${uid}-lastname" name="lastname" type="text" required autocomplete="family-name">
        </label>
        <label class="form-field">
          <span>Prénom</span>
          <input id="${uid}-firstname" name="firstname" type="text" required autocomplete="given-name">
        </label>
      </div>

      <div class="form-grid">
        <label class="form-field">
          <span>Téléphone</span>
          <input name="phone" type="tel" required autocomplete="tel">
        </label>
        <label class="form-field">
          <span>Email</span>
          <input name="email" type="email" required autocomplete="email">
        </label>
      </div>

      <label class="form-field">
        <span>Message</span>
        <textarea name="message" rows="4" required></textarea>
      </label>

      <button type="submit" class="btn btn-primary btn-block">
        ${icon('check', { size: 16 })}<span>Envoyer ma demande</span>
      </button>

      <p class="form-note">Vos informations restent confidentielles et ne sont utilisées que pour vous recontacter.</p>

      <div class="form-feedback" data-form-feedback hidden>
        <p>Merci, votre demande a bien été enregistrée. Un conseiller vous contactera prochainement.</p>
      </div>
    </form>
  `;
}