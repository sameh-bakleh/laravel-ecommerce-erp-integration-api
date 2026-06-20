<?php

namespace App\Integration\Services;

use App\Integration\Support\IntegrationSyncType;
use App\Integration\Support\SyncAuditRecorder;
use App\Jobs\RunStockSyncJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class WebhookHandlerService
{
    public function __construct(
        private readonly SyncAuditRecorder $audit,
    ) {}

    /**
     * @param  array<string, string|string[]>  $headers
     */
    public function handleSignedPayload(string $rawBody, array $headers): void
    {
        $started = microtime(true);

        $secret = (string) config('integration.webhook.secret', '');
        if ($secret === '') {
            throw new AccessDeniedHttpException('Webhook secret is not configured.');
        }

        $signature = $this->firstHeader($headers, 'X-ERP-Signature');
        if ($signature === null || ! hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature)) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        $decoded = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Webhook body must be a JSON object.');
        }

        $event = (string) Arr::get($decoded, 'event', '');
        if ($event === '') {
            throw new InvalidArgumentException('Webhook payload missing event.');
        }

        $correlationId = (string) Str::uuid();

        match ($event) {
            'stock.updated', 'inventory.changed' => Bus::dispatch(
                new RunStockSyncJob($correlationId),
            ),
            default => throw new InvalidArgumentException("Unsupported webhook event: {$event}"),
        };

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        $this->audit->logSuccess(
            IntegrationSyncType::WEBHOOK_ROUTED,
            $correlationId,
            $durationMs,
            $event,
            ['event' => $event],
        );
    }

    /**
     * @param  array<string, string|string[]>  $headers
     */
    private function firstHeader(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string) $key, $name) !== 0) {
                continue;
            }
            if (is_array($value)) {
                return $value[0] ?? null;
            }

            return $value;
        }

        return null;
    }
}
