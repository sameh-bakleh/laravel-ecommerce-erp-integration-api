<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('integration.internal_api_token', '');
        if ($expected === '' || $request->bearerToken() !== $expected) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
