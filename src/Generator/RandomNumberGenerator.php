<?php

namespace App\Generator;

use App\Contracts\RandomNumberGeneratorInterface;

class RandomNumberGenerator implements RandomNumberGeneratorInterface
{
    private string $seed;
    private array $entropy_sources;

    public function __construct()
    {
        $this->initializeEntropy();
    }

    /**
     * Generate a random reel position.
     */
    public function generateReelPosition(int $reelSize): int
    {
        return $this->generateSecureRandom(0, $reelSize - 1);
    }

    /**
     * Generate a random integer within range (interface method).
     */
    public function generateInt(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return $this->generateSecureRandom($min, $max);
    }

    /**
     * Generate a random number within a range using cryptographically secure methods.
     */
    public function generateSecureRandom(int $min, int $max): int
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Minimum value cannot be greater than maximum value');
        }

        $range = $max - $min + 1;

        // Use random_int for cryptographically secure random numbers
        try {
            return random_int($min, $max);
        } catch (\Exception $e) {
            // Fallback to openssl if random_int fails
            return $this->generateOpenSSLRandom($min, $max);
        }
    }

    /**
     * Generate random float between 0 and 1.
     */
    public function generateFloat(): float
    {
        return $this->generateSecureRandom(0, PHP_INT_MAX) / PHP_INT_MAX;
    }

    /**
     * Generate random boolean with optional bias.
     */
    public function generateBoolean(float $trueChance = 0.5): bool
    {
        return $this->generateFloat() < $trueChance;
    }

    /**
     * Generate weighted random selection (interface method).
     */
    public function generateWeighted(array $weights): int|string
    {
        return $this->generateWeightedRandom($weights);
    }

    /**
     * Generate weighted random selection.
     */
    public function generateWeightedRandom(array $weights): int
    {
        $totalWeight = array_sum($weights);
        $random = $this->generateFloat() * $totalWeight;

        $currentWeight = 0;
        foreach ($weights as $index => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $index;
            }
        }

        return count($weights) - 1; // Fallback to last index
    }

    /**
     * Generate array of unique random numbers.
     */
    public function generateUniqueRandom(int $count, int $min, int $max): array
    {
        if ($count > ($max - $min + 1)) {
            throw new \InvalidArgumentException('Cannot generate more unique numbers than available range');
        }

        $numbers = [];
        while (count($numbers) < $count) {
            $num = $this->generateSecureRandom($min, $max);
            if (!in_array($num, $numbers)) {
                $numbers[] = $num;
            }
        }

        return $numbers;
    }

    /**
     * Shuffle array using secure random.
     */
    public function shuffleArray(array $array): array
    {
        $shuffled = $array;
        $count = count($shuffled);

        for ($i = $count - 1; $i > 0; --$i) {
            $j = $this->generateSecureRandom(0, $i);
            [$shuffled[$i], $shuffled[$j]] = [$shuffled[$j], $shuffled[$i]];
        }

        return $shuffled;
    }

    /**
     * Get entropy information for audit purposes.
     */
    public function getEntropyInfo(): array
    {
        return [
            'sources' => $this->entropy_sources,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(),
            'system_load' => sys_getloadavg()[0] ?? 0,
        ];
    }

    /**
     * Generate seed for testing/replay purposes.
     */
    public function generateSeed(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Set seed for deterministic testing.
     */
    public function setSeed(string $seed): void
    {
        $this->seed = $seed;
        // Note: PHP's random_int cannot be seeded, this is for testing mock scenarios
    }

    private function generateOpenSSLRandom(int $min, int $max): int
    {
        $range = $max - $min + 1;
        $bytes = openssl_random_pseudo_bytes(4, $strong);

        if (!$strong) {
            throw new \RuntimeException('Unable to generate cryptographically strong random number');
        }

        $value = unpack('N', $bytes)[1];

        return $min + ($value % $range);
    }

    private function initializeEntropy(): void
    {
        $this->entropy_sources = [
            'system_time' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid(),
            'random_bytes' => bin2hex(random_bytes(16)),
        ];

        if (function_exists('sys_getloadavg')) {
            $this->entropy_sources['system_load'] = sys_getloadavg();
        }

        $this->seed = $this->generateSeed();
    }

    /**
     * Generate slot reel positions (interface method).
     */
    public function generateReelPositions(int $reelCount, int $symbolsPerReel): array
    {
        $positions = [];
        for ($i = 0; $i < $reelCount; ++$i) {
            $positions[] = $this->generateReelPosition($symbolsPerReel);
        }

        return $positions;
    }

    /**
     * Generate bonus game random values (interface method).
     */
    public function generateBonusValues(array $config): array
    {
        $values = [];
        foreach ($config as $key => $range) {
            if (is_array($range) && isset($range['min'], $range['max'])) {
                $values[$key] = $this->generateSecureRandom($range['min'], $range['max']);
            }
        }

        return $values;
    }

    /**
     * Generate sequence of numbers (interface method).
     */
    public function generateSequence(int $count, int $min, int $max): array
    {
        $sequence = [];
        for ($i = 0; $i < $count; ++$i) {
            $sequence[] = $this->generateSecureRandom($min, $max);
        }

        return $sequence;
    }

    /**
     * Validate RNG state (interface method).
     */
    public function validateState(): bool
    {
        try {
            // Test basic functionality
            $test = $this->generateSecureRandom(1, 100);

            return is_int($test) && $test >= 1 && $test <= 100;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get RNG statistics (interface method).
     */
    public function getStatistics(): array
    {
        return [
            'entropy_info' => $this->getEntropyInfo(),
            'seed' => $this->seed,
            'state_valid' => $this->validateState(),
            'timestamp' => time(),
        ];
    }

    /**
     * Reseed the generator (interface method).
     */
    public function reseed(?string $newSeed = null): void
    {
        if (null !== $newSeed) {
            $this->seed = $newSeed;
        } else {
            $this->seed = $this->generateSeed();
        }
        $this->initializeEntropy();
    }
}
