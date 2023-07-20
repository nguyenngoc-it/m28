<?php

namespace Modules\OrderPacking\Events;

use App\Base\Event;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\User\Models\User;

class OrderPackingServiceUpdated extends Event
{
    /**
     * @var OrderPacking
     */
    public $orderPacking;
    /**
     * @var User
     */
    public $creator;

    /**
     * OrderCreated constructor
     *
     * @param OrderPacking $orderPacking
     * @param User $creator
     */
    public function __construct(OrderPacking $orderPacking, User $creator)
    {
        $this->orderPacking = $orderPacking;
        $this->creator      = $creator;
    }
}
