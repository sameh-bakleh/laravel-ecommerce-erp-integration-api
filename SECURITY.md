# Security policy

## Scope

This repository is a **portfolio / demonstration** Laravel integration API. It uses **mock ERP data** only. Do not point it at production ERP tenants or commit real credentials.

## Supported versions

| Version | Supported |
|---------|-----------|
| `main`  | Yes       |

## Reporting a vulnerability

If you discover a security issue in this codebase, please open a **private** security advisory on GitHub or email the repository owner. Do not open a public issue for exploitable findings.

## Secrets and configuration

- Never commit `.env`, `.env.docker`, or real API keys.
- Replace all sample tokens before any shared/staging deployment:
  - `INTEGRATION_API_TOKEN`
  - `ERP_WEBHOOK_SECRET`
  - `APP_KEY`
- Do not use Docker demo tokens (`docker-demo-token`, etc.) outside local sandboxes.

## Demo vs production

| Area | Demo (this repo) | Production guidance |
|------|------------------|---------------------|
| ERP client | `MockErpClient` | Real HTTP client with OAuth/mTLS, timeouts, circuit breakers |
| API auth | Static bearer token | Rotating tokens, IP allowlist, or OAuth2 client credentials |
| Webhooks | Shared HMAC secret | Per-tenant secrets, replay protection, timestamp tolerance |
| Database | Empty MySQL root / SQLite | Least-privilege DB user, encrypted connections |
| Debug | May be enabled in Docker | `APP_DEBUG=false`, structured logging, log redaction |

## Webhook verification

Inbound webhooks must include `X-ERP-Signature` computed as:

```
hex(hash_hmac('sha256', rawBody, ERP_WEBHOOK_SECRET))
```

The application compares signatures with `hash_equals()`.

Full documentation: **[docs/webhook-security.md](docs/webhook-security.md)** (headers, supported events, production hardening).

## Sync and retry documentation

- **[docs/sync-workflows.md](docs/sync-workflows.md)** — product, stock, order, and webhook-driven sync paths
- **[docs/queue-and-retry.md](docs/queue-and-retry.md)** — Laravel job backoff, `failed_syncs` domain retry, scheduler

## Request logging

`LogApiRequest` middleware stores a **truncated** request body preview (max 2000 characters). Avoid sending PII in webhook payloads in production without a retention/redaction policy.

## Dependency updates

Run `composer audit` periodically and keep Laravel/framework dependencies current.

## Pre-commit checklist

Before pushing or opening a PR:

- [ ] `.env` and `.env.docker` are not staged (`git status`)
- [ ] No real API keys in `docker-compose.yml`, tests, or docs
- [ ] Demo tokens in `.env.docker.example` are clearly labelled as local-only
- [ ] `composer test` and `vendor/bin/pint --test` pass locally

To check whether `.env` was ever committed:

```bash
git log --all --full-history -- .env .env.docker
```

If anything appears, rotate all secrets and remove the files from history before making the repository public.
