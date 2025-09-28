<?php

declare(strict_types=1);

namespace App\Validators;

use App\Contracts\BetValidatorInterface;
use App\Entity\Game;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidBetException;
use Symfony\Component\Security\Core\User\UserInterface;

class BetValidator implements BetValidatorInterface
{
    public function validate(Game $game, UserInterface $user, float $betAmount): void
    {
        $this->validateBet($betAmount, $game);
        $this->validateBalance($user, $betAmount);
    }

    public function validateBet(float $betAmount, Game $game): void
    {
        if ($betAmount < $game->getMinBet() || $betAmount > $game->getMaxBet()) {
            throw new InvalidBetException("Bet must be between {$game->getMinBet()} and {$game->getMaxBet()}");
        }
    }

    public function validateBalance(UserInterface $user, float $betAmount): void
    {
        if ($user->getBalance() < $betAmount) {
            throw new InsufficientBalanceException('Insufficient balance for this bet');
        }
    }
}
