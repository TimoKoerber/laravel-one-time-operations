<?php

namespace TimoKoerber\LaravelOneTimeOperations;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;

class OneTimeOperationManager
{
    /**
     * @return Collection<OneTimeOperationFile>
     */
    public static function getUnprocessedOperationFiles(): Collection
    {
        $operationFiles = self::getUnprocessedFiles();

        return $operationFiles->map(fn (SplFileInfo $file) => OneTimeOperationFile::make($file));
    }

    /**
     * @return Collection<SplFileInfo>
     */
    public static function getAllOperationFiles(): Collection
    {
        $operationFiles = self::getAllFiles();

        return $operationFiles->map(fn (SplFileInfo $file) => OneTimeOperationFile::make($file));
    }

    /**
     * @return Collection<SplFileInfo>
     */
    public static function getUnprocessedFiles(): Collection
    {
        $allOperationFiles = self::getAllFiles();

        return $allOperationFiles->filter(function (SplFileInfo $operationFilepath) {
            $operation = self::getOperationNameFromFilename($operationFilepath->getBasename());

            return Operation::whereName($operation)->doesntExist();
        });
    }

    /**
     * @return Collection<SplFileInfo>
     */
    public static function getAllFiles(): Collection
    {
        try {
            return collect(File::files(self::getDirectoryPath()));
        } catch (DirectoryNotFoundException $e) {
            return collect();
        }
    }

    public static function getClassObjectByName(string $operationName): OneTimeOperation
    {
        $filepath = self::pathToFileByName($operationName);

        return File::getRequire($filepath);
    }

    public static function getModelByName(string $operationName): ?Operation
    {
        return Operation::whereName($operationName)->first();
    }

    public static function getOperationFileByModel(Operation $operationModel): OneTimeOperationFile
    {
        $filepath = self::pathToFileByName($operationModel->name);

        throw_unless(File::exists($filepath), FileNotFoundException::class);

        return OneTimeOperationFile::make(new SplFileInfo($filepath));
    }

    /**
     * @throws \Throwable
     */
    public static function getOperationFileByName(string $operationName): OneTimeOperationFile
    {
        $filepath = self::pathToFileByName($operationName);

        throw_unless(File::exists($filepath), FileNotFoundException::class, sprintf('File %s does not exist', self::buildFilename($operationName)));

        return OneTimeOperationFile::make(new SplFileInfo($filepath));
    }

    public static function pathToFileByName(string $operationName): string
    {
        return self::getDirectoryPath().self::buildFilename($operationName);
    }

    public static function fileExistsByName(string $operationName): bool
    {
        return File::exists(self::pathToFileByName($operationName));
    }

    public static function getDirectoryName(): string
    {
        return Config::get('one-time-operations.directory');
    }
    
    public static function setDirectoryName($path): void
    {
        Config::set('one-time-operations.directory', $path);
    }

    public static function getDirectoryPath(): string
    {
        return App::basePath(Str::of(self::getDirectoryName())->rtrim('/')).DIRECTORY_SEPARATOR;
    }

    public static function getOperationNameFromFilename(string $filename): string
    {
        return str($filename)->remove('.php');
    }

    public static function getTableName(): string
    {
        return Config::get('one-time-operations.table', 'operations'); // @TODO
    }

    public static function buildFilename($operationName): string
    {
        return $operationName.'.php';
    }
}
