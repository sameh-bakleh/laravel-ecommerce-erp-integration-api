<?php

namespace App\Jobs;

use App\Integration\Services\OrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunOrderSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 120];

    public function __construct(
        public readonly string $erpOrderNumber,
        public readonly string $correlationId,
    ) {}

    public function handle(OrderSyncService $orderSyncService): void
    {
        $orderSyncService->syncByErpNumber($this->erpOrderNumber, $this->correlationId);
    }
}
