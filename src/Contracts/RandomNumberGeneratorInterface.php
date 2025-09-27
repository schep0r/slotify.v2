<?php

declare(strict_types=1);

namespace App\Contracts;

interface RandomNumberGeneratorInterface
{
    /**
     * Generate a random integer within range.
     */
    public function generateInt(int $min = 0, int $max = PHP_INT_MAX): int;

    /**
     * Generate a random float between 0 and 1.
     */
    public function generateFloat(): float;

    /**
     * Generate weighted random selection.
     */
    public function generateWeighted(array $weights): int|string;

    /**
     * Generate slot reel positions.
     */
    public function generateReelPositions(int $reelCount, int $symbolsPerReel): array;

    /**
     * Generate bonus game random values.
     */
    public function generateBonusValues(array $config): array;

    /**
     * Generate sequence of numbers.
     */
    public function generateSequence(int $count, int $min, int $max): array;

    /**
     * Validate RNG state.
     */
    public function validateState(): bool;

    /**
     * Get RNG statistics.
     */
    public function getStatistics(): array;

    /**
     * Reseed the generator.
     */
    public function reseed(?string $newSeed = null): void;
}
