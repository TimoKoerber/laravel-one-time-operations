<?php

namespace EncoreDigitalGroup\LaravelOperations\Jobs;

use EncoreDigitalGroup\LaravelOperations\LaravelOperationManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LaravelOperationProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $operationName;

    public function __construct(string $operationName)
    {
        $this->operationName = $operationName;
    }

    public function handle(): void
    {
        LaravelOperationManager::getClassObjectByName($this->operationName)->process();
    }
}
