<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERP / E-commerce Integration API</title>
    <style>
        :root { color-scheme: light dark; font-family: system-ui, sans-serif; line-height: 1.5; }
        body { max-width: 42rem; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        p.lead { color: #666; margin-top: 0; }
        ul { padding-left: 1.25rem; }
        a { color: #2563eb; }
        code { font-size: 0.9em; }
        .badge { display: inline-block; font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 999px; background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
    <p class="badge">Portfolio sample · mock ERP only</p>
    <h1>ERP / E-commerce Integration API</h1>
    <p class="lead">B2B integration layer between a shop and ERP systems — product sync, stock, orders, signed webhooks, and retry queues.</p>

    <h2>Documentation</h2>
    <ul>
        <li><a href="https://github.com/sameh-bakleh/laravel-ecommerce-erp-integration-api/blob/main/docs/API.md">API reference</a></li>
        <li><a href="https://github.com/sameh-bakleh/laravel-ecommerce-erp-integration-api/blob/main/docs/WEBHOOKS.md">Webhooks &amp; HMAC</a></li>
        <li><a href="https://github.com/sameh-bakleh/laravel-ecommerce-erp-integration-api/blob/main/docs/SYNC_WORKFLOWS.md">Sync workflows</a></li>
        <li><a href="/up">Health check</a> — <code>/up</code></li>
    </ul>

    <h2>Demo credentials (local Docker)</h2>
    <p>See <code>.env.docker.example</code> — integration token and webhook secret are documented for local testing only.</p>

    <h2>Sample endpoints</h2>
    <ul>
        <li><code>POST /api/v1/products/sync</code> — push catalog to mock ERP</li>
        <li><code>POST /api/v1/orders/{id}/export</code> — export order to ERP</li>
        <li><code>POST /webhooks/erp</code> — inbound ERP events (HMAC-signed)</li>
    </ul>
</body>
</html>
