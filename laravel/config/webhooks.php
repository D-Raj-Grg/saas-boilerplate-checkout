<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Outbound Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for outbound webhook delivery system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'timeout' => env('WEBHOOK_TIMEOUT', 60),
    'max_tries' => env('WEBHOOK_MAX_TRIES', 3),
    'backoff' => env('WEBHOOK_BACKOFF', 60),
    'queue' => env('WEBHOOK_QUEUE', 'webhooks'),

    /*
    |--------------------------------------------------------------------------
    | Webhook URLs
    |--------------------------------------------------------------------------
    |
    | Define webhook URLs for different events.
    |
    */

    'urls' => [
        'user_created' => env('WEBHOOK_USER_CREATED_URL'),
        'organization_created' => env('WEBHOOK_ORGANIZATION_CREATED_URL'),
        'workspace_created' => env('WEBHOOK_WORKSPACE_CREATED_URL'),
        'waitlist_joined' => env('WEBHOOK_WAITLIST_JOINED_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'verify_ssl' => env('WEBHOOK_VERIFY_SSL', true),
    'secret_header' => env('WEBHOOK_SECRET_HEADER', 'X-Webhook-Secret'),
    'secret' => env('WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('WEBHOOK_RATE_LIMIT_ENABLED', false),
        'max_requests' => env('WEBHOOK_RATE_LIMIT_MAX_REQUESTS', 100),
        'per_minutes' => env('WEBHOOK_RATE_LIMIT_PER_MINUTES', 60),
    ],
];
