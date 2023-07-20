<?php

namespace Modules\Sapo\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Order\Models\Order;

class SyncSapoFreightBillJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'sapo';

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
     * SyncSapoFreightBillJob constructor
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
        Service::sapo()->syncFreightBill($this->store, $this->order, $this->trackingNo);
    }
}
