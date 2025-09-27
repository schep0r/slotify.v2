<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Slot-specific game data DTO containing all slot machine result information.
 */
readonly class SlotGameDataDto
{
    /**
     * @param int[]                            $reelPositions
     * @param array<int, array<int, string>>   $visibleSymbols
     * @param array<int, array<string, mixed>> $winningLines
     * @param array<string, mixed>             $scatterResult
     * @param int[]                            $wildPositions
     */
    public function __construct(
        public float $betAmount,
        public float $winAmount,
        public array $reelPositions,
        public array $visibleSymbols,
        public array $winningLines,
        public bool $isJackpot = false,
        public float $multiplier = 1.0,
        public int $freeSpinsAwarded = 0,
        public array $scatterResult = [],
        public array $wildPositions = [],
    ) {
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'betAmount' => $this->betAmount,
            'winAmount' => $this->winAmount,
            'gameData' => [
                'reelPositions' => $this->reelPositions,
                'visibleSymbols' => $this->visibleSymbols,
                'winningLines' => $this->winningLines,
                'isJackpot' => $this->isJackpot,
                'multiplier' => $this->multiplier,
                'freeSpinsAwarded' => $this->freeSpinsAwarded,
                'scatterResult' => $this->scatterResult,
                'wildPositions' => $this->wildPositions,
            ],
        ];
    }
}
