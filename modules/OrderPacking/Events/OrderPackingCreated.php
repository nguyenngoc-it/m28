<?php

namespace Modules\OrderPacking\Events;

use App\Base\Event;
use Modules\OrderPacking\Models\OrderPacking;

class OrderPackingCreated extends Event
{
    /**
     * @var OrderPacking
     */
    public $orderPacking;

    /**
     * OrderCreated constructor
     *
     * @param OrderPacking $orderPacking
     */
    public function __construct(OrderPacking $orderPacking)
    {
        $this->orderPacking = $orderPacking;
    }
}
