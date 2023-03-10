<?php

namespace TimoKoerber\LaravelOneTimeOperations\Tests\Feature;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use SplFileInfo;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationFile;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

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

    public function test_get_directory_path()
    {
        $this->assertStringEndsWith('tests/files/', OneTimeOperationManager::getDirectoryPath()); // path was set by self::mockDirectory()
    }

    public function test_get_path_to_file_by_name()
    {
        $this->assertStringEndsWith('/tests/files/narfpuit.php', OneTimeOperationManager::pathToFileByName('narfpuit'));
        $this->assertStringEndsWith('/tests/files/20220101_223355_foobar.php', OneTimeOperationManager::pathToFileByName('20220101_223355_foobar'));
    }

    public function test_build_filename()
    {
        $this->assertEquals('foo.php', OneTimeOperationManager::buildFilename('foo'));
        $this->assertEquals('bar.php', OneTimeOperationManager::buildFilename('bar'));
    }

    public function test_get_operation_name_from_filename()
    {
        $this->assertEquals('20220223_foo', OneTimeOperationManager::getOperationNameFromFilename('20220223_foo.php'));
        $this->assertEquals('20220223_bar', OneTimeOperationManager::getOperationNameFromFilename('20220223_bar.php'));
    }

    public function test_get_table_name()
    {
        $this->assertEquals('operations', OneTimeOperationManager::getTableName()); // was set in parent::mockTable();
    }

    public function test_get_operation_file_by_model()
    {
        $operationModel = Operation::factory()->make(['name' => self::TEST_OPERATION_NAME]);

        $operationFile = OneTimeOperationManager::getOperationFileByModel($operationModel);

        $this->assertInstanceOf(OneTimeOperationFile::class, $operationFile);
        $this->assertInstanceOf(OneTimeOperation::class, $operationFile->getClassObject());
        $this->assertEquals($operationModel->name, $operationFile->getOperationName());
    }

    public function test_get_operation_file_by_model_throws_exception()
    {
        $operationModel = Operation::factory()->make(['name' => 'file_does_not_exist']); // matching file does noe exist

        $this->expectException(FileNotFoundException::class);

        OneTimeOperationManager::getOperationFileByModel($operationModel);
    }

    public function test_get_class_object_by_name_missing_file()
    {
        $this->expectException(FileNotFoundException::class);

        OneTimeOperationManager::getClassObjectByName('file_does_not_exist');
    }

    public function test_get_class_object_by_name()
    {
        $operationClass = OneTimeOperationManager::getClassObjectByName(self::TEST_OPERATION_NAME);

        $this->assertInstanceOf(OneTimeOperation::class, $operationClass);
    }

    public function test_get_all_operation_files()
    {
        $files = OneTimeOperationManager::getAllFiles();

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

    public function test_get_unprocessed_files()
    {
        $files = OneTimeOperationManager::getUnprocessedFiles();

        /** @var SplFileInfo $firstFile */
        $firstFile = $files->first();

        $this->assertInstanceOf(Collection::class, $files);
        $this->assertCount(2, $files);

        $this->assertInstanceOf(SplFileInfo::class, $firstFile);

        // create entry for file #1 -> file is processed
        Operation::storeOperation('xxxx_xx_xx_xxxxxx_foo_bar', true);

        $files = OneTimeOperationManager::getUnprocessedFiles();
        $this->assertCount(1, $files);

        // create entry for file #2 -> file is processed
        Operation::storeOperation('xxxx_xx_xx_xxxxxx_narf_puit', false);

        $files = OneTimeOperationManager::getUnprocessedFiles();
        $this->assertCount(0, $files);
    }

    public function test_get_unprocessed_operation_files()
    {
        $files = OneTimeOperationManager::getUnprocessedOperationFiles();

        $this->assertInstanceOf(Collection::class, $files);
        $this->assertCount(2, $files);

        /** @var SplFileInfo $firstFile */
        $firstFile = $files->first();

        $this->assertInstanceOf(OneTimeOperationFile::class, $firstFile);
    }
}
