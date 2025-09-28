<?php

declare(strict_types=1);

namespace App\Managers;

use App\Entity\GameRound;
use App\Entity\GameSession;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class GameRoundManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Process a slot spin and create a game round (Doctrine/Symfony version).
     */
    public function processSpin(
        GameSession $session,
        array $spinData,
        ?string $rngSeed = null,
    ): GameRound {
        // We assume wallet changes are handled elsewhere; here we just log round state

        $betAmount = (float) ($spinData['bet_amount'] ?? 0.0);
        $winAmount = (float) ($spinData['win_amount'] ?? 0.0);
        $netResult = $winAmount - $betAmount;

        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp() ?? '127.0.0.1';
        $ua = $request?->headers->get('User-Agent') ?? 'unknown';

        $round = new GameRound();
        $round
            ->setGameSession($session)
            ->setPlayer($session->getPlayer())
            ->setGame($session->getGame())
            ->setBetAmount($betAmount)
            ->setWinAmount($winAmount)
            ->setNetResult($netResult)
            ->setBalanceBefore($spinData['balance_before'])
            ->setBalanceAfter($spinData['balance_after'])
            ->setReelsResult($spinData['reel_result'] ?? [])
            ->setPaylinesWon($spinData['paylines_won'] ?? [])
            ->setMultipliers($spinData['multipliers'] ?? [])
            ->setBonusFeatures($spinData['bonus_features'] ?? [])
            ->setLinesPlayed((int) ($spinData['lines_played'] ?? 1))
            ->setBetPerLine((float) ($spinData['bet_per_line'] ?? $betAmount))
            ->setIsBonusRound((bool) ($spinData['is_bonus_round'] ?? false))
            ->setBonusType($spinData['bonus_type'] ?? null)
            ->setFreeSpinsRemaining(isset($spinData['free_spins_remaining']) ? (int) $spinData['free_spins_remaining'] : null)
            ->setTransectionRef(Uuid::v4()->toRfc4122())
            ->setRtpContribution($spinData['rt_contribution'] ?? 0.00)
            ->setIpAddress($ip)
            ->setUserAgent($ua)
            ->setStatus('completed')
            ->setComplitedAt(new DateTimeImmutable())
            ->setExtraData($spinData['extra_data'] ?? []);

        // Very simple completion hash (deterministic): adjust as needed
        $hashPayload = json_encode([
            'user' => $session->getPlayer()->getId(),
            'session' => $session->getId(),
            'bet' => $betAmount,
            'win' => $winAmount,
            'balanceBefore' => $spinData['balance_before'],
            'balanceAfter' => $spinData['balance_after'],
            'reels' => $round->getReelsResult(),
            'rng' => $rngSeed,
        ], JSON_THROW_ON_ERROR);
        $round->setCompletionHash(hash('sha256', $hashPayload));

        $this->entityManager->persist($round);

        // Update session aggregates
        $session
            ->setTotalSpins((int) $session->getTotalSpins() + 1)
            ->setTotalBet((float) $session->getTotalBet() + $betAmount)
            ->setTotalWin((float) $session->getTotalWin() + $winAmount);

        $this->entityManager->flush();

        $this->logger->info('Game round processed', [
            'round_id' => $round->getId(),
            'user_id' => $session->getPlayer()->getId(),
            'session_id' => $session->getId(),
            'bet_amount' => $betAmount,
            'win_amount' => $winAmount,
            'balance_before' => $spinData['balance_before'],
            'balance_after' => $spinData['balance_after'],
        ]);

        return $round;
    }

    /**
     * Process bonus round.
     */
    public function processBonusRound(
        GameSession $session,
        array $bonusData,
    ): GameRound {
        $bonusData['is_bonus_round'] = true;
        $bonusData['bet_amount'] = 0.0; // Bonus rounds typically don't cost additional bet

        return $this->processSpin($session, $bonusData);
    }

    /**
     * Process free spin.
     */
    public function processFreeSpin(
        GameSession $session,
        array $spinData,
        int $freeSpinsRemaining,
    ): GameRound {
        $spinData['is_bonus_round'] = true;
        $spinData['bonus_type'] = 'free_spins';
        $spinData['bet_amount'] = 0.0; // Free spins don't cost
        $spinData['free_spins_remaining'] = $freeSpinsRemaining - 1;

        return $this->processSpin($session, $spinData);
    }

    /**
     * Cancel a round (for technical issues).
     */
    public function cancelRound(GameRound $gameRound, string $reason = 'technical_error'): bool
    {
        if ($gameRound->getStatus() !== 'completed') {
            return false;
        }

        $user = $gameRound->getPlayer();

        // Restore user balance if available
        if ($user instanceof User) {
            $balanceAdjustment = (float) $gameRound->getBetAmount() - (float) $gameRound->getWinAmount();
            $user->setBalance(((float) $user->getBalance()) + $balanceAdjustment);
        }

        // Update round status & extra data
        $extra = $gameRound->getExtraData() ?? [];
        $extra['cancellation_reason'] = $reason;
        $extra['cancelled_at'] = (new DateTimeImmutable())->format(DATE_ATOM);
        $extra['original_balance_after'] = $gameRound->getBalanceAfter();

        $gameRound
            ->setStatus('cancelled')
            ->setExtraData($extra);

        // Update session statistics
        $session = $gameRound->getGameSession();
        if ($session instanceof GameSession) {
            $session
                ->setTotalSpins(max(0, (int) $session->getTotalSpins() - 1))
                ->setTotalBet(max(0.0, (float) $session->getTotalBet() - (float) $gameRound->getBetAmount()))
                ->setTotalWin(max(0.0, (float) $session->getTotalWin() - (float) $gameRound->getWinAmount()));
        }

        $this->entityManager->flush();

        $this->logger->warning('Game round cancelled', [
            'round_id' => $gameRound->getId(),
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Verify round integrity (basic implementation).
     */
    public function verifyRoundIntegrity(GameRound $gameRound): array
    {
        $issues = [];

        // Recompute simple hash
        try {
            $payload = json_encode([
                'user' => $gameRound->getPlayer()?->getId(),
                'session' => $gameRound->getGameSession()?->getId(),
                'bet' => $gameRound->getBetAmount(),
                'win' => $gameRound->getWinAmount(),
                'balanceBefore' => $gameRound->getBalanceBefore(),
                'balanceAfter' => $gameRound->getBalanceAfter(),
                'reels' => $gameRound->getReelsResult(),
            ], JSON_THROW_ON_ERROR);
            $expected = hash('sha256', $payload);
            if ($expected !== $gameRound->getCompletionHash()) {
                $issues[] = 'Completion hash mismatch';
            }
        } catch (\Throwable) {
            $issues[] = 'Failed to compute completion hash';
        }

        // Basic validation
        if ($gameRound->getBetAmount() < 0) {
            $issues[] = 'Negative bet amount';
        }
        if ($gameRound->getWinAmount() < 0) {
            $issues[] = 'Negative win amount';
        }

        return $issues;
    }

    /**
     * Get round statistics for a session.
     */
    public function getSessionRoundStats(GameSession $session): array
    {
        $rounds = $session->getGameRounds();
        $total = count($rounds);
        $winning = 0;
        $bonus = 0;
        $biggestWin = 0.0;
        $sumBet = 0.0;
        $sumWin = 0.0;
        $sumNet = 0.0;

        foreach ($rounds as $r) {
            $sumBet += (float) $r->getBetAmount();
            $sumWin += (float) $r->getWinAmount();
            $sumNet += (float) $r->getNetResult();
            if ((float) $r->getWinAmount() > 0) {
                $winning++;
            }
            if ($r->isBonusRound()) {
                $bonus++;
            }
            $biggestWin = max($biggestWin, (float) $r->getWinAmount());
        }

        return [
            'total_rounds' => $total,
            'winning_rounds' => $winning,
            'bonus_rounds' => $bonus,
            'biggest_win' => $biggestWin,
            'average_bet' => $total > 0 ? $sumBet / $total : 0.0,
            'total_bet' => $sumBet,
            'total_win' => $sumWin,
            'net_result' => $sumNet,
        ];
    }

    /**
     * Get recent big wins.
     */
    public function getRecentBigWins(int $limit = 10, float $multiplier = 50.0): array
    {
        // Simple query: win >= bet * multiplier
        $qb = $this->entityManager->createQueryBuilder()
            ->select('gr', 'u', 'gs')
            ->from(GameRound::class, 'gr')
            ->join('gr.player', 'u')
            ->join('gr.gameSession', 'gs')
            ->where('gr.winAmount >= gr.betAmount * :mult')
            ->setParameter('mult', $multiplier)
            ->orderBy('gr.complitedAt', 'DESC')
            ->setMaxResults($limit);

        $rounds = $qb->getQuery()->getResult();

        $result = [];
        foreach ($rounds as $round) {
            if (!$round instanceof GameRound) {
                continue;
            }
            $result[] = [
                'username' => method_exists($round->getPlayer(), 'getEmail') ? $round->getPlayer()->getEmail() : (string) $round->getPlayer()?->getId(),
                'game_id' => $round->getGameSession()?->getGame()?->getId(),
                'bet_amount' => $round->getBetAmount(),
                'win_amount' => $round->getWinAmount(),
                'multiplier' => $round->getBetAmount() > 0 ? $round->getWinAmount() / $round->getBetAmount() : null,
                'created_at' => $round->getComplitedAt(),
            ];
        }

        return $result;
    }

    /**
     * Verify RNG result (placeholder).
     */
    private function verifyRngResult(GameRound $gameRound): bool
    {
        return true; // Implement if you have a provably fair system
    }

    /**
     * Archive old rounds (for database cleanup).
     */
    public function archiveOldRounds(int $daysOld = 90): int
    {
        $cutoffDate = (new DateTimeImmutable())->modify('-' . $daysOld . ' days');

        $qb = $this->entityManager->createQueryBuilder()
            ->delete(GameRound::class, 'gr')
            ->where('gr.complitedAt < :cutoff')
            ->andWhere('gr.status != :disputed')
            ->setParameter('cutoff', $cutoffDate)
            ->setParameter('disputed', 'disputed');

        return (int) $qb->getQuery()->execute();
    }
}
