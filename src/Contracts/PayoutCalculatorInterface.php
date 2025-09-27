<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entity\Game;

interface PayoutCalculatorInterface
{
    /**
     * Calculate total payout for a spin result.
     */
    public function calculatePayout(
        Game $game,
        array $visibleSymbols,
        float $betAmount,
        array $activePaylines,
    ): array;
}
