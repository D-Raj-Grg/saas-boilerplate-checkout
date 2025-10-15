<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Store URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your WordPress SureCart installation.
    |
    */
    'url' => env('BILLING_STORE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Billing Store API Key
    |--------------------------------------------------------------------------
    |
    | The secure API key for authenticating with the WordPress billing store.
    |
    */
    'api_key' => env('BILLING_STORE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Sync Endpoint
    |--------------------------------------------------------------------------
    |
    | The endpoint path for syncing users to the billing store.
    |
    */
    'sync_endpoint' => '/wp-json/surecrm-billing/v1/sync-user',
];
