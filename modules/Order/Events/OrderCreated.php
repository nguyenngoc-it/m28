<?php

namespace Modules\Order\Events;

use Modules\Order\Models\Order;

class OrderCreated extends OrderEvent
{
    /** @var string|null */
    public $targetStatus;

    /**
     * OrderCreated constructor
     *
     * @param Order $order
     * @param null $targetStatus
     */
    public function __construct(Order $order, $targetStatus = null)
    {
        $this->order        = $order->refresh();
        $this->targetStatus = $targetStatus;
    }
}
