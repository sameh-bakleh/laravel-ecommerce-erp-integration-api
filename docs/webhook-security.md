# Webhook security

Inbound ERP webhooks use **HMAC-SHA256** over the **raw request body**. There is no Bearer token on the webhook route — authenticity comes from the shared secret and timing-safe signature comparison.

## Header contract

| Header | Required | Description |
|--------|----------|-------------|
| `Content-Type` | Yes | `application/json` |
| `X-ERP-Signature` | Yes | Hex-encoded HMAC digest of the raw body |

Configure the secret via `ERP_WEBHOOK_SECRET` in `.env` (see [.env.example](../.env.example)).

## Signature algorithm

```
signature = hex( HMAC-SHA256( rawRequestBody, ERP_WEBHOOK_SECRET ) )
```

**Server-side verification** (`WebhookHandlerService`):

1. Reject if `ERP_WEBHOOK_SECRET` is empty (`403`).
2. Read `X-ERP-Signature` from headers (case-insensitive).
3. Compute `hash_hmac('sha256', $rawBody, $secret)`.
4. Compare with `hash_equals()` — never use `==`.
5. Only after a valid signature, parse JSON and route the `event`.

```php
hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
```

## Supported events

After signature validation, the payload must be a JSON object with an `event` field:

| Event | Action |
|-------|--------|
| `stock.updated` | Dispatch `RunStockSyncJob` |
| `inventory.changed` | Dispatch `RunStockSyncJob` |

Unsupported events return `422` **after** signature validation (no job is dispatched, no audit success row).

## Error responses

| HTTP | Cause |
|------|--------|
| `403` | Missing secret, missing header, or invalid signature |
| `422` | Invalid JSON, missing `event`, or unsupported `event` |
| `202` | Signature valid, event routed to queue |

## Generating a test signature

**OpenSSL (shell):**

```bash
BODY='{"event":"stock.updated"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$ERP_WEBHOOK_SECRET" | awk '{print $2}')
```

**PHP:**

```php
$sig = hash_hmac('sha256', $rawBody, $secret);
```

Docker demo values are in [.env.docker.example](../.env.docker.example) — local sandboxes only.

## Audit trail

Successful webhook routing writes a `sync_logs` row with `sync_type = webhook_routed`, `reference_key = <event>`, and a new `correlation_id` passed to the dispatched job.

HTTP-level audit is recorded separately in `api_request_logs` (body preview truncated to 2 KB).

## Production hardening (out of scope for this demo)

| Demo behaviour | Production recommendation |
|----------------|---------------------------|
| Single shared secret | Per-tenant secrets rotated via vault |
| No replay protection | `X-ERP-Timestamp` + tolerance window; reject stale signatures |
| Raw body logged (truncated) | Redact PII; retention policy on `api_request_logs` |
| No IP allowlist | Restrict webhook ingress to ERP egress IPs |

See also [SECURITY.md](../SECURITY.md).
