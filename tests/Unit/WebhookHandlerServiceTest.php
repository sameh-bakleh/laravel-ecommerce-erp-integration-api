<?php

namespace Tests\Unit;

use App\Integration\Services\WebhookHandlerService;
use App\Integration\Support\IntegrationSyncType;
use App\Jobs\RunStockSyncJob;
use App\Models\SyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class WebhookHandlerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_when_webhook_secret_not_configured(): void
    {
        config(['integration.webhook.secret' => '']);

        $this->expectException(AccessDeniedHttpException::class);

        app(WebhookHandlerService::class)->handleSignedPayload('{}', []);
    }

    public function test_rejects_invalid_signature(): void
    {
        $raw = '{"event":"stock.updated"}';

        $this->expectException(AccessDeniedHttpException::class);

        app(WebhookHandlerService::class)->handleSignedPayload($raw, [
            'X-ERP-Signature' => 'bad-signature',
        ]);
    }

    public function test_dispatches_stock_job_for_supported_events(): void
    {
        Queue::fake();

        $raw = '{"event":"inventory.changed"}';
        $sig = hash_hmac('sha256', $raw, 'whsec_test');

        app(WebhookHandlerService::class)->handleSignedPayload($raw, [
            'X-ERP-Signature' => $sig,
        ]);

        Queue::assertPushed(RunStockSyncJob::class);
        $this->assertDatabaseHas('sync_logs', [
            'sync_type' => IntegrationSyncType::WEBHOOK_ROUTED,
            'status' => 'success',
            'reference_key' => 'inventory.changed',
        ]);
    }

    public function test_rejects_unknown_event_after_signature_validation(): void
    {
        $raw = '{"event":"order.created"}';
        $sig = hash_hmac('sha256', $raw, 'whsec_test');

        $this->expectException(InvalidArgumentException::class);

        try {
            app(WebhookHandlerService::class)->handleSignedPayload($raw, [
                'X-ERP-Signature' => $sig,
            ]);
        } finally {
            $this->assertSame(0, SyncLog::query()->count());
        }
    }
}
