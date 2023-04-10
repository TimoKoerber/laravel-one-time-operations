<?php

namespace TimoKoerber\LaravelOneTimeOperations\Providers;

use Illuminate\Support\ServiceProvider;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationShowCommand;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsMakeCommand;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsProcessCommand;

class OneTimeOperationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([__DIR__.'/../../database/migrations']);

        if ($this->app->runningInConsole()) {
            $this->commands(OneTimeOperationsMakeCommand::class);
            $this->commands(OneTimeOperationsProcessCommand::class);
            $this->commands(OneTimeOperationShowCommand::class);

            $this->publishes([
                 __DIR__.'/../../config/one-time-operations.php' => config_path('one-time-operations.php'),
             ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/one-time-operations.php', 'one-time-operations'
        );
    }
}
