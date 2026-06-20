<?php

namespace App\Integration\Services;

use App\Integration\Erp\Contracts\ErpClientInterface;
use App\Integration\Mappers\ErpOrderMapper;
use App\Integration\Support\IntegrationSyncType;
use App\Integration\Support\SyncAuditRecorder;
use App\Models\IntegrationOrder;
use Throwable;

final class OrderSyncService
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly ErpOrderMapper $orderMapper,
        private readonly SyncAuditRecorder $audit,
    ) {}

    /**
     * @return array{erp_order_number: string}
     */
    public function syncByErpNumber(string $erpOrderNumber, string $correlationId): array
    {
        $started = microtime(true);

        try {
            $payload = $this->erpClient->fetchOrderPayload($erpOrderNumber);
            $attrs = $this->orderMapper->toInternalAttributes($payload);

            IntegrationOrder::query()->updateOrCreate(
                ['erp_order_number' => $attrs['erp_order_number']],
                $attrs,
            );

            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logSuccess(
                IntegrationSyncType::ORDER_SINGLE,
                $correlationId,
                $duration,
                $attrs['erp_order_number'],
            );

            return ['erp_order_number' => $attrs['erp_order_number']];
        } catch (Throwable $e) {
            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logFailure(
                IntegrationSyncType::ORDER_SINGLE,
                $correlationId,
                $duration,
                $e,
                $erpOrderNumber,
            );
            $this->audit->recordFailedSync(
                IntegrationSyncType::ORDER_SINGLE,
                $correlationId,
                $e,
                $erpOrderNumber,
                ['erp_order_number' => $erpOrderNumber],
            );

            throw $e;
        }
    }
}
