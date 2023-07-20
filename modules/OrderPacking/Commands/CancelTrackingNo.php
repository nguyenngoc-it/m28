<?php

namespace Modules\OrderPacking\Commands;

use Modules\FreightBill\Models\FreightBill;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\User\Models\User;

class CancelTrackingNo
{
    /**
     * @var OrderPacking|null
     */
    protected $orderPacking = null;
    /**
     * @var int
     */
    protected $creatorId;

    /**
     * @var User
     */
    protected $creator;

    /**
     * CancelTrackingNo constructor.
     * @param OrderPacking $orderPacking
     * @param $creatorId
     */
    public function __construct(OrderPacking $orderPacking, $creatorId)
    {
        $this->orderPacking = $orderPacking;
        $this->creator      = User::find($creatorId);
    }

    /**
     * @return OrderPacking|null
     */
    public function handle()
    {
        if(!$this->orderPacking->canCancelTrackingNo()) {
            return $this->orderPacking;
        }

        $freightBill = $this->orderPacking->freightBill;
        Service::freightBill()->changeStatus($freightBill, FreightBill::STATUS_CANCELLED, $this->creator);

        return $this->orderPacking;
    }
}
