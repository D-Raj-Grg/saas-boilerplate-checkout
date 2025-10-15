<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the rate limiting rules for various API endpoints.
    | You can customize these values based on your application's needs.
    |
    */

    'limits' => [
        // Authentication endpoints
        'auth' => [
            'attempts' => 5,
            'decay_minutes' => 1,
        ],

        'password-reset' => [
            'attempts' => 3,
            'decay_minutes' => 60,
        ],

        // General API endpoints
        'api' => [
            'attempts' => 500,
            'decay_minutes' => 1,
        ],

        'invitations' => [
            'attempts' => 20,
            'decay_minutes' => 1,
        ],

        'workspace_settings' => [
            'attempts' => 30,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan-Based Rate Limit Multipliers
    |--------------------------------------------------------------------------
    |
    | Define rate limit multipliers based on plan slugs.
    | These multipliers are applied to the base limits defined above.
    | The plan slug is matched from the plans table.
    |
    */

    'plan_multipliers' => [
        'free' => 1,
        'starter' => 2,
        'professional' => 5,
        'enterprise' => 10,
        'unlimited' => PHP_INT_MAX, // Effectively no limit
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Headers
    |--------------------------------------------------------------------------
    |
    | Configure whether to include rate limit information in response headers.
    |
    */

    'headers' => [
        'enabled' => true,
        'remaining' => 'X-RateLimit-Remaining',
        'limit' => 'X-RateLimit-Limit',
        'retry_after' => 'X-RateLimit-Retry-After',
        'reset' => 'X-RateLimit-Reset',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the cache store and prefix for rate limiting.
    |
    */

    'cache' => [
        'store' => env('RATE_LIMIT_CACHE_STORE', 'redis'),
        'prefix' => 'rate_limit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the response when rate limit is exceeded.
    |
    */

    'response' => [
        'message' => 'Too many requests. Please try again later.',
        'error_code' => 'RATE_LIMIT_EXCEEDED',
    ],
];
