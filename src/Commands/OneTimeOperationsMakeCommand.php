<?php

namespace TimoKoerber\LaravelOneTimeOperations\Commands;

use Throwable;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationCreator;

class OneTimeOperationsMakeCommand extends OneTimeOperationsCommand
{
    protected $signature = 'operations:make
                            {name : The name of the one-time operation}
                            {--e|essential : Create file without any attributes}';

    protected $description = 'Create a new one-time operation';

    public function handle(): int
    {
        try {
            $file = OneTimeOperationCreator::createOperationFile($this->argument('name'), $this->option('essential'));
            $this->components->info(sprintf('One-time operation [%s] created successfully.', $file->getOperationFilePath()));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->components->warn($e->getMessage());

            return self::FAILURE;
        }
    }
}
