# Testing

## What to test

- The data changes or side effects produced by the operation.
- Whether the operation should run sync or async.
- Queue selection when async work must use a non-default queue.
- Tag-based selection when operations are split into batches.

## Practical patterns

- Use `RefreshDatabase` when the operation mutates database records.
- Create operation files with `php artisan operations:make ...` in higher-level tests when you want to exercise the real command workflow.
- Prefer synchronous execution in tests that must assert the operation's side effects immediately.
- Use `php artisan operations:process --test` when you want repeatable processing without creating a processed-history row.

## Queue assertions

When you only need to verify dispatch behavior, fake the queue and assert that `TimoKoerber\LaravelOneTimeOperations\Jobs\OneTimeOperationProcessJob` was pushed with the expected queue or sync connection.

## Assertions for processed state

- Assert the application data changed as expected.
- Assert rows in the configured `operations` table when processed history matters.
- Use `php artisan operations:show` in feature tests if the user-facing status output is relevant.

## Keep tests bounded

- Write operations so they can target a small, deterministic dataset in tests.
- Avoid generating operations that depend on production-only infrastructure or huge tables to prove correctness.
