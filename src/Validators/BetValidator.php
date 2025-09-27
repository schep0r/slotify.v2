<?php

declare(strict_types=1);

namespace App\Validators;

use App\Contracts\BetValidatorInterface;
use App\Entity\Game;
use App\Entity\User;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidBetException;

class BetValidator implements BetValidatorInterface
{
    public function validate(Game $game, User $user, float $betAmount): void
    {
        $this->validateBet($betAmount, $game);
        // Balance validation is now handled by BalanceCheckMiddleware
    }

    public function validateBet(float $betAmount, Game $game): void
    {
        if ($betAmount < $game->min_bet || $betAmount > $game->max_bet) {
            throw new InvalidBetException("Bet must be between {$game->min_bet} and {$game->max_bet}");
        }
    }

    public function validateBalance(User $user, float $betAmount): void
    {
        // This method is deprecated - balance validation is now handled by BalanceCheckMiddleware
        // Keeping for backward compatibility, but consider removing in future versions
        if ($user->balance < $betAmount) {
            throw new InsufficientBalanceException('Insufficient balance for this bet');
        }
    }
}
