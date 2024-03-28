<?php

uses(\EncoreDigitalGroup\LaravelOperations\Tests\Feature\OneTimeOperationCase::class);
use EncoreDigitalGroup\LaravelOperations\LaravelOperation;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationCreator;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;


it('creates an operation file instance', function () {
    $directory = Config::get('operations.directory');
    $filepath = base_path($directory) . DIRECTORY_SEPARATOR . '2015_10_21_072800_test_operation.php';

    $operationFile = LaravelOperationCreator::createOperationFile('TestOperation');

    expect($filepath)->toBeFile();
    expect($operationFile)->toBeInstanceOf(LaravelOperationFile::class);
    expect($operationFile->getClassObject())->toBeInstanceOf(LaravelOperation::class);
    expect($operationFile->getOperationName())->toEqual('2015_10_21_072800_test_operation');

    File::delete($filepath);
});

it('creates an operation file with custom stub', function () {
    $mockFile = File::partialMock();
    $mockFile->allows('exists')->with(base_path('stubs/one-time-operation.stub'))->andReturnTrue();
    $mockFile->allows('get')->with(base_path('stubs/one-time-operation.stub'))->andReturns('This is a custom stub');

    $directory = Config::get('operations.directory');
    $filepath = base_path($directory) . DIRECTORY_SEPARATOR . '2015_10_21_072800_test_operation.php';

    LaravelOperationCreator::createOperationFile('TestOperation');

    expect($filepath)->toBeFile();
    $this->assertStringContainsString('This is a custom stub', File::get($filepath));

    File::delete($filepath);
});
