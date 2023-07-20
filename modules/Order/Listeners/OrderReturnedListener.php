<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderReturned;
use Modules\SupplierTransaction\Commands\CalculateSupplierTransaction;
use Modules\SupplierTransaction\Models\SupplierTransaction;

class OrderReturnedListener extends QueueableListener
{
    /**
     * @param OrderReturned $event
     */
    public function handle(OrderReturned $event)
    {
        $order = $event->order;

        /**
         * Tính toán công nợ supplier
         */
        (new CalculateSupplierTransaction($order, SupplierTransaction::TYPE_IMPORT_BY_RETURN))->handle();
    }
}
