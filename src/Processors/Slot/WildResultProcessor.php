<?php

declare(strict_types=1);

namespace App\Processors\Slot;

/**
 * WildResultProcessor - Handles wild symbol logic and substitutions.
 */
class WildResultProcessor
{
    public const SYMBOL_WILD = 'WILD';

    /**
     * Calculate multiplier from wild symbols.
     */
    public function calculateWildMultiplier(array $visibleSymbols): int
    {
        $wildCount = 0;
        foreach ($visibleSymbols as $reel) {
            foreach ($reel as $symbol) {
                if ($symbol === static::SYMBOL_WILD) {
                    ++$wildCount;
                }
            }
        }

        // Each wild symbol adds 1x multiplier
        return 1 + $wildCount;
    }

    /**
     * Find the best symbol for wild substitution.
     */
    public function findBestWildSubstitute(array $symbols): string
    {
        // Find the most frequent non-wild symbol
        $symbolCounts = array_count_values(array_filter($symbols, fn ($s) => self::SYMBOL_WILD !== $s));

        return $symbolCounts ? array_search(max($symbolCounts), $symbolCounts) : 'cherry';
    }

    /**
     * Process wild substitutions in a payline.
     */
    public function processWildSubstitutions(array $symbols, string $targetSymbol): array
    {
        return array_map(function ($symbol) use ($targetSymbol) {
            return self::SYMBOL_WILD === $symbol ? $targetSymbol : $symbol;
        }, $symbols);
    }

    /**
     * Check if a symbol can be substituted by wild.
     */
    public function canSubstituteWithWild(string $symbol): bool
    {
        // Scatter and bonus symbols typically cannot be substituted
        return !in_array($symbol, ['scatter', 'bonus', 'jackpot']);
    }

    /**
     * Get wild positions in the visible symbols grid.
     */
    public function getWildPositions(array $visibleSymbols): array
    {
        $wildPositions = [];

        foreach ($visibleSymbols as $reelIndex => $reel) {
            foreach ($reel as $rowIndex => $symbol) {
                if (self::SYMBOL_WILD === $symbol) {
                    $wildPositions[] = [
                        'reel' => $reelIndex,
                        'row' => $rowIndex,
                    ];
                }
            }
        }

        return $wildPositions;
    }

    /**
     * Calculate wild contribution to a specific payline.
     */
    public function calculateWildContribution(
        array $paylineSymbols,
        array $paytable,
        float $betPerLine,
    ): array {
        $wildCount = count(array_filter($paylineSymbols, fn ($s) => self::SYMBOL_WILD === $s));

        if (0 === $wildCount) {
            return ['multiplier' => 1, 'wildCount' => 0];
        }

        // Calculate base multiplier from wild count
        $baseMultiplier = 1 + ($wildCount * 0.5); // Each wild adds 0.5x multiplier

        return [
            'multiplier' => $baseMultiplier,
            'wildCount' => $wildCount,
            'positions' => array_keys(array_filter($paylineSymbols, fn ($s) => self::SYMBOL_WILD === $s)),
        ];
    }
}
