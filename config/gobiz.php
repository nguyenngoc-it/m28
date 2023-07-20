<?php

return [
    'm10' => [
        'url' => env('M10_URL', 'https://app.authen.me'),
        'timeout' => env('M10_TIMEOUT', 60),
    ],

    'm6' => [
        'url' => env('M6_URL', 'https://logistics.mygobiz.net/v1/'),
        'timeout' => env('M6_TIMEOUT', 60),
    ],

    'm4' => [
        'url' => env('M4_URL', 'https://hermes.gobizdev.com/'),
        'timeout' => env('M4_TIMEOUT', 60),
    ],


    'm32' => [
        'url' => env('M32_URL', 'https://dev-3-api.m32.gobizdev.com/'),
        'timeout' => env('M32_TIMEOUT', 60),
        'app_token_expire' => env('M32_APP_TOKEN_EXPIRE', 24*60), // Số phút sẽ trừ đi so với hạn token của m32
    ],

    /*
     * Site frontend của các tenant sử dụng SSL hay không
     */
    'tenant_ssl' => env('GOBIZ_TENANT_SSL', true),

    'domain_api' => env('GOBIZ_DOMAIN_API', ''),

    'count_date_run_calculate' => env('COUNT_DATE_RUN_CALCULATE', 3)
];
