<?php

namespace Modules\OrderPacking\Listeners;

use App\Base\QueueableListener;
use Modules\OrderPacking\Events\OrderPackingServiceUpdated;
use Modules\OrderPacking\Services\OrderPackingEvent;

class OrderPackingServiceUpdatedListener extends QueueableListener
{
    /**
     * @param OrderPackingServiceUpdated $event
     */
    public function handle(OrderPackingServiceUpdated $event)
    {
        $orderPacking = $event->orderPacking;
        $creator      = $event->creator;
        $orderPacking->logActivity(OrderPackingEvent::UPDATE_SERVICE, $creator, [$orderPacking->servicePrices->toArray()]);
    }
}
