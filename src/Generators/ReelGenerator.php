<?php

declare(strict_types=1);

namespace App\Generators;

use App\Contracts\RandomNumberGeneratorInterface;
use App\Contracts\ReelGeneratorInterface;
use App\DTOs\ReelVisibilityDto;
use App\Entity\Game;

class ReelGenerator implements ReelGeneratorInterface
{
    public function __construct(private RandomNumberGeneratorInterface $rng)
    {
    }

    public function generateReelPositions(Game $game): array
    {
        $positions = [];
        foreach ($game->reelsConfiguration->value as $reel) {
            $positions[] = $this->rng->generateInt(0, count($reel) - 1);
        }

        return $positions;
    }

    public function getVisibleSymbols(Game $game): ReelVisibilityDto
    {
        $positions = $this->generateReelPositions($game);
        $visible = [];
        $reels = $game->reelsConfiguration->value;
        $rows = $game->rowsConfiguration->value;

        foreach ($positions as $reelIndex => $position) {
            $reel = $reels[$reelIndex];
            $reelSymbols = [];

            for ($row = 0; $row < $rows; ++$row) {
                $symbolIndex = ($position + $row) % count($reel);
                $reelSymbols[] = $reel[$symbolIndex];
            }

            $visible[] = $reelSymbols;
        }

        return new ReelVisibilityDto($positions, $visible);
    }
}
