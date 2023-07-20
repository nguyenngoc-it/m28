<?php

return [

    /*
     * Thông tin kêt nối api
     */
    'api' => [
        'url' => env('WEBHOOK_URL', 'https://webhook.vinasat.gobizdev.com/api'),
        'token' => env('WEBHOOK_TOKEN'),
    ],

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

];
