<?php

namespace App\Command;

use App\Entity\User;
use App\Enums\PaymentMethod;
use App\Services\DepositService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deposit',
    description: 'Add funds to a user account (development command)',
)]
class DepositCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepositService $depositService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addArgument('amount', InputArgument::REQUIRED, 'Deposit amount')
            ->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'Payment method (card, paypal, dummy)', 'dummy')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force deposit without confirmation')
            ->setHelp('This command allows you to add funds to a user account for development purposes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $amount = (float) $input->getArgument('amount');
        $methodValue = $input->getOption('method');
        $force = $input->getOption('force');

        // Validate amount
        if ($amount <= 0) {
            $io->error('Amount must be greater than 0');

            return Command::FAILURE;
        }

        if ($amount > 10000) {
            $io->error('Amount cannot exceed $10,000');

            return Command::FAILURE;
        }

        // Validate payment method
        $paymentMethod = null;
        foreach (PaymentMethod::cases() as $method) {
            if ($method->value === $methodValue) {
                $paymentMethod = $method;
                break;
            }
        }

        if (!$paymentMethod) {
            $io->error(sprintf('Invalid payment method. Available: %s', implode(', ', array_map(fn ($m) => $m->value, PaymentMethod::cases()))));

            return Command::FAILURE;
        }

        // Find user
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error(sprintf('User with email "%s" not found', $email));

            return Command::FAILURE;
        }

        // Show confirmation
        $io->section('Deposit Details');
        $io->table(
            ['Field', 'Value'],
            [
                ['User', $user->getEmail()],
                ['Current Balance', '$'.number_format($user->getBalance(), 2)],
                ['Deposit Amount', '$'.number_format($amount, 2)],
                ['Payment Method', $paymentMethod->getLabel()],
                ['New Balance', '$'.number_format($user->getBalance() + $amount, 2)],
            ]
        );

        if (!$force && !$io->confirm('Proceed with this deposit?', false)) {
            $io->info('Deposit cancelled');

            return Command::SUCCESS;
        }

        // Process deposit
        try {
            $transaction = $this->depositService->processDeposit($user, $amount, $paymentMethod->value);

            if ($transaction) {
                $io->success([
                    'Deposit processed successfully!',
                    sprintf('Transaction ID: %s', $transaction->getId()),
                    sprintf('Reference ID: %s', $transaction->getReferenceId()),
                    sprintf('User balance updated: $%.2f → $%.2f',
                        $transaction->getBalanceBefore(),
                        $transaction->getBalanceAfter()
                    ),
                ]);

                return Command::SUCCESS;
            } else {
                $io->error('Deposit processing failed');

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error(sprintf('Error processing deposit: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
