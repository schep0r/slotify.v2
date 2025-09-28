<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entity\GameSession;
use Symfony\Component\Security\Core\User\UserInterface;

interface TransactionManagerInterface
{
    /**
     * Process bet and win transactions.
     */
    public function processSpinTransaction(
        UserInterface $user,
        GameSession $gameSession,
        float $betAmount,
        array $payoutResult,
    ): float;

    /**
     * Process generic game transaction.
     */
    public function processGameTransaction(
        UserInterface $user,
        GameSession $gameSession,
        float $betAmount,
        float $winAmount,
    ): float;
}
