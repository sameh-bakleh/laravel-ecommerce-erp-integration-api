<?php

namespace Tests\Feature;

use App\Integration\Erp\Contracts\ErpClientInterface;
use App\Integration\Erp\Mock\MockErpClient;
use App\Jobs\RunStockSyncJob;
use App\Models\ApiRequestLog;
use App\Models\FailedSync;
use App\Models\Product;
use App\Models\StockLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer test-token'];
    }

    public function test_sync_endpoints_require_bearer_token(): void
    {
        $this->postJson('/api/v1/sync/products')->assertUnauthorized();
    }

    public function test_product_sync_persists_demo_catalog_and_writes_audit_logs(): void
    {
        $response = $this->postJson('/api/v1/sync/products', [], $this->authHeaders());

        $response->assertAccepted()
            ->assertHeader('X-Request-Id')
            ->assertJsonStructure(['correlation_id', 'accepted']);

        $this->assertSame(2, Product::query()->count());
        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => 'product_bulk',
            'status' => 'success',
        ]);
        $this->assertSame(1, ApiRequestLog::query()->count());
    }

    public function test_stock_sync_fails_when_products_missing_and_records_failed_sync(): void
    {
        $this->postJson('/api/v1/sync/stock', [], $this->authHeaders())->assertServerError();

        $this->assertDatabaseHas('failed_syncs', [
            'sync_type' => 'stock_bulk',
            'status' => FailedSync::STATUS_PENDING_RETRY,
        ]);
        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => 'stock_bulk',
            'status' => 'failed',
        ]);
    }

    public function test_full_stack_product_stock_order_flow(): void
    {
        $this->postJson('/api/v1/sync/products', [], $this->authHeaders())->assertAccepted();
        $this->postJson('/api/v1/sync/stock', [], $this->authHeaders())->assertAccepted();

        $this->assertSame(2, StockLevel::query()->count());

        $this->postJson('/api/v1/sync/orders/PO-2026-0001', [], $this->authHeaders())->assertAccepted();

        $this->assertDatabaseHas('integration_orders', [
            'erp_order_number' => 'PO-2026-0001',
            'status' => 'confirmed',
        ]);
    }

    public function test_order_sync_failure_creates_failed_sync(): void
    {
        $this->postJson('/api/v1/sync/orders/INVALID-ORDER', [], $this->authHeaders())->assertServerError();

        $this->assertDatabaseHas('failed_syncs', [
            'sync_type' => 'order_single',
            'reference_key' => 'INVALID-ORDER',
        ]);
    }

    public function test_simulated_erp_transport_failure_is_audited(): void
    {
        config(['integration.erp.simulate_transport_failure' => true]);
        $this->app->forgetInstance(ErpClientInterface::class);
        $this->app->singleton(
            ErpClientInterface::class,
            fn () => new MockErpClient(true),
        );

        $this->postJson('/api/v1/sync/products', [], $this->authHeaders())->assertServerError();

        $this->assertDatabaseHas('failed_syncs', [
            'sync_type' => 'product_bulk',
        ]);
    }

    public function test_retry_failed_dispatches_jobs_for_pending_rows(): void
    {
        FailedSync::query()->create([
            'sync_type' => 'product_bulk',
            'reference_key' => 'bulk',
            'payload' => null,
            'attempts' => 0,
            'max_attempts' => 5,
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'last_error' => 'previous',
            'next_retry_at' => now()->subMinute(),
            'correlation_id' => 'test-correlation',
        ]);

        $this->postJson('/api/v1/sync/retry-failed', [], $this->authHeaders())
            ->assertOk()
            ->assertJsonFragment(['jobs_dispatched' => 1]);

        $this->assertSame(2, Product::query()->count());
        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => 'product_bulk',
            'status' => 'success',
        ]);
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $raw = '{"event":"stock.updated"}';
        $this->call('POST', '/api/v1/webhooks/erp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ERP_SIGNATURE' => 'invalid',
        ], $raw)->assertForbidden();
    }

    public function test_webhook_dispatches_stock_job_when_signature_valid(): void
    {
        Queue::fake();

        $raw = '{"event":"stock.updated"}';
        $sig = hash_hmac('sha256', $raw, 'whsec_test');

        $this->call('POST', '/api/v1/webhooks/erp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ERP_SIGNATURE' => $sig,
        ], $raw)->assertAccepted();

        Queue::assertPushed(RunStockSyncJob::class);
    }

    public function test_webhook_validation_error_for_unknown_event(): void
    {
        $raw = '{"event":"unknown.event"}';
        $sig = hash_hmac('sha256', $raw, 'whsec_test');

        $this->call('POST', '/api/v1/webhooks/erp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ERP_SIGNATURE' => $sig,
        ], $raw)->assertUnprocessable();
    }

    public function test_webhook_rejects_invalid_json_with_valid_signature(): void
    {
        $raw = '{not-json';
        $sig = hash_hmac('sha256', $raw, 'whsec_test');

        $this->call('POST', '/api/v1/webhooks/erp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ERP_SIGNATURE' => $sig,
        ], $raw)->assertUnprocessable();
    }
}
