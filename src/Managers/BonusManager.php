<?php

declare(strict_types=1);

namespace App\Managers;

use App\Entity\BonusClaim;
use App\Entity\BonusTransaction;
use App\Entity\BonusType;
use App\Entity\User;
use App\Entity\UserBonus;

class BonusManager
{
    /**
     * Check if user can claim a specific bonus.
     */
    public function canClaimBonus(User $user, BonusType $bonusType): array
    {
        if (!$bonusType->isClaimable()) {
            return ['can_claim' => false, 'reason' => 'Bonus is not active'];
        }

        // Check cooldown period
        $lastClaim = BonusClaim::where('user_id', $user->id)
            ->where('bonus_type_id', $bonusType->id)
            ->latest('claimed_at')
            ->first();

        if ($lastClaim) {
            $cooldownHours = $bonusType->getCooldownPeriod();
            $nextClaimTime = $lastClaim->claimed_at->addHours($cooldownHours);

            if ($nextClaimTime->isFuture()) {
                return [
                    'can_claim' => false,
                    'reason' => 'Cooldown period active',
                    'next_claim_at' => $nextClaimTime,
                ];
            }
        }

        // Check max claims limit
        $maxClaims = $bonusType->getMaxClaims();
        if ($maxClaims) {
            $claimCount = BonusClaim::where('user_id', $user->id)
                ->where('bonus_type_id', $bonusType->id)
                ->count();

            if ($claimCount >= $maxClaims) {
                return ['can_claim' => false, 'reason' => 'Maximum claims reached'];
            }
        }

        return ['can_claim' => true];
    }

    /**
     * Claim a bonus for a user.
     */
    public function claimBonus(User $user, BonusType $bonusType, ?string $ipAddress = null): UserBonus
    {
        $canClaim = $this->canClaimBonus($user, $bonusType);
        if (!$canClaim['can_claim']) {
            throw new \Exception($canClaim['reason']);
        }

        return DB::transaction(function () use ($user, $bonusType, $ipAddress) {
            // Record the claim
            BonusClaim::create([
                'user_id' => $user->id,
                'bonus_type_id' => $bonusType->id,
                'claimed_at' => now(),
                'ip_address' => $ipAddress,
            ]);

            // Calculate bonus amount and expiry
            $amount = $this->calculateBonusAmount($bonusType, $user);
            $expiresAt = $this->calculateExpiryTime($bonusType);
            $wageringRequirement = $this->calculateWageringRequirement($bonusType, $amount);

            // Create user bonus
            $userBonus = UserBonus::create([
                'user_id' => $user->id,
                'bonus_type_id' => $bonusType->id,
                'status' => 'pending',
                'amount' => $amount,
                'wagering_requirement' => $wageringRequirement,
                'expires_at' => $expiresAt,
                'metadata' => [
                    'claimed_ip' => $ipAddress,
                    'bonus_config' => $bonusType->config,
                ],
            ]);

            // Auto-activate certain bonus types
            if (in_array($bonusType->type, ['free_spins', 'bonus_coins'])) {
                $userBonus->activate();
            }

            return $userBonus;
        });
    }

    /**
     * Use bonus in slot game.
     */
    public function useBonusForSpin(User $user, int $betAmount, array $gameResult): array
    {
        $activeBonus = $this->getActiveBonusForUser($user);

        if (!$activeBonus) {
            return ['bonus_used' => false];
        }

        return DB::transaction(function () use ($activeBonus, $betAmount, $gameResult) {
            $result = ['bonus_used' => true, 'bonus_type' => $activeBonus->bonusType->type];

            switch ($activeBonus->bonusType->type) {
                case 'free_spins':
                    $result = $this->processFreeSpinBonus($activeBonus, $gameResult);
                    break;

                case 'bonus_coins':
                    $result = $this->processBonusCoinsDeduction($activeBonus, $betAmount);
                    break;

                case 'multiplier':
                    $result = $this->processMultiplierBonus($activeBonus, $gameResult);
                    break;
            }

            // Record wagering for bonuses with requirements
            if ($activeBonus->wagering_requirement > 0) {
                $this->recordWagering($activeBonus, $betAmount, $gameResult);
            }

            return $result;
        });
    }

    /**
     * Process free spin bonus.
     */
    private function processFreeSpinBonus(UserBonus $bonus, array $gameResult): array
    {
        if ($bonus->getRemainingAmount() <= 0) {
            $bonus->complete();

            return ['bonus_used' => false, 'reason' => 'No free spins remaining'];
        }

        // Use one free spin
        $bonus->increment('used_amount');

        BonusTransaction::create([
            'user_bonus_id' => $bonus->id,
            'type' => 'debit',
            'amount' => 1,
            'description' => 'Free spin used',
            'game_data' => $gameResult,
        ]);

        // Check if all spins are used
        if ($bonus->getRemainingAmount() <= 0) {
            $bonus->complete();
        }

        return [
            'bonus_used' => true,
            'free_spin' => true,
            'remaining_spins' => $bonus->getRemainingAmount(),
            'spin_result' => $gameResult,
        ];
    }

    /**
     * Process bonus coins deduction.
     */
    private function processBonusCoinsDeduction(UserBonus $bonus, int $betAmount): array
    {
        $remainingCoins = $bonus->getRemainingAmount();
        $coinsToUse = min($betAmount, $remainingCoins);

        if ($coinsToUse <= 0) {
            $bonus->complete();

            return ['bonus_used' => false, 'reason' => 'No bonus coins remaining'];
        }

        $bonus->increment('used_amount', $coinsToUse);

        BonusTransaction::create([
            'user_bonus_id' => $bonus->id,
            'type' => 'debit',
            'amount' => $coinsToUse,
            'description' => 'Bonus coins used for bet',
        ]);

        if ($bonus->getRemainingAmount() <= 0) {
            $bonus->complete();
        }

        return [
            'bonus_used' => true,
            'coins_used' => $coinsToUse,
            'remaining_coins' => $bonus->getRemainingAmount(),
        ];
    }

    /**
     * Process multiplier bonus.
     */
    private function processMultiplierBonus(UserBonus $bonus, array $gameResult): array
    {
        $multiplier = $bonus->bonusType->config['multiplier'] ?? 2;
        $originalWin = $gameResult['win_amount'] ?? 0;
        $bonusWin = $originalWin * ($multiplier - 1);

        if ($originalWin > 0) {
            BonusTransaction::create([
                'user_bonus_id' => $bonus->id,
                'type' => 'credit',
                'amount' => $bonusWin,
                'description' => "Multiplier bonus ({$multiplier}x)",
                'game_data' => $gameResult,
            ]);

            // Use the bonus (one-time multiplier)
            $bonus->complete();

            return [
                'bonus_used' => true,
                'multiplier_applied' => $multiplier,
                'bonus_win' => $bonusWin,
                'total_win' => $originalWin + $bonusWin,
            ];
        }

        return ['bonus_used' => false, 'reason' => 'No win to multiply'];
    }

    /**
     * Record wagering for bonus completion.
     */
    private function recordWagering(UserBonus $bonus, int $betAmount, array $gameResult): void
    {
        $bonus->increment('wagered_amount', $betAmount);

        BonusTransaction::create([
            'user_bonus_id' => $bonus->id,
            'type' => 'wager',
            'amount' => $betAmount,
            'description' => 'Wagering recorded',
            'game_data' => $gameResult,
        ]);

        // Check if wagering is complete
        if ($bonus->isWageringComplete() && 'active' === $bonus->status) {
            $bonus->update(['status' => 'used', 'completed_at' => now()]);
        }
    }

    /**
     * Get active bonus for user.
     */
    public function getActiveBonusForUser(User $user): ?UserBonus
    {
        return UserBonus::where('user_id', $user->id)
            ->active()
            ->with('bonusType')
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Get user's bonus history.
     */
    public function getUserBonusHistory(User $user, int $limit = 20): array
    {
        $bonuses = UserBonus::where('user_id', $user->id)
            ->with(['bonusType', 'transactions'])
            ->latest()
            ->limit($limit)
            ->get();

        return $bonuses->map(function ($bonus) {
            return [
                'id' => $bonus->id,
                'type' => $bonus->bonusType->name,
                'amount' => $bonus->amount,
                'used_amount' => $bonus->used_amount,
                'remaining' => $bonus->getRemainingAmount(),
                'status' => $bonus->status,
                'wagering_progress' => $bonus->getWageringProgress(),
                'expires_at' => $bonus->expires_at,
                'created_at' => $bonus->created_at,
            ];
        })->toArray();
    }

    /**
     * Calculate bonus amount based on type and user.
     */
    private function calculateBonusAmount(BonusType $bonusType, User $user): int
    {
        $config = $bonusType->config;

        switch ($bonusType->type) {
            case 'free_spins':
                return $config['spin_count'] ?? 10;

            case 'bonus_coins':
                return $config['coin_amount'] ?? 1000;

            case 'deposit_match':
                // This would be calculated based on user's deposit
                return $config['match_amount'] ?? 0;

            default:
                return $config['amount'] ?? 0;
        }
    }

    /**
     * Calculate expiry time for bonus.
     */
    private function calculateExpiryTime(BonusType $bonusType): ?Carbon
    {
        $expiryHours = $bonusType->config['expiry_hours'] ?? null;

        return $expiryHours ? now()->addHours($expiryHours) : null;
    }

    /**
     * Calculate wagering requirement.
     */
    private function calculateWageringRequirement(BonusType $bonusType, int $amount): float
    {
        $multiplier = $bonusType->config['wagering_multiplier'] ?? 0;

        return $amount * $multiplier;
    }

    /**
     * Expire old bonuses.
     */
    public function expireOldBonuses(): int
    {
        return UserBonus::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
