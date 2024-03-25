<?php

namespace EncoreDigitalGroup\LaravelOperations;

use ErrorException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;
use Throwable;

class LaravelOperationCreator
{
    protected string $operationsDirectory;

    protected string $providedName;

    protected string $operationName = '';

    protected bool $essential = false;

    public function __construct()
    {
        $this->operationsDirectory = LaravelOperationManager::getDirectoryPath();
    }

    /**
     * @throws Throwable
     */
    public static function createOperationFile(string $name, bool $essential = false): LaravelOperationFile
    {
        $instance = new self;
        $instance->setProvidedName($name);
        $instance->setEssential($essential);

        return LaravelOperationFile::make($instance->createFile());
    }

    /**
     * @throws Throwable
     */
    public function createFile(): SplFileInfo
    {
        $path = $this->getPath();
        $stub = $this->getStubFilepath();
        $this->ensureDirectoryExists();

        throw_if(File::exists($path), ErrorException::class, sprintf('File already exists: %s', $path));

        File::put($path, $stub);

        return new SplFileInfo($path);
    }

    public function getOperationName(): string
    {
        if (! $this->operationName) {
            $this->initOperationName();
        }

        return $this->operationName;
    }

    public function setProvidedName(string $providedName): void
    {
        $this->providedName = $providedName;
    }

    public function setEssential(bool $essential): void
    {
        $this->essential = $essential;
    }

    protected function getPath(): string
    {
        return $this->operationsDirectory . DIRECTORY_SEPARATOR . $this->getOperationName();
    }

    protected function getStubFilepath(): string
    {
        // check for custom stub file
        if (File::exists(base_path('stubs/one-time-operation.stub'))) {
            return File::get(base_path('stubs/one-time-operation.stub'));
        }

        if ($this->essential) {
            return File::get(__DIR__ . '/../stubs/one-time-operation-essential.stub');
        }

        return File::get(__DIR__ . '/../stubs/one-time-operation.stub');
    }

    protected function getDatePrefix(): string
    {
        return Carbon::now()->format('Y_m_d_His');
    }

    protected function initOperationName(): void
    {
        $this->operationName = $this->getDatePrefix() . '_' . Str::snake($this->providedName) . '.php';
    }

    protected function ensureDirectoryExists(): void
    {
        File::ensureDirectoryExists($this->operationsDirectory);
    }
}
