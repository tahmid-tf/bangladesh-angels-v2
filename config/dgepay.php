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
    | Currency & Conversion
    |--------------------------------------------------------------------------
    | When memberships are priced in USD, DGePay (a Bangladeshi PSO) settles
    | in BDT. If convert_usd_to_bdt is true, the USD price is multiplied by
    | usd_exchange_rate before initiating checkout with DGePay.
    | If your DGePay merchant account is configured to settle directly in USD,
    | set DGEPAY_CONVERT_USD_TO_BDT=false in .env.
    */
    'currency' => env('DGEPAY_CURRENCY', 'USD'),
    'convert_usd_to_bdt' => filter_var(env('DGEPAY_CONVERT_USD_TO_BDT', true), FILTER_VALIDATE_BOOLEAN),
    'usd_exchange_rate' => (float) env('DGEPAY_USD_EXCHANGE_RATE', 122.0),


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
