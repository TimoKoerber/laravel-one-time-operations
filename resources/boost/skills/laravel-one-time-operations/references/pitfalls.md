# Pitfalls

## Common mistakes

- Do not create a named class in `app/` and call it an operation. The package expects a PHP file that returns an anonymous class extending `OneTimeOperation`.
- Do not combine unrelated fixes in one operation just because they ship in the same release.
- Do not rely on `$queue` when the operation runs synchronously.
- Do not pass both `--sync` and `--async`; the command fails when both are set.
- Do not pass empty tag values to `--tag`; the command treats that as invalid input.

## Behavior to remember

- `--test` executes processing without storing a row in the operations table, so the same file still appears pending later.
- `--tag=...` skips untagged operations entirely.
- Renaming an operation file changes its operation name, so the package treats it as a new pending operation.
- Deleting a processed operation file does not delete its history; `operations:show` reports it as `DISPOSED`.
- The defaults are `operations/` and `operations`, but both directory and table names are configurable through `one-time-operations`.

## Review standard

- Generated operations should have obvious scope, bounded writes, and production-safe queries.
- If the work is long-running, favor async processing and chunked updates instead of one huge in-memory loop.
