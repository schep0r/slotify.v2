<?php

namespace App\Providers;

use App\Contracts\GameEngineInterface;
use App\Engines\SlotGameEngine;
use Illuminate\Support\ServiceProvider;

class GameServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            GameEngineInterface::class,
            SlotGameEngine::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
