<?php

declare(strict_types=1);

namespace App\Tests\Codeception\Unit\Generator;

use App\Generator\RandomNumberGenerator;
use Codeception\Test\Unit;

final class RandomNumberGeneratorTest extends Unit
{
    private RandomNumberGenerator $rng;

    protected function _before(): void
    {
        $this->rng = new RandomNumberGenerator();
    }

    public function testGenerateReelPositionWithinBounds(): void
    {
        $size = 10;
        for ($i = 0; $i < 100; ++$i) {
            $pos = $this->rng->generateReelPosition($size);
            $this->assertGreaterThanOrEqual(0, $pos);
            $this->assertLessThan($size, $pos);
        }
    }

    public function testGenerateIntWithinRange(): void
    {
        for ($i = 0; $i < 50; ++$i) {
            $n = $this->rng->generateInt(5, 15);
            $this->assertGreaterThanOrEqual(5, $n);
            $this->assertLessThanOrEqual(15, $n);
        }
    }

    public function testGenerateIntThrowsOnInvalidRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->rng->generateInt(10, 5);
    }

    public function testGenerateSecureRandomThrowsOnInvalidRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->rng->generateSecureRandom(3, 2);
    }

    public function testGenerateFloatRange(): void
    {
        for ($i = 0; $i < 50; ++$i) {
            $f = $this->rng->generateFloat();
            $this->assertGreaterThanOrEqual(0.0, $f);
            $this->assertLessThanOrEqual(1.0, $f);
        }
    }

    public function testGenerateBooleanReturnsBothValuesOverManyRuns(): void
    {
        $seenTrue = false;
        $seenFalse = false;
        for ($i = 0; $i < 200; ++$i) {
            $val = $this->rng->generateBoolean(0.5);
            if ($val) {
                $seenTrue = true;
            } else {
                $seenFalse = true;
            }
            if ($seenTrue && $seenFalse) {
                break;
            }
        }
        $this->assertTrue($seenTrue, 'Expected to see true at least once');
        $this->assertTrue($seenFalse, 'Expected to see false at least once');
    }

    public function testGenerateWeightedRandomReturnsValidIndex(): void
    {
        $weights = [1, 2, 3, 4];
        for ($i = 0; $i < 50; ++$i) {
            $idx = $this->rng->generateWeightedRandom($weights);
            $this->assertIsInt($idx);
            $this->assertGreaterThanOrEqual(0, $idx);
            $this->assertLessThan(count($weights), $idx);
        }
    }

    public function testGenerateUniqueRandomProducesUniqueValuesInRange(): void
    {
        $values = $this->rng->generateUniqueRandom(5, 10, 20);
        $this->assertCount(5, $values);
        $this->assertSameSize($values, array_unique($values));
        foreach ($values as $v) {
            $this->assertGreaterThanOrEqual(10, $v);
            $this->assertLessThanOrEqual(20, $v);
        }
    }

    public function testShuffleArrayPreservesElements(): void
    {
        $array = range(1, 20);
        $shuffled = $this->rng->shuffleArray($array);
        sort($array);
        $sortedShuffled = $shuffled;
        sort($sortedShuffled);
        $this->assertSame($array, $sortedShuffled);
        $this->assertCount(20, $shuffled);
    }

    public function testGetEntropyInfoContainsExpectedKeys(): void
    {
        $info = $this->rng->getEntropyInfo();
        $this->assertIsArray($info);
        $this->assertArrayHasKey('sources', $info);
        $this->assertArrayHasKey('timestamp', $info);
        $this->assertArrayHasKey('memory_usage', $info);
        $this->assertArrayHasKey('system_load', $info);
    }

    public function testGenerateSeedFormat(): void
    {
        $seed = $this->rng->generateSeed();
        $this->assertSame(64, strlen($seed));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $seed);
    }

    public function testSetSeedReflectedInStatistics(): void
    {
        $seed = str_repeat('a', 64);
        $this->rng->setSeed($seed);
        $stats = $this->rng->getStatistics();
        // getStatistics includes 'seed' field mirroring current seed
        $this->assertArrayHasKey('seed', $stats);
        $this->assertSame($seed, $stats['seed']);
    }

    public function testValidateStateReturnsBoolean(): void
    {
        $this->assertTrue($this->rng->validateState());
    }

    public function testGetStatisticsContainsExpectedKeys(): void
    {
        $stats = $this->rng->getStatistics();
        $this->assertArrayHasKey('entropy_info', $stats);
        $this->assertArrayHasKey('seed', $stats);
        $this->assertArrayHasKey('state_valid', $stats);
        $this->assertArrayHasKey('timestamp', $stats);
        $this->assertIsArray($stats['entropy_info']);
        $this->assertIsBool($stats['state_valid']);
    }

    public function testGenerateReelPositions(): void
    {
        $positions = $this->rng->generateReelPositions(5, 7);
        $this->assertCount(5, $positions);
        foreach ($positions as $pos) {
            $this->assertGreaterThanOrEqual(0, $pos);
            $this->assertLessThan(7, $pos);
        }
    }

    public function testGenerateBonusValues(): void
    {
        $config = [
            'bonusA' => ['min' => 1, 'max' => 3],
            'bonusB' => ['min' => 10, 'max' => 20],
        ];
        $values = $this->rng->generateBonusValues($config);
        $this->assertArrayHasKey('bonusA', $values);
        $this->assertArrayHasKey('bonusB', $values);
        $this->assertGreaterThanOrEqual(1, $values['bonusA']);
        $this->assertLessThanOrEqual(3, $values['bonusA']);
        $this->assertGreaterThanOrEqual(10, $values['bonusB']);
        $this->assertLessThanOrEqual(20, $values['bonusB']);
    }

    public function testGenerateSequence(): void
    {
        $seq = $this->rng->generateSequence(10, 100, 200);
        $this->assertCount(10, $seq);
        foreach ($seq as $n) {
            $this->assertGreaterThanOrEqual(100, $n);
            $this->assertLessThanOrEqual(200, $n);
        }
    }
}
