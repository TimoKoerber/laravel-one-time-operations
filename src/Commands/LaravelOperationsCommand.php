<?php

namespace EncoreDigitalGroup\LaravelOperations\Commands;

use Illuminate\Console\Command;
use EncoreDigitalGroup\LaravelOperations\Commands\Utils\ColoredOutput;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationManager;

abstract class LaravelOperationsCommand extends Command
{
    use ColoredOutput;

    public const LABEL_PROCESSED = 'PROCESSED';

    public const LABEL_PENDING = 'PENDING';

    public const LABEL_DISPOSED = 'DISPOSED';

    protected string $operationsDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->operationsDirectory = LaravelOperationManager::getDirectoryPath();
    }
}
