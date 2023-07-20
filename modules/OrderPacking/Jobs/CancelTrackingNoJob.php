<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class CancelTrackingNoJob extends Job
{
    public $queue = 'cancel_tracking_no';
    protected $orderPackingId = 0;
    /**
     * @var int
     */
    protected $creatorId;

    /**
     * CancelTrackingNoJob constructor.
     * @param $orderPackingId
     * @param $creatorId
     */
    public function __construct($orderPackingId, $creatorId)
    {
        $this->orderPackingId = $orderPackingId;
        $this->creatorId = $creatorId;
    }

    public function handle()
    {
        $orderPacking = OrderPacking::find($this->orderPackingId);
        if(
            !$orderPacking instanceof OrderPacking ||
            !$orderPacking->canCancelTrackingNo()
        ) {
            return;
        }

        Service::orderPacking()->cancelTrackingNo($orderPacking, $this->creatorId);
    }
}
