<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderSkusChanged extends OrderEvent
{
    /**
     * @var User
     */
    public $creator;

    /**
     * @var array
     */
    public $syncSkus;

    /**
     * OrderStatusChanged constructor
     *
     * @param Order $order
     * @param User $creator
     * @param array $syncSkus
     */
    public function __construct(Order $order, User $creator, array $syncSkus)
    {
        $this->order    = $order;
        $this->creator  = $creator;
        $this->syncSkus = $syncSkus;
    }
}
