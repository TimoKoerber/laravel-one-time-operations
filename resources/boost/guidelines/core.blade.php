## Laravel One-Time Operations

This package provides deployment-safe, one-time operations for Laravel applications.

Use this package when a change should be executed once after deployment, similar to how migrations are applied once and then tracked. It is intended for data fixes, backfills, post-release corrective updates, targeted synchronization work, and other one-off maintenance tasks that should not live forever as ad hoc jobs or temporary console commands.

### Core conventions

- Prefer one operation class per business change.
- Keep each operation narrowly scoped and easy to review.
- Write operations so they are safe to understand and maintain after deployment.
- Prefer explicit query scopes and explicit writes over broad or hidden side effects.
- Avoid mixing unrelated business changes in a single operation.
- Prefer this package over temporary deploy-only console commands when the change should be reviewable and trackable.
- Do not place this type of post-deployment data logic inside migrations unless schema and data must change together and there is no cleaner alternative.

### File structure

- Operation files are created in the configured operations directory.
- Operation files use a timestamp-prefixed snake_case filename.
- Each operation file returns an anonymous class extending `TimoKoerber\LaravelOneTimeOperations\OneTimeOperation`.

### Main workflow

1. Create a new operation with the package command.
2. Implement the `process()` method.
3. Keep the logic focused on one business outcome.
4. Process operations after deployment, after migrations have run.
5. Add tests around the underlying business behavior when practical.

### Typical commands

@verbatim
    <code-snippet name="Create Operation" lang="bash">
        php artisan operations:make BackfillServiceTypeIcons
    </code-snippet>

    <code-snippet name="Process Operations" lang="bash">
        php artisan operations:process
    </code-snippet>

    <code-snippet name="Process Operations Synchronously" lang="bash">
        php artisan operations:process --sync
    </code-snippet>

    <code-snippet name="Filter Operations By Tag" lang="bash">
        php artisan operations:process --tag=billing
    </code-snippet>
@endverbatim

### Operation design guidance

- Use `$async = true` for larger or queue-friendly work.
- Use `$async = false` only for small, predictable work that is appropriate for deployment-time execution.
- Set `$queue` only when asynchronous processing needs a specific queue.
- Use `$tag` to group operations for selective processing.
- Keep irreversible changes obvious and well named.
