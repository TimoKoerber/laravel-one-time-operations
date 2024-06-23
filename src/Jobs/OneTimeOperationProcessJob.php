<?php

namespace TimoKoerber\LaravelOneTimeOperations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use TimoKoerber\LaravelOneTimeOperations\Models\FailedOperation;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $operationName;

    public function __construct(string $operationName)
    {
        $this->operationName = $operationName;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $operationClassInstance = OneTimeOperationManager::getClassObjectByName($this->operationName);
        try {
            $operationClassInstance->process();
        } catch (Throwable $th) {
            $operation = Operation::where('name', $this->operationName)->first();

            if ($operation) {
                FailedOperation::create([
                    'name' => $this->operationName,
                    'queue' => $operationClassInstance->getQueue(),
                    'connection' => $operationClassInstance->getConnection(),
                    'dispatched_at' => $operation->processed_at,
                    'exception' => [
                        'message' => $th->getMessage(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'trace' => $th->getTraceAsString(),
                        'code' => $th->getCode(),
                    ],
                ]);
                $operation->delete();
            }
            throw $th;
        }
    }
}
