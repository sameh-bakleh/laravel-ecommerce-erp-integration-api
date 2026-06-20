<?php

namespace Tests\Unit;

use App\Integration\Support\IntegrationSyncType;
use App\Integration\Support\SyncAuditRecorder;
use App\Models\FailedSync;
use App\Models\SyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SyncAuditRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_success_persists_sync_log_row(): void
    {
        app(SyncAuditRecorder::class)->logSuccess(
            IntegrationSyncType::PRODUCT_BULK,
            'corr-1',
            42,
            'SKU-1',
            ['processed' => 1],
        );

        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => IntegrationSyncType::PRODUCT_BULK,
            'status' => 'success',
            'direction' => 'inbound',
            'reference_key' => 'SKU-1',
            'duration_ms' => 42,
            'correlation_id' => 'corr-1',
        ]);

        $log = SyncLog::query()->first();
        $this->assertSame(['processed' => 1], $log->metadata);
    }

    public function test_log_failure_truncates_message_and_stores_exception_class(): void
    {
        $message = str_repeat('x', 600);

        app(SyncAuditRecorder::class)->logFailure(
            IntegrationSyncType::STOCK_BULK,
            'corr-2',
            15,
            new RuntimeException($message),
            'bulk',
        );

        $log = SyncLog::query()->first();
        $this->assertSame('failed', $log->status);
        $this->assertSame(500, mb_strlen((string) $log->message));
        $this->assertSame(['exception' => RuntimeException::class], $log->metadata);
    }

    public function test_record_failed_sync_creates_pending_retry_row(): void
    {
        $failed = app(SyncAuditRecorder::class)->recordFailedSync(
            IntegrationSyncType::ORDER_SINGLE,
            'corr-3',
            new RuntimeException('ERP timeout'),
            'PO-1',
            ['retry' => true],
        );

        $this->assertInstanceOf(FailedSync::class, $failed);
        $this->assertDatabaseHas('failed_syncs', [
            'sync_type' => IntegrationSyncType::ORDER_SINGLE,
            'reference_key' => 'PO-1',
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'attempts' => 0,
            'max_attempts' => 5,
            'correlation_id' => 'corr-3',
        ]);
        $this->assertSame(['retry' => true], $failed->payload);
    }
}
