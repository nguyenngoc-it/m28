<?php

return [
    /*
     * Email Provider mặc định
     */
    'default' => env('EMAIL_PROVIDER', 'iris'),

    /*
     * Sender email mặc định
     */
    'sender' => env('EMAIL_SENDER', 'no-reply@gobiz.vn'),

    /*
     * Danh sách email provider
     */
    'providers' => [
        'iris' => [
            'api_url' => env('IRIS_API_URL', 'https://iris.gobiz.vn/api'),
            'username' => env('IRIS_USERNAME'),
            'password' => env('IRIS_PASSWORD'),
            'options' => [], // guzzle options
        ],
    ],
];
