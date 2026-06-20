<?php

namespace App\Jobs;

use App\Integration\Services\StockSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunStockSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 120];

    public function __construct(
        public readonly string $correlationId,
    ) {}

    public function handle(StockSyncService $stockSyncService): void
    {
        $stockSyncService->syncAll($this->correlationId);
    }
}
