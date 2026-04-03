# Examples

## Create operations

```shell
php artisan operations:make BackfillInvoiceStatuses
php artisan operations:make FixLegacyOrderFlag --essential
php artisan make:operation SyncReleaseMetadata
```

## Process operations

```shell
php artisan operations:process
php artisan operations:process --isolated
php artisan operations:process --sync
php artisan operations:process --queue=maintenance
php artisan operations:process --tag=post-release
php artisan operations:process 2026_04_03_101500_backfill_invoice_statuses
php artisan operations:show pending processed disposed
```

## Async backfill example

```php
<?php

use App\Models\Invoice;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    protected bool $async = true;

    protected string $queue = 'maintenance';

    protected ?string $tag = 'post-release';

    public function process(): void
    {
        Invoice::query()
            ->whereNull('status')
            ->chunkById(500, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $invoice->forceFill(['status' => 'draft'])->save();
                }
            });
    }
};
```

## Small synchronous correction

```php
<?php

use App\Models\Order;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    protected bool $async = false;

    public function process(): void
    {
        Order::query()
            ->where('source', 'legacy-import')
            ->whereNull('is_archived')
            ->update(['is_archived' => false]);
    }
};
```
