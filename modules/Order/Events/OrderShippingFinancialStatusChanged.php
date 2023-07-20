<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderShippingFinancialStatusChanged extends OrderEvent
{
    /**
     * @var string
     */
    public $fromStatus;

    /**
     * @var string
     */
    public $toStatus;

    /**
     * @var User
     */
    public $creator;

    /**
     * OrderStatusChanged constructor
     *
     * @param Order $order
     * @param string $toStatus
     * @param string|null $fromStatus
     * @param User|null $creator
     */
    public function __construct(Order $order, string $toStatus, string $fromStatus = null, User $creator = null)
    {
        $this->order      = $order;
        $this->fromStatus = $fromStatus;
        $this->toStatus   = $toStatus;
        $this->creator    = $creator;
    }
}
