<?php

declare(strict_types=1);

namespace App\Engines;

use App\Contracts\GameEngineInterface;
use App\Contracts\SpinStrategyInterface;
use App\DTOs\GameResultDto;
use App\Entity\Game;
use App\Entity\User;
use App\Strategies\BetSpinStrategy;
use App\Strategies\FreeSpinStrategy;

/**
 * SlotGameEngine - Orchestrates slot game flow using Strategy pattern.
 *
 * Uses Strategy pattern to handle different types of spins:
 * - BetSpinStrategy: Regular spins with bet deduction
 * - FreeSpinStrategy: Free spins without bet deduction
 */
class SlotGameEngine implements GameEngineInterface
{
    /** @var SpinStrategyInterface[] */
    private array $strategies;

    public function __construct(
        BetSpinStrategy $betSpinStrategy,
        FreeSpinStrategy $freeSpinStrategy,
    ) {
        $this->strategies = [
            $betSpinStrategy,
            $freeSpinStrategy,
        ];
    }

    /**
     * Execute a spin using the appropriate strategy.
     */
    public function play(User $user, Game $game, array $gameData): GameResultDto
    {
        $strategy = $this->getStrategy($gameData);

        return $strategy->execute($user, $game, $gameData);
    }

    /**
     * Get the appropriate strategy based on game data.
     */
    private function getStrategy(array $gameData): SpinStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($gameData)) {
                return $strategy;
            }
        }

        throw new \InvalidArgumentException('No suitable strategy found for the given game data');
    }

    public function validateInput(array $gameData, Game $game, User $user): void
    {
        $activePaylines = $gameData['activePaylines'] ?? [0];
        $maxPaylines = count($game->paylinesConfiguration->value ?? []);

        foreach ($activePaylines as $payline) {
            if ($payline >= $maxPaylines) {
                throw new \InvalidArgumentException("Invalid payline: {$payline}");
            }
        }
    }

    public function getRequiredInputs(): array
    {
        // Merge all strategy requirements
        $allInputs = [];
        foreach ($this->strategies as $strategy) {
            $allInputs = array_merge($allInputs, $strategy->getRequiredInputs());
        }

        // Add common inputs
        return array_merge($allInputs, [
            'activePaylines' => 'array|nullable',
            'useFreeSpins' => 'boolean|nullable',
        ]);
    }

    public function getConfigurationRequirements(): array
    {
        return [
            'reels' => 'required|array',
            'rows' => 'required|integer|min:1',
            'paylines' => 'required|array',
            'paytable' => 'required|array',
            'rtp' => 'required|numeric|between:80,99',
        ];
    }
}
