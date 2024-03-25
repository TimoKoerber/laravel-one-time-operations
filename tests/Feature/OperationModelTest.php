<?php

namespace EncoreDigitalGroup\LaravelOperations\Tests\Feature;

use EncoreDigitalGroup\LaravelOperations\Models\Operation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OperationModelTest extends OneTimeOperationCase
{
    use RefreshDatabase;

    /** @test */
    public function it_stores_operation()
    {
        $operationModel = Operation::storeOperation('foobar_amazing', true);

        $this->assertInstanceOf(Operation::class, $operationModel);
        $this->assertEquals('foobar_amazing', $operationModel->name);
        $this->assertStringEndsWith('tests/files/foobar_amazing.php', $operationModel->file_path);
        $this->assertEquals('async', $operationModel->dispatched);
        $this->assertEquals('2015-10-21 07:28:00', $operationModel->processed_at);
    }
}
