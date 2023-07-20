<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderExported;
use Modules\SupplierTransaction\Commands\CalculateSupplierTransaction;
use Modules\SupplierTransaction\Models\SupplierTransaction;

class OrderExportedListener extends QueueableListener
{
    /**
     * @param OrderExported $event
     */
    public function handle(OrderExported $event)
    {
        $order = $event->order;

        /**
         * Tính toán công nợ supplier
         */
        (new CalculateSupplierTransaction($order, SupplierTransaction::TYPE_EXPORT))->handle();
    }
}
