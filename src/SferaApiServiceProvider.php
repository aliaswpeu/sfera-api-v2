<?php

namespace Aliaswpeu\SferaApi;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SferaApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Make config available even if not published
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sfera-api.php',
            'sfera-api'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        Route::prefix('api')
            ->as('api.')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

        // Allow publishing config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/sfera-api.php' => config_path('sfera-api.php'),
            ], 'sfera-api-config');
        }
    }
}
