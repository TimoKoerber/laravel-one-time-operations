<?php

namespace  TimoKoerber\LaravelOneTimeOperations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TimoKoerber\LaravelOneTimeOperations\Models\Factories\OperationFactory;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class Operation extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const DISPATCHED_ASYNC = 'async';

    public const DISPATCHED_SYNC = 'sync';

    protected $fillable = [
        'name',
        'dispatched',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = OneTimeOperationManager::getTableName();
    }

    protected static function newFactory(): OperationFactory
    {
        return new OperationFactory();
    }

    public static function storeOperation(string $operation, bool $async): self
    {
        return self::firstOrCreate([
            'name' => $operation,
            'dispatched' => $async ? self::DISPATCHED_ASYNC : self::DISPATCHED_SYNC,
            'processed_at' => now(),
        ]);
    }

    public function getFilePathAttribute(): string
    {
        return OneTimeOperationManager::pathToFileByName($this->name);
    }
}
