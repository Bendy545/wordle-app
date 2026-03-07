import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal', 'terminal']

    connect() {
        this.handleEscape = this.handleEscape.bind(this);
        this.hasAnimated = false;
    }

    open() {
        this.modalTarget.classList.add('visible');
        document.addEventListener('keydown', this.handleEscape);

        if (!this.hasAnimated) {
            this.hasAnimated = true;
            this.runTerminalAnimation();
        }
    }

    close() {
        this.modalTarget.classList.remove('visible');
        document.removeEventListener('keydown', this.handleEscape);
    }

    backdropClose(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    runTerminalAnimation() {
        const lines = this.terminalTarget.querySelectorAll('.term-line');
        lines.forEach((line, i) => {
            line.style.animationDelay = `${i * 0.07}s`;
            line.classList.add('term-visible');
        });
    }
}