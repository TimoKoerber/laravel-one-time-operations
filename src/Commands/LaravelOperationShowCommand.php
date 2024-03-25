<?php

namespace EncoreDigitalGroup\LaravelOperations\Commands;

use EncoreDigitalGroup\LaravelOperations\Commands\Utils\OperationsLineElement;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationFile;
use EncoreDigitalGroup\LaravelOperations\LaravelOperationManager;
use EncoreDigitalGroup\LaravelOperations\Models\Operation;
use Exception;
use Illuminate\Support\Collection;
use Throwable;

class LaravelOperationShowCommand extends LaravelOperationsCommand
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
            $this->newLine();

            $operationOutputLines = $this->getOperationLinesForOutput();
            $operationOutputLines = $this->filterOperationLinesByStatus($operationOutputLines);

            if ($operationOutputLines->isEmpty()) {
                $this->components->info('No operations found.');
            }

            /** @var OperationsLineElement $lineElement */
            foreach ($operationOutputLines as $lineElement) {
                $lineElement->output($this->components);
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

        throw_if(array_diff($filters, $validFilters), Exception::class, 'Given filter is not valid. Allowed filters: ' . implode('|', array_map('strtolower', $this->validFilters)));
    }

    protected function shouldDisplayByFilter(string $filterName): bool
    {
        $givenFilters = $this->argument('filter');

        if (empty($givenFilters)) {
            return true;
        }

        $givenFilters = array_map(fn ($filter) => strtolower($filter), $givenFilters);

        return in_array(strtolower($filterName), $givenFilters);
    }

    protected function getOperationLinesForOutput(): Collection
    {
        $operationModels = Operation::all();
        $operationFiles = LaravelOperationManager::getAllOperationFiles();
        $operationOutputLines = collect();

        // add disposed operations
        foreach ($operationModels as $operation) {
            if (LaravelOperationManager::fileExistsByName($operation->name)) {
                continue;
            }

            $operationOutputLines->add(OperationsLineElement::make($operation->name, self::LABEL_DISPOSED, $operation->processed_at));
        }

        // add processed and pending operations
        foreach ($operationFiles->toArray() as $file) {
            /** @var LaravelOperationFile $file */
            if ($model = $file->getModel()) {
                $operationOutputLines->add(OperationsLineElement::make($model->name, self::LABEL_PROCESSED, $model->processed_at, $file->getClassObject()->getTag()));
            } else {
                $operationOutputLines->add(OperationsLineElement::make($file->getOperationName(), self::LABEL_PENDING, null, $file->getClassObject()->getTag()));
            }
        }

        return $operationOutputLines;
    }

    protected function filterOperationLinesByStatus(Collection $operationOutputLines): Collection
    {
        return $operationOutputLines->filter(function (OperationsLineElement $lineElement) {
            return $this->shouldDisplayByFilter($lineElement->getStatus());
        })->collect();
    }
}
