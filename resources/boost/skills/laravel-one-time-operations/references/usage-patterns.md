# Usage Patterns

## Naming

- Name operations by the business outcome, not the implementation detail.
- Good examples: `BackfillInvoiceStatuses`, `FixLegacyOrderFlag`, `SyncPostReleasePermissions`
- The generated filename is timestamp-prefixed and snake-cased, for example `2026_04_03_101500_backfill_invoice_statuses.php`.

## Implementation rules

- Keep one operation focused on one business change.
- Prefer targeted queries over blanket updates.
- Make writes easy to reason about in code review.
- For large record sets, process in chunks and keep memory usage bounded.
- If multiple steps must happen together, keep them tightly related and ordered inside the same `process()` method.

## Sync vs async guidance

- Leave the default async behavior in place for most backfills and post-release work.
- Use a custom `$queue` or `--queue` when the work should go to a dedicated worker queue.
- Set `$async = false` or pass `--sync` only for small, bounded corrections that should finish inline.
- Remember that synchronous processing ignores `$queue`.

## Tagging

- Use `$tag` when a deployment pipeline needs to process a subset of operations.
- `php artisan operations:process --tag=foo` only runs operations whose `$tag` exactly matches `foo`.
- Running `php artisan operations:process` without tags still processes all pending operations, including tagged ones.

## Recommended workflow

1. Create a new operation with `operations:make`.
2. Implement the smallest safe change that solves the release task.
3. Deploy code.
4. Run migrations.
5. Run `operations:process`, optionally with `--isolated` in multi-server deployments.
6. Confirm results with application-specific checks and `operations:show`.

## Custom stubs

If the application keeps a shared local layout for operations, the package will use `/stubs/one-time-operation.stub` instead of the package stub when generating new files.
