<?php

namespace App\Integration\Erp\Contracts;

/**
 * Abstraction over a remote ERP / PIM (e.g. SAP, Microsoft Dynamics, custom WWS).
 * Implementations must never embed real tenant URLs or credentials — configure via env.
 */
interface ErpClientInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchProductSnapshots(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchStockSnapshots(): array;

    /**
     * @return array<string, mixed>
     */
    public function fetchOrderPayload(string $erpOrderNumber): array;
}
