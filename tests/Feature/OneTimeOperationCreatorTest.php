<?php

namespace EncoreDigitalGroup\LaravelOperations\Tests\Feature;

use EncoreDigitalGroup\LaravelOperations\LaravelOperation;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationCreator;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class OneTimeOperationCreatorTest extends OneTimeOperationCase
{
    /** @test */
    public function it_creates_an_operation_file_instance()
    {
        $directory = Config::get('operations.directory');
        $filepath = base_path($directory) . DIRECTORY_SEPARATOR . '2015_10_21_072800_test_operation.php';

        $operationFile = LaravelOperationCreator::createOperationFile('TestOperation');

        $this->assertFileExists($filepath);
        $this->assertInstanceOf(LaravelOperationFile::class, $operationFile);
        $this->assertInstanceOf(LaravelOperation::class, $operationFile->getClassObject());
        $this->assertEquals('2015_10_21_072800_test_operation', $operationFile->getOperationName());

        File::delete($filepath);
    }

    /** @test */
    public function it_creates_an_operation_file_with_custom_stub()
    {
        $mockFile = File::partialMock();
        $mockFile->allows('exists')->with(base_path('stubs/one-time-operation.stub'))->andReturnTrue();
        $mockFile->allows('get')->with(base_path('stubs/one-time-operation.stub'))->andReturns('This is a custom stub');

        $directory = Config::get('operations.directory');
        $filepath = base_path($directory) . DIRECTORY_SEPARATOR . '2015_10_21_072800_test_operation.php';

        LaravelOperationCreator::createOperationFile('TestOperation');

        $this->assertFileExists($filepath);
        $this->assertStringContainsString('This is a custom stub', File::get($filepath));

        File::delete($filepath);
    }
}
