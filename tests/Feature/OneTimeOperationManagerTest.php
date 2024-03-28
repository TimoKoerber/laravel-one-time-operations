<?php

namespace EncoreDigitalGroup\LaravelOperations\Tests\Feature;

use EncoreDigitalGroup\LaravelOperations\LaravelOperation;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationFile;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationManager;
use EncoreDigitalGroup\LaravelOperations\Models\Operation;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use SplFileInfo;

class OneTimeOperationManagerTest extends OneTimeOperationCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFileDirectory();
    }

    protected function tearDown(): void
    {
        $this->deleteFileDirectory();
    }

    /**
     * @test
     */
    public function get_directory_path()
    {
        $this->assertStringEndsWith('tests/files/', LaravelOperationManager::getDirectoryPath()); // path was set by self::mockDirectory()
    }

    /**
     * @test
     */
    public function get_path_to_file_by_name()
    {
        $this->assertStringEndsWith('/tests/files/narfpuit.php', LaravelOperationManager::pathToFileByName('narfpuit'));
        $this->assertStringEndsWith('/tests/files/20220101_223355_foobar.php', LaravelOperationManager::pathToFileByName('20220101_223355_foobar'));
    }

    /**
     * @test
     */
    public function build_filename()
    {
        $this->assertEquals('foo.php', LaravelOperationManager::buildFilename('foo'));
        $this->assertEquals('bar.php', LaravelOperationManager::buildFilename('bar'));
    }

    /**
     * @test
     */
    public function get_operation_name_from_filename()
    {
        $this->assertEquals('20220223_foo', LaravelOperationManager::getOperationNameFromFilename('20220223_foo.php'));
        $this->assertEquals('20220223_bar', LaravelOperationManager::getOperationNameFromFilename('20220223_bar.php'));
    }

    /**
     * @test
     */
    public function get_table_name()
    {
        $this->assertEquals('operations', LaravelOperationManager::getTableName()); // was set in parent::mockTable();
    }

    /**
     * @test
     */
    public function get_operation_file_by_model()
    {
        $operationModel = Operation::factory()->make(['name' => self::TEST_OPERATION_NAME]);

        $operationFile = LaravelOperationManager::getOperationFileByModel($operationModel);

        $this->assertInstanceOf(LaravelOperationFile::class, $operationFile);
        $this->assertInstanceOf(LaravelOperation::class, $operationFile->getClassObject());
        $this->assertEquals($operationModel->name, $operationFile->getOperationName());
    }

    /**
     * @test
     */
    public function get_operation_file_by_model_throws_exception()
    {
        $operationModel = Operation::factory()->make(['name' => 'file_does_not_exist']); // matching file does noe exist

        $this->expectException(FileNotFoundException::class);

        LaravelOperationManager::getOperationFileByModel($operationModel);
    }

    /**
     * @test
     */
    public function get_class_object_by_name_missing_file()
    {
        $this->expectException(FileNotFoundException::class);

        LaravelOperationManager::getClassObjectByName('file_does_not_exist');
    }

    /**
     * @test
     */
    public function get_class_object_by_name()
    {
        $operationClass = LaravelOperationManager::getClassObjectByName(self::TEST_OPERATION_NAME);

        $this->assertInstanceOf(LaravelOperation::class, $operationClass);
    }

    /**
     * @test
     */
    public function get_all_operation_files()
    {
        $files = LaravelOperationManager::getAllFiles();

        /** @var SplFileInfo $firstFile */
        $firstFile = $files->first();
        /** @var SplFileInfo $secondFile */
        $secondFile = $files->last();

        $this->assertInstanceOf(Collection::class, $files);
        $this->assertCount(2, $files);

        $this->assertInstanceOf(SplFileInfo::class, $firstFile);
        $this->assertEquals('xxxx_xx_xx_xxxxxx_foo_bar.php', $firstFile->getBasename());

        $this->assertInstanceOf(SplFileInfo::class, $secondFile);
        $this->assertEquals('xxxx_xx_xx_xxxxxx_narf_puit.php', $secondFile->getBasename());
    }

    /**
     * @test
     */
    public function get_unprocessed_files()
    {
        $files = LaravelOperationManager::getUnprocessedFiles();

        /** @var SplFileInfo $firstFile */
        $firstFile = $files->first();

        $this->assertInstanceOf(Collection::class, $files);
        $this->assertCount(2, $files);

        $this->assertInstanceOf(SplFileInfo::class, $firstFile);

        // create entry for file #1 -> file is processed
        Operation::storeOperation('xxxx_xx_xx_xxxxxx_foo_bar', true);

        $files = LaravelOperationManager::getUnprocessedFiles();
        $this->assertCount(1, $files);

        // create entry for file #2 -> file is processed
        Operation::storeOperation('xxxx_xx_xx_xxxxxx_narf_puit', false);

        $files = LaravelOperationManager::getUnprocessedFiles();
        $this->assertCount(0, $files);
    }

    /**
     * @test
     */
    public function get_unprocessed_operation_files()
    {
        $files = LaravelOperationManager::getUnprocessedOperationFiles();

        $this->assertInstanceOf(Collection::class, $files);
        $this->assertCount(2, $files);

        /** @var SplFileInfo $firstFile */
        $firstFile = $files->first();

        $this->assertInstanceOf(LaravelOperationFile::class, $firstFile);
    }
}
