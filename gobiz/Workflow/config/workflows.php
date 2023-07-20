<?php

return [
    'workflows' => [
        'ticket' => [
            // Danh sách places
            'places' => [
                'NEW',
                'RECEIVED',
                'PENDING',
                'IN_PROGRESS',
                'COMPLETED',
                'CLOSED',
            ],

            // Khai báo transitions
            'transitions' => [
                // Khai báo dạng ngắn gọn
                'NEW' => ['RECEIVED'],
                'RECEIVED' => ['PENDING', 'IN_PROGRESS'],
                'PENDING' => ['IN_PROGRESS'],
                'IN_PROGRESS' => ['COMPLETED'],
                'COMPLETED' => ['CLOSED'],

                // Khai báo dạng đầy đủ
                [
                    'name' => 'receive',
                    'from' => 'NEW',
                    'to' => 'RECEIVED',
                ],
                [
                    'name' => 'complete',
                    'from' => 'IN_PROGRESS',
                    'to' => 'COMPLETED',
                ],
            ],

            // Cho phép chuyển ngược sang place trước đó hay không?
            'reverse_transitions' => true,

            // Khai báo các middleware khi chuyển place
            'middleware' => [
            ],
        ],
    ],
];
