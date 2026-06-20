<?php

return [
    'erp' => [
        /**
         * When true, MockErpClient throws transport errors (for retry / failure-path tests).
         */
        'simulate_transport_failure' => (bool) env('ERP_SIMULATE_TRANSPORT_FAILURE', false),
    ],
    'webhook' => [
        'secret' => env('ERP_WEBHOOK_SECRET', ''),
    ],
    'internal_api_token' => env('INTEGRATION_API_TOKEN', ''),
];
