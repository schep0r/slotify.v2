import { Controller } from '@hotwired/stimulus';

// Connects to data-controller="spin"
export default class extends Controller {
    static values = {
        url: String
    }

    static targets = [
        'form',
        'reels',
        'balance',
        'winDisplay',
        'lastResult',
        'spinButton'
    ]

    onSpin(event) {
        event.preventDefault();
        const button = this.hasSpinButtonTarget ? this.spinButtonTarget : event.currentTarget;
        button.disabled = true;

        try {
            const form = this.formTarget ?? this.element.querySelector('form');
            if (!form) {
                throw new Error('Spin form not found');
            }

            const formData = new FormData(form);

            fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok) {
                        throw new Error(data.error || 'Spin failed');
                    }
                    this.updateUI(data);
                })
                .catch((err) => {
                    console.error(err);
                    this.showError(err.message);
                })
                .finally(() => {
                    button.disabled = false;
                });
        } catch (e) {
            console.error(e);
            this.showError(e.message);
            button.disabled = false;
        }
    }

    updateUI(data) {
        // Expected data from controller: { betAmount, winAmount, newBalance, gameData: { betAmount, winAmount, gameData: { visibleSymbols, winningLines, ... } } }
        // Handle balance
        if (this.hasBalanceTarget && typeof data.newBalance !== 'undefined') {
            this.balanceTarget.textContent = `$${Number(data.newBalance).toFixed(2)}`;
        }

        // Handle reels and wins
        const innerGameData = data.gameData?.gameData || {};
        const visible = innerGameData.visibleSymbols || [];
        const winningLines = innerGameData.winningLines || [];

        if (this.hasReelsTarget && Array.isArray(visible) && visible.length) {
            this.reelsTarget.innerHTML = this.renderReelsHtml(visible, winningLines);
        }

        // Win banner
        if (this.hasWinDisplayTarget) {
            const win = Number(data.winAmount || 0);
            if (win > 0) {
                this.winDisplayTarget.innerHTML = this.renderWinBannerHtml(win, winningLines);
                this.winDisplayTarget.classList.remove('hidden');
            } else {
                this.winDisplayTarget.innerHTML = '';
                this.winDisplayTarget.classList.add('hidden');
            }
        }

        // Last result summary
        if (this.hasLastResultTarget) {
            this.lastResultTarget.innerHTML = this.renderLastResultHtml(
                Number(data.betAmount || 0),
                Number(data.winAmount || 0)
            );
        }
    }

    renderReelsHtml(visibleSymbols, winningLines) {
        // Build a set of winning positions for quick lookup: {reelIndex: Set(rowIndexes)}
        const winningPositions = new Map();
        if (Array.isArray(winningLines)) {
            for (const line of winningLines) {
                const positions = line?.positions || line?.symbolsPositions || [];
                positions.forEach((pos) => {
                    const [reel, row] = pos;
                    if (!winningPositions.has(reel)) winningPositions.set(reel, new Set());
                    winningPositions.get(reel).add(row);
                });
            }
        }

        const reelsCount = visibleSymbols.length;
        const columns = visibleSymbols.map((reel, reelIndex) => {
            const cells = reel.map((symbol, rowIndex) => {
                const isWin = winningPositions.get(reelIndex)?.has(rowIndex);
                const winClass = isWin ? ' winning' : '';
                return `<div class="symbol${winClass}">${this.escapeHtml(symbol)}</div>`;
            }).join('');
            return `<div class="reel-column">${cells}</div>`;
        }).join('');

        // Keep the outer grid element; only replace its inner columns
        return columns;
    }

    renderWinBannerHtml(winAmount, winningLines) {
        const linesCount = Array.isArray(winningLines) ? winningLines.length : 0;
        const linesText = linesCount > 0 ? `\n                            <div class="text-sm mt-2">${linesCount} winning line${linesCount > 1 ? 's' : ''}</div>` : '';
        return `
            <div class="result-display">
                <div class="text-2xl">🎉 WIN! 🎉</div>
                <div class="text-3xl font-bold">$${winAmount.toFixed(2)}</div>
                ${linesText}
            </div>
        `;
    }

    renderLastResultHtml(bet, win) {
        if (isNaN(bet)) bet = 0;
        if (isNaN(win)) win = 0;
        const profit = win - bet;
        const isWin = win > 0;
        return `
            <div class="bg-gray-800 rounded-xl p-4 border-2 border-gray-600">
                <h4 class="text-white font-bold mb-2">Last Spin</h4>
                <div class="text-sm text-gray-300 space-y-1">
                    <div>Bet: $${bet.toFixed(2)}</div>
                    <div>Win: $${win.toFixed(2)}</div>
                    ${isWin
                        ? `<div class="text-green-400">Profit: $${profit.toFixed(2)}</div>`
                        : `<div class="text-red-400">Loss: -$${bet.toFixed(2)}</div>`}
                </div>
            </div>
        `;
    }

    showError(message) {
        // Simple alert fallback; could be improved with a toast/inline error
        alert(message || 'Something went wrong');
    }

    escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}
