<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedSync extends Model
{
    public const STATUS_PENDING_RETRY = 'pending_retry';

    public const STATUS_DEAD = 'dead';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'sync_type',
        'reference_key',
        'payload',
        'attempts',
        'max_attempts',
        'status',
        'last_error',
        'next_retry_at',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_retry_at' => 'datetime',
        ];
    }
}
