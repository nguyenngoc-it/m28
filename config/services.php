<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'shopee' => [
        'partner_id' => env('SHOPEE_PARTNER_ID'),
        'partner_key' => env('SHOPEE_PARTNER_KEY'),
        'authorization_url' => env('SHOPEE_AUTHORIZATION_URL', 'https://partner.shopeemobile.com/api/v1/shop/auth_partner'),
        'api_url' => env('SHOPEE_API_URL', 'https://partner.shopeemobile.com'),
        'order_shipping_partner' => [
            'sync_every' => env('SHOPEE_ORDER_SHIPPING_PARTNER_SYNC_EVERY', 3600), // Khoảng time giữa các lần đồng bộ (default: 1h)
            'sync_duration' => env('SHOPEE_ORDER_SHIPPING_PARTNER_SYNC_DURATION', 86400), // Đồng bộ trong khoảng thời gian bao lâu (default: 1d)
        ],
    ],

    'kiotviet' => [
        'api_url' => env('KIOTVIET_API_URL', 'https://public.kiotapi.com'),
        'webhook_url' => env('KIOTVIET_WEBHOOK_URL', ''),
        'order_shipping_partner' => [
            'sync_every' => env('KIOTVIET_ORDER_SHIPPING_PARTNER_SYNC_EVERY', 3600), // Khoảng time giữa các lần đồng bộ (default: 1h)
            'sync_duration' => env('KIOTVIET_ORDER_SHIPPING_PARTNER_SYNC_DURATION', 86400), // Đồng bộ trong khoảng thời gian bao lâu (default: 1d)
        ],
        'token_exception' => 'TokenException',
    ],
    'lazada' => [
        'client_id' => env('LAZADA_CLIENT_ID'),
        'client_secret' => env('LAZADA_CLIENT_SECRET'),
        'uri_redirect'=>env('LAZADA_REDIRECT_URI'),
        'api_url' => env('LAZADA_API_URL', 'https://api.lazada.vn/rest'),
        'api_url_auth' => env('LAZADA_API_URL_AUTH', 'https://auth.lazada.com/rest'),
        'authorization_url' => env('LAZADA_AUTHORIZATION_URL', 'https://auth.lazada.com/oauth/authorize'),
        'webhook_url' => env('LAZADA_WEBHOOK_URL', ''),
        'order_shipping_partner' => [
            'sync_every' => env('LAZADA_ORDER_SHIPPING_PARTNER_SYNC_EVERY', 3600), // Khoảng time giữa các lần đồng bộ (default: 1h)
            'sync_duration' => env('LAZADA_ORDER_SHIPPING_PARTNER_SYNC_DURATION', 86400), // Đồng bộ trong khoảng thời gian bao lâu (default: 1d)
        ],
        'token_exception' => 'TokenException',
        'debug' => env('LAZADA_DEBUG', false),
    ],

    'tiki' => [
        'client_id' => env('TIKI_CLIENT_ID'),
        'client_secret' => env('TIKI_CLIENT_SECRET'),
        'uri_redirect'=>env('TIKI_REDIRECT_URI'),
        'api_url' => env('TIKI_API_URL', 'https://api.tiki.vn'),
        'authorization_url' => env('TIKI_AUTHORIZATION_URL', 'https://api.tiki.vn/sc/oauth2/auth'),
        'webhook_url' => env('TIKI_WEBHOOK_URL', ''),
        'order_shipping_partner' => [
            'sync_every' => env('TIKI_ORDER_SHIPPING_PARTNER_SYNC_EVERY', 3600), // Khoảng time giữa các lần đồng bộ (default: 1h)
            'sync_duration' => env('TIKI_ORDER_SHIPPING_PARTNER_SYNC_DURATION', 86400), // Đồng bộ trong khoảng thời gian bao lâu (default: 1d)
        ],
        'token_exception' => 'TokenException',
        'debug' => env('TIKI_DEBUG', false),
    ],

    'tiktokshop' => [
        'client_id' => env('TIKTOKSHOP_CLIENT_ID'),
        'client_secret' => env('TIKTOKSHOP_CLIENT_SECRET'),
        'uri_redirect'=>env('TIKTOKSHOP_REDIRECT_URI'),
        'api_url' => env('TIKTOKSHOP_API_URL', 'https://open-api.tiktokglobalshop.com'),
        'authorization_url' => env('TIKTOKSHOP_AUTHORIZATION_URL', 'https://auth.tiktok-shops.com/oauth/authorize'),
        'authorization_uri' => env('TIKTOKSHOP_AUTHORIZATION_URI', 'https://auth.tiktok-shops.com'),
        'webhook_url' => env('TIKTOKSHOP_WEBHOOK_URL', ''),
        'order_shipping_partner' => [
            'sync_every' => env('TIKTOKSHOP_ORDER_SHIPPING_PARTNER_SYNC_EVERY', 3600), // Khoảng time giữa các lần đồng bộ (default: 1h)
            'sync_duration' => env('TIKTOKSHOP_ORDER_SHIPPING_PARTNER_SYNC_DURATION', 86400), // Đồng bộ trong khoảng thời gian bao lâu (default: 1d)
        ],
        'token_exception' => 'TokenException',
        'debug' => env('TIKTOKSHOP_DEBUG', false),
    ],

    'shopbaseus' => [
        'client_id' => env('SHOPBASE_CLIENT_ID'),
        'client_secret' => env('SHOPBASE_CLIENT_SECRET'),
        'uri_redirect'=>env('SHOPBASE_REDIRECT_URI'),
        'api_url' => env('SHOPBASE_API_URL', 'onshopbase.com'),
        'authorization_url' => env('SHOPBASE_AUTHORIZATION_URL', ''),
        'authorization_uri' => env('SHOPBASE_AUTHORIZATION_URI', ''),
        'webhook_url' => env('SHOPBASE_WEBHOOK_URL', ''),
        'order_shipping_partner' => [
            'sync_every' => env('SHOPBASE_ORDER_SHIPPING_PARTNER_SYNC_EVERY', 3600), // Khoảng time giữa các lần đồng bộ (default: 1h)
            'sync_duration' => env('SHOPBASE_ORDER_SHIPPING_PARTNER_SYNC_DURATION', 86400), // Đồng bộ trong khoảng thời gian bao lâu (default: 1d)
        ],
        'token_exception' => 'TokenException',
        'debug' => env('SHOPBASE_DEBUG', false),
    ],

    'sapo' => [
        'client_id' => env('SAPO_CLIENT_ID'),
        'client_secret' => env('SAPO_CLIENT_SECRET'),
        'uri_redirect'=>env('SAPO_REDIRECT_URI'),
        'api_url' => env('SAPO_API_URL', 'mysapo.net'),
        'authorization_url' => env('SAPO_AUTHORIZATION_URL', ''),
        'authorization_uri' => env('SAPO_AUTHORIZATION_URI', ''),
        'webhook_url' => env('SAPO_WEBHOOK_URL', ''),
        'order_shipping_partner' => [
            'sync_every' => env('SAPO_ORDER_SHIPPING_PARTNER_SYNC_EVERY', 3600), // Khoảng time giữa các lần đồng bộ (default: 1h)
            'sync_duration' => env('SAPO_ORDER_SHIPPING_PARTNER_SYNC_DURATION', 86400), // Đồng bộ trong khoảng thời gian bao lâu (default: 1d)
        ],
        'token_exception' => 'TokenException',
        'debug' => env('SAPO_DEBUG', false),
    ],

    'topship' => [
        'api_url' => env('TOPSHIP_API_URL', 'https://api.etop.vn'),
    ],

    'idempotency' => [
        'ttl' => env('IDEMPOTENCY_TTL', 7200), // 2h
    ],

];
