<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register game-related services
        $this->app->singleton(\App\Processors\Slot\WildResultProcessor::class);
        $this->app->singleton(\App\Processors\Slot\ScatterResultProcessor::class);
        $this->app->singleton(\App\Processors\Slot\JackpotProcessor::class);

        // Register interfaces with their implementations
        $this->app->bind(
            \App\Contracts\ReelGeneratorInterface::class,
            \App\Generators\ReelGenerator::class
        );

        $this->app->bind(
            \App\Contracts\PayoutCalculatorInterface::class,
            \App\Processors\Slot\PayoutProcessor::class
        );

        $this->app->bind(
            \App\Contracts\BetValidatorInterface::class,
            \App\Validators\BetValidator::class
        );

        $this->app->bind(
            \App\Contracts\TransactionManagerInterface::class,
            \App\Managers\TransactionManager::class
        );

        $this->app->bind(
            \App\Contracts\GameLoggerInterface::class,
            \App\Loggers\GameLogger::class
        );

        // Check if RandomNumberGeneratorInterface binding exists
        if (!$this->app->bound(\App\Contracts\RandomNumberGeneratorInterface::class)) {
            $this->app->bind(
                \App\Contracts\RandomNumberGeneratorInterface::class,
                \App\Generators\RandomNumberGenerator::class
            );
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
