<?php

namespace App\Integration\Support;

final class IntegrationSyncType
{
    public const PRODUCT_BULK = 'product_bulk';

    public const STOCK_BULK = 'stock_bulk';

    public const ORDER_SINGLE = 'order_single';

    public const WEBHOOK_ROUTED = 'webhook_routed';
}
