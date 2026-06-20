<?php

namespace Tests\Unit;

use App\Jobs\RunOrderSyncJob;
use App\Jobs\RunProductSyncJob;
use App\Jobs\RunStockSyncJob;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SyncJobRetryTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string}>
     */
    public static function syncJobClasses(): array
    {
        return [
            'product' => [RunProductSyncJob::class],
            'stock' => [RunStockSyncJob::class],
            'order' => [RunOrderSyncJob::class],
        ];
    }

    #[DataProvider('syncJobClasses')]
    public function test_sync_jobs_define_laravel_retry_backoff(string $jobClass): void
    {
        $job = $jobClass === RunOrderSyncJob::class
            ? new RunOrderSyncJob('PO-1', 'corr-retry')
            : new $jobClass('corr-retry');

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60, 120], $job->backoff);
    }
}
