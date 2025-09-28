<?php

declare(strict_types=1);

namespace App\Loggers;

use App\Contracts\GameLoggerInterface;
use App\Entity\GameSession;
use App\Managers\GameRoundManager;

class GameLogger implements GameLoggerInterface
{
    public function __construct(
        private GameRoundManager $gameRoundManager,
    ) {
    }

    public function logGameRound(
        GameSession $gameSession,
        array $spinData
    ): void {
        $this->gameRoundManager->processSpin($gameSession, $spinData);
    }
}
