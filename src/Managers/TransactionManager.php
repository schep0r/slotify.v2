<?php

declare(strict_types=1);

namespace App\Managers;

use App\Contracts\TransactionManagerInterface;
use App\Entity\GameSession;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TransactionManager implements TransactionManagerInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function processSpinTransaction(
        UserInterface $user,
        GameSession $gameSession,
        float $betAmount,
        array $payoutResult,
    ): float {
        $currentBalance = $user->getBalance() ?? 0.0;
        $newBalance = $currentBalance - $betAmount + $payoutResult['totalPayout'];

        if ($payoutResult['totalPayout'] > 0) {
            $this->createWinTransaction(
                $user,
                $gameSession,
                $payoutResult['totalPayout'],
                $currentBalance,
                $newBalance,
                $payoutResult
            );
        } else {
            $this->createBetTransaction(
                $user,
                $gameSession,
                $betAmount,
                $currentBalance,
                $newBalance,
                $payoutResult
            );
        }

        $user->setBalance($newBalance);
        $this->entityManager->flush();

        return $newBalance;
    }

    public function processGameTransaction(
        UserInterface $user,
        GameSession $gameSession,
        float $betAmount,
        float $winAmount,
    ): float {
        $currentBalance = $user->getBalance() ?? 0.0;
        $newBalance = $currentBalance - $betAmount + $winAmount;

        // Create bet transaction
        if ($betAmount > 0) {
            $this->createBetTransaction(
                $user,
                $gameSession,
                $betAmount,
                $currentBalance,
                $currentBalance - $betAmount
            );
        }

        // Create win transaction if there's a win
        if ($winAmount > 0) {
            $this->createWinTransaction(
                $user,
                $gameSession,
                $winAmount,
                $currentBalance - $betAmount,
                $newBalance
            );
        }

        $user->setBalance($newBalance);
        $this->entityManager->flush();

        return $newBalance;
    }

    private function createBetTransaction(
        UserInterface $user,
        GameSession $gameSession,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        array $metadata = [],
    ): Transaction {
        $transaction = new Transaction();
        $transaction
            ->setPlayer($user)
            ->setGameSession($gameSession)
            ->setType(Transaction::TYPE_BET)
            ->setAmount($amount)
            ->setBalanceBefore($balanceBefore)
            ->setBalanceAfter($balanceAfter)
            ->setReferenceId(uniqid('bet_'))
            ->setDescription('Bet transaction')
            ->setMetadata($metadata)
            ->setStatus(Transaction::STATUS_COMPLETED);

        $this->entityManager->persist($transaction);

        return $transaction;
    }

    private function createWinTransaction(
        UserInterface $user,
        GameSession $gameSession,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        array $metadata = [],
    ): Transaction {
        $transaction = new Transaction();
        $transaction
            ->setPlayer($user)
            ->setGameSession($gameSession)
            ->setType(Transaction::TYPE_WIN)
            ->setAmount($amount)
            ->setBalanceBefore($balanceBefore)
            ->setBalanceAfter($balanceAfter)
            ->setReferenceId(uniqid('win_'))
            ->setDescription('Win transaction')
            ->setMetadata($metadata)
            ->setStatus(Transaction::STATUS_COMPLETED);

        $this->entityManager->persist($transaction);

        return $transaction;
    }
}
