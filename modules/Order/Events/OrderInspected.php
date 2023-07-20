<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderInspected extends OrderEvent
{
    /** @var User $user */
    public $user;

    /**
     * OrderCreated constructor
     *
     * @param Order $order
     * @param User $user
     */
    public function __construct(Order $order, User $user)
    {
        $this->order = $order->refresh();
        $this->user  = $user;
    }
}
