<?php

namespace App\Integration\Erp\Mock;

use App\Integration\Erp\Contracts\ErpClientInterface;
use RuntimeException;

/**
 * Deterministic fake ERP payloads for demos and automated tests.
 * Replace binding in AppServiceProvider with a real HTTP client when integrating a tenant.
 */
final class MockErpClient implements ErpClientInterface
{
    public function __construct(
        private readonly bool $simulateTransportFailure = false,
    ) {}

    public function fetchProductSnapshots(): array
    {
        $this->guardTransport();

        return [
            [
                'Artikelnummer' => 'SKU-DEMO-001',
                'Bezeichnung' => 'Demo product — hydraulic seal kit',
                'Listenpreis' => 129.99,
                'MwSt' => 19,
                'ExterneID' => 'ERP-ART-10001',
                'Aktiv' => true,
            ],
            [
                'Artikelnummer' => 'SKU-DEMO-002',
                'Bezeichnung' => 'Demo product — industrial sensor',
                'Listenpreis' => 459.00,
                'MwSt' => 19,
                'ExterneID' => 'ERP-ART-10002',
                'Aktiv' => true,
            ],
        ];
    }

    public function fetchStockSnapshots(): array
    {
        $this->guardTransport();

        return [
            [
                'Artikelnummer' => 'SKU-DEMO-001',
                'Lagerort' => 'MAIN',
                'Bestand' => 120,
            ],
            [
                'Artikelnummer' => 'SKU-DEMO-002',
                'Lagerort' => 'MAIN',
                'Bestand' => 4,
            ],
        ];
    }

    public function fetchOrderPayload(string $erpOrderNumber): array
    {
        $this->guardTransport();

        if ($erpOrderNumber === 'INVALID-ORDER') {
            throw new RuntimeException('ERP order not found');
        }

        return [
            'Auftragsnummer' => $erpOrderNumber,
            'Status' => 'FREIGEGEBEN',
            'Waehrung' => 'EUR',
            'Kundennummer' => 'K-900123',
            'Auftragsdatum' => '2026-05-08T14:22:00+02:00',
            'Positionen' => [
                ['Artikelnummer' => 'SKU-DEMO-001', 'Menge' => 2],
            ],
        ];
    }

    private function guardTransport(): void
    {
        if ($this->simulateTransportFailure) {
            throw new RuntimeException('Simulated ERP transport failure');
        }
    }
}
