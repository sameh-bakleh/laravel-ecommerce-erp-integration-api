<?php

namespace App\Integration\Support;

use App\Models\FailedSync;
use App\Models\SyncLog;
use Throwable;

final class SyncAuditRecorder
{
    public function logSuccess(
        string $syncType,
        string $correlationId,
        int $durationMs,
        ?string $referenceKey = null,
        ?array $metadata = null,
    ): void {
        SyncLog::query()->create([
            'sync_type' => $syncType,
            'direction' => 'inbound',
            'status' => 'success',
            'reference_key' => $referenceKey,
            'message' => null,
            'duration_ms' => $durationMs,
            'correlation_id' => $correlationId,
            'metadata' => $metadata,
        ]);
    }

    public function logFailure(
        string $syncType,
        string $correlationId,
        int $durationMs,
        Throwable $e,
        ?string $referenceKey = null,
    ): void {
        SyncLog::query()->create([
            'sync_type' => $syncType,
            'direction' => 'inbound',
            'status' => 'failed',
            'reference_key' => $referenceKey,
            'message' => mb_substr($e->getMessage(), 0, 500),
            'duration_ms' => $durationMs,
            'correlation_id' => $correlationId,
            'metadata' => ['exception' => $e::class],
        ]);
    }

    public function recordFailedSync(
        string $syncType,
        string $correlationId,
        Throwable $e,
        ?string $referenceKey = null,
        ?array $payload = null,
    ): FailedSync {
        return FailedSync::query()->create([
            'sync_type' => $syncType,
            'reference_key' => $referenceKey,
            'payload' => $payload,
            'attempts' => 0,
            'max_attempts' => 5,
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'last_error' => $e->getMessage(),
            'next_retry_at' => now(),
            'correlation_id' => $correlationId,
        ]);
    }
}
