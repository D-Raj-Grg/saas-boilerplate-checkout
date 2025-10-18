<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | Define all supported currencies with their symbols, names, and formatting
    |
    */
    'supported' => [
        'NPR' => [
            'code' => 'NPR',
            'symbol' => 'Rs.',
            'name' => 'Nepalese Rupee',
            'decimal_places' => 2,
            'symbol_position' => 'before', // before or after
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
        'USD' => [
            'code' => 'USD',
            'symbol' => '$',
            'name' => 'US Dollar',
            'decimal_places' => 2,
            'symbol_position' => 'before',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
        'EUR' => [
            'code' => 'EUR',
            'symbol' => '€',
            'name' => 'Euro',
            'decimal_places' => 2,
            'symbol_position' => 'before',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
        'GBP' => [
            'code' => 'GBP',
            'symbol' => '£',
            'name' => 'British Pound',
            'decimal_places' => 2,
            'symbol_position' => 'before',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
        'INR' => [
            'code' => 'INR',
            'symbol' => '₹',
            'name' => 'Indian Rupee',
            'decimal_places' => 2,
            'symbol_position' => 'before',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency used throughout the application
    |
    */
    'default' => env('DEFAULT_CURRENCY', 'NPR'),

    /*
    |--------------------------------------------------------------------------
    | Currency by Market
    |--------------------------------------------------------------------------
    |
    | Map markets to their default currencies
    |
    */
    'markets' => [
        'nepal' => 'NPR',
        'international' => 'USD',
        'india' => 'INR',
        'europe' => 'EUR',
        'uk' => 'GBP',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rates
    |--------------------------------------------------------------------------
    |
    | Define base exchange rates (base currency: NPR)
    | For production, integrate with a currency API service
    |
    */
    'exchange_rates' => [
        'NPR' => 1.0,
        'USD' => 0.0075,    // 1 NPR = 0.0075 USD (approximately)
        'EUR' => 0.0069,    // 1 NPR = 0.0069 EUR
        'GBP' => 0.0059,    // 1 NPR = 0.0059 GBP
        'INR' => 0.62,      // 1 NPR = 0.62 INR
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Detection
    |--------------------------------------------------------------------------
    |
    | Detect currency based on user location, IP, or request headers
    |
    */
    'auto_detect' => env('CURRENCY_AUTO_DETECT', true),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Currency Support
    |--------------------------------------------------------------------------
    |
    | Map payment gateways to supported currencies
    |
    */
    'gateway_support' => [
        'esewa' => ['NPR'],
        'khalti' => ['NPR'],
        'fonepay' => ['NPR'],
        'stripe' => ['USD', 'EUR', 'GBP', 'INR'],
        'mock' => ['NPR', 'USD', 'EUR', 'GBP', 'INR'], // Mock supports all for testing
    ],
];
