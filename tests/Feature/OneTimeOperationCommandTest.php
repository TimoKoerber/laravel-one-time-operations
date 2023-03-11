<?php

namespace TimoKoerber\LaravelOneTimeOperations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use TimoKoerber\LaravelOneTimeOperations\Jobs\OneTimeOperationProcessJob;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;

class OneTimeOperationCommandTest extends OneTimeOperationCase
{
    use RefreshDatabase;

    /** @test */
    public function test_the_whole_command_process()
    {
        $filepath = $this->filepath('2015_10_21_072800_awesome_operation.php');
        File::delete($filepath);

        // no files, database entries or jobs
        $this->assertFileDoesNotExist($filepath);
        $this->assertEquals(0, Operation::count());
        Queue::assertNothingPushed();

        // create operation file
        $this->artisan('operations:make AwesomeOperation')
            ->assertSuccessful()
            ->expectsOutputToContain('One-time operation [2015_10_21_072800_awesome_operation] created successfully.');

        // file was created, but no operation entry yet
        $this->assertFileExists($filepath);
        $this->assertEquals(0, Operation::count());

        // process available operations succesfully
        $this->artisan('operations:process')
            ->assertSuccessful()
            ->expectsOutputToContain('Processing operations.')
            ->expectsOutputToContain('2015_10_21_072800_awesome_operation')
            ->expectsOutputToContain('Processing finished.');

        // operation was exectued - database entry and job was created
        $this->assertEquals(1, Operation::count());
        Queue::assertPushed(OneTimeOperationProcessJob::class, function (OneTimeOperationProcessJob $job) {
            return $job->connection === null; // async
        });

        // entry was created successfully
        $operation = Operation::first();
        $this->assertEquals('2015_10_21_072800_awesome_operation', $operation->name);
        $this->assertEquals('2015-10-21 07:28:00', $operation->processed_at);
        $this->assertEquals('async', $operation->dispatched);

        // process once more - nothing to do
        $this->artisan('operations:process')
            ->assertSuccessful()
            ->expectsOutputToContain('No operations to process.')
            ->doesntExpectOutputToContain('2015_10_21_072800_awesome_operation');

        // no more jobs were created
        Queue::assertPushed(OneTimeOperationProcessJob::class, 1);

        // re-run job explicitly, but cancel process
        $this->artisan('operations:process 2015_10_21_072800_awesome_operation')
            ->expectsConfirmation('Operation was processed before. Process it again?', 'no')
            ->expectsOutputToContain('Operation aborted.');

        // test different processed_at timestamp later
        $this->travel(1)->hour(); // 2015-10-21 08:28:00

        // re-run job explicitly and confirm
        $this->artisan('operations:process 2015_10_21_072800_awesome_operation')
            ->expectsConfirmation('Operation was processed before. Process it again?', 'yes') //confirm
            ->expectsOutputToContain('Processing operation 2015_10_21_072800_awesome_operation')
            ->expectsOutputToContain('Processing finished.');

        // another job was pushed to the queue
        Queue::assertPushed(OneTimeOperationProcessJob::class, 2);

        // another database entry was created
        $this->assertEquals(2, Operation::count());

        // newest entry has updated timestamp
        $operation = Operation::all()->last();
        $this->assertEquals('2015_10_21_072800_awesome_operation', $operation->name);
        $this->assertEquals('2015-10-21 08:28:00', $operation->processed_at);
        $this->assertEquals('async', $operation->dispatched);
    }

    public function test_sync_processing()
    {
        $filepath = $this->filepath('2015_10_21_072800_foo_bar_operation.php');
        File::delete($filepath);

        // no jobs yet
        Queue::assertNothingPushed();

        // create operation
        $this->artisan('operations:make FooBarOperation')->assertSuccessful();

        // Process - error is thrown because both flags are used
        $this->artisan('operations:process --sync --async')
            ->assertFailed()
            ->expectsOutputToContain('Abort! Process either with --sync or --async.');

        // process operation without jobs
        $this->artisan('operations:process --sync')
            ->assertSuccessful()
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation');

        // Job was executed synchronously
        Queue::assertPushed(OneTimeOperationProcessJob::class, function (OneTimeOperationProcessJob $job) {
            return $job->connection === 'sync';
        });

        $operation = Operation::first();
        $this->assertEquals('2015_10_21_072800_foo_bar_operation', $operation->name);
        $this->assertEquals('sync', $operation->dispatched);
    }

    public function test_sync_processing_with_file_attribute()
    {
        $filepath = $this->filepath('2015_10_21_072800_foo_bar_operation.php');
        Queue::assertNothingPushed();

        // create file
        $this->artisan('operations:make FooBarOperation')->assertSuccessful();

        // edit file so it will be executed synchronously
        $fileContent = File::get($filepath);
        $newContent = Str::replaceFirst('$async = true;', '$async = false;', $fileContent);
        File::put($filepath, $newContent);

        // process
        $this->artisan('operations:process')->assertSuccessful();

        // Job was executed synchronously
        Queue::assertPushed(OneTimeOperationProcessJob::class, function (OneTimeOperationProcessJob $job) {
            return $job->operationName === '2015_10_21_072800_foo_bar_operation' && $job->connection === 'sync'; // sync
        });

        $operation = Operation::first();
        $this->assertEquals('2015_10_21_072800_foo_bar_operation', $operation->name);
        $this->assertEquals('sync', $operation->dispatched);

        // process again - now asynchronously
        $this->artisan('operations:process 2015_10_21_072800_foo_bar_operation --async')
            ->expectsConfirmation('Operation was processed before. Process it again?', 'yes')
            ->assertSuccessful();

        // Job was executed asynchronously
        Queue::assertPushed(OneTimeOperationProcessJob::class, function (OneTimeOperationProcessJob $job) {
            return $job->operationName === '2015_10_21_072800_foo_bar_operation' && $job->connection === null; // async
        });

        $operation = Operation::all()->last();
        $this->assertEquals('2015_10_21_072800_foo_bar_operation', $operation->name);
        $this->assertEquals('async', $operation->dispatched);
    }

    public function test_processing_with_test_flag()
    {
        // no database entry yet
        $this->assertEquals(0, Operation::count());

        // create file
        $this->artisan('operations:make FooBarOperation')->assertSuccessful();

        // process with test flag
        $this->artisan('operations:process --test')
            ->assertSuccessful()
            ->expectsOutputToContain('Testmode! Operation won\'t be tagged as `processed`.')
            ->expectsOutputToContain('Processing operations.')
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation')
            ->expectsOutputToContain('Processing finished.');

        // still no database entry because of test mode
        $this->assertEquals(0, Operation::count());
    }

    public function test_operations_show_command()
    {
        // no files found
        $this->artisan('operations:show')
            ->expectsOutputToContain('No operations found.');

        // create operations
        $this->artisan('operations:make FooBarOperation')->assertSuccessful();

        // no files found
        $this->artisan('operations:show')
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation'); // PENDING

        $this->artisan('operations:process')->assertSuccessful();

        $this->artisan('operations:show')
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation'); // PROCESSED

        // create operations
        $this->artisan('operations:make AwesomeOperation')->assertSuccessful();

        // new pending operation added
        $this->artisan('operations:show')
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // PROCESSED
            ->expectsOutputToContain('2015_10_21_072800_awesome_operation'); // PENDING

        $this->artisan('operations:process')->assertSuccessful();

        // create operations
        $this->artisan('operations:make SuperiorOperation')->assertSuccessful();

        // seconds operation was processed, third operation was added
        $this->artisan('operations:show')
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // PROCESSED
            ->expectsOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
            ->expectsOutputToContain('2015_10_21_072800_superior_operation'); // PENDING

        // delete first file
        File::delete($this->filepath('2015_10_21_072800_foo_bar_operation.php'));

        $this->artisan('operations:show')
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
            ->expectsOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
            ->expectsOutputToContain('2015_10_21_072800_superior_operation'); // PENDING

        // filter disposed
        $this->artisan('operations:show disposed')
            ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
            ->doesntExpectOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
            ->doesntExpectOutputToContain('2015_10_21_072800_superior_operation'); // PENDING

        // filter processed
        $this->artisan('operations:show processed')
            ->doesntExpectOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
            ->expectsOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
            ->doesntExpectOutputToContain('2015_10_21_072800_superior_operation'); // PENDING

        // filter pending
        $this->artisan('operations:show pending')
            ->doesntExpectOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
            ->doesntExpectOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
            ->expectsOutputToContain('2015_10_21_072800_superior_operation'); // PENDING

        // filter pending
        $this->artisan('operations:show stuff')
            ->assertFailed()
            ->expectsOutputToContain('Given filter is not valid. Allowed filters: pending|processed|disposed.');
    }

    protected function filepath(string $filename): string
    {
        return base_path(config('one-time-operations.directory')).DIRECTORY_SEPARATOR.$filename;
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path(config('one-time-operations.directory')));

        parent::tearDown();
    }
}
