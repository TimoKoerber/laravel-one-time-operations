<?php

//uses(\EncoreDigitalGroup\LaravelOperations\Tests\Feature\OneTimeOperationCase::class);
use EncoreDigitalGroup\LaravelOperations\Models\Operation;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('stores operation', function () {
    $operationModel = Operation::storeOperation('foobar_amazing', true);

    expect($operationModel)->toBeInstanceOf(Operation::class)
        ->and($operationModel->name)->toEqual('foobar_amazing')
        ->and($operationModel->file_path)->toEndWith('tests/files/foobar_amazing.php')
        ->and($operationModel->dispatched)->toEqual('async')
        ->and($operationModel->processed_at)->toEqual('2015-10-21 07:28:00');
});