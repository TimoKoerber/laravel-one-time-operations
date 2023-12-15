<?php

namespace TimoKoerber\LaravelOneTimeOperations\Commands;

use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TimoKoerber\LaravelOneTimeOperations\Jobs\OneTimeOperationProcessJob;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationFile;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationsProcessCommand extends OneTimeOperationsCommand implements Isolatable
{
    protected $signature = 'operations:process
                            {name? : Name of specific operation}
                            {--test : Process operation without tagging it as processed, so you can call it again}
                            {--async : Ignore setting in operation and process all operations asynchronously}
                            {--sync : Ignore setting in operation and process all operations synchronously}
                            {--queue= : Set the queue, that all jobs will be dispatched to}
                            {--tag=* : Process only operations, that have one of the given tag}
                            {--database= : Database connection for the operations table to be used}
                            {--path= : Path to load the files from}';

    protected $description = 'Process all unprocessed one-time operations';

    protected bool $forceAsync = false;

    protected bool $forceSync = false;

    protected ?string $queue = null;

    protected array $tags = [];

    public function handle(): int
    {
        if($this->option('path')) {
            OneTimeOperationManager::setDirectoryName($this->option('path'));
        }

        $this->displayTestmodeWarning();

        $this->forceAsync = (bool) $this->option('async');
        $this->forceSync = (bool) $this->option('sync');
        $this->queue = $this->option('queue');
        $this->tags = $this->option('tag');

        if (! $this->tagOptionsAreValid()) {
            $this->components->error('Abort! Do not provide empty tags!');

            return self::FAILURE;
        }

        if (! $this->syncOptionsAreValid()) {
            $this->components->error('Abort! Process either with --sync or --async.');

            return self::FAILURE;
        }

        if ($operationName = $this->argument('name')) {
            return $this->proccessSingleOperation($operationName);
        }

        return $this->processNextOperations();
    }

    protected function proccessSingleOperation(string $providedOperationName): int
    {
        $providedOperationName = str($providedOperationName)->rtrim('.php')->toString();

        try {
            if ($operationModel = OneTimeOperationManager::getModelByName($providedOperationName)) {
                return $this->processOperationModel($operationModel);
            }

            $operationsFile = OneTimeOperationManager::getOperationFileByName($providedOperationName);

            return $this->processOperationFile($operationsFile);
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    protected function processOperationFile(OneTimeOperationFile $operationFile): int
    {
        $this->components->task($operationFile->getOperationName(), function () use ($operationFile) {
            $this->dispatchOperationJob($operationFile);
            $this->storeOperation($operationFile);
        });

        $this->newLine();
        $this->components->info('Processing finished.');

        return self::SUCCESS;
    }

    protected function processOperationModel(Operation $operationModel): int
    {
        if (! $this->components->confirm('Operation was processed before. Process it again?')) {
            $this->components->info('Operation aborted');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Processing operation %s.', $operationModel->name));

        $this->components->task($operationModel->name, function () use ($operationModel) {
            $operationFile = OneTimeOperationManager::getOperationFileByModel($operationModel);

            $this->dispatchOperationJob($operationFile);
            $this->storeOperation($operationFile);
        });

        $this->newLine();
        $this->components->info('Processing finished.');

        return self::SUCCESS;
    }

    protected function processNextOperations(): int
    {
        $processingOutput = 'Processing operations.';
        $unprocessedOperationFiles = OneTimeOperationManager::getUnprocessedOperationFiles();

        if ($this->tags) {
            $processingOutput = sprintf('Processing operations with tags (%s)', Arr::join($this->tags, ','));
            $unprocessedOperationFiles = $this->filterOperationsByTags($unprocessedOperationFiles);
        }

        if ($unprocessedOperationFiles->isEmpty()) {
            $this->components->info('No operations to process.');

            return self::SUCCESS;
        }

        $this->components->info($processingOutput);

        foreach ($unprocessedOperationFiles as $operationFile) {
            $this->components->task($operationFile->getOperationName(), function () use ($operationFile) {
                $this->dispatchOperationJob($operationFile);
                $this->storeOperation($operationFile);
            });
        }

        $this->newLine();
        $this->components->info('Processing finished.');

        return self::SUCCESS;
    }

    protected function tagMatched(OneTimeOperationFile $operationFile): bool
    {
        return in_array($operationFile->getClassObject()->getTag(), $this->tags);
    }

    protected function storeOperation(OneTimeOperationFile $operationFile): void
    {
        if ($this->testModeEnabled()) {
            return;
        }

        $operation = new Operation();
        
        if($this->option('database'))
            $operation->setConnection($this->option('database'));

        $operation->storeOperation($operationFile->getOperationName(), $this->isAsyncMode($operationFile));
    }

    protected function dispatchOperationJob(OneTimeOperationFile $operationFile)
    {
        if ($this->isAsyncMode($operationFile)) {
            OneTimeOperationProcessJob::dispatch($operationFile->getOperationName())->onQueue($this->getQueue($operationFile));

            return;
        }

        OneTimeOperationProcessJob::dispatchSync($operationFile->getOperationName());
    }

    protected function testModeEnabled(): bool
    {
        return $this->option('test');
    }

    protected function displayTestmodeWarning(): void
    {
        if ($this->testModeEnabled()) {
            $this->components->warn('Testmode! Operation won\'t be tagged as `processed`');
        }
    }

    protected function isAsyncMode(OneTimeOperationFile $operationFile): bool
    {
        if ($this->forceAsync) {
            return true;
        }

        if ($this->forceSync) {
            return false;
        }

        return $operationFile->getClassObject()->isAsync();
    }

    protected function getQueue(OneTimeOperationFile $operationFile): ?string
    {
        if ($this->queue) {
            return $this->queue;
        }

        return $operationFile->getClassObject()->getQueue() ?: null;
    }

    protected function filterOperationsByTags(Collection $unprocessedOperationFiles): Collection
    {
        return $unprocessedOperationFiles->filter(function (OneTimeOperationFile $operationFile) {
            return $this->tagMatched($operationFile);
        })->collect();
    }

    protected function tagOptionsAreValid(): bool
    {
        // no tags provided
        if (empty($this->tags)) {
            return true;
        }

        // all tags are not empty
        if (count($this->tags) === count(array_filter($this->tags))) {
            return true;
        }

        return false;
    }

    protected function syncOptionsAreValid(): bool
    {
        // do not use both options at the same time
        return ! ($this->forceAsync && $this->forceSync);
    }
}
