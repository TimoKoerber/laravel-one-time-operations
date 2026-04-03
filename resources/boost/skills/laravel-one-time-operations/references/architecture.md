# Architecture

## Core classes

- `TimoKoerber\LaravelOneTimeOperations\OneTimeOperation`
  The abstract base class every operation extends. It defines `process(): void` plus optional protected properties:
  - `protected bool $async = true;`
  - `protected string $queue = 'default';`
  - `protected ?string $tag = null;`
- `TimoKoerber\LaravelOneTimeOperations\OneTimeOperationCreator`
  Creates new timestamped operation files from package or custom stubs.
- `TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager`
  Resolves the operations directory, loads operation files, and finds pending or processed operations.
- `TimoKoerber\LaravelOneTimeOperations\OneTimeOperationFile`
  Wraps a file on disk and lazily loads the anonymous operation instance with `File::getRequire(...)`.
- `TimoKoerber\LaravelOneTimeOperations\Jobs\OneTimeOperationProcessJob`
  Executes an operation by name and calls its `process()` method.
- `TimoKoerber\LaravelOneTimeOperations\Models\Operation`
  Stores processed history rows.

## Operation file shape

Operations are anonymous classes returned from plain PHP files, not named classes under `app/`:

```php
<?php

use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    public function process(): void
    {
        //
    }
};
```

The package loads these files with `File::getRequire(...)`, so the file must directly return the operation instance.

## Artisan commands

- `php artisan operations:make {name}`
- `php artisan operations:make {name} --essential`
- `php artisan make:operation {name}`
- `php artisan operations:process {name?}`
- `php artisan operations:process --test`
- `php artisan operations:process --async`
- `php artisan operations:process --sync`
- `php artisan operations:process --queue=<name>`
- `php artisan operations:process --tag=<tag>`
- `php artisan operations:show {filter?*}`

`OneTimeOperationsProcessCommand` implements Laravel's `Isolatable` contract, so `php artisan operations:process --isolated` is also supported.

## Config keys and defaults

The package config file is `config/one-time-operations.php`:

- `one-time-operations.directory`: default `operations`
- `one-time-operations.table`: default `operations`
- `one-time-operations.connection`: default `null`

The directory is resolved from `App::basePath(...)`, so the default operations directory is at the project root, not under `app/`.

## Processed-state storage

Processed state is stored in the configured table through `TimoKoerber\LaravelOneTimeOperations\Models\Operation`.

- Table columns: `name`, `dispatched`, `processed_at`
- `dispatched` values: `async` or `sync`
- A file is considered already processed when any row exists for its operation name
- Re-running a named operation creates another history row with a newer `processed_at` timestamp after confirmation
- If the file is deleted later, `operations:show` reports the entry as `DISPOSED`
