<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entity\Game;
use Symfony\Component\Security\Core\User\UserInterface;

interface BetValidatorInterface
{
    /**
     * Run all validating.
     */
    public function validate(Game $game, UserInterface $user, float $betAmount): void;

    /**
     * Validate bet amount against game limits.
     */
    public function validateBet(float $betAmount, Game $game): void;

    /**
     * Validate user has sufficient balance.
     *
     * @deprecated Balance validation is now handled by BalanceCheckMiddleware
     */
    public function validateBalance(UserInterface $user, float $betAmount): void;
}
