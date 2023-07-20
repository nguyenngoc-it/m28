<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Modules\Order\Models\Order;

abstract class OrderEvent extends Event
{
    /**
     * @var Order
     */
    public $order;
}
