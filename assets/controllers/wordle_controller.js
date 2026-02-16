import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        maxRows: { type: Number, default: 6},
        maxCols: { type: Number, default: 5},
        slotId: { type: String, default: ''}
    }

    connect() {
        this.currentRow = 0;
        this.currentCol = 0;
        this.gameOver = false;
        this.isRevealing = false;
        this.guesses = [];

        this.handlePhysicalKeyboard = this.handlePhysicalKeyboard.bind(this);
        document.addEventListener('keydown', this.handlePhysicalKeyboard);

        this.restoreState();
    }

    disconnect() {
        document.removeEventListener('keydown', this.handlePhysicalKeyboard);
    }

    getStorageKey() {
        return `itordle_${this.slotIdValue}`;
    }

    saveState() {
        const state = {
            slotId: this.slotIdValue,
            guesses: this.guesses,
            gameOver: this.gameOver
        };

        localStorage.setItem(this.getStorageKey(), JSON.stringify(state));
    }

    restoreState() {
        if (!this.slotIdValue) return;

        const raw = localStorage.getItem(this.getStorageKey());
        if (!raw) return;

        try {
            const state = JSON.parse(raw);

            if (state.slotId !== this.slotIdValue) return;

            for (const guess of state.guesses) {
                for (let col = 0; col < guess.result.length; col++) {
                    const tile = this.getTile(this.currentRow, col);
                    tile.textContent = guess.result[col].letter;
                    tile.classList.add(guess.result[col].status);
                }
                this.updateKeyboard(guess.result);
                this.currentRow++;
            }

            this.guesses = state.guesses;
            this.currentCol = 0;
            this.gameOver = state.gameOver;

            if (this.gameOver) {
                const lastGuess = state.guesses[state.guesses.length - 1];
                const won = lastGuess.won;

                if (won) {
                    this.showMessage('Good job');
                } else {
                    this.showMessage(`The word was ${lastGuess.answer}`);
                }
            }
        } catch (e) {

        }

        this.cleanupOldSlots();
    }

    cleanupOldSlots() {
        const currentKey = this.getStorageKey();

        for (let i = localStorage.length - 1; i >= 0; i--) {
            const key = localStorage.key(i);
            if (key.startsWith('itordle_') && key !== currentKey) {
                localStorage.removeItem(key);
            }
        }
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
        tile.classList.remove('filled');
        void tile.offsetWidth;
        tile.classList.add('filled');
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

    getRow(row) {
        return this.element.querySelector(`.row[data-row="${row}"]`);
    }

    shakeRow() {
        const row = this.getRow(this.currentRow);
        if (!row) return;

        row.classList.remove('shake');
        void row.offsetWidth;
        row.classList.add('shake');

        row.addEventListener('animationend', () => {
            row.classList.remove('shake');
        }, { once: true });
    }

    async submitGuess() {
        if (this.gameOver || this.isRevealing) return;
        if (this.currentCol < this.maxColsValue) {
            this.shakeRow();
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
                this.shakeRow();
                this.showMessage(data.error || 'Something went wrong');
                return;
            }

            await this.revealRow(data.result);
            this.updateKeyboard(data.result);

            this.guesses.push({
                word: guess,
                result: data.result,
                won: data.won,
                answer: data.answer || null,
            });

            this.isRevealing = false;

            if (data.won) {
                this.gameOver = true;
                this.saveState();
                await this.danceRow();
                this.showMessage('Good job');
            } else if (this.currentRow >= this.maxRowsValue - 1) {
                this.gameOver = true;
                this.saveState();
                this.showMessage(`The word was ${data.answer}`);
            } else {
                this.saveState();
                this.currentRow++;
                this.currentCol = 0;
            }
        } catch (error) {
            this.isRevealing = false;
            this.showMessage('Connection Error');
        }
    }

    async revealRow(result) {
        const FLIP_HALF = 200;
        const STAGGER = 280;

        const promises = result.map((item, col) => {
            return new Promise(resolve => {
                setTimeout(() => {
                    const tile = this.getTile(this.currentRow, col);
                    const status = item.status;

                    tile.classList.add('revealing');

                    setTimeout(() => {
                        tile.classList.remove('revealing', 'filled');
                        tile.classList.add(status, 'reveal-out');

                        setTimeout(() => {
                            tile.classList.remove('reveal-out');
                            resolve();
                        }, FLIP_HALF);
                    }, FLIP_HALF);
                }, STAGGER * col);
            });
        });

        await Promise.all(promises);
        await this.delay(80);
    }

    async danceRow() {
        await this.delay(200);

        const STAGGER = 100;
        const promises = [];

        for (let col = 0; col < this.maxColsValue; col++) {
            promises.push(new Promise(resolve => {
                setTimeout(() => {
                    const tile = this.getTile(this.currentRow, col);
                    tile.classList.add('dance');

                    tile.addEventListener('animationend', () => {
                        tile.classList.remove('dance');
                        resolve();
                    }, { once: true });
                }, STAGGER * col);
            }));
        }

        await Promise.all(promises);
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
        toast.classList.remove('show');
        void toast.offsetWidth;
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