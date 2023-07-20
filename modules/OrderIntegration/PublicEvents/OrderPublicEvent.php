<?php

namespace Modules\OrderIntegration\PublicEvents;

use Gobiz\Event\EventService;
use Gobiz\Event\PublicEvent;
use Modules\Order\Models\Order;
use Modules\OrderIntegration\Constants\OrderIntegrationConstant;

abstract class OrderPublicEvent extends PublicEvent
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * Get the event key
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->order->tenant->code . '-' . $this->order->code;
    }

    /**
     * Public event
     *
     * @param null $topic
     */
    public function publish($topic = null)
    {
        EventService::publicEventDispatcher()->publish($topic ?: OrderIntegrationConstant::M28_ORDER_TOPIC, $this);
    }
}