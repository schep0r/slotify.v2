<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entity\GameSession;

interface GameLoggerInterface
{
    /**
     * Log game round data.
     */
    public function logGameRound(
        GameSession $gameSession,
        array $spinData
    ): void;
}
