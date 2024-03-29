<?php

//uses(\EncoreDigitalGroup\LaravelOperations\Tests\Feature\OneTimeOperationCase::class);
use EncoreDigitalGroup\LaravelOperations\LaravelOperation;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationFile;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationManager;
use EncoreDigitalGroup\LaravelOperations\Models\Operation;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->mockFileDirectory();
});

afterEach(function () {
    $this->deleteFileDirectory();
});

test('get directory path', function () {
    expect(LaravelOperationManager::getDirectoryPath())->toEndWith('tests/files/');
    // path was set by self::mockDirectory()
});

test('get path to file by name', function () {
    expect(LaravelOperationManager::pathToFileByName('narfpuit'))->toEndWith('/tests/files/narfpuit.php')
        ->and(LaravelOperationManager::pathToFileByName('20220101_223355_foobar'))->toEndWith('/tests/files/20220101_223355_foobar.php');
});

test('build filename', function () {
    expect(LaravelOperationManager::buildFilename('foo'))->toEqual('foo.php')
        ->and(LaravelOperationManager::buildFilename('bar'))->toEqual('bar.php');
});

test('get operation name from filename', function () {
    expect(LaravelOperationManager::getOperationNameFromFilename('20220223_foo.php'))->toEqual('20220223_foo')
        ->and(LaravelOperationManager::getOperationNameFromFilename('20220223_bar.php'))->toEqual('20220223_bar');
});

test('get table name', function () {
    expect(LaravelOperationManager::getTableName())->toEqual('operations');
    // was set in parent::mockTable();
});

test('get operation file by model', function () {
    $operationModel = Operation::factory()->make(['name' => TEST_OPERATION_NAME]);

    $operationFile = LaravelOperationManager::getOperationFileByModel($operationModel);

    expect($operationFile)->toBeInstanceOf(LaravelOperationFile::class)
        ->and($operationFile->getClassObject())->toBeInstanceOf(LaravelOperation::class)
        ->and($operationFile->getOperationName())->toEqual($operationModel->name);
});

test('get operation file by model throws exception', function () {
    $operationModel = Operation::factory()->make(['name' => 'file_does_not_exist']);

    // matching file does noe exist
    $this->expectException(FileNotFoundException::class);

    LaravelOperationManager::getOperationFileByModel($operationModel);
});

test('get class object by name missing file', function () {
    $this->expectException(FileNotFoundException::class);

    LaravelOperationManager::getClassObjectByName('file_does_not_exist');
});

test('get class object by name', function () {
    $operationClass = LaravelOperationManager::getClassObjectByName(TEST_OPERATION_NAME);

    expect($operationClass)->toBeInstanceOf(LaravelOperation::class);
});

test('get all operation files', function () {
    $files = LaravelOperationManager::getAllFiles();

    /** @var SplFileInfo $firstFile */
    $firstFile = $files->first();

    /** @var SplFileInfo $secondFile */
    $secondFile = $files->last();

    expect($files)->toBeInstanceOf(Collection::class)
        ->and($files)->toHaveCount(2)
        ->and($firstFile)->toBeInstanceOf(SplFileInfo::class)
        ->and($firstFile->getBasename())->toEqual('xxxx_xx_xx_xxxxxx_foo_bar.php')
        ->and($secondFile)->toBeInstanceOf(SplFileInfo::class)
        ->and($secondFile->getBasename())->toEqual('xxxx_xx_xx_xxxxxx_narf_puit.php');

});

test('get unprocessed files', function () {
    $files = LaravelOperationManager::getUnprocessedFiles();

    /** @var SplFileInfo $firstFile */
    $firstFile = $files->first();

    expect($files)->toBeInstanceOf(Collection::class)
        ->and($files)->toHaveCount(2)
        ->and($firstFile)->toBeInstanceOf(SplFileInfo::class);

    // create entry for file #1 -> file is processed
    Operation::storeOperation('xxxx_xx_xx_xxxxxx_foo_bar', true);

    $files = LaravelOperationManager::getUnprocessedFiles();
    expect($files)->toHaveCount(1);

    // create entry for file #2 -> file is processed
    Operation::storeOperation('xxxx_xx_xx_xxxxxx_narf_puit', false);

    $files = LaravelOperationManager::getUnprocessedFiles();
    expect($files)->toHaveCount(0);
});

test('get unprocessed operation files', function () {
    $files = LaravelOperationManager::getUnprocessedOperationFiles();

    expect($files)->toBeInstanceOf(Collection::class)
        ->and($files)->toHaveCount(2);

    /** @var SplFileInfo $firstFile */
    $firstFile = $files->first();

    expect($firstFile)->toBeInstanceOf(LaravelOperationFile::class);
});