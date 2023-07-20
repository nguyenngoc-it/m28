<?php

namespace Modules\EventBridge\Services;

use App\Base\Event;
use App\Base\QueueableListener;
use Modules\EventBridge\Events\OrderEvent as OrderEventBridge;
use Modules\Order\Events\OrderEvent;

class EventListener extends QueueableListener
{
    public $queue = 'aws';

    public function handle(Event $event)
    {
        if (!config('aws.event_bridge.name')) {
            return;
        }

        if ($event instanceof OrderEvent) {
            (new OrderEventBridge($event->order, 'Order.'.class_basename($event)))->put();
        }
    }
}
