<?php

namespace Tests\Feature;

use App\Jobs\RunOrderSyncJob;
use App\Jobs\RunProductSyncJob;
use App\Jobs\RunStockSyncJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer test-token'];
    }

    public function test_product_sync_dispatches_job_without_running_it(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/sync/products', [], $this->authHeaders());

        $response->assertAccepted()
            ->assertJsonStructure(['correlation_id', 'accepted']);

        Queue::assertPushed(RunProductSyncJob::class, function (RunProductSyncJob $job) use ($response): bool {
            return $job->correlationId === $response->json('correlation_id');
        });
    }

    public function test_stock_sync_dispatches_job_without_running_it(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/sync/stock', [], $this->authHeaders())->assertAccepted();

        Queue::assertPushed(RunStockSyncJob::class);
    }

    public function test_order_sync_dispatches_job_with_erp_order_number(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/sync/orders/PO-2026-0042', [], $this->authHeaders())->assertAccepted();

        Queue::assertPushed(RunOrderSyncJob::class, function (RunOrderSyncJob $job): bool {
            return $job->erpOrderNumber === 'PO-2026-0042';
        });
    }
}
