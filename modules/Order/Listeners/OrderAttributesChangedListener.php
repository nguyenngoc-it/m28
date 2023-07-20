<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Modules\Order\Events\OrderAttributesChanged;
use Modules\Order\Services\OrderEvent;

class OrderAttributesChangedListener extends QueueableListener
{
    /**
     * @param OrderAttributesChanged $event
     */
    public function handle(OrderAttributesChanged $event)
    {
        $order         = $event->order;
        $creator       = $event->creator;
        $orderOriginal = $event->orderOriginal;
        $changedAtts   = $event->changedAttributes;
        /**
         * Lưu log thay đổi thông tin skus
         */
        $order->logActivity(OrderEvent::UPDATE_ATTRIBUTES, $creator, array_merge_recursive($orderOriginal, $changedAtts), [
            'time' => $event->order->updated_at,
        ]);
    }
}
