<?php

namespace TimoKoerber\LaravelOneTimeOperations\Commands;

use Throwable;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationCreator;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationsMakeCommand extends OneTimeOperationsCommand
{
    protected $signature = 'operations:make
                            {name : The name of the one-time operation}
                            {--path= : Path to load the files from}
                            {--e|essential : Create file without any attributes}';

    protected $description = 'Create a new one-time operation';

    public function handle(): int
    {
        if($this->option('path')) {
            OneTimeOperationManager::setDirectoryName($this->option('path'));
        }
        
        try {
            $file = OneTimeOperationCreator::createOperationFile($this->argument('name'), $this->option('essential'));
            $this->components->info(sprintf('One-time operation [%s] created successfully.', $file->getOperationName()));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->components->warn($e->getMessage());

            return self::FAILURE;
        }
    }
}
