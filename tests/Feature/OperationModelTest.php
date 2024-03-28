<?php

uses(\EncoreDigitalGroup\LaravelOperations\Tests\Feature\OneTimeOperationCase::class);
use EncoreDigitalGroup\LaravelOperations\Models\Operation;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('stores operation', function () {
    $operationModel = Operation::storeOperation('foobar_amazing', true);

    expect($operationModel)->toBeInstanceOf(Operation::class);
    expect($operationModel->name)->toEqual('foobar_amazing');
    expect($operationModel->file_path)->toEndWith('tests/files/foobar_amazing.php');
    expect($operationModel->dispatched)->toEqual('async');
    expect($operationModel->processed_at)->toEqual('2015-10-21 07:28:00');
});