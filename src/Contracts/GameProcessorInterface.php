<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entity\Game;
use App\Entity\User;

interface GameProcessorInterface
{
    public function process(Game $game, User $user, array $playGameRequest): array;
}
