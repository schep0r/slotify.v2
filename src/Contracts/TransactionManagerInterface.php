<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entity\GameSession;
use App\Entity\User;

interface TransactionManagerInterface
{
    /**
     * Process bet and win transactions.
     */
    public function processSpinTransaction(
        User $user,
        GameSession $gameSession,
        float $betAmount,
        array $payoutResult,
    ): float;

    /**
     * Process generic game transaction.
     */
    public function processGameTransaction(
        User $user,
        GameSession $gameSession,
        float $betAmount,
        float $winAmount,
    ): float;
}
