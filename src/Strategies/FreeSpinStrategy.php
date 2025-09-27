<?php

declare(strict_types=1);

namespace App\Strategies;

use App\Contracts\GameLoggerInterface;
use App\Contracts\PayoutCalculatorInterface;
use App\Contracts\ReelGeneratorInterface;
use App\Contracts\SpinStrategyInterface;
use App\DTOs\GameResultDto;
use App\DTOs\SlotGameDataDto;
use App\Entity\Game;
use App\Entity\User;
use App\Exceptions\InsufficientFreeSpinsException;
use App\Managers\FreeSpinManager;
use App\Managers\GameSessionManager;

/**
 * Strategy for handling free spins.
 */
class FreeSpinStrategy implements SpinStrategyInterface
{
    public function __construct(
        private readonly ReelGeneratorInterface $reelGenerator,
        private readonly PayoutCalculatorInterface $payoutCalculator,
        private readonly GameLoggerInterface $gameLogger,
        private readonly GameSessionManager $gameSessionManager,
        private readonly FreeSpinManager $freeSpinManager,
    ) {
    }

    public function execute(User $user, Game $game, array $gameData): GameResultDto
    {
        $activePaylines = $gameData['activePaylines'] ?? [0];

        // Step 1: Check if user has available free spins
        $availableFreeSpins = $this->freeSpinManager->getAvailableFreeSpins($user, $game->id);

        if ($availableFreeSpins <= 0) {
            throw new InsufficientFreeSpinsException('No free spins available for this game');
        }

        // Step 2: Get or create game session
        $gameSession = $this->gameSessionManager->getOrCreateUserSession($user, $game);

        // Step 3: Generate reel results
        $spinResult = $this->reelGenerator->getVisibleSymbols($game);
        $visibleSymbols = $spinResult->symbols;

        // Step 4: Get the free spin bet value (from the free spin record)
        $freeSpinRecord = $this->freeSpinManager->getUserFreeSpins($user, $game->id)->first();
        $betAmount = $freeSpinRecord->bet_value ?? $game->min_bet;

        // Step 5: Calculate payouts (no bet deduction for free spins)
        $payoutResult = $this->payoutCalculator->calculatePayout(
            $game,
            $visibleSymbols,
            $betAmount,
            $activePaylines
        );

        // Step 6: Use the free spin and process winnings
        $freeSpinTransaction = $this->freeSpinManager->useFreeSpin(
            $user,
            $game->id,
            [
                'reelPositions' => $spinResult->positions,
                'visibleSymbols' => $visibleSymbols,
                'winningLines' => $payoutResult['winningLines'],
            ],
            $payoutResult['totalPayout']
        );

        // Step 7: Get updated balance (already updated by FreeSpinManager)
        $user->refresh();
        $newBalance = $user->balance;

        // Step 8: Log game round
        $this->gameLogger->logGameRound($gameSession, $payoutResult, $betAmount, $visibleSymbols);

        // Step 9: Return game result
        return $this->buildGameResult($spinResult->positions, $visibleSymbols, $payoutResult, $newBalance, true);
    }

    public function canHandle(array $gameData): bool
    {
        return $gameData['useFreeSpins'] ?? false;
    }

    public function getRequiredInputs(): array
    {
        return [
            'useFreeSpins' => 'required|boolean|accepted',
            'activePaylines' => 'array|nullable',
        ];
    }

    private function buildGameResult(
        array $reelPositions,
        array $visibleSymbols,
        array $payoutResult,
        float $newBalance,
        bool $wasFreeSpinUsed = false,
    ): GameResultDto {
        $slotGameData = new SlotGameDataDto(
            betAmount: $payoutResult['betAmount'],
            winAmount: $payoutResult['totalPayout'],
            reelPositions: $reelPositions,
            visibleSymbols: $visibleSymbols,
            winningLines: $payoutResult['winningLines'],
            isJackpot: $payoutResult['isJackpot'] ?? false,
            multiplier: $payoutResult['multiplier'] ?? 1.0,
            freeSpinsAwarded: $payoutResult['freeSpinsAwarded'] ?? 0,
            scatterResult: $payoutResult['scatterResult'] ?? [],
            wildPositions: $payoutResult['wildPositions'] ?? []
        );

        return new GameResultDto(
            betAmount: 0, // No bet amount for free spins
            winAmount: $payoutResult['totalPayout'],
            newBalance: $newBalance,
            gameData: $slotGameData
        );
    }
}
