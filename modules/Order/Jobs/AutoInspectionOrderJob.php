<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\User\Models\User;

class AutoInspectionOrderJob extends Job
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var int
     */
    protected $creatorId;

    /**
     * AutoInspectionOrderJob constructor
     *
     * @param int $orderId
     * @param int $creatorId
     */
    public function __construct($orderId, $creatorId)
    {
        $this->orderId = $orderId;
        $this->creatorId = $creatorId;
    }

    public function handle()
    {
        Service::order()->autoInspection(Order::find($this->orderId), User::find($this->creatorId));
    }
}
