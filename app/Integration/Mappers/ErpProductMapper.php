<?php

namespace App\Integration\Mappers;

use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Maps German-style ERP article rows into internal Product attributes.
 */
final class ErpProductMapper
{
    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function toInternalAttributes(array $row): array
    {
        $sku = trim((string) Arr::get($row, 'Artikelnummer', ''));
        if ($sku === '') {
            throw new InvalidArgumentException('Product payload missing Artikelnummer (SKU).');
        }

        $name = trim((string) Arr::get($row, 'Bezeichnung', ''));
        if ($name === '') {
            throw new InvalidArgumentException('Product payload missing Bezeichnung (name).');
        }

        $listPrice = Arr::get($row, 'Listenpreis');
        if (! is_numeric($listPrice)) {
            throw new InvalidArgumentException('Product payload has invalid Listenpreis.');
        }

        $grossCents = (int) round(((float) $listPrice) * 100);

        $tax = Arr::get($row, 'MwSt', 19);
        if (! is_numeric($tax)) {
            throw new InvalidArgumentException('Product payload has invalid MwSt.');
        }

        return [
            'sku' => $sku,
            'erp_external_id' => Arr::get($row, 'ExterneID') ? (string) Arr::get($row, 'ExterneID') : null,
            'name' => $name,
            'gross_price_cents' => max(0, $grossCents),
            'tax_rate' => (float) $tax,
            'is_active' => (bool) Arr::get($row, 'Aktiv', true),
            'metadata' => ['source' => 'erp', 'raw_keys' => array_keys($row)],
        ];
    }
}
