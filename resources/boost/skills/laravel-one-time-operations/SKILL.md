---
name: laravel-one-time-operations
description: Build and work with Laravel One-Time Operations features, including creating operations, processing deployment-safe one-off changes, tagging operations, using sync or async execution, and applying package-specific conventions.
---

# Laravel One-Time Operations

## When to use this skill

Use this skill when:

- creating a new one-time operation
- moving a one-off deploy task into an operation
- debugging why an operation was not processed
- deciding between sync and async execution
- adding tags or queue selection to operations
- documenting or reviewing operation usage in this package
- replacing temporary data-fix commands with proper tracked operations

## What this package is for

This package is for changes that should run once after deployment and then be tracked as processed.

Good fits:

- data backfills
- post-release corrective updates
- one-time synchronization work
- cleanup after feature or schema rollouts
- targeted production maintenance tasks

Poor fits:

- recurring scheduled tasks
- normal queue jobs that are part of daily application behavior
- long-term domain workflows
- broad schema migrations used as a substitute for operational code

## Core rules

- Prefer one operation per business change.
- Keep operations small, explicit, and easy to audit.
- Do not combine unrelated fixes in one operation.
- Avoid hidden side effects.
- Scope updates carefully.
- Prefer package operations over temporary deploy-only commands when the work should be reviewable and tracked.
- If the work is large, default to async processing unless there is a strong reason not to.

## Package conventions

- Operation files should be created with the package artisan commands instead of handwritten filenames.
- Operation classes should extend `TimoKoerber\LaravelOneTimeOperations\OneTimeOperation`.
- The operation file should return an anonymous class instance.
- Business intent should be obvious from the operation name.
- Timestamp-prefixed filenames are expected.
- Use tags only when they provide a meaningful deployment or operational grouping.

## Commands

```bash
php artisan operations:make OperationName
php artisan operations:make OperationName --essential
php artisan make:operation OperationName
php artisan operations:process
php artisan operations:process --sync
php artisan operations:process --async
php artisan operations:process --test
php artisan operations:process --isolated
php artisan operations:process --queue=default
php artisan operations:process --tag=billing
php artisan operations:process 2026_04_03_120000_backfill_service_type_icons
php artisan operations:show
php artisan operations:show pending
php artisan operations:show processed
php artisan operations:show disposed