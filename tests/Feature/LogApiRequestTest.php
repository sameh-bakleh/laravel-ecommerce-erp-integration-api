<?php

namespace Tests\Feature;

use App\Models\ApiRequestLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogApiRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_request_writes_audit_log_with_request_id_header(): void
    {
        $response = $this->postJson('/api/v1/sync/products', [], [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertAccepted();
        $requestId = $response->headers->get('X-Request-Id');
        $this->assertNotEmpty($requestId);

        $this->assertDatabaseHas('api_request_logs', [
            'request_id' => $requestId,
            'method' => 'POST',
            'path' => '/api/v1/sync/products',
            'status_code' => 202,
        ]);

        $log = ApiRequestLog::query()->where('request_id', $requestId)->first();
        $this->assertNotNull($log->duration_ms);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
    }

    public function test_webhook_audit_log_truncates_large_request_body_preview(): void
    {
        $raw = '{"event":"unknown.event","padding":"'.str_repeat('a', 2500).'"}';
        $sig = hash_hmac('sha256', $raw, 'whsec_test');

        $this->call('POST', '/api/v1/webhooks/erp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ERP_SIGNATURE' => $sig,
        ], $raw)->assertUnprocessable();

        $log = ApiRequestLog::query()->where('path', '/api/v1/webhooks/erp')->first();
        $this->assertNotNull($log);
        $this->assertSame(422, $log->status_code);
        $this->assertSame(2000, mb_strlen((string) $log->request_body_preview));
    }
}
