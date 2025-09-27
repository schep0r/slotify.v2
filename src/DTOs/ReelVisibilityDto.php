<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object representing the result of a reel visibility calculation.
 *
 * This includes both:
 * - positions: The starting index for each reel used to derive the visible window.
 * - symbols:   The 2D array (reel x row) of visible symbols for the current spin.
 */
readonly class ReelVisibilityDto
{
    /**
     * @param int[]                          $positions
     * @param array<int, array<int, string>> $symbols
     */
    public function __construct(
        public array $positions,
        public array $symbols,
    ) {
    }
}
