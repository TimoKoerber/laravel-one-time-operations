<?php

namespace TimoKoerber\LaravelOneTimeOperations\Providers;

use Illuminate\Support\ServiceProvider;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationDisposeCommand;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationShowCommand;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsMakeCommand;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsProcessCommand;

class OneTimeOperationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom([__DIR__.'/../../database/migrations']);

        $this->publishes([
            __DIR__.'/../../config/one-time-operations.php' => config_path('one-time-operations.php'),
            //            __DIR__.'/../../database/migrations/create_one_time_operations_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_one_time_operations_table.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands(OneTimeOperationsMakeCommand::class);
            $this->commands(OneTimeOperationsProcessCommand::class);
            $this->commands(OneTimeOperationShowCommand::class);
            $this->commands(OneTimeOperationDisposeCommand::class);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/one-time-operations.php', 'one-time-operations'
        );
    }
}
