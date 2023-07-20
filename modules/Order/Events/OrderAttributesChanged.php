<?php

namespace Modules\Order\Events;

use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderAttributesChanged extends OrderEvent
{
    /**
     * @var User
     */
    public $creator;

    /**
     * @var array
     */
    public $orderOriginal;

    /**
     * @var array
     */
    public $changedAttributes;

    /**
     * OrderStatusChanged constructor
     *
     * @param Order $order
     * @param User $creator
     * @param array $orderOriginal
     * @param array $changedAttributes
     */
    public function __construct(Order $order, User $creator, array $orderOriginal, array $changedAttributes)
    {
        $this->order             = $order;
        $this->creator           = $creator;
        $this->orderOriginal     = $orderOriginal;
        $this->changedAttributes = $changedAttributes;
    }
}
