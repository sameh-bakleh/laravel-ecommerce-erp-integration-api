<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunStockSyncJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class StockSyncController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        Bus::dispatch(new RunStockSyncJob($correlationId));

        return response()->json([
            'accepted' => true,
            'correlation_id' => $correlationId,
        ], 202);
    }
}
