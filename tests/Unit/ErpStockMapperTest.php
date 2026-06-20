<?php

namespace Tests\Unit;

use App\Integration\Mappers\ErpStockMapper;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ErpStockMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_stock_row_when_product_exists(): void
    {
        $product = Product::query()->create([
            'sku' => 'SKU-1',
            'name' => 'Widget',
            'gross_price_cents' => 1000,
            'tax_rate' => 19,
            'is_active' => true,
        ]);

        $resolved = (new ErpStockMapper)->resolveStockRow([
            'Artikelnummer' => 'SKU-1',
            'Lagerort' => 'main',
            'Bestand' => 42,
        ]);

        $this->assertSame($product->id, $resolved['product_id']);
        $this->assertSame('MAIN', $resolved['warehouse_code']);
        $this->assertSame(42, $resolved['quantity']);
    }

    public function test_throws_when_product_not_synced_yet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No local product for SKU');

        (new ErpStockMapper)->resolveStockRow([
            'Artikelnummer' => 'MISSING',
            'Lagerort' => 'MAIN',
            'Bestand' => 1,
        ]);
    }

    public function test_throws_when_quantity_invalid(): void
    {
        Product::query()->create([
            'sku' => 'SKU-1',
            'name' => 'Widget',
            'gross_price_cents' => 1000,
            'tax_rate' => 19,
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new ErpStockMapper)->resolveStockRow([
            'Artikelnummer' => 'SKU-1',
            'Lagerort' => 'MAIN',
            'Bestand' => 'n/a',
        ]);
    }
}
