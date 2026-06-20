<?php

namespace App\Console\Commands;

use App\Integration\Services\RetryFailedSyncService;
use Illuminate\Console\Command;

class RetryFailedIntegrationSyncCommand extends Command
{
    protected $signature = 'integration:retry-failed';

    protected $description = 'Dispatch queued jobs for pending failed_sync rows that are due';

    public function handle(RetryFailedSyncService $retryFailedSyncService): int
    {
        $result = $retryFailedSyncService->retryDue();
        $this->info('Jobs dispatched: '.$result['jobs_dispatched'].', records marked dead: '.$result['records_marked_dead']);

        return self::SUCCESS;
    }
}
