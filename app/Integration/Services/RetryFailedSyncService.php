<?php

namespace App\Integration\Services;

use App\Integration\Support\IntegrationSyncType;
use App\Jobs\RunOrderSyncJob;
use App\Jobs\RunProductSyncJob;
use App\Jobs\RunStockSyncJob;
use App\Models\FailedSync;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

final class RetryFailedSyncService
{
    /**
     * Re-dispatch queue jobs for domain failed_sync rows that are due.
     *
     * @return array{jobs_dispatched: int, records_marked_dead: int}
     */
    public function retryDue(): array
    {
        $jobsDispatched = 0;
        $recordsMarkedDead = 0;

        $query = FailedSync::query()
            ->where('status', FailedSync::STATUS_PENDING_RETRY)
            ->where(function ($q): void {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->whereColumn('attempts', '<', 'max_attempts');

        foreach ($query->cursor() as $failed) {
            $correlationId = (string) Str::uuid();

            match ($failed->sync_type) {
                IntegrationSyncType::PRODUCT_BULK => Bus::dispatch(new RunProductSyncJob($correlationId)),
                IntegrationSyncType::STOCK_BULK => Bus::dispatch(new RunStockSyncJob($correlationId)),
                IntegrationSyncType::ORDER_SINGLE => Bus::dispatch(
                    new RunOrderSyncJob((string) $failed->reference_key, $correlationId),
                ),
                default => null,
            };

            $jobsDispatched++;

            $failed->increment('attempts');
            $failed->refresh();

            if ($failed->attempts >= $failed->max_attempts) {
                $failed->update(['status' => FailedSync::STATUS_DEAD]);
                $recordsMarkedDead++;
            } else {
                $failed->update([
                    'next_retry_at' => now()->addMinutes(10),
                    'last_error' => null,
                ]);
            }
        }

        return [
            'jobs_dispatched' => $jobsDispatched,
            'records_marked_dead' => $recordsMarkedDead,
        ];
    }
}
