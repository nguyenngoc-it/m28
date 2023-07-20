<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderSkusCompletedBatch;
use Modules\Order\Services\OrderEvent;

class OrderSkusCompletedBatchListener extends QueueableListener
{
    /**
     * @param OrderSkusCompletedBatch $event
     */
    public function handle(OrderSkusCompletedBatch $event)
    {
        $order      = $event->order;
        $creator    = $event->creator;
        $payload    = $event->payload;
        $actionTime = $event->actionTime;
        /**
         * Lưu log thay đổi thông tin skus
         */
        $order->logActivity(OrderEvent::COMPLETE_BATCH, $creator, $payload, [
            'time' => $actionTime,
        ]);

        /**
         * Init cost of goods sold
         */
        $order->setCostOfGoods();
    }
}
