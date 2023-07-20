<?php

namespace Modules\Order\Events;

use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderExported extends OrderEvent
{
    /** @var User */
    public $creator;


    /**
     * OrderCreated constructor
     *
     * @param Order $order
     * @param User $user
     */
    public function __construct(Order $order, User $user)
    {
        $this->order   = $order->refresh();
        $this->creator = $user;
    }
}
