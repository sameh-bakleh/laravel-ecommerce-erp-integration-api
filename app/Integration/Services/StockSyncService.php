<?php

namespace App\Integration\Services;

use App\Integration\Erp\Contracts\ErpClientInterface;
use App\Integration\Mappers\ErpStockMapper;
use App\Integration\Support\IntegrationSyncType;
use App\Integration\Support\SyncAuditRecorder;
use App\Models\StockLevel;
use Throwable;

final class StockSyncService
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly ErpStockMapper $stockMapper,
        private readonly SyncAuditRecorder $audit,
    ) {}

    /**
     * @return array{processed: int}
     */
    public function syncAll(string $correlationId): array
    {
        $started = microtime(true);

        try {
            $rows = $this->erpClient->fetchStockSnapshots();
            $processed = 0;

            foreach ($rows as $row) {
                $resolved = $this->stockMapper->resolveStockRow($row);
                StockLevel::query()->updateOrCreate(
                    [
                        'product_id' => $resolved['product_id'],
                        'warehouse_code' => $resolved['warehouse_code'],
                    ],
                    [
                        'quantity' => $resolved['quantity'],
                        'synced_from_erp_at' => now(),
                    ],
                );
                $processed++;
            }

            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logSuccess(
                IntegrationSyncType::STOCK_BULK,
                $correlationId,
                $duration,
                null,
                ['processed' => $processed],
            );

            return ['processed' => $processed];
        } catch (Throwable $e) {
            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logFailure(IntegrationSyncType::STOCK_BULK, $correlationId, $duration, $e);
            $this->audit->recordFailedSync(IntegrationSyncType::STOCK_BULK, $correlationId, $e, 'bulk');

            throw $e;
        }
    }
}
