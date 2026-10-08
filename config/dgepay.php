<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DGePay Mode
    |--------------------------------------------------------------------------
    | Supported: "sandbox", "live"
    */
    'mode' => env('DGEPAY_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    | Currency configured for transactions (e.g. USD, BDT).
    */
    'currency' => env('DGEPAY_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox Credentials
    |--------------------------------------------------------------------------
    */
    'sandbox' => [
        'base_url' => env('DGEPAY_SANDBOX_BASE_URL', 'https://apiv2.dgepay.net/dipon/v3'),
        'client_id' => env('DGEPAY_SANDBOX_CLIENT_ID', 'test_client_id'),
        'client_secret' => env('DGEPAY_SANDBOX_CLIENT_SECRET', 'test_secret_16ch'),
        'client_api_key' => env('DGEPAY_SANDBOX_CLIENT_API_KEY', 'b0a5941bfdf4f2fb724a6382de52f44'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Live Credentials
    |--------------------------------------------------------------------------
    */
    'live' => [
        'base_url' => env('DGEPAY_LIVE_BASE_URL', 'https://apiv2.dgepay.net/dipon/v3'),
        'client_id' => env('DGEPAY_LIVE_CLIENT_ID'),
        'client_secret' => env('DGEPAY_LIVE_CLIENT_SECRET'),
        'client_api_key' => env('DGEPAY_LIVE_CLIENT_API_KEY'),
    ],
];
