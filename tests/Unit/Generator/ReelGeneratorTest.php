<?php

declare(strict_types=1);

namespace App\Tests\Codeception\Unit\Generator;

use App\Contracts\RandomNumberGeneratorInterface;
use App\DTOs\ReelVisibilityDto;
use App\Entity\Game;
use App\Generator\ReelGenerator;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ReelGeneratorTest extends Unit
{
    private ReelGenerator $reelGenerator;
    private RandomNumberGeneratorInterface|MockObject $mockRng;
    private Game $game;

    protected function _before(): void
    {
        $this->mockRng = $this->createMock(RandomNumberGeneratorInterface::class);
        $this->reelGenerator = new ReelGenerator($this->mockRng);
        $this->game = new Game();
    }

    public function testGenerateReelPositionsWithCustomReels(): void
    {
        // Arrange
        $customReels = [
            ['A', 'B', 'C'],
            ['X', 'Y', 'Z'],
            ['1', '2', '3', '4']
        ];
        $this->game->setReels($customReels);

        $this->mockRng->expects($this->exactly(3))
            ->method('generateInt')
            ->willReturnCallback(function ($min, $max) {
                $this->assertSame(0, $min);
                return match ($max) {
                    2 => 1, // For reels with 3 symbols (0-2)
                    3 => 2, // For reel with 4 symbols (0-3)
                    default => 0
                };
            });

        // Act
        $positions = $this->reelGenerator->generateReelPositions($this->game);

        // Assert
        $this->assertCount(3, $positions);
        $this->assertSame([1, 1, 2], $positions);
    }

    public function testGenerateReelPositionsWithDefaultReels(): void
    {
        // Arrange - game with no custom reels (will use defaults)
        $this->mockRng->expects($this->exactly(5))
            ->method('generateInt')
            ->with(0, 9) // Default reels have 10 symbols each (0-9)
            ->willReturn(3);

        // Act
        $positions = $this->reelGenerator->generateReelPositions($this->game);

        // Assert
        $this->assertCount(5, $positions);
        $this->assertSame([3, 3, 3, 3, 3], $positions);
    }

    public function testGenerateReelPositionsWithEmptyReels(): void
    {
        // Arrange
        $this->game->setReels([]);
        
        // Empty reels array means no reels to iterate over, so no RNG calls
        $this->mockRng->expects($this->never())
            ->method('generateInt');

        // Act
        $positions = $this->reelGenerator->generateReelPositions($this->game);

        // Assert
        $this->assertCount(0, $positions);
        $this->assertSame([], $positions);
    }

    public function testGetVisibleSymbolsWithCustomConfiguration(): void
    {
        // Arrange
        $customReels = [
            ['A', 'B', 'C', 'D'],
            ['X', 'Y', 'Z']
        ];
        $this->game->setReels($customReels);
        $this->game->setRows(2);

        $this->mockRng->expects($this->exactly(2))
            ->method('generateInt')
            ->willReturnOnConsecutiveCalls(1, 0); // positions for each reel

        // Act
        $result = $this->reelGenerator->getVisibleSymbols($this->game);

        // Assert
        $this->assertInstanceOf(ReelVisibilityDto::class, $result);
        $this->assertSame([1, 0], $result->positions);
        
        // Expected visible symbols:
        // Reel 0: position 1, rows 2 -> symbols at indices 1,2 -> ['B', 'C']
        // Reel 1: position 0, rows 2 -> symbols at indices 0,1 -> ['X', 'Y']
        $expectedSymbols = [
            ['B', 'C'],
            ['X', 'Y']
        ];
        $this->assertSame($expectedSymbols, $result->symbols);
    }

    public function testGetVisibleSymbolsWithWrappingPositions(): void
    {
        // Arrange
        $customReels = [
            ['A', 'B', 'C'] // 3 symbols
        ];
        $this->game->setReels($customReels);
        $this->game->setRows(3);

        $this->mockRng->expects($this->once())
            ->method('generateInt')
            ->with(0, 2)
            ->willReturn(2); // Last position in reel

        // Act
        $result = $this->reelGenerator->getVisibleSymbols($this->game);

        // Assert
        $this->assertSame([2], $result->positions);
        
        // Expected visible symbols with wrapping:
        // Position 2, rows 3 -> indices 2,0,1 (wraps around) -> ['C', 'A', 'B']
        $expectedSymbols = [
            ['C', 'A', 'B']
        ];
        $this->assertSame($expectedSymbols, $result->symbols);
    }

    public function testGetVisibleSymbolsWithDefaultConfiguration(): void
    {
        // Arrange - using defaults (no reels set, no rows set)
        $this->mockRng->expects($this->exactly(5))
            ->method('generateInt')
            ->with(0, 9)
            ->willReturn(0);

        // Act
        $result = $this->reelGenerator->getVisibleSymbols($this->game);

        // Assert
        $this->assertSame([0, 0, 0, 0, 0], $result->positions);
        $this->assertCount(5, $result->symbols); // 5 reels
        
        // Each reel should have 3 symbols (default rows)
        foreach ($result->symbols as $reelSymbols) {
            $this->assertCount(3, $reelSymbols);
        }
        
        // First reel starting at position 0 should show first 3 symbols
        $this->assertSame(['🍒', '🍋', '🍊'], $result->symbols[0]);
    }

    public function testGetVisibleSymbolsWithSingleRow(): void
    {
        // Arrange
        $customReels = [
            ['A', 'B', 'C', 'D', 'E']
        ];
        $this->game->setReels($customReels);
        $this->game->setRows(1);

        $this->mockRng->expects($this->once())
            ->method('generateInt')
            ->with(0, 4)
            ->willReturn(3);

        // Act
        $result = $this->reelGenerator->getVisibleSymbols($this->game);

        // Assert
        $this->assertSame([3], $result->positions);
        $expectedSymbols = [
            ['D'] // Only one symbol visible per reel
        ];
        $this->assertSame($expectedSymbols, $result->symbols);
    }

    public function testGetVisibleSymbolsWithLargeRowCount(): void
    {
        // Arrange
        $customReels = [
            ['A', 'B', 'C'] // Only 3 symbols
        ];
        $this->game->setReels($customReels);
        $this->game->setRows(5); // More rows than symbols

        $this->mockRng->expects($this->once())
            ->method('generateInt')
            ->with(0, 2)
            ->willReturn(1);

        // Act
        $result = $this->reelGenerator->getVisibleSymbols($this->game);

        // Assert
        $this->assertSame([1], $result->positions);
        
        // Expected symbols with multiple wrapping:
        // Position 1, rows 5 -> indices 1,2,0,1,2 -> ['B', 'C', 'A', 'B', 'C']
        $expectedSymbols = [
            ['B', 'C', 'A', 'B', 'C']
        ];
        $this->assertSame($expectedSymbols, $result->symbols);
    }

    public function testGetVisibleSymbolsConsistencyBetweenCalls(): void
    {
        // Arrange
        $customReels = [
            ['X', 'Y', 'Z'],
            ['1', '2', '3']
        ];
        $this->game->setReels($customReels);
        $this->game->setRows(2);

        // Mock to return same positions for both calls
        $this->mockRng->expects($this->exactly(4)) // 2 calls × 2 reels
            ->method('generateInt')
            ->willReturnOnConsecutiveCalls(1, 2, 1, 2);

        // Act
        $result1 = $this->reelGenerator->getVisibleSymbols($this->game);
        $result2 = $this->reelGenerator->getVisibleSymbols($this->game);

        // Assert - both calls should produce identical results
        $this->assertSame($result1->positions, $result2->positions);
        $this->assertSame($result1->symbols, $result2->symbols);
    }

    public function testReelGeneratorIsReadonly(): void
    {
        // This test ensures the readonly class behavior is maintained
        $reflection = new \ReflectionClass(ReelGenerator::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testConstructorAcceptsRandomNumberGeneratorInterface(): void
    {
        // Arrange & Act
        $generator = new ReelGenerator($this->mockRng);

        // Assert
        $this->assertInstanceOf(ReelGenerator::class, $generator);
    }
}