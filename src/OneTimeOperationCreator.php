<?php

namespace TimoKoerber\LaravelOneTimeOperations;

use ErrorException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OneTimeOperationCreator
{
    protected string $operationsDirectory;

    protected string $providedName;

    protected string $operationName = '';

    public function __construct()
    {
        $this->operationsDirectory = OneTimeOperationManager::getDirectoryPath();
    }

    /**
     * @throws \Throwable
     */
    public static function createOperationFile(string $name): OneTimeOperationFile
    {
        $instance = new self();
        $instance->setProvidedName($name);

        return OneTimeOperationFile::make($instance->createFile());
    }

    /**
     * @throws \Throwable
     */
    public function createFile(): \SplFileInfo
    {
        $path = $this->getPath();
        $stub = $this->getStubFilepath();
        $this->ensureDirectoryExists();

        throw_if(File::exists($path), ErrorException::class, sprintf('File already exists: %s', $path));

        File::put($path, $stub);

        return new \SplFileInfo($path);
    }

    protected function getPath(): string
    {
        return $this->operationsDirectory.DIRECTORY_SEPARATOR.$this->getOperationName();
    }

    protected function getStubFilepath(): string
    {
        return File::get(__DIR__.'/../stubs/one-time-operation.stub');
    }

    public function getOperationName(): string
    {
        if (! $this->operationName) {
            $this->initOperationName();
        }

        return $this->operationName;
    }

    protected function getDatePrefix(): string
    {
        return Carbon::now()->format('Y_m_d_His');
    }

    protected function initOperationName(): void
    {
        $this->operationName = $this->getDatePrefix().'_'.Str::snake($this->providedName).'.php';
    }

    protected function ensureDirectoryExists(): void
    {
        File::ensureDirectoryExists($this->operationsDirectory);
    }

    public function setProvidedName(string $providedName): void
    {
        $this->providedName = $providedName;
    }
}
