<?php

declare(strict_types=1);

namespace App\Processors;

use App\Contracts\GameProcessorInterface;
use App\Engines\SlotGameEngine;
use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class GameProcessor implements GameProcessorInterface
{
    public function __construct(
        private SlotGameEngine $gameEngine,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(Game $game, User $user, array $gameData): array
    {
        try {
            $this->entityManager->beginTransaction();
            $result = $this->gameEngine->play($user, $game, $gameData);
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }

        // Convert DTO to array for API response
        return $result->toArray();
    }
}
