<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Integration\Services\RetryFailedSyncService;
use Illuminate\Http\JsonResponse;

class RetryFailedSyncController extends Controller
{
    public function __invoke(RetryFailedSyncService $retryFailedSyncService): JsonResponse
    {
        $result = $retryFailedSyncService->retryDue();

        return response()->json($result);
    }
}
