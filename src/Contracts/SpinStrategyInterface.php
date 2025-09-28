<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\GameResultDto;
use App\Entity\Game;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Strategy interface for different types of spins (bet spins vs free spins).
 */
interface SpinStrategyInterface
{
    /**
     * Execute the spin strategy.
     */
    public function execute(UserInterface $user, Game $game, array $gameData): GameResultDto;

    /**
     * Validate if this strategy can handle the given game data.
     */
    public function canHandle(array $gameData): bool;

    /**
     * Get the required inputs for this strategy.
     */
    public function getRequiredInputs(): array;
}
