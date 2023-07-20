<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class CreateTrackingNoJob extends Job
{
    public $queue = 'create_tracking_no';
    protected $orderPackingId = 0;
    /**
     * @var int
     */
    protected $creatorId;

    /**
     * @var string
     */
    protected $pickupType;

    /**
     * CreateTrackingNoJob constructor.
     * @param $orderPackingId
     * @param $creatorId
     * @param null $pickupType
     */
    public function __construct($orderPackingId, $creatorId, $pickupType = null)
    {
        $this->orderPackingId = $orderPackingId;
        $this->creatorId = $creatorId;
        $this->pickupType = $pickupType;
    }

    public function handle()
    {
        $orderPacking = OrderPacking::find($this->orderPackingId);
        if(
            !$orderPacking instanceof OrderPacking
        ) {
            return;
        }

        Service::orderPacking()->createTrackingNo($orderPacking, $this->creatorId, $this->pickupType);
    }
}
