<?php

namespace App\Jobs;

use App\Integration\Services\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunProductSyncJob implements ShouldQueue
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

    public function handle(ProductSyncService $productSyncService): void
    {
        $productSyncService->syncAll($this->correlationId);
    }
}
