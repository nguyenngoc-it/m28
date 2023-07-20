<?php

namespace Modules\KiotViet\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Order\Models\Order;

class SyncKiotVietFreightBillJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'kiotviet';

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
     * SyncKiotVietOrdersJob constructor
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
        Service::kiotviet()->syncFreightBill($this->store, $this->order, $this->trackingNo);
    }
}
