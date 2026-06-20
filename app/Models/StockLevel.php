<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_code',
        'quantity',
        'synced_from_erp_at',
    ];

    protected function casts(): array
    {
        return [
            'synced_from_erp_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
