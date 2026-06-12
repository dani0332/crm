<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TPA Client API Configuration
    |--------------------------------------------------------------------------
    |
    | This is the TPAAClientAPIV2 configuration for Embedded-Product Excess-Cashback (EP-ECB).
    |
    */
    'tpa_client_api' => [
        'base_url' => env('TPA_CLIENT_API_BASE_URL', 'https://devwp.waypoint-systems.com:446/TPAClientAPI'),
        'client_code' => env('TPA_CLIENT_CODE', 'ENOC'),
        'client_id' => env('TPA_CLIENT_ID'),
        'client_secret' => env('TPA_CLIENT_SECRET'),
        'timeout' => env('TPA_CLIENT_API_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sage API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Sage API settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Alfred Coins (InsuranceMarket webhook)
    |--------------------------------------------------------------------------
    |
    | Dispatched when a quote reaches PolicyBooked ( see QuotePolicyBooked event).
    |
    */
    'alfred_coins' => [
        'insurancemarket_webhook' => [
            'url' => env('ALFRED_COINS_INSURANCEMARKET_WEBHOOK_URL', 'https://api-stage-alfredcoins.myalfred.me/webhook/upload/imcrm'),
            'private_key' => env('ALFRED_COINS_INSURANCEMARKET_PRIVATE_KEY'),
            'timeout' => (int) env('ALFRED_COINS_INSURANCEMARKET_TIMEOUT', 15),
        ],
    ],

];
