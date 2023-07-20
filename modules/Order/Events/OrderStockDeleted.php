<?php

namespace Modules\Order\Events;

use App\Base\Event;
use Carbon\Carbon;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class OrderStockDeleted extends Event
{
    /** @var Order */
    public $order;
    /** @var User */
    public $user;
    /** @var array $stockIds */
    public $stockIds;
    /** @var Carbon */
    public $actionTime;

    /**
     * @param Order $order
     * @param User $user
     * @param Carbon $carbon
     * @param array $stockIds
     */
    public function __construct(Order $order, User $user, Carbon $carbon, array $stockIds = [])
    {
        $this->order      = $order;
        $this->user       = $user;
        $this->stockIds   = $stockIds;
        $this->actionTime = $carbon;
    }
}
