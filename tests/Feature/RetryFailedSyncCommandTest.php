<?php

namespace Tests\Feature;

use App\Integration\Support\IntegrationSyncType;
use App\Jobs\RunProductSyncJob;
use App\Models\FailedSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetryFailedSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_dispatches_due_retries(): void
    {
        Queue::fake();

        FailedSync::query()->create([
            'sync_type' => IntegrationSyncType::PRODUCT_BULK,
            'reference_key' => 'bulk',
            'attempts' => 0,
            'max_attempts' => 5,
            'status' => FailedSync::STATUS_PENDING_RETRY,
            'next_retry_at' => now()->subMinute(),
            'correlation_id' => 'cmd-correlation',
        ]);

        $this->artisan('integration:retry-failed')
            ->expectsOutputToContain('Jobs dispatched: 1')
            ->assertSuccessful();

        Queue::assertPushed(RunProductSyncJob::class);
    }
}
