<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderStatusChanged extends OrderEvent
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
     * @param string $fromStatus
     * @param string $toStatus
     * @param User $creator
     */
    public function __construct(Order $order, $fromStatus, $toStatus, User $creator)
    {
        $this->order = $order;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->creator = $creator;
    }
}
