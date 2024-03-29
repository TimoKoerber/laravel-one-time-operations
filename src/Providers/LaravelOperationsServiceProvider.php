<?php

namespace EncoreDigitalGroup\LaravelOperations\Providers;

use EncoreDigitalGroup\LaravelOperations\Commands\LaravelOperationShowCommand;
use EncoreDigitalGroup\LaravelOperations\Commands\LaravelOperationsMakeCommand;
use EncoreDigitalGroup\LaravelOperations\Commands\LaravelOperationsProcessCommand;
use Illuminate\Support\ServiceProvider;

class LaravelOperationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([__DIR__ . '/../../database/migrations']);

        $this->publishes([
            __DIR__ . '/../../config/operations.php' => config_path('operations.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands(LaravelOperationsMakeCommand::class);
            $this->commands(LaravelOperationsProcessCommand::class);
            $this->commands(LaravelOperationShowCommand::class);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/operations.php', 'operations'
        );
    }
}
