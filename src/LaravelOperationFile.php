<?php

namespace EncoreDigitalGroup\LaravelOperations;

use EncoreDigitalGroup\LaravelOperations\Models\Operation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class LaravelOperationFile
{
    protected SplFileInfo $file;

    protected ?LaravelOperation $classObject = null;

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    public static function make(SplFileInfo $file): self
    {
        return new self($file);
    }

    public function getOperationName(): string
    {
        $pathElements = explode(DIRECTORY_SEPARATOR, $this->file->getRealPath());
        $filename = end($pathElements);

        return Str::remove('.php', $filename);
    }

    public function getClassObject(): LaravelOperation
    {
        if (! $this->classObject) {
            $this->classObject = File::getRequire($this->file);
        }

        return $this->classObject;
    }

    public function getModel(): ?Operation
    {
        return Operation::whereName($this->getOperationName())->first();
    }
}
