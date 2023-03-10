<?php

namespace TimoKoerber\LaravelOneTimeOperations\Commands;

use Illuminate\Console\Command;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

abstract class OneTimeOperationsCommand extends Command
{
    protected const LABEL_PROCESSED = 'PROCESSED';

    protected const LABEL_PENDING = 'PENDING';

    protected const LABEL_DISPOSED = 'DISPOSED';

    protected string $operationsDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->operationsDirectory = OneTimeOperationManager::getDirectoryPath();
    }

    protected function bold(string $message): string
    {
        return sprintf('<options=bold>%s</>', $message);
    }

    protected function lightgray(string $message): string
    {
        return sprintf('<fg=white>%s</>', $message);
    }

    protected function gray(string $message): string
    {
        return sprintf('<fg=gray>%s</>', $message);
    }

    protected function brightgreen(string $message): string
    {
        return sprintf('<fg=bright-green>%s</>', $message);
    }

    protected function green(string $message): string
    {
        return sprintf('<fg=green>%s</>', $message);
    }

    protected function white(string $message): string
    {
        return sprintf('<fg=bright-white>%s</>', $message);
    }

    protected function grayBadge(string $message): string
    {
        return sprintf('<fg=#fff;bg=gray>%s</>', $message);
    }
}
