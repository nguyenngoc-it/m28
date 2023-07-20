<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Modules\Order\Models\OrderStock;
use Modules\User\Models\User;

class OrderStockCreated extends Event
{
    /** @var OrderStock $orderStock */
    public $orderStock;
    /** @var User $user */
    public $user;

    /**
     * OrderCreated constructor
     *
     * @param OrderStock $orderStock
     * @param User $user
     */
    public function __construct(OrderStock $orderStock, User $user)
    {
        $this->orderStock = $orderStock;
        $this->user       = $user;
    }
}
