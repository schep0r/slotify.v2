<?php

namespace App\Tests\Unit\Services;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enums\PaymentMethod;
use App\Services\DepositService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DepositServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private DepositService $depositService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->depositService = new DepositService($this->entityManager, $this->logger);
    }

    public function testProcessDepositSuccess(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setBalance(100.0);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction')
        ;
        $this->entityManager
            ->expects($this->once())
            ->method('commit')
        ;
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
        ;
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        // Act
        $result = $this->depositService->processDeposit($user, 50.0, PaymentMethod::DUMMY->value);

        // Assert
        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals(Transaction::TYPE_DEPOSIT, $result->getType());
        $this->assertEquals(50.0, $result->getAmount());
        $this->assertEquals(150.0, $user->getBalance());
        $this->assertEquals(100.0, $result->getBalanceBefore());
        $this->assertEquals(150.0, $result->getBalanceAfter());
    }

    public function testProcessDepositFailure(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setBalance(100.0);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('rollback');

        // Mock database error
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Database error'));

        // Act
        $result = $this->depositService->processDeposit($user, 50.0, PaymentMethod::DUMMY->value);

        // Assert
        $this->assertNull($result);
        $this->assertEquals(100.0, $user->getBalance()); // Balance should remain unchanged
    }

    public function testDummyPaymentAlwaysSucceeds(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setBalance(0.0);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('commit');

        // Act
        $result = $this->depositService->processDeposit($user, 25.0, PaymentMethod::DUMMY->value);

        // Assert
        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals(25.0, $user->getBalance());
        $this->assertEquals(Transaction::STATUS_COMPLETED, $result->getStatus());
        $this->assertEquals(PaymentMethod::DUMMY->value, $result->getPaymentMethod());
    }

    public function testDepositCreatesCorrectTransaction(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setBalance(75.0);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->callback(function ($entity) use ($user) {
                if ($entity === $user) {
                    return true;
                }
                if ($entity instanceof Transaction) {
                    $this->assertEquals(Transaction::TYPE_DEPOSIT, $entity->getType());
                    $this->assertEquals(25.0, $entity->getAmount());
                    $this->assertEquals(75.0, $entity->getBalanceBefore());
                    $this->assertEquals(100.0, $entity->getBalanceAfter());
                    $this->assertEquals($user, $entity->getPlayer());
                    $this->assertEquals(PaymentMethod::CARD->value, $entity->getPaymentMethod());
                    $this->assertNull($entity->getGameSession()); // Deposits don't have game sessions
                    return true;
                }
                return false;
            }));

        // Act
        $result = $this->depositService->processDeposit($user, 25.0, PaymentMethod::CARD->value);

        // Assert
        $this->assertInstanceOf(Transaction::class, $result);
    }

    public function testInvalidPaymentMethod(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setBalance(100.0);

        // Act & Assert
        $this->expectException(\ValueError::class);
        $this->depositService->processDeposit($user, 50.0, 'invalid_method');
    }
}
