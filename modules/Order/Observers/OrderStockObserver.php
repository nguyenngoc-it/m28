<?php

namespace Modules\Order\Observers;

use Gobiz\Log\LogService;
use Modules\Order\Models\OrderStock;
use Modules\Service;

class OrderStockObserver
{
    public function created(OrderStock $orderStock)
    {
        Service::stock()->decrementQuantity($orderStock->stock, $orderStock->quantity);

        LogService::logger('stock')->debug('DECREMENT_QUANTITY_WHEN_ORDER_STOCK_CREATED', [
            'order_stock' => $orderStock->attributesToArray(),
        ]);
    }
}
