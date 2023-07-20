<?php

return [
    /*
     * Kết nối mặc định đến kafka
     */
    'default' => env('KAFKA_CONNECTION', 'main'),

    /*
     * Danh sách các kết nối đến kafka
     */
    'connections' => [
        'main' => [
            'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
        ],
    ],
];
