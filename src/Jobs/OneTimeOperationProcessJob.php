<?php

namespace TimoKoerber\LaravelOneTimeOperations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;

class OneTimeOperationProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $operationName;

    public function __construct(string $operationName)
    {
        $this->operationName = $operationName;
    }

    public function handle(): void
    {
        try {
            OneTimeOperationManager::getClassObjectByName($this->operationName)->process();
        } catch (\Throwable $th) {
            $operation = Operation::where('name', $this->operationName)->first();

            if($operation)
            {
                $operation->delete();
            }
        }
        
    }
}
