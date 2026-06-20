<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationOrder extends Model
{
    protected $fillable = [
        'erp_order_number',
        'status',
        'currency',
        'customer_number',
        'placed_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}
