<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ReelVisibilityDto;
use App\Entity\Game;

interface ReelGeneratorInterface
{
    /**
     * Generate random positions for each reel.
     */
    public function generateReelPositions(Game $game): array;

    /**
     * Get visible symbols and positions for a spin.
     */
    public function getVisibleSymbols(Game $game): ReelVisibilityDto;
}
