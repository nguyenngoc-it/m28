<?php

return [
    /*
     * Kết nối mặc định đến kafka
     */
    'default' => env('KAFKA_CONNECTION', 'public_event'),

    /*
     * Danh sách các kết nối đến kafka
     */
    'connections' => [
        /*
         * Server kafka phục vụ các public event của các service
         */
        'public_event' => [
            'brokers' => env('KAFKA_PUBLIC_EVENT_BROKERS', 'kafka:9092'),
        ],
    ],

    /*
     * Prefix của các consumer group
     */
    'consumer_group_prefix' => env('KAFKA_CONSUMER_GROUP_PREFIX', ''),

    /*
     * Log lại thông tin message được pub & sub trên kafka
     */
    'debug' => env('KAFKA_DEBUG', false),
];
