<?php
return [
    'region' => env('AWS_REGION', 'ap-southeast-1'),

    'version' => 'latest',

    'event_bridge' => [
        /*
         * The name or ARN of the event bus to receive the event
         */
        'name' => env('EVENT_BRIDGE_NAME'),

        /*
         * Danh sách transformer tương ứng với các đối tượng
         */
        'transformers' => [
            \Modules\Tenant\Models\Tenant::class => \Modules\EventBridge\Transformers\TenantTransformer::class,
            \Modules\Merchant\Models\Merchant::class => \Modules\EventBridge\Transformers\MerchantTransformer::class,
            \Modules\Order\Models\Order::class => \Modules\EventBridge\Transformers\OrderTransformer::class,
            \Modules\Order\Models\OrderSku::class => \Modules\EventBridge\Transformers\OrderSkuTransformer::class,
        ],

        /*
         * Danh sách transformer finder
         */
        'transformer_finders' => [
        ],
    ],
];
