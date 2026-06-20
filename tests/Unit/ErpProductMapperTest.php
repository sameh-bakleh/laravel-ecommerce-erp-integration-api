<?php

namespace Tests\Unit;

use App\Integration\Mappers\ErpProductMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ErpProductMapperTest extends TestCase
{
    public function test_maps_german_erp_row_to_internal_attributes(): void
    {
        $mapper = new ErpProductMapper;
        $attrs = $mapper->toInternalAttributes([
            'Artikelnummer' => 'SKU-1',
            'Bezeichnung' => 'Test article',
            'Listenpreis' => 10.5,
            'MwSt' => 19,
            'ExterneID' => 'EXT-1',
            'Aktiv' => true,
        ]);

        $this->assertSame('SKU-1', $attrs['sku']);
        $this->assertSame('EXT-1', $attrs['erp_external_id']);
        $this->assertSame(1050, $attrs['gross_price_cents']);
        $this->assertSame(19.0, $attrs['tax_rate']);
    }

    public function test_throws_when_sku_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ErpProductMapper)->toInternalAttributes([
            'Bezeichnung' => 'X',
            'Listenpreis' => 1,
        ]);
    }

    public function test_throws_when_price_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ErpProductMapper)->toInternalAttributes([
            'Artikelnummer' => 'SKU-1',
            'Bezeichnung' => 'X',
            'Listenpreis' => 'nope',
        ]);
    }
}
