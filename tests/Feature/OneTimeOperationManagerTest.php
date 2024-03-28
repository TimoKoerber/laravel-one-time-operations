<?php

use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\artisan;
use Illuminate\Support\Facades\File;

it('operations_show_command', function () {
    // no files found
    Artisan::call('operations:show')
        ->expectsOutput('No operations found.');

    // create operations
    artisan('operations:make FooBarOperation')->assertSuccessful();

    // no files found
    artisan('operations:show')
        ->expectsOutput('2015_10_21_072800_foo_bar_operation');

    // PENDING
    artisan('operations:process')->assertSuccessful();

    artisan('operations:show')
        ->expectsOutput('2015_10_21_072800_foo_bar_operation');

    // PROCESSED
    // create operations
    artisan('operations:make AwesomeOperation')->assertSuccessful();

    // new pending operation added
    artisan('operations:show')
        ->expectsOutput('2015_10_21_072800_foo_bar_operation') // PROCESSED
        ->expectsOutput('2015_10_21_072800_awesome_operation');

    // PENDING
    artisan('operations:process')->assertSuccessful();

    // create operations
    artisan('operations:make SuperiorOperation')->assertSuccessful();

    // seconds operation was processed, third operation was added
    artisan('operations:show')
        ->expectsOutput('2015_10_21_072800_foo_bar_operation') // PROCESSED
        ->expectsOutput('2015_10_21_072800_awesome_operation') // PROCESSED
        ->expectsOutput('2015_10_21_072800_superior_operation');

    // PENDING
    // delete first file
    File::delete(filepath('2015_10_21_072800_foo_bar_operation.php'));

    artisan('operations:show')
        ->expectsOutput('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->expectsOutput('2015_10_21_072800_awesome_operation') // PROCESSED
        ->expectsOutput('2015_10_21_072800_superior_operation');

    // PENDING
    // filter disposed
    artisan('operations:show disposed')
        ->expectsOutput('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->doesntExpectOutput('2015_10_21_072800_awesome_operation') // PROCESSED
        ->doesntExpectOutput('2015_10_21_072800_superior_operation');

    // PENDING
    // filter processed
    artisan('operations:show processed')
        ->doesntExpectOutput('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->expectsOutput('2015_10_21_072800_awesome_operation') // PROCESSED
        ->doesntExpectOutput('2015_10_21_072800_superior_operation');

    // PENDING
    // filter pending
    artisan('operations:show pending')
        ->doesntExpectOutput('2015_10_21_072800_foo_bar_operation') // DISPOSED
        ->doesntExpectOutput('2015_10_21_072800_awesome_operation') // PROCESSED
        ->expectsOutput('2015_10_21_072800_superior_operation');

    // PENDING
    // filter pending
    artisan('operations:show stuff')
        ->assertFailed()
        ->expectsOutput('Given filter is not valid. Allowed filters: pending|processed|disposed.');
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