<?php

namespace TimoKoerber\LaravelOneTimeOperations\Commands;

use Throwable;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationShowCommand extends OneTimeOperationsCommand
{
    protected $signature = 'operations:show {filter?* : List of filters: pending|processed|disposed}';

    protected $description = 'List of all one-time operations';

    protected array $validFilters = [
        self::LABEL_PENDING,
        self::LABEL_PROCESSED,
        self::LABEL_DISPOSED,
    ];

    public function handle(): int
    {
        try {
            $this->validateFilters();

            $operationModels = Operation::all();
            $operationFiles = OneTimeOperationManager::getAllOperationFiles();
            $this->newLine();

            foreach ($operationModels as $operation) {
                if (OneTimeOperationManager::fileExistsByName($operation->name)) {
                    continue;
                }

                $this->shouldDisplay(self::LABEL_DISPOSED) && $this->components->twoColumnDetail($operation->name, $this->gray($operation->processed_at).' '.$this->green(self::LABEL_DISPOSED));
            }

            foreach ($operationFiles->toArray() as $file) {
                if ($model = $file->getModel()) {
                    $this->shouldDisplay(self::LABEL_PROCESSED) && $this->components->twoColumnDetail($model->name, $this->gray($model->processed_at).' '.$this->brightgreen(self::LABEL_PROCESSED));
                } else {
                    $this->shouldDisplay(self::LABEL_PENDING) && $this->components->twoColumnDetail($file->getOperationName(), $this->white(self::LABEL_PENDING));
                }
            }

            if ($operationModels->isEmpty() && $operationFiles->isEmpty()) {
                $this->components->info('No operations found.');
            }

            $this->newLine();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @throws Throwable
     */
    protected function validateFilters(): void
    {
        $filters = array_map(fn ($filter) => strtolower($filter), $this->argument('filter'));
        $validFilters = array_map(fn ($filter) => strtolower($filter), $this->validFilters);

        throw_if(array_diff($filters, $validFilters), \Exception::class, 'Given filter is not valid. Allowed filters: '.implode('|', array_map('strtolower', $this->validFilters)));
    }

    protected function shouldDisplay(string $filterName): bool
    {
        $givenFilters = $this->argument('filter');

        if (empty($givenFilters)) {
            return true;
        }

        $givenFilters = array_map(fn ($filter) => strtolower($filter), $givenFilters);

        return in_array(strtolower($filterName), $givenFilters);
    }
}
