<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Gobiz\Support\RestApiException;
use Modules\Order\Events\OrderStatusChanged;

class PublishOrderWebhookEventListener extends QueueableListener
{
    /**
     * @param $event
     * @throws RestApiException
     */
    public function handle($event)
    {
        if ($event instanceof OrderStatusChanged) {
            $event->order->webhook()->changeStatus($event->fromStatus, $event->toStatus)->publish();
            return;
        }
    }
}
