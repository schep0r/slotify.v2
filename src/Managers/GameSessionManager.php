<?php

declare(strict_types=1);

namespace App\Managers;

use App\Entity\Game;
use App\Entity\GameSession;
use App\Entity\User;
use App\Repository\GameSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class GameSessionManager
{
    public const SESSION_LIFETIME = 86400;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameSessionRepository $gameSessionRepository,
    ) {
    }

    public function getOrCreateUserSession(UserInterface $user, Game $game): GameSession
    {
        $activeSession = $this->gameSessionRepository->findOneBy([
            'player' => $user,
            'game' => $game,
            'status' => 'active',
        ]);

        if (!$activeSession) {
            return $this->startGameSession($user, $game);
        }

        $lastUpdated = $activeSession->getStartedAt();
        $expiryTime = new \DateTimeImmutable('-'.self::SESSION_LIFETIME.' seconds');

        if ($lastUpdated && $lastUpdated < $expiryTime) {
            $activeSession->setStatus('closed');
            $this->entityManager->flush();

            return $this->startGameSession($user, $game);
        }

        return $activeSession;
    }

    public function startGameSession(UserInterface $user, Game $game): GameSession
    {
        $gameSession = new GameSession();
        $gameSession
            ->setPlayer($user)
            ->setGame($game)
            ->setSessionToken(Uuid::v4()->toRfc4122())
            ->setStartedAt(new \DateTimeImmutable())
            ->setStatus('active')
            ->setTotalSpins(0)
            ->setTotalBet(0.0)
            ->setTotalWin(0.0);

        $this->entityManager->persist($gameSession);
        $this->entityManager->flush();

        return $gameSession;
    }
}
