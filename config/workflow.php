<?php

use Modules\Order\Middleware\DispatchOrderStatusChangedEvent;
use Modules\Order\Middleware\LogOrderChangeStatus;
use Modules\Order\Middleware\PrepareOrderChangeStatus;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Middleware\LogOrderPackingChangeStatus;
use Modules\OrderPacking\Middleware\UpdateWhenOrderPackingChangeStatus;
use Modules\OrderPacking\Models\OrderPacking;

return [
    'workflows' => [

        'order' => [
            // Danh sách status
            'places' => [
                Order::STATUS_WAITING_INSPECTION,
                Order::STATUS_WAITING_CONFIRM,
                Order::STATUS_WAITING_PROCESSING,
                Order::STATUS_WAITING_PICKING,
                Order::STATUS_WAITING_PACKING,
                Order::STATUS_WAITING_DELIVERY,
                Order::STATUS_DELIVERING,
                Order::STATUS_PART_DELIVERED,
                Order::STATUS_DELIVERED,
                Order::STATUS_FINISH,
                Order::STATUS_CANCELED,
                Order::STATUS_RETURN,
                Order::STATUS_RETURN_COMPLETED,
                Order::STATUS_FAILED_DELIVERY,
            ],

            // Khai báo status flow
            'transitions' => [
                Order::STATUS_WAITING_INSPECTION => [
                    Order::STATUS_WAITING_CONFIRM,
                    Order::STATUS_WAITING_PROCESSING,
                    Order::STATUS_CANCELED,
                ],
                Order::STATUS_WAITING_CONFIRM => [
                    Order::STATUS_WAITING_PROCESSING,
                    Order::STATUS_CANCELED,
                ],
                Order::STATUS_WAITING_PROCESSING => [
                    Order::STATUS_WAITING_PICKING,
                    Order::STATUS_CANCELED,
                ],
                Order::STATUS_WAITING_PICKING => [
                    Order::STATUS_WAITING_PROCESSING,
                    Order::STATUS_WAITING_PACKING,
                    Order::STATUS_WAITING_DELIVERY,
                    Order::STATUS_CANCELED,
                ],
                Order::STATUS_WAITING_PACKING => [
                    Order::STATUS_WAITING_DELIVERY,
                    Order::STATUS_CANCELED,
                ],
                Order::STATUS_WAITING_DELIVERY => [
                    Order::STATUS_DELIVERING,
                ],
                Order::STATUS_DELIVERING => [
                    Order::STATUS_PART_DELIVERED,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_FINISH,
                    Order::STATUS_RETURN,
                    Order::STATUS_RETURN_COMPLETED,
                    Order::STATUS_FAILED_DELIVERY,
                ],
                Order::STATUS_PART_DELIVERED => [
                    Order::STATUS_DELIVERED,
                    Order::STATUS_FINISH,
                    Order::STATUS_RETURN,
                    Order::STATUS_RETURN_COMPLETED,
                    Order::STATUS_FAILED_DELIVERY,
                ],
                Order::STATUS_RETURN => [
                    Order::STATUS_FINISH,
                    Order::STATUS_RETURN_COMPLETED,
                    Order::STATUS_DELIVERING,
                    Order::STATUS_DELIVERED
                ],
                Order::STATUS_FAILED_DELIVERY => [
                    Order::STATUS_FINISH,
                    Order::STATUS_RETURN_COMPLETED,
                    Order::STATUS_DELIVERING,
                    Order::STATUS_DELIVERED
                ],
                Order::STATUS_DELIVERED => [
                    Order::STATUS_FINISH,
                    Order::STATUS_RETURN_COMPLETED
                ],
            ],

            // Cho phép chuyển ngược status trước đó hay không?
            'reverse_transitions' => false,

            // Khai báo các middleware khi chuyển status
            'middleware' => [
                DispatchOrderStatusChangedEvent::class,
                LogOrderChangeStatus::class,
                PrepareOrderChangeStatus::class,
            ],
        ],

        'order_packing' => [
            // Danh sách status
            'places' => [
                OrderPacking::STATUS_WAITING_PROCESSING,
                OrderPacking::STATUS_WAITING_PICKING,
                OrderPacking::STATUS_WAITING_PACKING,
                OrderPacking::STATUS_PACKED,
                OrderPacking::STATUS_CANCELED,
            ],

            // Khai báo status flow
            'transitions' => [
                OrderPacking::STATUS_WAITING_PROCESSING => [
                    OrderPacking::STATUS_WAITING_PICKING,
                    OrderPacking::STATUS_CANCELED,
                ],
                OrderPacking::STATUS_WAITING_PICKING => [
                    OrderPacking::STATUS_WAITING_PROCESSING,
                    OrderPacking::STATUS_WAITING_PACKING,
                    OrderPacking::STATUS_PACKED,
                    OrderPacking::STATUS_CANCELED,
                ],
                OrderPacking::STATUS_WAITING_PACKING => [
                    OrderPacking::STATUS_PACKED,
                    OrderPacking::STATUS_CANCELED,
                ],
            ],

            // Cho phép chuyển ngược status trước đó hay không?
            'reverse_transitions' => false,

            // Khai báo các middleware khi chuyển status
            'middleware' => [
                LogOrderPackingChangeStatus::class,
                UpdateWhenOrderPackingChangeStatus::class,
            ],
        ],

    ],
];
