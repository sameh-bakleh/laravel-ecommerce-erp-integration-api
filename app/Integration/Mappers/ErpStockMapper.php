<?php

namespace App\Integration\Mappers;

use App\Models\Product;
use Illuminate\Support\Arr;
use InvalidArgumentException;

final class ErpStockMapper
{
    /**
     * @param  array<string, mixed>  $row
     * @return array{product_id: int, warehouse_code: string, quantity: int}
     */
    public function resolveStockRow(array $row): array
    {
        $sku = trim((string) Arr::get($row, 'Artikelnummer', ''));
        if ($sku === '') {
            throw new InvalidArgumentException('Stock payload missing Artikelnummer.');
        }

        $warehouse = strtoupper(trim((string) Arr::get($row, 'Lagerort', 'MAIN')));
        if ($warehouse === '') {
            throw new InvalidArgumentException('Stock payload missing Lagerort.');
        }

        $qty = Arr::get($row, 'Bestand');
        if (! is_numeric($qty)) {
            throw new InvalidArgumentException('Stock payload has invalid Bestand.');
        }

        $product = Product::query()->where('sku', $sku)->first();
        if ($product === null) {
            throw new InvalidArgumentException("No local product for SKU {$sku}; sync products first.");
        }

        return [
            'product_id' => $product->id,
            'warehouse_code' => $warehouse,
            'quantity' => (int) $qty,
        ];
    }
}
