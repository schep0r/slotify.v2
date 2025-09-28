<?php

declare(strict_types=1);

namespace App\Processors\Slot;

use App\Contracts\PayoutCalculatorInterface;
use App\Entity\Game;

class PayoutProcessor implements PayoutCalculatorInterface
{
    private array $paytable = [];
    private array $paylines = [];
    private WildResultProcessor $wildResultProcessor;
    private ScatterResultProcessor $scatterResultProcessor;
    private JackpotProcessor $jackpotProcessor;

    public function __construct(
        WildResultProcessor $wildResultProcessor,
        ScatterResultProcessor $scatterResultProcessor,
        JackpotProcessor $jackpotProcessor,
    ) {
        $this->wildResultProcessor = $wildResultProcessor;
        $this->scatterResultProcessor = $scatterResultProcessor;
        $this->jackpotProcessor = $jackpotProcessor;
    }

    /**
     * Calculate total payout for a spin result.
     */
    public function calculatePayout(
        Game $game,
        array $visibleSymbols,
        float $betAmount,
        array $activePaylines,
    ): array {
        $winningLines = [];
        $totalPayout = 0;
        $isJackpot = false;
        $freeSpinsAwarded = 0;
        $this->initConfigurations($game);

        // Check each active payline
        foreach ($activePaylines as $paylineIndex) {
            if (!isset($this->paylines[$paylineIndex])) {
                continue;
            }

            $payline = $this->paylines[$paylineIndex];
            $lineResult = $this->checkPayline($visibleSymbols, $payline, $betAmount);

            if ($lineResult['payout'] > 0) {
                $winningLines[] = [
                    'payline' => $paylineIndex,
                    'symbols' => $lineResult['symbols'],
                    'count' => $lineResult['count'],
                    'payout' => $lineResult['payout'],
                    'symbol' => $lineResult['winningSymbol'],
                ];

                $totalPayout += $lineResult['payout'];
            }
        }

        // Check for scatter bonuses using dedicated service
        $scatterResult = $this->scatterResultProcessor->checkScatterBonus($game, $visibleSymbols, $betAmount);
        if ($scatterResult['payout'] > 0) {
            $totalPayout += $scatterResult['payout'];
            $freeSpinsAwarded = $scatterResult['freeSpins'];
        }

        // Apply wild multipliers using dedicated service
        //        $multiplier = $this->wildResultProcessor->calculateWildMultiplier($visibleSymbols);
        //        $totalPayout *= $multiplier;

        // Progressive jackpot check via dedicated processor
        if ($this->jackpotProcessor->isProgressiveJackpot($visibleSymbols)) {
            $jackpotAmount = $this->jackpotProcessor->getProgressiveJackpotAmount();
            $totalPayout += $jackpotAmount;
            $isJackpot = true;
        }

        return [
            'betAmount' => $betAmount,
            'winningLines' => $winningLines,
            'totalPayout' => round($totalPayout, 2),
            'isJackpot' => $isJackpot,
            //            'multiplier' => $multiplier,
            'freeSpinsAwarded' => $freeSpinsAwarded,
            'scatterResult' => $scatterResult,
            'wildPositions' => $this->wildResultProcessor->getWildPositions($visibleSymbols),
        ];
    }

    /**
     * Check a single payline for winning combinations.
     */
    private function checkPayline(
        array $visibleSymbols,
        array $payline,
        float $betAmount,
    ): array {
        $symbols = [];

        // Extract symbols along the payline
        foreach ($payline as $reelIndex => $row) {
            $symbols[] = $visibleSymbols[$reelIndex][$row];
        }

        $winningSymbol = null;
        $count = 0;
        $payout = 0;

        // Check for winning combinations (left to right)
        $firstSymbol = $symbols[0];
        if (WildResultProcessor::SYMBOL_WILD === $firstSymbol) {
            // Handle wild as first symbol using wild service
            $firstSymbol = $this->wildResultProcessor->findBestWildSubstitute($symbols);
        }

        $consecutiveCount = 1;
        for ($i = 1; $i < count($symbols); ++$i) {
            $currentSymbol = $symbols[$i];

            if ($currentSymbol === $firstSymbol || WildResultProcessor::SYMBOL_WILD === $currentSymbol) {
                ++$consecutiveCount;
            } else {
                break;
            }
        }

        // Check if we have a winning combination
        if ($consecutiveCount >= 3 && isset($this->paytable[$firstSymbol][$consecutiveCount])) {
            $winningSymbol = $firstSymbol;
            $count = $consecutiveCount;
            $basePayout = $this->paytable[$firstSymbol][$consecutiveCount];

            // Calculate wild contribution for this payline
            $wildContribution = $this->wildResultProcessor->calculateWildContribution(
                $symbols,
                $this->paytable,
                $betAmount / count($this->paylines)
            );

            $payout = $basePayout * $betAmount;
        }

        return [
            'symbols' => $symbols,
            'winningSymbol' => $winningSymbol,
            'count' => $count,
            'payout' => $payout,
        ];
    }

    private function initConfigurations(Game $game): void
    {
        $this->paylines = $game->getPaylines() ?? $this->getDefaultPaylines();
        $this->paytable = $game->getPaytable() ?? $this->getDefaultPaytable();
    }

    private function getDefaultPaylines(): array
    {
        // Default 25 paylines for a 5x3 slot
        return [
            [1, 1, 1, 1, 1], // Middle row
            [0, 0, 0, 0, 0], // Top row
            [2, 2, 2, 2, 2], // Bottom row
            [0, 1, 2, 1, 0], // V shape
            [2, 1, 0, 1, 2], // Inverted V
            [1, 0, 0, 0, 1], // Top corners
            [1, 2, 2, 2, 1], // Bottom corners
            [0, 0, 1, 2, 2], // Diagonal up
            [2, 2, 1, 0, 0], // Diagonal down
            [1, 2, 1, 0, 1], // Zigzag
            [1, 0, 1, 2, 1], // Inverted zigzag
            [0, 1, 0, 1, 0], // Top zigzag
            [2, 1, 2, 1, 2], // Bottom zigzag
            [1, 1, 0, 1, 1], // Top dip
            [1, 1, 2, 1, 1], // Bottom dip
            [0, 0, 2, 0, 0], // Top corners bottom
            [2, 2, 0, 2, 2], // Bottom corners top
            [0, 2, 0, 2, 0], // Alternating top-bottom
            [2, 0, 2, 0, 2], // Alternating bottom-top
            [1, 0, 2, 0, 1], // Diamond
            [1, 2, 0, 2, 1], // Inverted diamond
            [0, 1, 1, 1, 0], // Top to middle
            [2, 1, 1, 1, 2], // Bottom to middle
            [0, 0, 1, 0, 0], // Top center dip
            [2, 2, 1, 2, 2], // Bottom center dip
        ];
    }

    private function getDefaultPaytable(): array
    {
        // Default paytable with multipliers for different symbol combinations
        return [
            '🍒' => [3 => 5, 4 => 25, 5 => 100],
            '🍋' => [3 => 5, 4 => 25, 5 => 100],
            '🍊' => [3 => 10, 4 => 50, 5 => 200],
            '🍇' => [3 => 10, 4 => 50, 5 => 200],
            '🔔' => [3 => 20, 4 => 100, 5 => 500],
            '⭐' => [3 => 50, 4 => 250, 5 => 1000],
            '💎' => [3 => 100, 4 => 500, 5 => 2000],
            '7️⃣' => [3 => 200, 4 => 1000, 5 => 5000],
        ];
    }
}
