<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunOrderSyncJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class OrderSyncController extends Controller
{
    public function __invoke(string $erpOrderNumber): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        Bus::dispatch(new RunOrderSyncJob($erpOrderNumber, $correlationId));

        return response()->json([
            'accepted' => true,
            'correlation_id' => $correlationId,
            'erp_order_number' => $erpOrderNumber,
        ], 202);
    }
}
