<?php

namespace Modules\EventBridge\Events;

use Modules\EventBridge\Services\EventBridge;
use Modules\Order\Models\Order;

class OrderEvent extends EventBridge
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $eventName;

    /**
     * OrderEvent constructor
     *
     * @param Order $order
     * @param string $eventName
     */
    public function __construct(Order $order, string $eventName)
    {
        $this->order = $order;
        $this->eventName = $eventName;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return [
            'tenant' => $this->order->tenant,
            'merchant' => $this->order->merchant,
            'order' => $this->order,
            'order_skus' => $this->order->orderSkus,
        ];
    }
}
