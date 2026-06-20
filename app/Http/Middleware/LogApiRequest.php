<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->has('integration_request_id')) {
            $request->attributes->set('integration_request_id', (string) Str::uuid());
            $request->attributes->set('integration_log_started', microtime(true));
        }

        $response = $next($request);
        $response->headers->set('X-Request-Id', (string) $request->attributes->get('integration_request_id'));

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->is('api/*')) {
            return;
        }

        $started = (float) $request->attributes->get('integration_log_started', microtime(true));
        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $requestId = (string) $request->attributes->get('integration_request_id');

        $body = $request->getContent();
        $preview = $body !== '' ? mb_substr($body, 0, 2000) : null;

        ApiRequestLog::query()->create([
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => '/'.$request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'ip_address' => $request->ip(),
            'request_body_preview' => $preview,
        ]);
    }
}
