<?php

namespace Modules\ShopBaseUs\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Order\Models\Order;

class SyncShopBaseUsFreightBillJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopbaseus';

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
     * SyncShopBaseUsFreightBillJob constructor
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
        Service::shopBaseUs()->syncFreightBill($this->store, $this->order, $this->trackingNo);
    }
}
