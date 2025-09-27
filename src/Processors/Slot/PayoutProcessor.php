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
        $this->paylines = $game->paylinesConfiguration->value;
        $this->paytable = $game->paytableConfiguration->value;
    }
}
