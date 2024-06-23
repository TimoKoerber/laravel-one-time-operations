<?php

namespace  TimoKoerber\LaravelOneTimeOperations\Models;

use Illuminate\Database\Eloquent\Model;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class FailedOperation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'dispatched_at',
        'connection',
        'queue',
        'failed_at',
        'exception',
    ];

    protected $casts = [
        'exception' => 'array',
        'dispatched_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = 'failed_' . OneTimeOperationManager::getTableName();
    }
}
