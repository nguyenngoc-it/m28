<?php

return [
    /*
     * Khai báo middleware tương ứng với các command
     */
    'listen' => [
        \Modules\FreightBill\Commands\ChangeFreightBillStatus::class => [
            \Modules\Order\Middleware\UpdateOrderStatusFromFreightBill::class,
            \Modules\OrderPacking\Middleware\UpdateOrderPackingStatusFromFreightBill::class,
        ],
        \Modules\Transaction\Commands\ProcessTransaction::class => [
        ],
    ],
];
