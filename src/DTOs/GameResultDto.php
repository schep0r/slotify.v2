<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Main game result DTO that represents the response from any game engine's play method.
 * This provides a consistent structure across all game types.
 */
readonly class GameResultDto
{
    public function __construct(
        public float $betAmount,
        public float $winAmount,
        public float $newBalance,
        public SlotGameDataDto $gameData,
    ) {
    }

    /**
     * Convert the DTO to an array for API responses.
     */
    public function toArray(): array
    {
        return [
            'betAmount' => $this->betAmount,
            'winAmount' => $this->winAmount,
            'newBalance' => $this->newBalance,
            'gameData' => $this->gameData->toArray(),
        ];
    }
}
