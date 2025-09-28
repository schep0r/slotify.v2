<?php

namespace App\Command;

use App\Engines\SlotGameEngine;
use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-slot',
    description: 'Test the slot machine engine',
)]
class TestSlotCommand extends Command
{
    public function __construct(
        private SlotGameEngine $slotGameEngine,
        private GameRepository $gameRepository,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Get a test game and user
            $game = $this->gameRepository->findOneBy(['isActive' => true]);
            $user = $this->userRepository->findOneBy([]);

            if (!$game) {
                $io->error('No active games found');

                return Command::FAILURE;
            }

            if (!$user) {
                $io->error('No users found');

                return Command::FAILURE;
            }

            $io->info("Testing slot engine with game: {$game->getName()}");
            $io->info("User balance: {$user->getBalance()}");

            // Test a spin
            $gameData = [
                'betAmount' => $game->getMinBet(),
                'activePaylines' => null,
                'useFreeSpins' => false,
            ];

            $result = $this->slotGameEngine->play($user, $game, $gameData);

            $io->success('Slot engine test completed successfully!');
            $io->table(
                ['Property', 'Value'],
                [
                    ['Bet Amount', '$'.number_format($result->betAmount, 2)],
                    ['Win Amount', '$'.number_format($result->winAmount, 2)],
                    ['New Balance', '$'.number_format($result->newBalance, 2)],
                    ['Visible Symbols', json_encode($result->gameData->visibleSymbols)],
                    ['Winning Lines', count($result->gameData->winningLines)],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Slot engine test failed: '.$e->getMessage());
            $io->error('Stack trace: '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
