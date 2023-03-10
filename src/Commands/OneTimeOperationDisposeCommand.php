<?php

namespace TimoKoerber\LaravelOneTimeOperations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationDisposeCommand extends Command
{
    protected $signature = 'operations:dispose';

    protected $description = 'Remove all one-time operation files from directory. Only on local environment! ';

    public function handle(): int
    {
        if (! App::environment('local')) {
            $this->components->error('This command should not be used in non-local environments. Use it locally and commit the changes to the repository.');

            return self::INVALID;
        }

        $allFiles = OneTimeOperationManager::getAllFiles();

        if ($allFiles->isEmpty()) {
            $this->components->info(sprintf('Directory `%s` is already empty.', OneTimeOperationManager::getDirectoryName()));

            return self::SUCCESS;
        }

        if (! $this->components->confirm(sprintf('All %s operation files in directory `%s` will be deleted. Are you sure?', $allFiles->count(), OneTimeOperationManager::getDirectoryName()))) {
            $this->components->warn('Disposable of files aborted.');

            return self::SUCCESS;
        }

        foreach ($allFiles as $file) {
            File::delete($file->getRealPath());
        }

        $this->components->info('All operation files werde deleted.');

        return self::SUCCESS;
    }
}
