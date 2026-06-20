<?php

namespace App\Integration\Services;

use App\Integration\Erp\Contracts\ErpClientInterface;
use App\Integration\Mappers\ErpProductMapper;
use App\Integration\Support\IntegrationSyncType;
use App\Integration\Support\SyncAuditRecorder;
use App\Models\Product;
use InvalidArgumentException;
use Throwable;

final class ProductSyncService
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly ErpProductMapper $productMapper,
        private readonly SyncAuditRecorder $audit,
    ) {}

    /**
     * @return array{processed: int}
     */
    public function syncAll(string $correlationId): array
    {
        $started = microtime(true);

        try {
            $snapshots = $this->erpClient->fetchProductSnapshots();
            $processed = 0;

            foreach ($snapshots as $row) {
                $attrs = $this->productMapper->toInternalAttributes($row);
                Product::query()->updateOrCreate(
                    ['sku' => $attrs['sku']],
                    $attrs,
                );
                $processed++;
            }

            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logSuccess(
                IntegrationSyncType::PRODUCT_BULK,
                $correlationId,
                $duration,
                null,
                ['processed' => $processed],
            );

            return ['processed' => $processed];
        } catch (Throwable $e) {
            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logFailure(IntegrationSyncType::PRODUCT_BULK, $correlationId, $duration, $e);
            $this->audit->recordFailedSync(IntegrationSyncType::PRODUCT_BULK, $correlationId, $e, 'bulk');

            throw $e;
        }
    }

    /**
     * Used when a single ERP row fails validation inside a larger batch (partial failure pattern).
     *
     * @param  array<string, mixed>  $row
     */
    public function syncSingleRow(array $row, string $correlationId): void
    {
        $started = microtime(true);
        try {
            $attrs = $this->productMapper->toInternalAttributes($row);
            Product::query()->updateOrCreate(['sku' => $attrs['sku']], $attrs);
            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logSuccess(
                IntegrationSyncType::PRODUCT_BULK,
                $correlationId,
                $duration,
                $attrs['sku'],
            );
        } catch (InvalidArgumentException $e) {
            $duration = (int) round((microtime(true) - $started) * 1000);
            $this->audit->logFailure(
                IntegrationSyncType::PRODUCT_BULK,
                $correlationId,
                $duration,
                $e,
                isset($row['Artikelnummer']) ? (string) $row['Artikelnummer'] : null,
            );
            throw $e;
        }
    }
}
