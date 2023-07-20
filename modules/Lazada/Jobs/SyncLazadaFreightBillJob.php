<?php

namespace Modules\Lazada\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Order\Models\Order;

class SyncLazadaFreightBillJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'lazada';

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $trackingNo;

    /**
     * SyncLazadaFreightBillJob constructor
     *
     * @param int $store
     * @param array $orderInputs
     */
    public function __construct(Store $store, Order $order, string $trackingNo)
    {
        $this->store       = $store;
        $this->order       = $order;
        $this->trackingNo  = $trackingNo;
    }

    public function handle()
    {
        Service::lazada()->syncFreightBill($this->store, $this->order, $this->trackingNo);
    }
}
