<?php

declare(strict_types=1);

namespace App\Managers;

use App\Entity\FreeSpin;
use App\Entity\FreeSpinTransaction;
use App\Entity\User;

class FreeSpinManager
{
    /**
     * Award free spins to a user.
     */
    public function awardFreeSpins(
        User $user,
        int $amount,
        string $source = 'bonus',
        ?float $betValue = null,
        ?int $gameRestriction = null,
        ?Carbon $expiresAt = null,
        array $metadata = [],
    ): FreeSpin {
        return FreeSpin::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'source' => $source,
            'bet_value' => $betValue,
            'game_restriction' => $gameRestriction,
            'expires_at' => $expiresAt,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get user's available free spins for a specific game.
     */
    public function getAvailableFreeSpins(User $user, ?int $gameId = null): int
    {
        $query = FreeSpin::where('user_id', $user->id)->valid();

        if ($gameId) {
            $query->forGame($gameId);
        }

        return $query->sum(DB::raw('amount - used_amount'));
    }

    /**
     * Get user's free spin records.
     */
    public function getUserFreeSpins(User $user, ?string $gameId = null)
    {
        $query = FreeSpin::where('user_id', $user->id)->valid();

        if ($gameId) {
            $query->forGame($gameId);
        }

        return $query->orderBy('expires_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Use a free spin.
     */
    public function useFreeSpin(
        User $user,
        string $gameId,
        array $spinResult,
        float $winAmount = 0,
    ): ?FreeSpinTransaction {
        return DB::transaction(function () use ($user, $gameId, $spinResult, $winAmount) {
            // Find the oldest valid free spin for this game
            $freeSpin = FreeSpin::where('user_id', $user->id)
                ->valid()
                ->forGame($gameId)
                ->orderBy('expires_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$freeSpin) {
                throw new \Exception('No valid free spins available for this game');
            }

            // Increment used amount
            $freeSpin->increment('used_amount');

            // Create transaction record
            $transaction = FreeSpinTransaction::create([
                'user_id' => $user->id,
                'free_spin_id' => $freeSpin->id,
                'game_id' => $gameId,
                'bet_amount' => $freeSpin->bet_value ?? 0,
                'win_amount' => $winAmount,
                'spin_result' => $spinResult,
                'played_at' => now(),
            ]);

            // If user won something, add to their balance
            if ($winAmount > 0) {
                $user->increment('balance', $winAmount);
            }

            return $transaction;
        });
    }

    /**
     * Clean up expired free spins.
     */
    public function cleanupExpiredFreeSpins(): int
    {
        return FreeSpin::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Get user's free spin statistics.
     */
    public function getUserFreeSpinStats(User $user): array
    {
        $totalAwarded = FreeSpin::where('user_id', $user->id)->sum('amount');
        $totalUsed = FreeSpin::where('user_id', $user->id)->sum('used_amount');
        $totalAvailable = $this->getAvailableFreeSpins($user);
        $totalWinnings = FreeSpinTransaction::where('user_id', $user->id)->sum('win_amount');

        return [
            'total_awarded' => $totalAwarded,
            'total_used' => $totalUsed,
            'total_available' => $totalAvailable,
            'total_winnings' => $totalWinnings,
            'conversion_rate' => $totalUsed > 0 ? ($totalWinnings / $totalUsed) * 100 : 0,
        ];
    }
}
