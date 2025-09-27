<?php

namespace App\Managers;

use App\Entity\GameRound;
use App\Entity\GameSession;
use App\Entity\User;

class GameRoundManager
{
    /**
     * Process a slot spin and create game round.
     */
    public function processSpin(
        GameSession $session,
        array $spinData,
        ?string $rngSeed = null,
    ): GameRound {
        return DB::transaction(function () use ($session, $spinData, $rngSeed) {
            $user = $session->user;
            // Lock user balance to prevent concurrent modifications
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            $balanceAfter = $user->balance;
            $betAmount = $spinData['bet_amount'];
            $winAmount = $spinData['win_amount'] ?? 0;
            $balanceBefore = $balanceAfter + $betAmount - $winAmount;

            // Create game round
            $gameRound = GameRound::create([
                'game_session_id' => $session->id,
                'user_id' => $user->id,
                'game_id' => $session->game_id,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reel_result' => $spinData['reel_result'],
                'paylines_won' => $spinData['paylines_won'] ?? null,
                'multipliers' => $spinData['multipliers'] ?? null,
                'bonus_features' => $spinData['bonus_features'] ?? null,
                'lines_played' => $spinData['lines_played'] ?? 1,
                'bet_per_line' => $spinData['bet_per_line'] ?? $betAmount,
                'rng_seed' => $rngSeed,
                'is_bonus_round' => $spinData['is_bonus_round'] ?? false,
                'bonus_type' => $spinData['bonus_type'] ?? null,
                'free_spins_remaining' => $spinData['free_spins_remaining'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'round_status' => 'completed',
                'completed_at' => now(),
            ]);

            // Log the transaction for audit
            Log::info('Game round processed', [
                'round_id' => $gameRound->round_id,
                'user_id' => $user->id,
                'session_id' => $session->session_id,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            return $gameRound;
        });
    }

    /**
     * Process bonus round.
     */
    public function processBonusRound(
        GameSession $session,
        array $bonusData,
    ): GameRound {
        $bonusData['is_bonus_round'] = true;
        $bonusData['bet_amount'] = 0; // Bonus rounds typically don't cost additional bet

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
        $spinData['bet_amount'] = 0; // Free spins don't cost
        $spinData['free_spins_remaining'] = $freeSpinsRemaining - 1;

        return $this->processSpin($session, $spinData);
    }

    /**
     * Cancel a round (for technical issues).
     */
    public function cancelRound(GameRound $gameRound, string $reason = 'technical_error'): bool
    {
        return DB::transaction(function () use ($gameRound, $reason) {
            if ('completed' !== $gameRound->round_status) {
                return false;
            }

            $user = $gameRound->user;

            // Restore user balance
            $balanceAdjustment = $gameRound->bet_amount - $gameRound->win_amount;
            $user->increment('balance', $balanceAdjustment);

            // Update round status
            $gameRound->update([
                'round_status' => 'cancelled',
                'extra_data' => array_merge($gameRound->extra_data ?? [], [
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now()->toISOString(),
                    'original_balance_after' => $gameRound->balance_after,
                ]),
            ]);

            // Update session statistics
            $session = $gameRound->gameSession;
            $session->decrement('total_spins');
            $session->decrement('total_bet', $gameRound->bet_amount);
            $session->decrement('total_win', $gameRound->win_amount);
            $session->update([
                'net_result' => $session->total_win - $session->total_bet,
            ]);

            Log::warning('Game round cancelled', [
                'round_id' => $gameRound->round_id,
                'reason' => $reason,
                'balance_adjustment' => $balanceAdjustment,
            ]);

            return true;
        });
    }

    /**
     * Verify round integrity.
     */
    public function verifyRoundIntegrity(GameRound $gameRound): array
    {
        $issues = [];

        // Check completion hash
        if (!$gameRound->verifyIntegrity()) {
            $issues[] = 'Completion hash mismatch';
        }

        // Validate round data
        $validationErrors = $gameRound->validateRoundData();
        $issues = array_merge($issues, $validationErrors);

        // Check against RNG if seed is available
        if ($gameRound->rng_seed && !$this->verifyRngResult($gameRound)) {
            $issues[] = 'RNG result verification failed';
        }

        return $issues;
    }

    /**
     * Get round statistics for a session.
     */
    public function getSessionRoundStats(GameSession $session): array
    {
        $rounds = $session->gameRounds();

        return [
            'total_rounds' => $rounds->count(),
            'winning_rounds' => $rounds->winning()->count(),
            'bonus_rounds' => $rounds->bonusRounds()->count(),
            'biggest_win' => $rounds->max('win_amount'),
            'average_bet' => $rounds->avg('bet_amount'),
            'total_bet' => $rounds->sum('bet_amount'),
            'total_win' => $rounds->sum('win_amount'),
            'net_result' => $rounds->sum('net_result'),
        ];
    }

    /**
     * Get recent big wins.
     */
    public function getRecentBigWins(int $limit = 10, float $multiplier = 50.0): array
    {
        return GameRound::bigWins($multiplier)
            ->with(['user:id,username', 'gameSession:id,game_id'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($round) {
                return [
                    'username' => $round->user->username,
                    'game_id' => $round->gameSession->game_id,
                    'bet_amount' => $round->bet_amount,
                    'win_amount' => $round->win_amount,
                    'multiplier' => $round->getMultiplier(),
                    'created_at' => $round->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Verify RNG result (implement based on your RNG provider).
     */
    private function verifyRngResult(GameRound $gameRound): bool
    {
        // This should be implemented based on your RNG provider's verification method
        // For example, if using a provably fair system:

        // $expectedResult = $this->rngService->generateResult(
        //     $gameRound->rng_seed,
        //     $gameRound->game_id
        // );

        // return $expectedResult === $gameRound->reel_result;

        return true; // Placeholder - implement actual verification
    }

    /**
     * Archive old rounds (for database cleanup).
     */
    public function archiveOldRounds(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        // This could move rounds to an archive table or delete them
        // depending on your data retention policy

        return GameRound::where('created_at', '<', $cutoffDate)
            ->where('round_status', '!=', 'disputed')
            ->delete();
    }
}
