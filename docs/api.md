# API reference

Base URL (local): `http://localhost:8000`

**Related docs:** [webhook-security.md](webhook-security.md) · [sync-workflows.md](sync-workflows.md) · [queue-and-retry.md](queue-and-retry.md) · [docker.md](docker.md)

All JSON responses use `application/json`. Successful sync triggers return **202 Accepted** with a `correlation_id` for tracing in `sync_logs` and `api_request_logs`.

## Authentication

| Route group | Mechanism |
|-------------|-----------|
| `/api/v1/sync/*` | `Authorization: Bearer <INTEGRATION_API_TOKEN>` |
| `/api/v1/webhooks/erp` | HMAC header `X-ERP-Signature` |

### Webhook signature

```
X-ERP-Signature: hex(hash_hmac('sha256', rawRequestBody, ERP_WEBHOOK_SECRET))
```

Use timing-safe comparison on the server (`hash_equals`).

---

## Endpoints

### `POST /api/v1/sync/products`

Triggers a queued bulk product import from the configured ERP client.

**Auth:** Bearer token

**Request body:** none

**Response `202`:**

```json
{
  "accepted": true,
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Headers:** `X-Request-Id` (same value used in `api_request_logs`)

---

### `POST /api/v1/sync/stock`

Triggers a queued bulk stock sync. Products must exist locally first.

**Auth:** Bearer token

**Response `202`:** same shape as product sync

**Errors:** `500` when ERP/stock mapping fails; creates `failed_syncs` + `sync_logs` rows

---

### `POST /api/v1/sync/orders/{erpOrderNumber}`

Triggers a queued single-order import.

**Auth:** Bearer token

**Path parameter:** `erpOrderNumber` — e.g. `PO-2026-0001`

**Response `202`:** same shape as product sync

---

### `POST /api/v1/sync/retry-failed`

Re-dispatches queue jobs for `failed_syncs` rows that are due (`next_retry_at <= now`, `attempts < max_attempts`).

**Auth:** Bearer token

**Response `200`:**

```json
{
  "jobs_dispatched": 1,
  "records_marked_dead": 0
}
```

Also available as: `php artisan integration:retry-failed`

---

### `POST /api/v1/webhooks/erp`

Accepts signed ERP webhook envelopes and routes supported events to queue jobs.

**Auth:** HMAC signature (no Bearer token)

**Request body:**

```json
{
  "event": "stock.updated"
}
```

Supported events: `stock.updated`, `inventory.changed`

**Response `202`:**

```json
{
  "accepted": true
}
```

**Errors:**

| Status | Reason |
|--------|--------|
| `403` | Missing/invalid signature |
| `422` | Invalid JSON or unsupported `event` |

---

## Example `curl` commands (Docker demo)

Demo credentials come from `.env.docker.example` (copy to `.env.docker`).

```bash
# Product sync
curl -s -X POST http://localhost:8000/api/v1/sync/products \
  -H "Authorization: Bearer docker-demo-token" \
  -H "Accept: application/json"

# Stock sync (run product sync first)
curl -s -X POST http://localhost:8000/api/v1/sync/stock \
  -H "Authorization: Bearer docker-demo-token" \
  -H "Accept: application/json"

# Order sync
curl -s -X POST http://localhost:8000/api/v1/sync/orders/PO-2026-0001 \
  -H "Authorization: Bearer docker-demo-token" \
  -H "Accept: application/json"

# Webhook (stock.updated)
BODY='{"event":"stock.updated"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "docker-demo-webhook-secret" | awk '{print $2}')
curl -s -X POST http://localhost:8000/api/v1/webhooks/erp \
  -H "Content-Type: application/json" \
  -H "X-ERP-Signature: $SIG" \
  -d "$BODY"
```

---

## Persistence (audit tables)

| Table | Purpose |
|-------|---------|
| `products` | Normalized article master data |
| `stock_levels` | Per-warehouse quantities |
| `integration_orders` | Inbound B2B orders |
| `sync_logs` | Per-run success/failure audit |
| `failed_syncs` | Domain-level retry queue |
| `api_request_logs` | HTTP request audit trail |
| `jobs` / `failed_jobs` | Laravel queue infrastructure |

---

## Health check

`GET /up` — Laravel health endpoint (used in Docker/CI smoke checks).
