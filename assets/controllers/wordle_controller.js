import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        maxRows: { type: Number, default: 6},
        maxCols: { type: Number, default: 5},
    }

    connect() {
        this.currentRow = 0;
        this.currentCol = 0;
        this.gameOver = false;
        this.isRevealing = false;

        this.handlePhysicalKeyboard = this.handlePhysicalKeyboard.bind(this);
        document.addEventListener('keydown', this.handlePhysicalKeyboard);
    }

    disconnect() {
        document.removeEventListener('keydown', this.handlePhysicalKeyboard);
    }

    handlePhysicalKeyboard(event) {
        if (event.ctrlKey || event.metaKey || event.altKey) return;

        const key = event.key;

        if (key === 'Enter') {
            this.submitGuess();
        } else if (key === 'Backspace') {
            this.deleteLetter();
        } else if (/^[a-zA-Z]$/.test(key)) {
            this.addLetter(key.toUpperCase());
        }
    }

    keyPress(event) {
        const key = event.currentTarget.dataset.key;

        if (key === 'Enter') {
            this.submitGuess();
        } else if (key === 'Backspace') {
            this.deleteLetter();
        } else {
            this.addLetter(key);
        }
    }

    addLetter(letter) {
        if (this.gameOver || this.isRevealing) return;
        if (this.currentCol >= this.maxColsValue) return;

        const tile = this.getTile(this.currentRow, this.currentCol);
        tile.textContent = letter;
        tile.classList.add('filled');
        tile.classList.remove('pop');
        void tile.offsetWidth;
        tile.classList.add('pop');
        this.currentCol++;
    }

    deleteLetter() {
        if (this.gameOver || this.isRevealing) return;
        if (this.currentCol <= 0) return;

        this.currentCol--;
        const tile = this.getTile(this.currentRow, this.currentCol);
        tile.textContent = '';
        tile.classList.remove('filled');
    }

    getCurrentWord() {
        let word = '';
        for (let col = 0; col < this.maxColsValue; col++) {
            word += this.getTile(this.currentRow, col).textContent;
        }

        return word;
    }

    getTile(row, col) {
        return this.element.querySelector(`.tile[data-row="${row}"][data-col="${col}"]`);
    }

    async submitGuess() {
        if (this.gameOver || this.isRevealing) return;
        if (this.currentCol < this.maxColsValue) {
            this.showMessage('Not enough letters');
            return;
        }

        const guess = this.getCurrentWord();

        try {
            this.isRevealing = true;

            const response = await fetch('/api/guess', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({guess}),
            })

            const data = await response.json();

            if (!response.ok) {
                this.isRevealing = false;
                this.showMessage(data.error || 'Something went wrong');
                return;
            }

            await this.revealRow(data.result);
            this.updateKeyboard(data.result);

            this.isRevealing = false;

            if (data.won) {
                this.gameOver = true;
                this.showMessage('Good job');
            } else if (this.currentRow >= this.maxRowsValue - 1) {
                this.gameOver = true;
                this.showMessage(`The word was ${data.answer}`);
            } else {
                this.currentRow++;
                this.currentCol = 0;
            }
        } catch (error) {
            this.isRevealing = false;
            this.showMessage('Connection Error');
        }
    }

    async revealRow(result) {
        for (let col = 0; col < result.length; col++) {
            const tile = this.getTile(this.currentRow, col);
            const status = result[col].status;

            await this.delay(300 * col);

            tile.classList.add('revealing');

            await this.delay(250);

            tile.classList.add(status);
            tile.classList.remove('filled');

            tile.classList.remove('revealing');
        }

        await this.delay(100);
    }

    updateKeyboard(result) {
        const priority = { 'correct': 3, 'present': 2, 'absent': 1 };

        result.forEach(({ letter, status }) => {
            const key = this.element.querySelector(`.key[data-key="${letter}"]`);
            if (!key) return;

            const current = key.dataset.status || '';
            const currentPriority = priority[current] || 0;
            const newPriority = priority[status] || 0;

            if (newPriority > currentPriority) {
                key.classList.remove('correct', 'present', 'absent');
                key.classList.add(status);
                key.dataset.status = status;
            }
        });
    }

    showMessage(text) {
        let toast = this.element.querySelector('.toast');

        if (!toast) {
            toast = document.createElement('div');
            toast.classList.add('toast');
            this.element.prepend(toast);
        }

        toast.textContent = text;
        toast.classList.add('show');

        clearTimeout(this.toastTimeout);
        this.toastTimeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 1500);
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}