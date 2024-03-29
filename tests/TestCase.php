<?php

namespace EncoreDigitalGroup\LaravelOperations\Tests;

use EncoreDigitalGroup\LaravelOperations\Providers\LaravelOperationsServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

//use PHPUnit\Framework\TestCase as BaseTestCase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const TEST_OPERATION_NAME = 'xxxx_xx_xx_xxxxxx_foo_bar';

    protected const TEST_FILE_DIRECTORY = 'tests/files';

    protected const TEST_TABLE_NAME = 'operations';

    protected const TEST_DATETIME = '2015-10-21 07:28:00';

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Carbon::setTestNow(self::TEST_DATETIME);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
//        DB::rollback();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelOperationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Setup directory to provide test files
        $app['config']->set('operations.directory', '../../../../' . self::TEST_FILE_DIRECTORY);
        $app['config']->set('operations.table', self::TEST_TABLE_NAME);
        $app['config']->set('queue.default', 'database');
    }

    protected function deleteFileDirectory()
    {
        File::deleteDirectory(self::TEST_FILE_DIRECTORY);
    }

    protected function mockFileDirectory()
    {
        File::copyDirectory('tests/resources', self::TEST_FILE_DIRECTORY);
    }
}
