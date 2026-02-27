import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if (!document.cookie.match(/analytics_consent=/)) {
            this.element.classList.add('visible');
        }
    }
    accept() { this.setCookie('accepted'); }
    decline() { this.setCookie('declined'); }
    setCookie(value) {
        document.cookie = `analytics_consent=${value}; path=/; max-age=31536000; SameSite=Lax`;
        this.element.classList.remove('visible');
    }
}