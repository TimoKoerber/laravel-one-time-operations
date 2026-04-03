# Overview

## Use this package for

- Data backfills that should run once after a release.
- Corrective updates to existing records.
- Post-release sync work after schema or feature changes.
- One-off maintenance tasks that are safer to track in source control than to run manually.

## Prefer an operation when

- The work is tied to a specific deployment or business change.
- You want a reviewed artifact in git instead of an ad hoc console command.
- The task should be visible in a release checklist and processed once.

## Prefer something else when

- The behavior should run repeatedly or on a schedule.
- The code belongs in a long-lived domain service, job, listener, or command.
- The change is a schema change that belongs in a migration.

## Default structure

- Config file: `config/one-time-operations.php`
- Default operations directory: project-root `operations/`
- Operation files: timestamp-prefixed PHP files such as `2026_04_03_101500_backfill_invoice_statuses.php`
- Operation implementation: `return new class extends TimoKoerber\LaravelOneTimeOperations\OneTimeOperation`

## Normal release flow

1. Create an operation file for a single business change.
2. Implement `process(): void` with bounded, explicit writes.
3. Deploy the code.
4. Run `php artisan migrate`.
5. Run `php artisan operations:process`.
6. Review status with `php artisan operations:show`.
