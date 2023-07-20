<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderSkusUpdatedBatch;
use Modules\Order\Services\OrderEvent;

class OrderSkusUpdatedBatchListener extends QueueableListener
{
    /**
     * @param OrderSkusUpdatedBatch $event
     */
    public function handle(OrderSkusUpdatedBatch $event)
    {
        $order      = $event->order;
        $creator    = $event->creator;
        $payload    = $event->payload;
        $actionTime = $event->actionTime;
        /**
         * Lưu log thay đổi thông tin skus
         */
        $order->logActivity(OrderEvent::UPDATE_BATCH, $creator, $payload, [
            'time' => $actionTime,
        ]);
    }
}
