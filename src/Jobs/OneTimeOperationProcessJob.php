<?php

namespace TimoKoerber\LaravelOneTimeOperations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TimoKoerber\LaravelOneTimeOperations\Contracts\OneTimeOperationProcessJob as ContractsOneTimeOperationProcessJob;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationProcessJob implements ShouldQueue, ContractsOneTimeOperationProcessJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $operationName;

    public function __construct($operationName = null)
    {
        $this->operationName = $operationName;
    }

    public function handle(): void
    {
        OneTimeOperationManager::getClassObjectByName($this->operationName)->process();
    }
}
