# Docker setup

Run the full stack (MySQL 8 + Laravel API) without installing PHP or MySQL locally.

## Prerequisites

- Docker Engine 24+
- Docker Compose v2

## Quick start (MySQL)

```bash
cp .env.docker.example .env.docker
docker compose up --build
```

| Service | URL / port |
|---------|------------|
| API | http://localhost:8000 |
| MySQL | `localhost:3307` (database `erp_integration`, user `root`, empty password) |
| Health | http://localhost:8000/up |

On first boot the `app` container runs migrations automatically.

## Queue worker (required for async sync)

Sync endpoints return `202` immediately; jobs need a worker:

```bash
docker compose exec app php artisan queue:work database --tries=3 --backoff=10,60,120
```

Without a worker, jobs sit in the `jobs` table until processed.

## Demo API calls

Credentials come from `.env.docker.example` (local demo only):

```bash
# Product sync
curl -s -X POST http://localhost:8000/api/v1/sync/products \
  -H "Authorization: Bearer docker-demo-token" \
  -H "Accept: application/json"

# Stock sync (after products)
curl -s -X POST http://localhost:8000/api/v1/sync/stock \
  -H "Authorization: Bearer docker-demo-token" \
  -H "Accept: application/json"

# Signed webhook
BODY='{"event":"stock.updated"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "docker-demo-webhook-secret" | awk '{print $2}')
curl -s -X POST http://localhost:8000/api/v1/webhooks/erp \
  -H "Content-Type: application/json" \
  -H "X-ERP-Signature: $SIG" \
  -d "$BODY"
```

More examples: [api.md](api.md).

## PostgreSQL profile

```bash
cp .env.docker.example .env.docker
docker compose --profile postgres up --build
```

| Service | URL / port |
|---------|------------|
| API (postgres) | http://localhost:8001 |
| PostgreSQL | `localhost:5433` (user/password `erp`) |

The `app-postgres` service overrides `DB_CONNECTION=pgsql` via Compose environment.

## Environment files

| File | Committed | Purpose |
|------|-----------|---------|
| `.env.docker.example` | Yes | Template with demo tokens |
| `.env.docker` | No (gitignored) | Your local Docker env |
| `.env.example` | Yes | Non-Docker local setup |

Never commit `.env.docker`. Demo tokens are not for production.

## Image build

The [Dockerfile](../Dockerfile) uses `php:8.2-cli-bookworm` with `pdo_mysql`, `pdo_pgsql`, and Composer 2. The app serves via `php artisan serve` on port 8000 inside the container.

Rebuild after dependency changes:

```bash
docker compose build --no-cache app
```

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `Connection refused` to MySQL | Wait for `mysql` healthcheck; `docker compose ps` |
| Sync returns 202 but no data | Start `queue:work` (see above) |
| Port 8000 in use | Change `ports` mapping in `docker-compose.yml` |
| Migrations fail on rebuild | `docker compose down -v` (destroys DB volume) |

## Production note

This Compose file is for **local demos and portfolio review**. Production would use PHP-FPM + nginx, secrets management, non-root DB users, and horizontal queue workers — not `artisan serve` in a single container.
