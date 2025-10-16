<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Nepal-based payment gateways (eSewa, Khalti, Fonepay)
    |
    */

    'esewa' => [
        'merchant_id' => env('ESEWA_MERCHANT_ID'),
        'secret_key' => env('ESEWA_SECRET_KEY'),
        'success_url' => env('FRONTEND_URL').'/payment/success',
        'failure_url' => env('FRONTEND_URL').'/payment/failure',
        'api_url' => env('ESEWA_API_URL', 'https://esewa.com.np/epay/main'),
        'verify_url' => env('ESEWA_VERIFY_URL', 'https://esewa.com.np/epay/transrec'),
    ],

    'khalti' => [
        'public_key' => env('KHALTI_PUBLIC_KEY'),
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'api_url' => env('KHALTI_API_URL', 'https://khalti.com/api/v2/'),
        'return_url' => env('FRONTEND_URL').'/payment/return',
        'website_url' => env('FRONTEND_URL'),
    ],

    'fonepay' => [
        'merchant_code' => env('FONEPAY_MERCHANT_CODE'),
        'secret' => env('FONEPAY_SECRET'),
        'api_url' => env('FONEPAY_API_URL', 'https://fonepay.com/api/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | The default payment gateway to use if none is specified
    |
    */
    'default' => env('PAYMENT_GATEWAY_DEFAULT', 'esewa'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The currency used for payments (Nepali Rupee)
    |
    */
    'currency' => 'NPR',
];
