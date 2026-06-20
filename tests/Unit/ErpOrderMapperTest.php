<?php

namespace Tests\Unit;

use App\Integration\Mappers\ErpOrderMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ErpOrderMapperTest extends TestCase
{
    public function test_maps_german_erp_order_to_internal_attributes(): void
    {
        $attrs = (new ErpOrderMapper)->toInternalAttributes([
            'Auftragsnummer' => 'PO-2026-0001',
            'Status' => 'FREIGEGEBEN',
            'Waehrung' => 'eur',
            'Kundennummer' => 'K-900123',
            'Auftragsdatum' => '2026-05-08T14:22:00+02:00',
            'Positionen' => [],
        ]);

        $this->assertSame('PO-2026-0001', $attrs['erp_order_number']);
        $this->assertSame('confirmed', $attrs['status']);
        $this->assertSame('EUR', $attrs['currency']);
        $this->assertSame('K-900123', $attrs['customer_number']);
        $this->assertNotNull($attrs['placed_at']);
        $this->assertIsArray($attrs['raw_payload']);
    }

    public function test_maps_cancelled_status(): void
    {
        $attrs = (new ErpOrderMapper)->toInternalAttributes([
            'Auftragsnummer' => 'PO-1',
            'Status' => 'STORNIERT',
            'Auftragsdatum' => '2026-05-08T14:22:00+02:00',
        ]);

        $this->assertSame('cancelled', $attrs['status']);
    }

    public function test_throws_when_order_number_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ErpOrderMapper)->toInternalAttributes([
            'Status' => 'FREIGEGEBEN',
            'Auftragsdatum' => '2026-05-08T14:22:00+02:00',
        ]);
    }
}
