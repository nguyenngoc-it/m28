<?php

namespace Modules\Order\Events;

use Carbon\Carbon;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderSkusUpdatedBatch extends OrderEvent
{
    /** @var User */
    public $creator;
    /** @var array */
    public $payload;
    /** @var Carbon */
    public $actionTime;

    /**
     * @param Order $order
     * @param User $creator
     * @param array $payload
     * @param Carbon $actionTime
     */
    public function __construct(Order $order, User $creator, Carbon $actionTime, array $payload = [])
    {
        $this->order      = $order;
        $this->creator    = $creator;
        $this->payload    = $payload;
        $this->actionTime = $actionTime;
    }
}
