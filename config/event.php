<?php

return [
    /*
     * Thông tin config liên quan đến public event để giao tiếp giữa các service
     */
    'public_event' => [
        /*
         * Kafka connection để pub/sub event
         */
        'kafka_connection' => env('PUBLIC_EVENT_KAFKA_CONNECTION', 'public_event'),

        /*
         * Danh sách transformer tương ứng với các đối tượng
         */
        'transformers' => [
            \Modules\User\Models\User::class => \Modules\User\Transformers\UserPublicEventTransformer::class,
        ],

        /*
         * Danh sách transformer finder
         */
        'transformer_finders' => [
        ],

        /*
         * Tên schema đăng kí trong avro
         */
        'schema' => env('PUBLIC_EVENT_SCHEMA', 'public_event'),
    ],
];
