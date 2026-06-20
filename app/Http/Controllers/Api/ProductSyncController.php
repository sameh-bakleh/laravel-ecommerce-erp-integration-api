<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunProductSyncJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class ProductSyncController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        Bus::dispatch(new RunProductSyncJob($correlationId));

        return response()->json([
            'accepted' => true,
            'correlation_id' => $correlationId,
        ], 202);
    }
}
