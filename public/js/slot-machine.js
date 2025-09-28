class SlotMachine {
    constructor() {
        this.currentBet = window.gameData.minBet;
        this.balance = parseFloat(document.getElementById('balance').textContent.replace('$', ''));
        this.isSpinning = false;
        
        this.initializeElements();
        this.bindEvents();
        this.updateBetDisplay();
    }

    initializeElements() {
        this.balanceElement = document.getElementById('balance');
        this.betDisplayElement = document.getElementById('bet-display');
        this.spinButton = document.getElementById('spin-button');
        this.betIncreaseButton = document.getElementById('bet-increase');
        this.betDecreaseButton = document.getElementById('bet-decrease');
        this.winDisplay = document.getElementById('win-display');
        this.winAmount = document.getElementById('win-amount');
        this.lastWin = document.getElementById('last-win');
        this.lastWinDetails = document.getElementById('last-win-details');
        this.reelsContainer = document.getElementById('reels');
    }

    bindEvents() {
        this.spinButton.addEventListener('click', () => this.spin());
        this.betIncreaseButton.addEventListener('click', () => this.increaseBet());
        this.betDecreaseButton.addEventListener('click', () => this.decreaseBet());
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space' && !this.isSpinning) {
                e.preventDefault();
                this.spin();
            }
        });
    }

    increaseBet() {
        const newBet = this.currentBet + window.gameData.stepBet;
        if (newBet <= window.gameData.maxBet && newBet <= this.balance) {
            this.currentBet = newBet;
            this.updateBetDisplay();
        }
    }

    decreaseBet() {
        const newBet = this.currentBet - window.gameData.stepBet;
        if (newBet >= window.gameData.minBet) {
            this.currentBet = newBet;
            this.updateBetDisplay();
        }
    }

    updateBetDisplay() {
        this.betDisplayElement.textContent = `$${this.currentBet.toFixed(2)}`;
        
        // Update button states
        this.betIncreaseButton.disabled = 
            this.currentBet >= window.gameData.maxBet || 
            this.currentBet + window.gameData.stepBet > this.balance;
        
        this.betDecreaseButton.disabled = this.currentBet <= window.gameData.minBet;
        
        // Update spin button state
        this.spinButton.disabled = this.currentBet > this.balance || this.isSpinning;
    }

    async spin() {
        if (this.isSpinning || this.currentBet > this.balance) {
            return;
        }

        this.isSpinning = true;
        this.updateSpinButton(true);
        this.hideWinDisplay();
        
        // Start reel animation
        this.startReelAnimation();

        try {
            const response = await fetch(window.gameData.spinUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    betAmount: this.currentBet,
                    activePaylines: null, // Use all paylines
                    useFreeSpins: false
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Spin failed');
            }

            // Wait for animation to complete
            await this.sleep(2000);
            
            // Stop reel animation and show results
            this.stopReelAnimation();
            this.displayResults(data.result);
            
        } catch (error) {
            console.error('Spin error:', error);
            this.showError(error.message);
            this.stopReelAnimation();
        } finally {
            this.isSpinning = false;
            this.updateSpinButton(false);
        }
    }

    startReelAnimation() {
        const reels = document.querySelectorAll('.reel');
        reels.forEach(reel => {
            reel.classList.add('spinning');
            
            // Randomize symbols during spin
            const symbols = reel.querySelectorAll('.symbol');
            const animationInterval = setInterval(() => {
                symbols.forEach(symbol => {
                    const randomSymbol = window.gameData.symbols[
                        Math.floor(Math.random() * window.gameData.symbols.length)
                    ];
                    symbol.textContent = randomSymbol;
                });
            }, 100);
            
            reel.dataset.animationInterval = animationInterval;
        });
    }

    stopReelAnimation() {
        const reels = document.querySelectorAll('.reel');
        reels.forEach(reel => {
            reel.classList.remove('spinning');
            
            // Clear animation interval
            if (reel.dataset.animationInterval) {
                clearInterval(parseInt(reel.dataset.animationInterval));
                delete reel.dataset.animationInterval;
            }
        });
    }

    displayResults(result) {
        // Update balance
        this.balance = result.newBalance;
        this.balanceElement.textContent = `$${this.balance.toFixed(2)}`;
        
        // Display reel results
        if (result.gameData && result.gameData.visibleSymbols) {
            this.displayReelResults(result.gameData.visibleSymbols);
        }
        
        // Show win if any
        if (result.winAmount > 0) {
            this.showWin(result.winAmount);
            this.highlightWinningLines(result.gameData.winningLines || []);
        }
        
        // Update last win info
        this.updateLastWin(result);
        
        // Update bet controls
        this.updateBetDisplay();
    }

    displayReelResults(visibleSymbols) {
        Object.entries(visibleSymbols).forEach(([reelIndex, symbols]) => {
            symbols.forEach((symbol, rowIndex) => {
                const symbolElement = document.querySelector(
                    `[data-reel="${reelIndex}"][data-row="${rowIndex}"]`
                );
                if (symbolElement) {
                    symbolElement.textContent = symbol;
                }
            });
        });
    }

    highlightWinningLines(winningLines) {
        // Clear previous winning highlights
        document.querySelectorAll('.symbol.winning').forEach(el => {
            el.classList.remove('winning');
        });

        // Highlight winning symbols
        winningLines.forEach(line => {
            if (line.positions) {
                line.positions.forEach(pos => {
                    const symbolElement = document.querySelector(
                        `[data-reel="${pos.reel}"][data-row="${pos.row}"]`
                    );
                    if (symbolElement) {
                        symbolElement.classList.add('winning');
                    }
                });
            }
        });
    }

    showWin(amount) {
        this.winAmount.textContent = `$${amount.toFixed(2)}`;
        this.winDisplay.classList.remove('hidden');
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            this.hideWinDisplay();
        }, 3000);
    }

    hideWinDisplay() {
        this.winDisplay.classList.add('hidden');
        
        // Clear winning highlights
        document.querySelectorAll('.symbol.winning').forEach(el => {
            el.classList.remove('winning');
        });
    }

    updateLastWin(result) {
        const details = [
            `Bet: $${result.betAmount.toFixed(2)}`,
            `Win: $${result.winAmount.toFixed(2)}`,
            `Balance: $${result.newBalance.toFixed(2)}`
        ];

        if (result.gameData.winningLines && result.gameData.winningLines.length > 0) {
            details.push(`Lines: ${result.gameData.winningLines.length}`);
        }

        if (result.gameData.multiplier && result.gameData.multiplier > 1) {
            details.push(`Multiplier: ${result.gameData.multiplier}x`);
        }

        this.lastWinDetails.innerHTML = details.join('<br>');
        this.lastWin.classList.remove('hidden');
    }

    updateSpinButton(spinning) {
        if (spinning) {
            this.spinButton.textContent = '🎰 SPINNING... 🎰';
            this.spinButton.classList.add('spinning');
            this.spinButton.disabled = true;
        } else {
            this.spinButton.textContent = '🎰 SPIN 🎰';
            this.spinButton.classList.remove('spinning');
            this.spinButton.disabled = this.currentBet > this.balance;
        }
    }

    showError(message) {
        // Simple error display - could be enhanced with a proper modal
        alert(`Error: ${message}`);
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Initialize the slot machine when the page loads
document.addEventListener('DOMContentLoaded', () => {
    new SlotMachine();
});