<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class MappingTrackingNoJob extends Job
{
    public $queue = 'create_tracking_no';
    protected $orderPackingId = 0;
    /**
     * @var int
     */
    protected $creatorId;

    /**
     * CreateTrackingNoJob constructor.
     * @param $orderPackingId
     * @param $creatorId
     */
    public function __construct($orderPackingId, $creatorId)
    {
        $this->orderPackingId = $orderPackingId;
        $this->creatorId      = $creatorId;
    }

    public function handle()
    {
        $orderPacking = OrderPacking::find($this->orderPackingId);
        if (
        !$orderPacking instanceof OrderPacking
        ) {
            return;
        }

        Service::orderPacking()->mappingTrackingNo($orderPacking, $this->creatorId);
    }
}
