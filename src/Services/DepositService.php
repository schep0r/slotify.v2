<?php

namespace App\Services;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enums\PaymentMethod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class DepositService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function processDeposit(User $user, float $amount, string $paymentMethod): ?Transaction
    {
        try {
            $this->entityManager->beginTransaction();

            // Process payment based on method
            $paymentResult = $this->processPayment($amount, $paymentMethod, $user);

            if (!$paymentResult['success']) {
                $this->entityManager->rollback();
                return null;
            }

            // Update user balance
            $balanceBefore = $user->getBalance();
            $balanceAfter = $balanceBefore + $amount;
            $user->setBalance($balanceAfter);

            // Create transaction record
            $transaction = new Transaction();
            $transaction->setPlayer($user);
            $transaction->setType(Transaction::TYPE_DEPOSIT);
            $transaction->setAmount($amount);
            $transaction->setBalanceBefore($balanceBefore);
            $transaction->setBalanceAfter($balanceAfter);
            $transaction->setReferenceId(uniqid('dep_', true));
            $transaction->setDescription('Deposit via '.PaymentMethod::from($paymentMethod)->getLabel());
            $transaction->setStatus(Transaction::STATUS_COMPLETED);
            $transaction->setPaymentMethod($paymentMethod);
            $transaction->setCreatedAt(new \DateTimeImmutable());
            $transaction->setMetadata($paymentResult['data']);
            $transaction->setGameSession(null); // Deposits don't have game sessions

            $this->entityManager->persist($user);
            $this->entityManager->persist($transaction);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Deposit processed successfully', [
                'transaction_id' => $transaction->getId(),
                'user_id' => $user->getId(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]);

            return $transaction;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Deposit processing failed', [
                'user_id' => $user->getId(),
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function processPayment(float $amount, string $paymentMethod, User $user): array
    {
        return match (PaymentMethod::from($paymentMethod)) {
            PaymentMethod::CARD => $this->processCardPayment($amount),
            PaymentMethod::PAYPAL => $this->processPayPalPayment($amount, $user),
            PaymentMethod::DUMMY => $this->processDummyPayment($amount),
        };
    }

    private function processCardPayment(float $amount): array
    {
        // Simulate card payment processing
        // In real implementation, integrate with payment gateway like Stripe
        // Simulate 95% success rate
        $success = mt_rand(1, 100) <= 95;

        return [
            'success' => $success,
            'data' => [
                'transaction_id' => 'card_'.uniqid(),
                'gateway' => 'stripe',
                'card_last4' => '****',
                'processed_at' => (new DateTimeImmutable())->format('c'),
            ],
        ];
    }

    private function processPayPalPayment(float $amount, User $user): array
    {
        // Simulate PayPal payment processing
        // In real implementation, integrate with PayPal API
        // Simulate 98% success rate
        $success = mt_rand(1, 100) <= 98;

        return [
            'success' => $success,
            'data' => [
                'transaction_id' => 'pp_'.uniqid(),
                'gateway' => 'paypal',
                'payer_email' => $user->getEmail(),
                'processed_at' => (new DateTimeImmutable())->format('c'),
            ],
        ];
    }

    private function processDummyPayment(float $amount): array
    {
        // Always successful for development
        return [
            'success' => true,
            'data' => [
                'transaction_id' => 'dummy_'.uniqid(),
                'gateway' => 'dummy',
                'note' => 'Development payment - always successful',
                'processed_at' => (new \DateTimeImmutable())->format('c'),
            ],
        ];
    }
}
