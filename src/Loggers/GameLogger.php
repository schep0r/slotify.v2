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
        array $spinData,
        float $betAmount,
        array $visibleSymbols,
    ): void {
        $spinData = array_merge(
            $spinData,
            [
                'bet_amount' => $betAmount,
                'win_amount' => $spinData['totalPayout'],
                'reel_result' => $visibleSymbols,
            ]
        );

        $this->gameRoundManager->processSpin($gameSession, $spinData);
    }
}
