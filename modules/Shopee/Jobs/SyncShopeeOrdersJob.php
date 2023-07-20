<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Modules\Service;

class SyncShopeeOrdersJob extends Job
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
     * @var array
     */
    protected $orderInputs;

    /**
     * SyncShopeeOrdersJob constructor
     *
     * @param int $shopId
     * @param array $orderInputs
     */
    public function __construct($shopId, array $orderInputs)
    {
        $this->shopId = $shopId;
        $this->orderInputs = $orderInputs;
    }

    public function handle()
    {
        Service::shopee()->syncOrders($this->shopId, $this->orderInputs);
    }
}
