<?php

//uses(\EncoreDigitalGroup\LaravelOperations\Tests\Feature\OneTimeOperationCase::class);
use EncoreDigitalGroup\LaravelOperations\Jobs\LaravelOperationProcessJob;
use EncoreDigitalGroup\LaravelOperations\Models\Operation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

afterEach(function () {
    File::deleteDirectory(base_path(config('operations.directory')));

});

test('make command with attributes', function () {
    $filepath = filepath('2015_10_21_072800_awesome_operation.php');
    File::delete($filepath);

    // create operation file
    $this->artisan('operations:make AwesomeOperation')
        ->assertSuccessful()
        ->expectsOutputToContain('One-time operation [2015_10_21_072800_awesome_operation] created successfully.');

    // file was created, but no operation entry yet
    expect($filepath)->toBeFile();

    $fileContent = File::get($filepath);

    // file should contain attributes and method
    $this->assertStringContainsString('protected bool $async = true;', $fileContent);
    $this->assertStringContainsString('protected string $queue = \'default\';', $fileContent);
    $this->assertStringContainsString('protected ?string $tag = null;', $fileContent);
    $this->assertStringContainsString('public function process(): void', $fileContent);
});

test('make command without attributes', function () {
    $filepath = filepath('2015_10_21_072800_awesome_operation.php');
    File::delete($filepath);

    // create operation file with essential flag
    $this->artisan('operations:make AwesomeOperation --essential')->assertSuccessful();

    $fileContent = File::get($filepath);

    // file should contain method
    $this->assertStringContainsString('public function process(): void', $fileContent);

    // file should not contain attributes
    $this->assertStringNotContainsString('protected bool $async = true;', $fileContent);
    $this->assertStringNotContainsString('protected string $queue = \'default\';', $fileContent);
    $this->assertStringNotContainsString('protected ?string $tag = null;', $fileContent);
});

test('make command without attributes shortcut', function () {
    $filepath = filepath('2015_10_21_072800_awesome_operation.php');
    File::delete($filepath);

    // create operation file with shortcut for essential flag
    $this->artisan('operations:make AwesomeOperation -e')->assertSuccessful();

    $fileContent = File::get($filepath);

    // file should contain method
    $this->assertStringContainsString('public function process(): void', $fileContent);

    // file should not contain attributes
    $this->assertStringNotContainsString('protected bool $async = true;', $fileContent);
    $this->assertStringNotContainsString('protected string $queue = \'default\';', $fileContent);
    $this->assertStringNotContainsString('protected ?string $tag = null;', $fileContent);
});

test('the whole command process', function () {
    $filepath = filepath('2015_10_21_072800_awesome_operation.php');
    File::delete($filepath);

    // no files, database entries or jobs
    $this->assertFileDoesNotExist($filepath);
    expect(Operation::count())->toEqual(0);
    Queue::assertNothingPushed();

    // create operation file
    $this->artisan('operations:make AwesomeOperation')
        ->assertSuccessful()
        ->expectsOutputToContain('One-time operation [2015_10_21_072800_awesome_operation] created successfully.');

    // file was created, but no operation entry yet
    expect($filepath)->toBeFile()
        ->and(Operation::count())->toEqual(0);

    // process available operations succesfully
    $this->artisan('operations:process')
        ->assertSuccessful()
        ->expectsOutputToContain('Processing operations.')
        ->expectsOutputToContain('2015_10_21_072800_awesome_operation')
        ->expectsOutputToContain('Processing finished.');

    // operation was exectued - database entry and job was created
    expect(Operation::count())->toEqual(1);
    Queue::assertPushed(LaravelOperationProcessJob::class, function (LaravelOperationProcessJob $job) {
        return $job->connection === null; // async
    });

    // entry was created successfully
    $operation = Operation::first();
    expect($operation->name)->toEqual('2015_10_21_072800_awesome_operation')
        ->and($operation->processed_at)->toEqual('2015-10-21 07:28:00')
        ->and($operation->dispatched)->toEqual('async');

    // process once more - nothing to do
    $this->artisan('operations:process')
        ->assertSuccessful()
        ->expectsOutputToContain('No operations to process.')
        ->doesntExpectOutputToContain('2015_10_21_072800_awesome_operation');

    // no more jobs were created
    Queue::assertPushed(LaravelOperationProcessJob::class, 1);

    // re-run job explicitly, but cancel process
    $this->artisan('operations:process 2015_10_21_072800_awesome_operation')
        ->expectsConfirmation('Operation was processed before. Process it again?', 'no')
        ->expectsOutputToContain('Operation aborted.');

    // test different processed_at timestamp later
    $this->travel(1)->hour();

    // 2015-10-21 08:28:00
    // re-run job explicitly and confirm
    $this->artisan('operations:process 2015_10_21_072800_awesome_operation')
        ->expectsConfirmation('Operation was processed before. Process it again?', 'yes') //confirm
        ->expectsOutputToContain('Processing operation 2015_10_21_072800_awesome_operation')
        ->expectsOutputToContain('Processing finished.');

    // another job was pushed to the queue
    Queue::assertPushed(LaravelOperationProcessJob::class, 2);

    // another database entry was created
    expect(Operation::count())->toEqual(2);

    // newest entry has updated timestamp
    $operation = Operation::all()->last();
    expect($operation->name)->toEqual('2015_10_21_072800_awesome_operation')
        ->and($operation->processed_at)->toEqual('2015-10-21 08:28:00')
        ->and($operation->dispatched)->toEqual('async');
});

test('sync processing', function () {
    $filepath = filepath('2015_10_21_072800_foo_bar_operation.php');
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
    Queue::assertPushed(LaravelOperationProcessJob::class, function (LaravelOperationProcessJob $job) {
        return $job->connection === 'sync';
    });

    $operation = Operation::first();
    expect($operation->name)->toEqual('2015_10_21_072800_foo_bar_operation')
        ->and($operation->dispatched)->toEqual('sync');
});

test('sync processing with file attribute', function () {
    Queue::assertNothingPushed();

    // create file
    $this->artisan('operations:make FooBarOperation')->assertSuccessful();

    // edit file so it will be executed synchronously
    editFile('2015_10_21_072800_foo_bar_operation.php', '$async = true;', '$async = false;');

    // process
    $this->artisan('operations:process')->assertSuccessful();

    // Job was executed synchronously
    Queue::assertPushed(LaravelOperationProcessJob::class, function (LaravelOperationProcessJob $job) {
        return $job->operationName === '2015_10_21_072800_foo_bar_operation'
            && $job->connection === 'sync' // sync
            && $job->queue === null; // no queue
    });

    $operation = Operation::first();
    expect($operation->name)->toEqual('2015_10_21_072800_foo_bar_operation')
        ->and($operation->dispatched)->toEqual('sync');

    // process again - now asynchronously
    $this->artisan('operations:process 2015_10_21_072800_foo_bar_operation --async')
        ->expectsConfirmation('Operation was processed before. Process it again?', 'yes')
        ->assertSuccessful();

    // Job was executed asynchronously
    Queue::assertPushed(LaravelOperationProcessJob::class, function (LaravelOperationProcessJob $job) {
        return $job->operationName === '2015_10_21_072800_foo_bar_operation'
            && $job->connection === null // async
            && $job->queue === 'default'; // default queue
    });

    $operation = Operation::all()->last();
    expect($operation->name)->toEqual('2015_10_21_072800_foo_bar_operation')
        ->and($operation->dispatched)->toEqual('async');

    // process again - now on queue "foobar"
    $this->artisan('operations:process 2015_10_21_072800_foo_bar_operation --async --queue=foobar')
        ->expectsConfirmation('Operation was processed before. Process it again?', 'yes')
        ->assertSuccessful();

    // Job was executed asynchronously on queue "foobar"
    Queue::assertPushed(LaravelOperationProcessJob::class, function (LaravelOperationProcessJob $job) {
        return $job->operationName === '2015_10_21_072800_foo_bar_operation'
            && $job->connection === null // async
            && $job->queue === 'foobar'; // default queue
    });
});

test('processing with queue', function () {
    Queue::assertNothingPushed();

    // create file
    $this->artisan('operations:make FooBarOperation')->assertSuccessful();

    // edit file so it will use different queue
    editFile('2015_10_21_072800_foo_bar_operation.php', '$queue = \'default\';', '$queue = \'narfpuit\';');

    // process
    $this->artisan('operations:process')->assertSuccessful();

    // Job was executed synchronously
    Queue::assertPushed(LaravelOperationProcessJob::class, function (LaravelOperationProcessJob $job) {
        return $job->operationName === '2015_10_21_072800_foo_bar_operation'
            && $job->connection === null // async
            && $job->queue === 'narfpuit'; // queue narfpuit
    });

    // process again - overwrite queue with "foobar"
    $this->artisan('operations:process 2015_10_21_072800_foo_bar_operation --queue=foobar')
        ->expectsConfirmation('Operation was processed before. Process it again?', 'yes')
        ->assertSuccessful();

    // Job was executed asynchronously on queue "foobar"
    Queue::assertPushed(LaravelOperationProcessJob::class, function (LaravelOperationProcessJob $job) {
        return $job->operationName === '2015_10_21_072800_foo_bar_operation'
            && $job->connection === null // async
            && $job->queue === 'foobar'; // queue foobar
    });
});

test('processing with test flag', function () {
    // no database entry yet
    expect(Operation::count())->toEqual(0);

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
    expect(Operation::count())->toEqual(0);
});

test('processing with tags', function () {
    // create files
    $this->artisan('operations:make FooBarOperation')->assertSuccessful();
    $this->artisan('operations:make NarfPuitOperation')->assertSuccessful();
    $this->artisan('operations:make NullTagOperation')->assertSuccessful();

    // edit files so they will use tags
    editFile('2015_10_21_072800_foo_bar_operation.php', '$tag = null;', '$tag = \'foobar\';');
    editFile('2015_10_21_072800_narf_puit_operation.php', '$tag = null;', '$tag = \'narfpuit\';');

    // error because tag is empty
    $this->artisan('operations:process --test --tag')
        ->expectsOutputToContain('Abort! Do not provide empty tags!')
        ->assertFailed();

    // error because tag is empty
    $this->artisan('operations:process --test --tag=')
        ->expectsOutputToContain('Abort! Do not provide empty tags!')
        ->assertFailed();

    // error because second tag is empty
    $this->artisan('operations:process --test --tag=narfpuit --tag=')
        ->expectsOutputToContain('Abort! Do not provide empty tags!')
        ->assertFailed();

    // tag does not match, so operations won't get processed
    $this->artisan('operations:process --test --tag=awesome')
        ->expectsOutputToContain('No operations to process.')
        ->doesntExpectOutputToContain('2015_10_21_072800_null_tag_operation')
        ->doesntExpectOutputToContain('2015_10_21_072800_foo_bar_operation')
        ->doesntExpectOutputToContain('2015_10_21_072800_narf_puit_operation')
        ->assertSuccessful();

    // foobar operation will be processed because tag matches
    $this->artisan('operations:process --test --tag=foobar')
        ->doesntExpectOutputToContain('2015_10_21_072800_null_tag_operation')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation')
        ->doesntExpectOutputToContain('No operations to process.')
        ->doesntExpectOutputToContain('2015_10_21_072800_narf_puit_operation')
        ->assertSuccessful();

    // narfpuit operation will be processed because tag matches
    $this->artisan('operations:process --test --tag=narfpuit')
        ->doesntExpectOutputToContain('2015_10_21_072800_null_tag_operation')
        ->expectsOutputToContain('2015_10_21_072800_narf_puit_operation')
        ->doesntExpectOutputToContain('No operations to process.')
        ->doesntExpectOutputToContain('2015_10_21_072800_foo_bar_operation')
        ->assertSuccessful();

    // only foobar operations will be processed because awesome tag does not match
    $this->artisan('operations:process --test --tag=awesome --tag=foobar')
        ->doesntExpectOutputToContain('2015_10_21_072800_null_tag_operation')
        ->doesntExpectOutputToContain('2015_10_21_072800_narf_puit_operation')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation')
        ->doesntExpectOutputToContain('No operations to process.')
        ->assertSuccessful();

    // both operations will be processed because tag match
    $this->artisan('operations:process --test --tag=narfpuit --tag=foobar')
        ->doesntExpectOutputToContain('2015_10_21_072800_null_tag_operation')
        ->expectsOutputToContain('2015_10_21_072800_narf_puit_operation')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation')
        ->doesntExpectOutputToContain('No operations to process.')
        ->assertSuccessful();

    // both operation will be processed because no tag is given
    $this->artisan('operations:process --test')
        ->expectsOutputToContain('2015_10_21_072800_null_tag_operation')
        ->expectsOutputToContain('2015_10_21_072800_narf_puit_operation')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation')
        ->doesntExpectOutputToContain('No operations to process.')
        ->assertSuccessful();
});

test('operations show command', function () {
    // no files found
    $this->artisan('operations:show')
        ->expectsOutputToContain('No operations found.');

    // create operations
    $this->artisan('operations:make FooBarOperation')->assertSuccessful();

    // no files found
    $this->artisan('operations:show')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation');

    // PENDING
    $this->artisan('operations:process')->assertSuccessful();

    $this->artisan('operations:show')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation');

    // PROCESSED
    // create operations
    $this->artisan('operations:make AwesomeOperation')->assertSuccessful();

    // new pending operation added
    $this->artisan('operations:show')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // PROCESSED
        ->expectsOutputToContain('2015_10_21_072800_awesome_operation');

    // PENDING
    $this->artisan('operations:process')->assertSuccessful();

    // create operations
    $this->artisan('operations:make SuperiorOperation')->assertSuccessful();

    // seconds operation was processed, third operation was added
    $this->artisan('operations:show')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // PROCESSED
        ->expectsOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
        ->expectsOutputToContain('2015_10_21_072800_superior_operation');

    // PENDING
    // delete first file
    File::delete(filepath('2015_10_21_072800_foo_bar_operation.php'));

    $this->artisan('operations:show')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->expectsOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
        ->expectsOutputToContain('2015_10_21_072800_superior_operation');

    // PENDING
    // filter disposed
    $this->artisan('operations:show disposed')
        ->expectsOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->doesntExpectOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
        ->doesntExpectOutputToContain('2015_10_21_072800_superior_operation');

    // PENDING
    // filter processed
    $this->artisan('operations:show processed')
        ->doesntExpectOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->expectsOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
        ->doesntExpectOutputToContain('2015_10_21_072800_superior_operation');

    // PENDING
    // filter pending
    $this->artisan('operations:show pending')
        ->doesntExpectOutputToContain('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->doesntExpectOutputToContain('2015_10_21_072800_awesome_operation') // PROCESSED
        ->expectsOutputToContain('2015_10_21_072800_superior_operation');

    // PENDING
    // filter pending
    $this->artisan('operations:show stuff')
        ->assertFailed()
        ->expectsOutputToContain('Given filter is not valid. Allowed filters: pending|processed|disposed.');
});

function filepath(string $filename): string
{
    return base_path(config('operations.directory')) . DIRECTORY_SEPARATOR . $filename;
}

function editFile(string $filename, string $search, string $replace)
{
    $filepath = filepath($filename);
    $fileContent = File::get($filepath);
    $newContent = Str::replaceFirst($search, $replace, $fileContent);
    File::put($filepath, $newContent);
}