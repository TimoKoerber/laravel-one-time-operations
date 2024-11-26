<?php

namespace TimoKoerber\LaravelOneTimeOperations\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationShowCommand;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsMakeCommand;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsProcessCommand;

class OneTimeOperationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands(OneTimeOperationsMakeCommand::class);
        $this->commands(OneTimeOperationsProcessCommand::class);
        $this->commands(OneTimeOperationShowCommand::class);

        $this->publishes([
            __DIR__.'/../../config/one-time-operations.php' => config_path('one-time-operations.php'),
        ], 'one-time-operations-config');

        $this->publishes([
            __DIR__.'/../../database/migrations/2023_02_28_000000_create_one_time_operations_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_one_time_operations_table.php'),
        ], 'one-time-operations-migrations');

        if (! $this->migrationFileExists()) {
            $this->loadMigrationsFrom([__DIR__.'/../../database/migrations']);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/one-time-operations.php', 'one-time-operations'
        );
    }

    protected function migrationFileExists(): bool
    {
        $files = $this->app->make(Filesystem::class)->glob(sprintf(
            '%s%s%s',
            database_path('migrations'),
            DIRECTORY_SEPARATOR,
            '*_create_one_time_operations_table.php'
        ));

        return count($files) > 0;
    }
}
