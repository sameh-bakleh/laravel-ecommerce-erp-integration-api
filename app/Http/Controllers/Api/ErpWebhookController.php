<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Integration\Services\WebhookHandlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ErpWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookHandlerService $handler): JsonResponse
    {
        try {
            $handler->handleSignedPayload($request->getContent(), $request->headers->all());
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (JsonException $e) {
            return response()->json(['message' => 'Invalid JSON body'], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['accepted' => true], 202);
    }
}
