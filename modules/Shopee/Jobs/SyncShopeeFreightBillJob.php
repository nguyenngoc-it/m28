<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Modules\Service;

class SyncShopeeFreightBillJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopee';

    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var string
     */
    protected $orderCode;

    /**
     * @var string
     */
    protected $trackingNo;

    /**
     * SyncShopeeFreightBillJob constructor.
     * @param int $shopId
     * @param string $orderCode
     * @param string $trackingNo
     */
    public function __construct($shopId, $orderCode, $trackingNo)
    {
        $this->shopId = $shopId;
        $this->orderCode = $orderCode;
        $this->trackingNo = $trackingNo;
    }

    public function handle()
    {
        Service::shopee()->syncFreightBill($this->shopId, $this->orderCode, $this->trackingNo);
    }
}
