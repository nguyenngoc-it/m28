<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Modules\Service;

class SyncShopeeProductsJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopee';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var bool
     */
    protected $filterUpdateTime = true;

    /**
     * SyncShopeeProductsJob constructor.
     * @param $storeId
     * @param $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct($storeId, $merchantId, $filterUpdateTime = true)
    {
        $this->storeId = $storeId;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
    }

    public function handle()
    {
        Service::shopee()->syncProducts($this->storeId, $this->merchantId, $this->filterUpdateTime);
    }
}
