<?php

namespace TimoKoerber\LaravelOneTimeOperations\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationCreator;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationFile;

class OneTimeOperationCreatorTest extends OneTimeOperationCase
{
    /** @test */
    public function it_creates_an_operation_file_instance()
    {
        $directory = Config::get('one-time-operations.directory');
        $filepath = base_path($directory).DIRECTORY_SEPARATOR.'2015_10_21_072800_test_operation.php';

        $operationFile = OneTimeOperationCreator::createOperationFile('TestOperation');

        $this->assertFileExists($filepath);
        $this->assertInstanceOf(OneTimeOperationFile::class, $operationFile);
        $this->assertInstanceOf(OneTimeOperation::class, $operationFile->getClassObject());
        $this->assertEquals('2015_10_21_072800_test_operation', $operationFile->getOperationName());
        $this->assertStringContainsString('tests/files/2015_10_21_072800_test_operation.php', $operationFile->getOperationFilePath());

        File::delete($filepath);
    }

    /** @test */
    public function it_creates_an_operation_file_with_custom_stub()
    {
        $mockFile = File::partialMock();
        $mockFile->allows('exists')->with(base_path('stubs/one-time-operation.stub'))->andReturnTrue();
        $mockFile->allows('get')->with(base_path('stubs/one-time-operation.stub'))->andReturns('This is a custom stub');

        $directory = Config::get('one-time-operations.directory');
        $filepath = base_path($directory).DIRECTORY_SEPARATOR.'2015_10_21_072800_test_operation.php';

        OneTimeOperationCreator::createOperationFile('TestOperation');

        $this->assertFileExists($filepath);
        $this->assertStringContainsString('This is a custom stub', File::get($filepath));

        File::delete($filepath);
    }
}
