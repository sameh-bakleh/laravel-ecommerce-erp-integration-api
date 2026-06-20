<?php

namespace Tests\Unit;

use App\Integration\Services\RetryFailedSyncService;
use App\Integration\Support\IntegrationSyncType;
use App\Jobs\RunOrderSyncJob;
use App\Jobs\RunProductSyncJob;
use App\Jobs\RunStockSyncJob;
use App\Models\FailedSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetryFailedSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_matching_job_per_sync_type(): void
    {
        Queue::fake();

        FailedSync::query()->create([
            'sync_type' => IntegrationSyncType::STOCK_BULK,
            'reference_key' => 'bulk',
            'attempts' => 0,
            'max_attempts' => 5,
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'next_retry_at' => now()->subMinute(),
            'correlation_id' => 'corr-stock',
        ]);

        $result = app(RetryFailedSyncService::class)->retryDue();

        $this->assertSame(1, $result['jobs_dispatched']);
        Queue::assertPushed(RunStockSyncJob::class);
        Queue::assertNotPushed(RunProductSyncJob::class);
    }

    public function test_dispatches_order_job_with_reference_key(): void
    {
        Queue::fake();

        FailedSync::query()->create([
            'sync_type' => IntegrationSyncType::ORDER_SINGLE,
            'reference_key' => 'PO-99',
            'attempts' => 1,
            'max_attempts' => 5,
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'next_retry_at' => now()->subMinute(),
            'correlation_id' => 'corr-order',
        ]);

        app(RetryFailedSyncService::class)->retryDue();

        Queue::assertPushed(RunOrderSyncJob::class, function (RunOrderSyncJob $job): bool {
            return $job->erpOrderNumber === 'PO-99';
        });
    }

    public function test_skips_rows_not_yet_due(): void
    {
        Queue::fake();

        FailedSync::query()->create([
            'sync_type' => IntegrationSyncType::PRODUCT_BULK,
            'reference_key' => 'bulk',
            'attempts' => 0,
            'max_attempts' => 5,
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'next_retry_at' => now()->addHour(),
            'correlation_id' => 'corr-future',
        ]);

        $result = app(RetryFailedSyncService::class)->retryDue();

        $this->assertSame(0, $result['jobs_dispatched']);
        Queue::assertNothingPushed();
    }

    public function test_marks_record_dead_when_max_attempts_reached(): void
    {
        Queue::fake();

        $failed = FailedSync::query()->create([
            'sync_type' => IntegrationSyncType::PRODUCT_BULK,
            'reference_key' => 'bulk',
            'attempts' => 4,
            'max_attempts' => 5,
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'next_retry_at' => now()->subMinute(),
            'correlation_id' => 'corr-dead',
        ]);

        $result = app(RetryFailedSyncService::class)->retryDue();

        $this->assertSame(1, $result['jobs_dispatched']);
        $this->assertSame(1, $result['records_marked_dead']);
        $failed->refresh();
        $this->assertSame(FailedSync::STATUS_DEAD, $failed->status);
        $this->assertSame(5, $failed->attempts);
    }
}
