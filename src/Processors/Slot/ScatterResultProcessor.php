<?php

declare(strict_types=1);

namespace App\Processors\Slot;

use App\Entity\Game;

/**
 * ScatterResultProcessor - Handles scatter symbol logic and bonuses.
 */
class ScatterResultProcessor
{
    private const KEY_SYMBOL = 'symbol_id';

    /**
     * Check for scatter symbol bonuses.
     */
    public function checkScatterBonus(Game $game, array $visibleSymbols, float $betAmount): array
    {
        $payout = 0;
        $freeSpins = 0;
        $scatterCounts = [];
        $scatterPositions = [];

        // Default scatter symbol (star)
        $scatterSymbol = '⭐';
        
        $scatterCount = $this->countScatterSymbols($visibleSymbols, $scatterSymbol);
        $positions = $this->getScatterPositions($visibleSymbols, $scatterSymbol);

        if ($scatterCount >= 3) {
            $payout = $this->calculateScatterPayout($scatterCount, $betAmount);
            $freeSpins = $this->calculateFreeSpins($scatterCount);
            $scatterCounts[] = $scatterCount;
            $scatterPositions[] = $positions;
        }

        return [
            'payout' => $payout,
            'freeSpins' => $freeSpins,
            'scatterCount' => $scatterCounts,
            'positions' => $scatterPositions,
            'isScatterWin' => $scatterCount >= 3,
        ];
    }

    /**
     * Count scatter symbols across all reels.
     */
    public function countScatterSymbols(array $visibleSymbols, string $scatterSymbol): int
    {
        $scatterCount = 0;

        foreach ($visibleSymbols as $reel) {
            foreach ($reel as $symbol) {
                if ($symbol === $scatterSymbol) {
                    ++$scatterCount;
                }
            }
        }

        return $scatterCount;
    }

    /**
     * Get positions of all scatter symbols.
     */
    public function getScatterPositions(array $visibleSymbols, string $scatterSymbol): array
    {
        $positions = [];

        foreach ($visibleSymbols as $reelIndex => $reel) {
            foreach ($reel as $rowIndex => $symbol) {
                if ($symbol === $scatterSymbol) {
                    $positions[] = [
                        'reel' => $reelIndex,
                        'row' => $rowIndex,
                    ];
                }
            }
        }

        return $positions;
    }

    /**
     * Calculate scatter payout based on count.
     */
    public function calculateScatterPayout(int $scatterCount, float $betAmount): float
    {
        // Scatter payouts are usually multiplied by total bet
        $scatterMultipliers = [
            3 => 2,
            4 => 10,
            5 => 100,
        ];

        $multiplier = $scatterMultipliers[$scatterCount] ?? 0;

        return $multiplier * $betAmount;
    }

    /**
     * Calculate free spins awarded based on scatter count.
     */
    public function calculateFreeSpins(int $scatterCount): int
    {
        $freeSpinAwards = [
            3 => 10,
            4 => 15,
            5 => 25,
        ];

        return $freeSpinAwards[$scatterCount] ?? 0;
    }

    /**
     * Check if scatter symbols appear on specific reels (some games require this).
     */
    public function checkScatterOnSpecificReels(
        string $scatterSymbol,
        array $visibleSymbols,
        array $requiredReels = [0, 2, 4],
    ): bool {
        $scatterReels = [];

        foreach ($visibleSymbols as $reelIndex => $reel) {
            foreach ($reel as $symbol) {
                if ($symbol === $scatterSymbol) {
                    $scatterReels[] = $reelIndex;
                    break; // Only need one scatter per reel
                }
            }
        }

        // Check if we have scatters on all required reels
        return count(array_intersect($requiredReels, $scatterReels)) === count($requiredReels);
    }

    /**
     * Calculate progressive scatter bonus (for games with increasing scatter rewards).
     */
    public function calculateProgressiveScatterBonus(
        int $scatterCount,
        float $betAmount,
        int $gameLevel = 1,
    ): array {
        $baseMultiplier = $this->calculateScatterPayout($scatterCount, 1) / 1; // Get base multiplier
        $levelMultiplier = 1 + ($gameLevel * 0.1); // 10% increase per level

        $finalPayout = $betAmount * $baseMultiplier * $levelMultiplier;

        return [
            'payout' => $finalPayout,
            'baseMultiplier' => $baseMultiplier,
            'levelMultiplier' => $levelMultiplier,
            'gameLevel' => $gameLevel,
        ];
    }

    /**
     * Check for scatter combinations that trigger special features.
     */
    public function checkSpecialScatterFeatures(array $visibleSymbols, string $scatterSymbol): array
    {
        $scatterCount = $this->countScatterSymbols($visibleSymbols, $scatterSymbol);
        $positions = $this->getScatterPositions($visibleSymbols, $scatterSymbol);

        $features = [];

        // Check for different scatter-triggered features
        if ($scatterCount >= 3) {
            $features[] = 'free_spins';
        }

        if ($scatterCount >= 4) {
            $features[] = 'bonus_multiplier';
        }

        if (5 === $scatterCount) {
            $features[] = 'mega_bonus';
        }

        // Check for scatter patterns (e.g., diagonal, corners)
        if ($this->checkScatterPattern($positions, 'diagonal')) {
            $features[] = 'diagonal_bonus';
        }

        return $features;
    }

    /**
     * Check if scatter symbols form a specific pattern.
     */
    private function checkScatterPattern(array $positions, string $pattern): bool
    {
        switch ($pattern) {
            case 'diagonal':
                // Check for diagonal pattern (simplified example)
                $diagonalPositions = [
                    ['reel' => 0, 'row' => 0],
                    ['reel' => 1, 'row' => 1],
                    ['reel' => 2, 'row' => 2],
                ];

                return count(array_intersect($positions, $diagonalPositions)) >= 3;

            case 'corners':
                // Check for corner pattern
                $cornerPositions = [
                    ['reel' => 0, 'row' => 0],
                    ['reel' => 0, 'row' => 2],
                    ['reel' => 4, 'row' => 0],
                    ['reel' => 4, 'row' => 2],
                ];

                return count(array_intersect($positions, $cornerPositions)) >= 4;

            default:
                return false;
        }
    }
}
